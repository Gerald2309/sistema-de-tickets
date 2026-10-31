import { gzipSync } from 'node:zlib'

/**
 * Presupuesto del paquete (RNF-F02): el JavaScript que hace falta para mostrar el inicio de sesión (la entrada,
 * la vista de ingreso y todo lo que importan de forma estática) no pasa de `limiteKb` comprimido con gzip. Si
 * lo pasa, la compilación falla: así el crecimiento no llega en silencio.
 * @param {{ limiteKb: number, vistaInicial: string }} opciones `vistaInicial`: final de la ruta del módulo
 * @returns {import('vite').Plugin}
 */
export function presupuestoDelPaquete({ limiteKb, vistaInicial }) {
  return {
    name: 'presupuesto-del-paquete',
    apply: 'build',
    generateBundle(_opciones, paquete) {
      const trozos = /** @type {import('rollup').OutputChunk[]} */ (
        Object.values(paquete).filter((salida) => salida.type === 'chunk')
      )
      const raices = trozos.filter((trozo) => trozo.isEntry || trozo.facadeModuleId?.endsWith(vistaInicial))
      if (!raices.some((trozo) => !trozo.isEntry)) {
        this.error(`Presupuesto del paquete: no se encontró el trozo de ${vistaInicial}.`)
      }

      /** @type {Set<string>} */
      const iniciales = new Set()
      const pendientes = raices.map((trozo) => trozo.fileName)
      while (pendientes.length) {
        const nombre = /** @type {string} */ (pendientes.pop())
        if (iniciales.has(nombre)) continue
        iniciales.add(nombre)
        const trozo = /** @type {import('rollup').OutputChunk | undefined} */ (paquete[nombre])
        pendientes.push(...(trozo?.imports ?? []))
      }

      const bytes = [...iniciales].reduce(
        (total, nombre) => total + gzipSync(/** @type {import('rollup').OutputChunk} */ (paquete[nombre]).code).length,
        0,
      )
      const kb = bytes / 1024
      const resumen = `JavaScript inicial: ${kb.toFixed(1)} KB gzip en ${iniciales.size} archivos (límite ${limiteKb} KB).`
      if (kb > limiteKb) {
        this.error(`Presupuesto del paquete superado. ${resumen}`)
      }
      this.info(resumen)
    },
  }
}
