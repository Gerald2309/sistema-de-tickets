/**
 * Simulador de permisos: la misma decisión que toma la API, capa por capa, con los permisos de los roles de
 * ejemplo. Las reglas copian a `AlcanceIncidentes` (backend/app/Incidentes/Dominio) y a `IncidentePolicy`:
 * lo que no se ve responde 404 (no revela que existe) y lo que se ve pero no se puede hacer, 403.
 */

/** Permisos de los roles de ejemplo (`RolesSeeder`). */
export const ROLES = {
  administrador: [
    'crear_incidente',
    'ver_todos',
    'editar_todos',
    'cambiar_estado',
    'reasignar',
    'borrar',
    'ver_historial',
    'ver_dashboard',
    'administrar_roles',
    'administrar_usuarios',
    'ver_auditoria',
    'administrar_automatizaciones',
  ],
  soporte: ['crear_incidente', 'ver_todos', 'editar_todos', 'cambiar_estado', 'ver_historial', 'ver_dashboard'],
  solicitante: ['crear_incidente', 'ver_propios', 'editar_propios', 'ver_historial'],
  sinSesion: [],
}

/** Lo que se intenta: la ruta y, si actualiza, los campos que envía. */
export const ACCIONES = {
  ver: { titulo: 'Ver el incidente', ruta: 'GET /incidentes/{id}' },
  tomar: { titulo: 'Tomarlo', ruta: 'POST /incidentes/{id}/tomar' },
  estado: { titulo: 'Cambiar el estado', ruta: 'PATCH /incidentes/{id}', cambios: { estado: 'resuelto' } },
  critico: { titulo: 'Marcarlo como crítico', ruta: 'PATCH /incidentes/{id}', cambios: { prioridad: 'critica' } },
  titulo: { titulo: 'Corregir el título', ruta: 'PATCH /incidentes/{id}', cambios: { titulo: '…' } },
  auditoria: { titulo: 'Ver la auditoría', ruta: 'GET /auditoria' },
}

/**
 * @typedef {object} Situacion
 * @property {keyof typeof ROLES} rol
 * @property {keyof typeof ACCIONES} accion
 * @property {'yo' | 'otra'} creador quién reportó el incidente
 * @property {'nadie' | 'yo' | 'otra'} asignado quién lo atiende
 */

/**
 * @typedef {object} Capa
 * @property {string} nombre
 * @property {string} archivo
 * @property {'pasa' | 'falla' | 'no-llega'} resultado
 * @property {string} motivo
 */

/**
 * @param {Situacion} situacion
 * @returns {{ capas: Capa[], estado: number, explicacion: string }}
 */
export function evaluar({ rol, accion, creador, asignado }) {
  const permisos = new Set(ROLES[rol])
  const tiene = (/** @type {string} */ clave) => permisos.has(clave)
  const loCreo = creador === 'yo'
  const loAtiende = asignado === 'yo'
  const sinAsignar = asignado === 'nadie'

  // AlcanceIncidentes
  const puedeVer = tiene('ver_todos') || (tiene('ver_propios') && loCreo)
  const puedeClasificar =
    puedeVer && ((tiene('editar_todos') && loAtiende) || (tiene('editar_propios') && loCreo && sinAsignar))
  const puedeCorregirRelato = puedeVer && tiene('editar_propios') && loCreo && sinAsignar
  const puedeCambiarEstado = puedeVer && tiene('cambiar_estado') && loAtiende
  const puedeReasignar = puedeVer && (tiene('reasignar') || loAtiende)
  const puedeCambiarPrioridad = puedeClasificar || (puedeVer && tiene('reasignar'))
  const puedeActualizar = puedeClasificar || puedeCorregirRelato || puedeCambiarEstado || puedeReasignar

  /** @type {Capa[]} */
  const capas = []
  /**
   * @param {string} nombre
   * @param {string} archivo
   * @param {boolean} pasa
   * @param {string} motivo
   */
  const capa = (nombre, archivo, pasa, motivo) =>
    capas.push({ nombre, archivo, resultado: pasa ? 'pasa' : 'falla', motivo })
  /** @param {number} estado @param {string} explicacion */
  const fin = (estado, explicacion) => {
    for (const nombre of ['Autenticación', 'Ruta y política', 'Permiso por campo', 'Caso de uso']) {
      if (!capas.some((c) => c.nombre === nombre)) {
        capas.push({ nombre, archivo: '', resultado: 'no-llega', motivo: 'No se evalúa: la petición ya se respondió.' })
      }
    }
    return { capas, estado, explicacion }
  }

  capa(
    'Autenticación',
    'backend/routes/api.php',
    rol !== 'sinSesion',
    rol === 'sinSesion' ? 'Sin token: `auth:sanctum` responde antes de buscar el recurso.' : 'Token válido.',
  )
  if (rol === 'sinSesion') return fin(401, 'Sin sesión no se llega a buscar el incidente: no se revela si existe.')

  if (accion === 'auditoria') {
    const pasa = tiene('ver_auditoria')
    capa(
      'Ruta y política',
      'backend/routes/api.php',
      pasa,
      pasa ? 'Tiene `ver_auditoria`.' : 'La ruta exige `ver_auditoria` con `can()`.',
    )
    if (!pasa) return fin(403, 'La auditoría existe y la ruta es conocida, pero este rol no tiene el permiso.')
    capa('Caso de uso', 'backend/app/Auditoria/Consultas/ListarAuditoria.php', true, 'Lista paginada con filtros.')
    return fin(200, 'Ve la auditoría.')
  }

  const politica = 'backend/app/Incidentes/Policies/IncidentePolicy.php'
  if (!puedeVer) {
    capa('Ruta y política', politica, false, 'No lo ve: sin `ver_todos` solo se ven los propios.')
    return fin(
      404,
      'Responde 404, igual que un incidente que no existe: así nadie descubre qué incidentes hay probando ids.',
    )
  }

  if (accion === 'ver') {
    capa('Ruta y política', politica, true, 'Lo ve.')
    capa(
      'Caso de uso',
      'backend/app/Incidentes/Http/Controllers/IncidenteController.php',
      true,
      'Devuelve el incidente; las notas internas se filtran según quién pide.',
    )
    return fin(200, 'Lo ve.')
  }

  if (accion === 'tomar') {
    const pasa = tiene('cambiar_estado')
    capa('Ruta y política', politica, pasa, pasa ? 'Lo ve y tiene `cambiar_estado`.' : 'Tomar exige `cambiar_estado`.')
    if (!pasa) return fin(403, 'Lo ve, pero no atiende incidentes.')
    capa(
      'Caso de uso',
      'backend/app/Incidentes/Acciones/TomarIncidente.php',
      sinAsignar,
      sinAsignar
        ? 'Sigue libre: queda asignado a quien lo pidió.'
        : 'Ya lo tiene alguien: la acción lo comprueba con el incidente bloqueado.',
    )
    return sinAsignar
      ? fin(200, 'Queda asignado a quien lo tomó.')
      : fin(409, 'Ya está asignado: dos personas no pueden tomar el mismo a la vez.')
  }

  capa(
    'Ruta y política',
    politica,
    puedeActualizar,
    puedeActualizar ? 'Puede modificar algo de este incidente.' : 'No puede modificar nada de este incidente.',
  )
  if (!puedeActualizar) return fin(403, 'Lo ve, pero no lo atiende ni lo creó sin que nadie lo haya tomado.')

  const pideCritico = accion === 'critico'
  const plazosOk = !pideCritico || tiene('editar_todos')
  const campoOk =
    accion === 'estado'
      ? puedeCambiarEstado
      : accion === 'critico'
        ? puedeCambiarPrioridad && plazosOk
        : puedeCorregirRelato
  const motivos = {
    estado: campoOk
      ? 'Tiene `cambiar_estado` y lo atiende.'
      : 'Cambiar el estado es de quien lo atiende: primero se toma.',
    critico: !plazosOk
      ? 'Marcarlo como crítico mueve los plazos del acuerdo: eso es de soporte (`editar_todos`).'
      : campoOk
        ? 'Lo clasifica quien lo atiende, o quien reparte el trabajo (`reasignar`).'
        : 'La prioridad la cambia quien lo atiende o quien tiene `reasignar`.',
    titulo: campoOk
      ? 'Lo reportó y nadie lo ha tomado todavía.'
      : 'El título es de quien lo reportó, y solo mientras nadie lo haya tomado.',
  }
  capa(
    'Permiso por campo',
    'backend/app/Incidentes/Http/Requests/ActualizarIncidenteRequest.php',
    campoOk,
    motivos[accion],
  )
  if (!campoOk) return fin(403, 'Puede tocar otras cosas del incidente, pero no este campo.')
  capa(
    'Caso de uso',
    'backend/app/Incidentes/Acciones/ActualizarIncidente.php',
    true,
    'Guarda, registra el historial y avisa en tiempo real tras el commit.',
  )
  return fin(200, 'El cambio se guarda.')
}
