<script setup>
import { inject } from 'vue'
import { iconos } from '@/compartido/iconos.js'
import { bloqueDe, textoDelPaso } from '../catalogo.js'
import { EDITOR } from '../editor.js'
import { erroresDelPaso } from '../flujo.js'
import TarjetaDePaso from './TarjetaDePaso.vue'
import UnionDelFlujo from './UnionDelFlujo.vue'

/**
 * Una lista de pasos con sus «+». Una condición abre dos caminos, cada uno pintado con este mismo componente:
 * por eso el árbol se dibuja solo, a cualquier profundidad.
 */
const props = defineProps({
  pasos: { type: /** @type {import('vue').PropType<import('../tipos.js').Paso[]>} */ (Array), required: true },
  // Ruta de la lista en el documento (`pasos`, `pasos.0.si`): la misma con la que la API señala un error.
  ruta: { type: String, required: true },
  condicionesAntes: { type: Number, required: true },
})

const editor = /** @type {import('../editor.js').ContextoDelEditor} */ (inject(EDITOR))
const SALIDAS = /** @type {const} */ ([
  ['si', 'Sí'],
  ['no', 'No'],
])

/** @param {number} k */
const eligiendoEn = (k) => editor.insertarEn.value?.lista === props.pasos && editor.insertarEn.value.k === k
/** @param {number} k */
const anadirEn = (k) => editor.abrirBiblioteca(props.pasos, k, props.condicionesAntes)
</script>

<template>
  <UnionDelFlujo :eligiendo="eligiendoEn(0)" @anadir="anadirEn(0)" />
  <template v-for="(paso, i) in pasos" :key="i">
    <TarjetaDePaso
      :tipo="paso.tipo === 'condicion' ? 'si' : 'entonces'"
      :icono="bloqueDe(paso).icono"
      :titulo="bloqueDe(paso).titulo"
      :texto="textoDelPaso(paso, editor.nombres)"
      :seleccionada="editor.seleccionado.value === paso"
      :con-error="erroresDelPaso(editor.errores.value, `${ruta}.${i}`).length > 0"
      @elegir="editor.elegir(paso)"
      @quitar="editor.quitar(pasos, i)"
    />
    <div v-if="paso.tipo === 'condicion'" class="bifurcacion">
      <div class="bifurcacion__tallo" />
      <div class="bifurcacion__rama">
        <div v-for="[salida, nombre] in SALIDAS" :key="salida" class="bifurcacion__camino">
          <span class="bifurcacion__salida" :class="`bifurcacion__salida--${salida}`">{{ nombre }}</span>
          <CaminoDelFlujo
            :pasos="paso[salida]"
            :ruta="`${ruta}.${i}.${salida}`"
            :condiciones-antes="condicionesAntes + 1"
          />
        </div>
      </div>
    </div>
    <UnionDelFlujo v-else :eligiendo="eligiendoEn(i + 1)" @anadir="anadirEn(i + 1)" />
  </template>
  <div v-if="pasos.at(-1)?.tipo !== 'condicion'" class="fin"><v-icon :icon="iconos.fin" size="16" />Fin</div>
</template>

<style scoped>
.bifurcacion {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.bifurcacion__tallo {
  width: 1px;
  height: 18px;
  background: var(--borde-fuerte);
}

.bifurcacion__rama {
  display: flex;
  align-items: flex-start;
}

/* La línea horizontal une los dos caminos; cada camino baja desde ella hasta su etiqueta. */
.bifurcacion__camino {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 22px 14px 0;
}

.bifurcacion__camino::before {
  position: absolute;
  top: 0;
  right: 0;
  left: 0;
  height: 1px;
  background: var(--borde-fuerte);
  content: '';
}

.bifurcacion__camino:first-child::before {
  left: 50%;
}

.bifurcacion__camino:last-child::before {
  right: 50%;
}

.bifurcacion__camino::after {
  position: absolute;
  top: 0;
  left: 50%;
  width: 1px;
  height: 22px;
  background: var(--borde-fuerte);
  content: '';
}

.bifurcacion__salida {
  position: relative;
  z-index: 1;
  padding: 1px 10px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  background: var(--superficie);
  border: 1px solid var(--borde-fuerte);
  border-radius: var(--radio-chip);
}

.bifurcacion__salida--si {
  color: var(--exito-texto);
  border-color: var(--exito-borde);
}

.bifurcacion__salida--no {
  color: var(--suave);
}

.fin {
  display: flex;
  gap: 6px;
  align-items: center;
  padding: 6px 14px;
  color: var(--tenue);
  font-size: 13px;
  font-weight: 600;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
}
</style>
