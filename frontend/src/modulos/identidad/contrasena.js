/**
 * Requisitos de una contraseña nueva. Reproducen `Password::defaults()` del backend para avisar mientras se escribe;
 * la API sigue siendo quien decide, y solo ella comprueba si apareció en una filtración.
 */
export const LONGITUD_MINIMA = 15
export const LONGITUD_MAXIMA = 256

/** @type {ReadonlyArray<{ texto: string, cumple: (contrasena: string) => boolean }>} */
export const REQUISITOS = Object.freeze([
  { texto: `Al menos ${LONGITUD_MINIMA} caracteres`, cumple: (contrasena) => contrasena.length >= LONGITUD_MINIMA },
  {
    texto: 'Una mayúscula y una minúscula',
    cumple: (contrasena) => /\p{Lu}/u.test(contrasena) && /\p{Ll}/u.test(contrasena),
  },
  { texto: 'Un número', cumple: (contrasena) => /\p{N}/u.test(contrasena) },
  { texto: 'Un símbolo o un espacio', cumple: (contrasena) => /[\p{P}\p{S}\p{Z}]/u.test(contrasena) },
])

/**
 * @param {string} contrasena
 * @returns {boolean} cumple todos los requisitos que se pueden comprobar aquí
 */
export const cumpleRequisitos = (contrasena) => REQUISITOS.every(({ cumple }) => cumple(contrasena))
