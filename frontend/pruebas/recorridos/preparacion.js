import { ADMINISTRADOR, api, iniciarSesionPorApi } from './entorno.js'

/**
 * Antes de todos los recorridos: una sola sesión de administrador para preparar datos y abrir páginas. El
 * límite de inicio de sesión es de 5 por minuto por correo, así que no se inicia una sesión por prueba.
 */
export default async function preparar() {
  if (!ADMINISTRADOR.correo || !ADMINISTRADOR.clave) {
    throw new Error('Faltan ADMIN_EMAIL y ADMIN_PASSWORD (se leen de .env.example y .env, como Docker Compose).')
  }
  const token = await iniciarSesionPorApi(ADMINISTRADOR)
  process.env.TOKEN_ADMINISTRADOR = token
  // Al terminar se cierra esa sesión.
  return async () => {
    await api('auth/logout', { metodo: 'POST', token })
  }
}
