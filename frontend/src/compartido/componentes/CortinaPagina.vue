<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { registrarCortina } from '@/compartido/cortina.js'
import { CURVA, CURVA_ENTRADA_SALIDA, reducirMovimiento } from '@/compartido/movimiento.js'

/**
 * Pinta la cortina de compartido/cortina.js: tres franjas inclinadas (cian, verde y blanco) entran, la palabra
 * sube letra a letra, ocurre el cambio y todo sale. Con «reducir movimiento» es un fundido breve.
 */
const visible = ref(false)
const simple = ref(false)
const palabra = ref('')
const raiz = ref(/** @type {HTMLElement | null} */ (null))

const INCLINACION = 'skewX(-14deg)'
const fuera = (/** @type {number} */ x) => `translateX(${x}%) ${INCLINACION}`

/**
 * @param {Animation[]} animaciones
 * @returns {Promise<unknown>}
 */
const terminar = (animaciones) => Promise.all(animaciones.map((animacion) => animacion.finished))
const esperar = (/** @type {number} */ ms) => new Promise((resolver) => setTimeout(resolver, ms))

/** @type {import('@/compartido/cortina.js').Reproductor} */
async function reproducir(texto, alMedio) {
  palabra.value = texto
  simple.value = reducirMovimiento() || typeof HTMLElement.prototype.animate !== 'function'
  visible.value = true
  await nextTick()
  const elemento = /** @type {HTMLElement} */ (raiz.value)
  try {
    if (simple.value) {
      await terminar([elemento.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 160, fill: 'forwards' })])
      await alMedio()
      await terminar([elemento.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 220, fill: 'forwards' })])
      return
    }
    const franjas = /** @type {HTMLElement[]} */ ([...elemento.querySelectorAll('.cortina__franja')])
    const letras = /** @type {HTMLElement[]} */ ([...elemento.querySelectorAll('.cortina__letra')])
    await terminar(
      franjas.map((franja, i) =>
        franja.animate([{ transform: fuera(-130) }, { transform: fuera(0) }], {
          duration: 620,
          delay: i * 110,
          easing: CURVA_ENTRADA_SALIDA,
          fill: 'forwards',
        }),
      ),
    )
    await terminar(
      letras.map((letra, i) =>
        letra.animate([{ transform: 'translateY(115%)' }, { transform: 'none' }], {
          duration: 650,
          delay: i * 32,
          easing: CURVA,
          fill: 'forwards',
        }),
      ),
    )
    await alMedio()
    await esperar(240)
    await terminar(
      letras.map((letra, i) =>
        letra.animate([{ transform: 'none' }, { transform: 'translateY(-115%)' }], {
          duration: 460,
          delay: i * 18,
          easing: CURVA_ENTRADA_SALIDA,
          fill: 'forwards',
        }),
      ),
    )
    await terminar(
      franjas.map((franja, i) =>
        franja.animate([{ transform: fuera(0) }, { transform: fuera(130) }], {
          duration: 620,
          delay: i * 110,
          easing: CURVA_ENTRADA_SALIDA,
          fill: 'forwards',
        }),
      ),
    )
  } finally {
    elemento.getAnimations({ subtree: true }).forEach((animacion) => animacion.cancel())
    visible.value = false
  }
}

onMounted(() => registrarCortina(reproducir))
onBeforeUnmount(() => registrarCortina(null))
</script>

<template>
  <div v-show="visible" ref="raiz" class="cortina" :class="{ 'cortina--simple': simple }" aria-hidden="true">
    <div class="cortina__franja cortina__franja--cian" />
    <div class="cortina__franja cortina__franja--verde" />
    <div class="cortina__franja cortina__franja--blanca" />
    <div class="cortina__palabra">
      <span v-for="(parte, i) in palabra.split(' ')" :key="i" class="cortina__parte">
        <span v-for="(letra, j) in parte" :key="j" class="cortina__mascara"
          ><span class="cortina__letra">{{ letra }}</span></span
        >
      </span>
    </div>
  </div>
</template>

<style scoped>
.cortina {
  position: fixed;
  inset: 0;
  z-index: 9000;
  overflow: hidden;
  pointer-events: all;
}

.cortina__franja {
  position: absolute;
  top: 0;
  left: -35vw;
  width: 170vw;
  height: 100%;
  transform: translateX(-130%) skewX(-14deg);
  will-change: transform;
}

.cortina__franja--cian {
  background: var(--marca-cian);
}

.cortina__franja--verde {
  background: var(--marca-verde-medio);
}

.cortina__franja--blanca {
  background: var(--superficie);
}

.cortina__palabra {
  position: absolute;
  inset: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 0 0.25em;
  align-items: center;
  justify-content: center;
  color: var(--texto);
  font-size: clamp(52px, 9vw, 132px);
  font-weight: 800;
  line-height: 1;
  letter-spacing: -0.05em;
  pointer-events: none;
}

/* La máscara deja aire abajo para los trazos que bajan de la línea (p, g, y). */
.cortina__mascara {
  display: inline-block;
  margin-bottom: -0.16em;
  padding-bottom: 0.16em;
  overflow: hidden;
  vertical-align: bottom;
}

.cortina__letra {
  display: inline-block;
  transform: translateY(115%);
}

.cortina--simple .cortina__franja:not(.cortina__franja--blanca),
.cortina--simple .cortina__palabra {
  display: none;
}

.cortina--simple .cortina__franja--blanca {
  transform: none;
}
</style>
