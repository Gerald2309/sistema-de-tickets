/**
 * Nombres y descripciones de los permisos para la matriz de roles (sistema de diseño, Roles y permisos). Las
 * claves son las de la API (`GET /permisos`); una prueba falla si la API trae una sin presentación.
 */

/** @typedef {{ clave: string, nombre: string, descripcion: string }} PresentacionDePermiso */

/** @type {ReadonlyArray<{ titulo: string, permisos: PresentacionDePermiso[] }>} */
export const GRUPOS_DE_PERMISOS = Object.freeze([
  {
    titulo: 'Incidentes',
    permisos: [
      { clave: 'crear_incidente', nombre: 'Crear incidentes', descripcion: 'Reportar un incidente nuevo.' },
      { clave: 'ver_propios', nombre: 'Ver los propios', descripcion: 'Solo los que creó la persona.' },
      {
        clave: 'editar_propios',
        nombre: 'Clasificar los propios',
        descripcion: 'Prioridad, categoría y vencimiento de los suyos, mientras nadie los ha tomado.',
      },
      {
        clave: 'ver_todos',
        nombre: 'Ver todos',
        descripcion: 'Todos los incidentes; incluye los propios, los similares y el aviso de incidente masivo.',
      },
      {
        clave: 'editar_todos',
        nombre: 'Clasificar los que atiende',
        descripcion: 'Los mismos campos en los incidentes que atiende.',
      },
      { clave: 'cambiar_estado', nombre: 'Cambiar estado', descripcion: 'Avanzar el estado y registrar la solución.' },
      { clave: 'reasignar', nombre: 'Reasignar', descripcion: 'Elegir o cambiar a quién está asignado.' },
      { clave: 'borrar', nombre: 'Eliminar', descripcion: 'Eliminar incidentes (quedan en el historial).' },
      { clave: 'ver_historial', nombre: 'Ver historial', descripcion: 'Los cambios de cada incidente.' },
    ],
  },
  {
    titulo: 'Panel',
    permisos: [
      { clave: 'ver_dashboard', nombre: 'Ver el panel', descripcion: 'Indicadores de lo que la persona puede ver.' },
    ],
  },
  {
    titulo: 'Administración',
    permisos: [
      {
        clave: 'administrar_usuarios',
        nombre: 'Administrar usuarios',
        descripcion: 'Altas, cambios, desactivación, contraseñas, roles y empresas.',
      },
      {
        clave: 'administrar_roles',
        nombre: 'Administrar roles',
        descripcion: 'Crear, renombrar y eliminar roles y fijar sus permisos.',
      },
      { clave: 'ver_auditoria', nombre: 'Ver la auditoría', descripcion: 'El registro de acciones de administración.' },
    ],
  },
])

/**
 * @param {string} clave
 * @returns {string} nombre legible; la clave si no se conoce
 */
export function nombreDePermiso(clave) {
  return (
    GRUPOS_DE_PERMISOS.flatMap((grupo) => grupo.permisos).find((permiso) => permiso.clave === clave)?.nombre ?? clave
  )
}
