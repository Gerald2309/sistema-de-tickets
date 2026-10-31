/**
 * Sin secretos en el paquete (RNF-F15): todo lo que se compila llega al navegador de cualquiera. La aplicación
 * no usa variables `VITE_` (su única configuración se lee al arrancar de /configuracion.json), así que nada con
 * forma de secreto debería aparecer en la salida; si aparece, la compilación falla y nombra el archivo.
 */
const PATRONES = [
  { nombre: 'clave privada', expresion: /-----BEGIN [A-Z ]*PRIVATE KEY-----/ },
  { nombre: 'clave de aplicación de Laravel', expresion: /base64:[A-Za-z0-9+/]{40,}={0,2}/ },
  { nombre: 'variable de secreto del servidor', expresion: /\b(?:APP_KEY|DB_PASSWORD|[A-Z_]+_SECRET)\b/ },
]

/** @returns {import('vite').Plugin} */
export function sinSecretosEnElPaquete() {
  return {
    name: 'sin-secretos-en-el-paquete',
    apply: 'build',
    generateBundle(_opciones, paquete) {
      for (const salida of Object.values(paquete)) {
        const contenido = salida.type === 'chunk' ? salida.code : String(salida.source)
        const hallado = PATRONES.find(({ expresion }) => expresion.test(contenido))
        if (hallado) {
          this.error(`Posible secreto en el paquete (${hallado.nombre}) en ${salida.fileName}.`)
        }
      }
    },
  }
}
