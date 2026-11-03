/**
 * Tipos del módulo de identidad (modelo-datos-cliente.md §3.2).
 * @typedef {import('@/compartido/api/paginacion.js').Referencia} Referencia
 */

/**
 * Clave de permiso: el catálogo fijo de la API (`ClavePermiso` en el backend). Al tiparla, un permiso mal
 * escrito en una vista o una ruta falla en `npm run tipos` en lugar de ocultar el botón en silencio.
 * @typedef {'crear_incidente' | 'ver_propios' | 'editar_propios' | 'ver_todos' | 'editar_todos' | 'cambiar_estado'
 *   | 'reasignar' | 'borrar' | 'ver_historial' | 'ver_dashboard' | 'administrar_roles' | 'administrar_usuarios'
 *   | 'ver_auditoria' | 'administrar_automatizaciones'} Permiso
 */

/**
 * @typedef {object} Perfil persona con sesión iniciada (`GET /me`)
 * @property {number} id
 * @property {string} nombre
 * @property {string} correo
 * @property {Referencia} rol
 * @property {Referencia | null} cliente empresa cliente; nula para el personal de Psicol
 * @property {Permiso[]} permisos
 * @property {boolean} debeCambiarContrasena recibió una contraseña de un administrador: solo puede cambiarla
 * @property {{ en: string, ip: string | null } | null} inicioAnterior el inicio de sesión previo al de esta sesión
 */

/**
 * @typedef {object} Sesion una sesión abierta de la persona (`GET perfil/sesiones`)
 * @property {number} id
 * @property {string | null} ip
 * @property {string | null} agente navegador y sistema, tal como los envió
 * @property {string | null} iniciadaEn ISO 8601 en UTC
 * @property {string | null} ultimoUsoEn ISO 8601 en UTC
 * @property {boolean} actual la de esta pestaña
 */

/**
 * @typedef {object} CredencialDeSesion token Bearer devuelto al iniciar sesión
 * @property {string} token
 * @property {string | null} expiraEn ISO 8601 en UTC
 */

/**
 * @typedef {object} Usuario
 * @property {number} id
 * @property {string} nombre
 * @property {string} correo
 * @property {boolean} activo
 * @property {Referencia} rol
 * @property {Referencia | null} cliente null para el personal de Psicol
 */

/**
 * @typedef {object} FiltrosDeUsuarios
 * @property {string} [q]
 * @property {number} [rol]
 * @property {number} [cliente]
 * @property {'activos' | 'inactivos'} [estado]
 * @property {number} [pagina]
 */

/** @typedef {{ id: number, nombre: string, activo: boolean, usuarios: number }} Cliente empresa cliente; `usuarios` es cuántas personas tiene */

/** @typedef {{ id: number, nombre: string, permisos: string[], usuarios: number }} Rol */

/** @typedef {{ id: number, nombre: string, activo: boolean, rol?: string }} Persona para asignar o filtrar (G-05); el rol solo al asignar */

export {}

/**
 * @typedef {object} AcuerdoDeServicio plazos de una empresa, en minutos, por prioridad
 * @property {boolean} horarioLaboral si corren solo en horario laboral
 * @property {Record<string, { respuestaMinutos: number, resolucionMinutos: number }>} plazos
 */
