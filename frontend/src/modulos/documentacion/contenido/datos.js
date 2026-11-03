import { iconos } from '@/compartido/iconos.js'

/** @type {import('../tipos.js').Plano} */
const entidades = {
  codigo: 'D-01',
  titulo: 'Entidades y relaciones',
  nivel: 'Datos',
  ancho: 1000,
  alto: 640,
  nodos: [
    { id: 'z-ide', zona: true, x: 25, y: 20, w: 470, h: 250, titulo: 'IDENTIDAD' },
    { id: 'z-inc', zona: true, x: 25, y: 290, w: 950, h: 260, titulo: 'INCIDENTES' },
    { id: 'z-aut', zona: true, x: 735, y: 20, w: 240, h: 250, titulo: 'AUTOMATIZACIONES' },
    { id: 'roles', x: 40, y: 50, w: 200, h: 60, tipo: 'tabla', titulo: 'roles', sub: 'nombre único' },
    {
      id: 'permiso_rol',
      x: 260,
      y: 50,
      w: 200,
      h: 60,
      tipo: 'pivote',
      titulo: 'permiso_rol',
      sub: 'PK (rol, permiso)',
    },
    { id: 'users', x: 40, y: 180, w: 200, h: 60, tipo: 'tabla', titulo: 'users', sub: 'email único' },
    { id: 'clientes', x: 300, y: 180, w: 180, h: 60, tipo: 'tabla', titulo: 'clientes', sub: 'nombre único' },
    {
      id: 'acuerdos',
      x: 520,
      y: 180,
      w: 190,
      h: 60,
      tipo: 'tabla',
      titulo: 'acuerdos_de_servicio',
      sub: 'plazos JSON',
    },
    {
      id: 'automatizaciones',
      x: 750,
      y: 50,
      w: 210,
      h: 60,
      tipo: 'tabla',
      titulo: 'automatizaciones',
      sub: 'pasos JSON',
    },
    {
      id: 'ejecuciones',
      x: 750,
      y: 180,
      w: 210,
      h: 60,
      tipo: 'tabla',
      titulo: 'ejecuciones_de_…',
      sub: 'único (regla, inc.)',
    },
    {
      id: 'incidentes',
      x: 300,
      y: 330,
      w: 300,
      h: 64,
      tipo: 'tabla',
      titulo: 'incidentes',
      sub: 'plazos · FULLTEXT · soft delete',
      clave: true,
    },
    {
      id: 'historial',
      x: 40,
      y: 470,
      w: 200,
      h: 60,
      tipo: 'tabla',
      titulo: 'historial_incidentes',
      sub: 'antes / después',
    },
    {
      id: 'mensajes',
      x: 300,
      y: 470,
      w: 200,
      h: 60,
      tipo: 'tabla',
      titulo: 'mensajes_incidente',
      sub: 'interno · origen',
    },
    { id: 'adjuntos', x: 540, y: 470, w: 180, h: 60, tipo: 'tabla', titulo: 'adjuntos_mensaje', sub: 'ruta única' },
    {
      id: 'lecturas',
      x: 760,
      y: 470,
      w: 200,
      h: 60,
      tipo: 'tabla',
      titulo: 'lecturas_conversacion',
      sub: 'PK (inc., persona)',
    },
  ],
  aristas: [
    { de: 'permiso_rol', a: 'roles' },
    { de: 'users', a: 'roles', texto: 'rol_id' },
    { de: 'users', a: 'clientes', texto: 'cliente_id' },
    { de: 'acuerdos', a: 'clientes', texto: '1 : 1' },
    { de: 'ejecuciones', a: 'automatizaciones' },
    { de: 'incidentes', a: 'users', texto: 'creador · asignado' },
    { de: 'incidentes', a: 'clientes' },
    { de: 'ejecuciones', a: 'incidentes', modo: 'h', mx: 680 },
    { de: 'historial', a: 'incidentes' },
    { de: 'mensajes', a: 'incidentes' },
    { de: 'adjuntos', a: 'mensajes' },
    { de: 'lecturas', a: 'incidentes' },
  ],
}

/** @type {import('../tipos.js').Pagina} */
export const datos = {
  id: 'datos',
  titulo: 'Modelo de datos',
  grupo: 'tecnico',
  icono: iconos.datos,
  entradilla:
    'MySQL 8.4 en InnoDB. Las tablas, sus relaciones y los índices pensados para que la lista, los filtros y el panel respondan igual con cien mil incidentes. Tomado del esquema real después de todas las migraciones.',
  secciones: [
    {
      titulo: 'Entidades y relaciones',
      extra: 'plano D-01',
      bloques: [
        { tipo: 'plano', plano: entidades },
        {
          tipo: 'nota',
          texto:
            'Fuera del plano, por claridad: `permisos`, `auditoria`, `personal_access_tokens`, `notifications`, las tablas de Mi día (`notas_personales`, `entradas_del_plan`, `bloqueos_personales`) y las de Laravel (`cache`, `failed_jobs`).',
        },
      ],
    },
    {
      titulo: 'incidentes',
      extra: 'la tabla grande',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Clasificación',
              '`estado`, `prioridad` y `categoria` como ENUM, en el orden del ciclo de vida (MySQL ordena el ENUM por posición).',
            ],
            [
              'Personas',
              '`usuario_creador_id`, `usuario_asignado_id` (opcional) y `cliente_id` (opcional, para quien no es de una empresa).',
            ],
            [
              'Plazos',
              '`vence_respuesta_en`, `fecha_vencimiento`, `fecha_requerida`, `primera_respuesta_en` y `resuelto_en`: se guardan, no se calculan al consultar.',
            ],
            [
              'Cierre',
              '`solucion` (obligatoria al resolver) y `calificacion`, que la da el solicitante al confirmar; un CHECK de la base la limita de 1 a 5.',
            ],
            ['Idempotencia', '`clave_de_idempotencia` con único `(usuario_creador_id, clave_de_idempotencia)`.'],
            ['Borrado lógico', '`deleted_at`: todos los índices de consulta lo incluyen al final.'],
          ],
        },
      ],
    },
    {
      titulo: 'Índices pensados para volumen',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['(estado, prioridad, deleted_at)', 'Filtros combinados de la lista y del panel.'],
            ['(estado, fecha_vencimiento, deleted_at)', 'Vencidos y por vencer: una comparación con el índice.'],
            ['(estado, deleted_at, created_at)', 'Pendientes por antigüedad («cuánto llevan esperando»).'],
            [
              '(categoria · prioridad · cliente_id · created_at · resuelto_en, deleted_at)',
              'Un índice por cada filtro y orden de la lista.',
            ],
            ['FULLTEXT (titulo, descripcion)', 'Búsqueda de texto y casos similares.'],
            ['historial (campo, created_at)', 'Indicadores que leen cambios de un campo en un periodo.'],
            ['mensajes (incidente_id, id)', 'Conversación por cursor, sin ordenar en memoria.'],
            ['auditoria (accion, created_at) · (created_at)', 'Filtros de la auditoría por tipo y fecha.'],
            ['entradas_del_plan (usuario_id, fecha, inicio)', 'El día de una persona, ya ordenado.'],
          ],
        },
      ],
    },
    {
      titulo: 'Restricciones que protegen los datos',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'CHECK',
              '`incidentes_calificacion_entre_1_y_5`: la calificación no puede salir de 1 a 5 ni por fuera de la API.',
            ],
            [
              'Únicos',
              '`users.email`, `roles.nombre`, `clientes.nombre`, `permisos.clave`, `adjuntos_mensaje.ruta`, `acuerdos_de_servicio.cliente_id` (uno por empresa).',
            ],
            [
              'Idempotencia',
              'Únicos de incidentes y mensajes por persona y clave: dos envíos iguales no crean dos filas.',
            ],
            ['Una ejecución por incidente', '`una_ejecucion_por_incidente (automatizacion_id, incidente_id)`.'],
            [
              'Llaves compuestas',
              '`permiso_rol (rol_id, permiso_id)`, `lecturas_conversacion (incidente_id, usuario_id)`, `bloqueos_personales (usuario_id, incidente_id)`.',
            ],
            [
              'Plan del día',
              'Único `(usuario_id, fecha, incidente_id)`: un incidente aparece una vez por día en el plan de cada persona.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'Seguridad en los datos',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Contraseñas',
              'Con hash (`password`); `debe_cambiar_contrasena` obliga a cambiar la que puso un administrador.',
            ],
            [
              'Tokens',
              '`personal_access_tokens` guarda solo el hash del token, con vencimiento (`expires_at`), IP y agente de cada sesión.',
            ],
            [
              'Inicios de sesión',
              '`ultimo_inicio_en/ip` e `inicio_anterior_en/ip`, para mostrar a cada persona su acceso anterior.',
            ],
            ['Auditoría', '`auditoria` con acción (ENUM), quién, a quién, detalle e IP.'],
          ],
        },
      ],
    },
    {
      titulo: 'Migraciones',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['Cantidad', '26 migraciones en `database/migrations`, en orden.'],
            ['Pruebas', 'Corren contra su propia base (`incidentes_test`), nunca contra la de desarrollo.'],
          ],
        },
      ],
    },
  ],
}
