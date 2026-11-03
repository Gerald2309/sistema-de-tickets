import { iconos } from '@/compartido/iconos.js'
import capturaAuditoria from '../recursos/auditoria.png'
import capturaEmpresas from '../recursos/empresas.png'
import capturaMiDia from '../recursos/mi-dia.png'
import capturaPanel from '../recursos/panel.png'
import capturaRoles from '../recursos/roles.png'
import capturaUsuarios from '../recursos/usuarios.png'

/**
 * @param {string} src
 * @param {string} pantalla
 * @returns {import('../tipos.js').Seccion}
 */
const captura = (src, pantalla) => ({
  titulo: 'La pantalla',
  extra: 'captura real',
  bloques: [{ tipo: 'imagen', src, alt: `Pantalla ${pantalla}` }],
})

/** @type {import('../tipos.js').Pagina} */
export const panel = {
  id: 'panel',
  titulo: 'Panel',
  grupo: 'aplicacion',
  icono: iconos.panel,
  entradilla:
    'La operación de hoy: cuántos incidentes hay en cada etapa, cuántos vencieron, cuánto llevan esperando y cuánto se tarda en resolver. Cada persona ve las cifras de lo que puede ver.',
  secciones: [
    captura(capturaPanel, 'Panel'),
    {
      titulo: 'Cómo se calculan las cifras',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Recorrido por etapas',
              '`ResumenDeIncidentes`: el total y los agrupados por estado salen de las mismas filas, así que siempre cuadran; el total de vencidos es la suma de los vencidos por estado.',
            ],
            [
              'Cuánto llevan esperando',
              'Tramos de espera de lo pendiente: menos de 1 día, de 1 a 3, de 3 a 7, de 7 a 28 y más de 28 (`IndicadoresDelPanel`).',
            ],
            [
              'Cuánto tardamos en resolver',
              'Tiempos en segundos frente al acuerdo de servicio, con la tendencia del periodo anterior. Sin datos se muestra «sin datos», no un 0 que parece un resultado.',
            ],
            ['Mi trabajo', 'Lo asignado a quien inició sesión, lo que le vence hoy y lo que creó.'],
            [
              'Alcance',
              'Las cifras se calculan con el mismo `AlcanceIncidentes` que la lista: nadie cuenta lo que no puede ver.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'Rendimiento',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Caché por alcance',
              '`CacheDelPanel` guarda el resumen 60 segundos (lo único que cambia sin tocar un incidente son los vencidos, con el reloj) y los indicadores 300; un cambio en un incidente los renueva antes.',
            ],
            [
              'Conteos en SQL',
              'Agrupaciones sobre los índices de `estado`, `prioridad` y fechas: no se traen incidentes a PHP.',
            ],
            ['Tiempo real', 'Al llegar `incidentes.cambiados`, la interfaz vuelve a pedir el panel.'],
          ],
        },
      ],
    },
    {
      titulo: 'API y permisos',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['GET /dashboard', 'Conteos por etapa y vencidos.'],
            ['GET /dashboard/indicadores', 'Espera, entradas y salidas por día y tiempos de resolución.'],
            ['Permiso', '`ver_dashboard` (Administrador y Soporte).'],
          ],
        },
      ],
    },
  ],
}

/** @type {import('../tipos.js').Pagina} */
export const miDia = {
  id: 'mi-dia',
  titulo: 'Mi día',
  grupo: 'aplicacion',
  icono: iconos.miDia,
  entradilla:
    'La agenda de cada persona de soporte: qué va a atender hoy y a qué hora, sus notas y lo que tiene asignado sin planear. Cada quien ve y toca solo lo suyo.',
  secciones: [
    captura(capturaMiDia, 'Mi día'),
    {
      titulo: 'Cómo funciona',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Plan del día',
              'Bloques con hora de inicio y duración (`entradas_del_plan`), que pueden llevar un incidente o una nota.',
            ],
            [
              'Sugerir el día',
              '`SugerirDia` llena el día hasta lo habitual con lo más urgente de la bandeja, sin lo bloqueado, en los huecos libres. Todo o nada: una sola transacción.',
            ],
            [
              'Mover',
              'El mismo incidente va una vez por día: si ya estaba en el día de destino, queda solo el que se movió.',
            ],
            [
              'Bloqueos',
              'Una persona puede apartar un incidente con un motivo (`bloqueos_personales`): la sugerencia no lo propone.',
            ],
            ['Notas', 'Notas personales cortas, opcionalmente ligadas a un incidente; nadie más las ve.'],
          ],
        },
      ],
    },
    {
      titulo: 'API y datos',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['GET · POST /mi-dia/plan, PATCH · DELETE /mi-dia/plan/{id}', 'Ver, agregar, mover y quitar bloques.'],
            ['GET /mi-dia/pendientes · /mi-dia/por-planear', 'Lo asignado y lo que aún no está en el plan.'],
            ['POST /mi-dia/sugerencia', 'Proponer el día.'],
            ['PUT · DELETE /mi-dia/bloqueos/{incidente}', 'Apartar o liberar un incidente.'],
            ['GET · POST /mi-dia/notas, PATCH · DELETE /mi-dia/notas/{id}', 'Notas personales.'],
            [
              'Restricciones',
              'Único `(usuario_id, fecha, incidente_id)` en el plan; índice `(usuario_id, fecha, inicio)` para leer el día ya ordenado.',
            ],
          ],
        },
      ],
    },
  ],
}

/** @type {import('../tipos.js').Pagina} */
export const usuarios = {
  id: 'usuarios',
  titulo: 'Usuarios',
  grupo: 'administracion',
  icono: iconos.usuarios,
  entradilla:
    'Las personas que usan el sistema, su rol, su empresa y si están activas. Requiere `administrar_usuarios`.',
  secciones: [
    captura(capturaUsuarios, 'Usuarios'),
    {
      titulo: 'Reglas',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Contraseña inicial',
              'La pone el administrador: la persona debe cambiarla al entrar (`debe_cambiar_contrasena`). Lo mismo al restablecerla.',
            ],
            [
              'Último administrador',
              'Desactivar, cambiar de rol o quitar permisos a un rol se hace de una en una, bloqueando las filas de `roles` en el mismo orden (`BloqueoAdministracion`): el sistema nunca queda sin administradores, ni con operaciones simultáneas.',
            ],
            [
              'Desactivar',
              'La fila se bloquea mientras se decide: un inicio de sesión simultáneo no deja un token vivo para una cuenta inactiva.',
            ],
            [
              'Traspaso',
              'Antes de desactivar a alguien se pueden pasar sus incidentes abiertos a otra persona, por tramos, sin una consulta gigante (`TraspasarIncidentesAbiertos`).',
            ],
            ['Sesiones', 'Como máximo 5 sesiones por persona: al abrir una nueva se cierran las más antiguas.'],
            [
              'Auditoría',
              'Creado, modificado, desactivado, reactivado, rol asignado y contraseña restablecida quedan registrados.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'API',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['GET · POST /usuarios, GET · PATCH /usuarios/{id}', 'Listar, crear, ver y editar.'],
            ['PUT /usuarios/{id}/rol', 'Cambiar el rol.'],
            ['GET /usuarios/{id}/incidentes-abiertos', 'Lo que tiene abierto antes de desactivarla.'],
            ['POST /usuarios/{id}/incidentes-abiertos/traspaso', 'Traspasar esos incidentes.'],
          ],
        },
      ],
    },
  ],
}

/** @type {import('../tipos.js').Pagina} */
export const roles = {
  id: 'roles',
  titulo: 'Roles y permisos',
  grupo: 'administracion',
  icono: iconos.roles,
  entradilla:
    'Qué puede hacer cada rol. Los cambios se aplican de inmediato a todas sus personas. Requiere `administrar_roles`. La matriz completa está en Seguridad.',
  secciones: [
    captura(capturaRoles, 'Roles y permisos'),
    {
      titulo: 'Reglas',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['Rol nuevo', 'Nace sin permisos: se le asignan aparte.'],
            [
              'Eliminar',
              'Con la fila bloqueada, nadie puede asignar el rol mientras se comprueba que no tiene personas y se borra.',
            ],
            [
              'Último administrador',
              'Quitar un permiso de administración respeta la misma regla que desactivar personas.',
            ],
            [
              'Catálogo',
              'Los permisos son fijos (`permisos.clave`) porque el código los consulta; los roles sí se crean y renombran.',
            ],
            ['Auditoría', 'Rol creado, renombrado y eliminado; permiso concedido y retirado.'],
          ],
        },
      ],
    },
    {
      titulo: 'API',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['GET · POST /roles, GET · PATCH · DELETE /roles/{id}', 'Listar, crear, ver, renombrar y eliminar.'],
            ['PUT /roles/{id}/permisos', 'Reemplazar sus permisos.'],
            ['GET /permisos', 'El catálogo de permisos.'],
          ],
        },
      ],
    },
  ],
}

/** @type {import('../tipos.js').Pagina} */
export const empresas = {
  id: 'empresas',
  titulo: 'Empresas',
  grupo: 'administracion',
  icono: iconos.empresa,
  entradilla:
    'Los clientes y su acuerdo de servicio: cuánto puede tardar la primera respuesta y la resolución según la prioridad, y si el plazo cuenta solo en horario laboral.',
  secciones: [
    captura(capturaEmpresas, 'Empresas'),
    {
      titulo: 'Acuerdo de servicio',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Uno por empresa',
              '`acuerdos_de_servicio` con único `cliente_id`; los plazos por prioridad van en una columna JSON.',
            ],
            [
              'Horario laboral',
              'Lunes a viernes de 8:00 a 18:00; lo que no cabe hoy sigue en la siguiente apertura. Los festivos no se descuentan (`HorarioLaboral`).',
            ],
            ['Efecto', 'De aquí salen las fechas límite de cada incidente nuevo de esa empresa.'],
            ['Auditoría', 'Empresa creada o modificada y acuerdo modificado.'],
          ],
        },
      ],
    },
    {
      titulo: 'API y permisos',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'GET /clientes',
              'Listar: la habilidad `consultar_clientes` la tiene quien tenga `administrar_usuarios`, `ver_todos` o `administrar_automatizaciones`.',
            ],
            ['POST /clientes · PATCH /clientes/{id}', 'Crear y editar (`administrar_usuarios`).'],
            ['GET · PUT /clientes/{id}/acuerdo-de-servicio', 'Ver y cambiar el acuerdo (`administrar_usuarios`).'],
          ],
        },
      ],
    },
  ],
}

/** @type {import('../tipos.js').Pagina} */
export const auditoria = {
  id: 'auditoria',
  titulo: 'Auditoría',
  grupo: 'administracion',
  icono: iconos.auditoria,
  entradilla:
    'Quién hizo qué y cuándo en la administración y en la seguridad de las cuentas, con un detalle breve y la IP. Requiere `ver_auditoria`.',
  secciones: [
    captura(capturaAuditoria, 'Auditoría'),
    {
      titulo: 'Qué se registra',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['Cuentas', '`login_fallido`, `contrasena_cambiada`, `contrasena_restablecida`, `alerta_de_seguridad`.'],
            [
              'Personas',
              '`usuario_creado`, `usuario_modificado`, `usuario_desactivado`, `usuario_reactivado`, `rol_asignado`.',
            ],
            ['Roles', '`rol_creado`, `rol_renombrado`, `rol_eliminado`, `permiso_concedido`, `permiso_retirado`.'],
            ['Empresas', '`cliente_creado`, `cliente_modificado`, `acuerdo_de_servicio_modificado`.'],
            [
              'Automatizaciones',
              '`automatizacion_creada`, `automatizacion_modificada`, `automatizacion_activada`, `automatizacion_desactivada`, `automatizacion_eliminada`.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'Cómo se guarda y se consulta',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['Tabla', '`auditoria`: acción (ENUM), quién, a quién, detalle, IP y fecha.'],
            [
              'Inicios fallidos',
              'Se registran fuera de la transacción para que no se pierdan, sin guardar lo que se escribió; al superar el umbral, `VigilarIniciosFallidos` registra una alerta.',
            ],
            ['Filtros', '`GET /auditoria` por acción, quién, a quién y rango de fechas, paginado.'],
            ['Índices', '`(accion, created_at)` y `(created_at)`.'],
            ['Independencia', 'El módulo de Auditoría no depende de ningún otro: los demás le cuentan lo que pasa.'],
          ],
        },
      ],
    },
  ],
}
