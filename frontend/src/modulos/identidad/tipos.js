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

/** @typedef {{ id: number, nombre: string, activo: boolean }} Persona para asignar o filtrar (G-05) */

export {}
