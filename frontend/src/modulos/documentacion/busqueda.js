/**
 * Buscador de la documentación. Indexa los mismos datos que dibujan las páginas (secciones, filas, pasos,
 * código y cajas de los planos), así que el índice no puede quedar desactualizado respecto de lo que se ve.
 */

/** @typedef {import('./tipos.js').Pagina} Pagina */

/**
 * @typedef {object} Destino a dónde lleva un resultado
 * @property {string} pagina
 * @property {number} [seccion] índice de la sección dentro de la página
 * @property {number} [fila]
 * @property {number} [paso]
 */

/**
 * @typedef {object} Entrada
 * @property {string} titulo
 * @property {string} donde página y sección, para el rótulo del resultado
 * @property {string} texto lo que acompaña al título: explicación, archivo, código
 * @property {Destino} destino
 * @property {string} clave todo junto y normalizado, para buscar
 * @property {string} claveDelTitulo
 */

/**
 * @param {string} texto
 * @returns {string} sin mayúsculas ni tildes, para que «auditoria» encuentre «Auditoría»
 */
export function normalizar(texto) {
  return texto
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .toLowerCase()
}

/**
 * @param {string} texto
 * @returns {string} sin las comillas invertidas que marcan código
 */
const plano = (texto) => texto.replaceAll('`', '')

/**
 * @param {Pagina[]} paginas
 * @returns {Entrada[]}
 */
export function construirIndice(paginas) {
  /** @type {Omit<Entrada, 'clave' | 'claveDelTitulo'>[]} */
  const entradas = []
  for (const pagina of paginas) {
    entradas.push({ titulo: pagina.titulo, donde: 'Página', texto: pagina.entradilla, destino: { pagina: pagina.id } })
    pagina.secciones.forEach((seccion, indiceSeccion) => {
      const donde = `${pagina.titulo} › ${seccion.titulo}`
      const destino = { pagina: pagina.id, seccion: indiceSeccion }
      const resumen = seccion.bloques.flatMap((bloque) => {
        if (bloque.tipo === 'nota') return [bloque.texto]
        if (bloque.tipo === 'codigo') return [bloque.codigo]
        if (bloque.tipo === 'planos') return bloque.vistas.map((vista) => vista.nota)
        if (bloque.tipo === 'focos') return bloque.focos.map((foco) => foco.nota)
        return []
      })
      entradas.push({ titulo: seccion.titulo, donde: pagina.titulo, texto: resumen.join(' '), destino })

      for (const bloque of seccion.bloques) {
        if (bloque.tipo === 'filas') {
          bloque.filas.forEach(([titulo, texto], fila) =>
            entradas.push({ titulo, donde, texto, destino: { ...destino, fila } }),
          )
        }
        if (bloque.tipo === 'recorrido') {
          bloque.pasos.forEach((paso, i) =>
            entradas.push({
              titulo: paso.titulo,
              donde: `${pagina.titulo} › Paso ${i + 1}`,
              texto: [paso.texto, paso.archivo, paso.codigo ?? ''].join(' '),
              destino: { ...destino, paso: i },
            }),
          )
        }
        const planos =
          bloque.tipo === 'plano' || bloque.tipo === 'focos' || bloque.tipo === 'recorrido'
            ? [bloque.plano]
            : bloque.tipo === 'planos'
              ? bloque.vistas.map((vista) => vista.plano)
              : []
        for (const unPlano of planos) {
          for (const nodo of unPlano.nodos.filter((n) => !n.zona)) {
            entradas.push({
              titulo: nodo.titulo,
              donde: `Plano ${unPlano.codigo} · ${unPlano.titulo}`,
              texto: [nodo.tipo ?? '', nodo.sub ?? ''].join(' '),
              destino,
            })
          }
        }
      }
    })
  }
  return entradas.map((entrada) => ({
    ...entrada,
    titulo: plano(entrada.titulo),
    texto: plano(entrada.texto),
    clave: normalizar(plano(`${entrada.titulo} ${entrada.donde} ${entrada.texto}`)),
    claveDelTitulo: normalizar(plano(entrada.titulo)),
  }))
}

/**
 * Todas las palabras deben aparecer; primero lo que las tiene en el título.
 * @param {Entrada[]} indice
 * @param {string} consulta
 * @param {number} [maximo]
 * @returns {Entrada[]}
 */
export function buscar(indice, consulta, maximo = 30) {
  const terminos = normalizar(consulta).split(/\s+/).filter(Boolean)
  if (!terminos.length) {
    return []
  }
  return indice
    .filter((entrada) => terminos.every((termino) => entrada.clave.includes(termino)))
    .map((entrada) => ({
      entrada,
      peso:
        terminos.filter((termino) => entrada.claveDelTitulo.includes(termino)).length * 10 +
        (entrada.claveDelTitulo.startsWith(terminos[0]) ? 5 : 0) +
        (entrada.destino.fila === undefined ? 1 : 0),
    }))
    .sort((a, b) => b.peso - a.peso)
    .slice(0, maximo)
    .map(({ entrada }) => entrada)
}

/**
 * Parte un texto en tramos marcados y sin marcar, sin importar tildes ni mayúsculas. Se compara sobre el texto
 * normalizado y se marca el original.
 * @param {string} texto
 * @param {string} consulta
 * @returns {{ texto: string, marcado: boolean }[]}
 */
export function marcar(texto, consulta) {
  const caracteres = [...texto]
  let normal = ''
  /** @type {number[]} de cada carácter normalizado, su posición en el original */
  const origen = []
  caracteres.forEach((caracter, i) => {
    for (const n of normalizar(caracter)) {
      normal += n
      origen.push(i)
    }
  })
  const marcados = caracteres.map(() => false)
  for (const termino of normalizar(consulta)
    .split(/\s+/)
    .filter((t) => t.length > 1)) {
    for (let i = normal.indexOf(termino); i >= 0; i = normal.indexOf(termino, i + termino.length)) {
      for (let k = origen[i]; k <= origen[i + termino.length - 1]; k++) marcados[k] = true
    }
  }
  /** @type {{ texto: string, marcado: boolean }[]} */
  const tramos = []
  caracteres.forEach((caracter, i) => {
    const ultimo = tramos.at(-1)
    if (ultimo && ultimo.marcado === marcados[i]) {
      ultimo.texto += caracter
    } else {
      tramos.push({ texto: caracter, marcado: marcados[i] })
    }
  })
  return tramos
}

/**
 * @param {string} texto
 * @param {string} consulta
 * @param {number} [largo]
 * @returns {string} un trozo del texto alrededor de la primera coincidencia
 */
export function extracto(texto, consulta, largo = 140) {
  const primero = normalizar(consulta).split(/\s+/).find(Boolean) ?? ''
  const inicio = Math.max(0, normalizar(texto).indexOf(primero) - 40)
  return (inicio > 0 ? '…' : '') + texto.slice(inicio, inicio + largo)
}
