/**
 * Tipos del módulo de identidad (modelo-datos-cliente.md §3.2).
 * @typedef {import('@/compartido/api/paginacion.js').Referencia} Referencia
 */

/**
 * @typedef {object} Perfil persona con sesión iniciada (`GET /me`)
 * @property {number} id
 * @property {string} nombre
 * @property {string} correo
 * @property {Referencia} rol
 * @property {Referencia | null} cliente empresa cliente; nula para el personal de Psicol
 * @property {string[]} permisos claves de permiso, por ejemplo 'ver_todos'
 */

/**
 * @typedef {object} CredencialDeSesion token Bearer devuelto al iniciar sesión
 * @property {string} token
 * @property {string | null} expiraEn ISO 8601 en UTC
 */

export {}
