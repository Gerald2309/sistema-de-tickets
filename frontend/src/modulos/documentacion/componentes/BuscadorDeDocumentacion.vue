<script setup>
import { computed, ref, watch } from 'vue'
import { iconos } from '@/compartido/iconos.js'
import { buscar, extracto, marcar } from '../busqueda.js'

/**
 * Buscador de la documentación, con la misma forma que «Buscar o ir a…» de la aplicación. Busca en todo lo que
 * muestran las páginas y lleva al punto exacto: la sección, la fila o el paso. Flechas para moverse, Enter
 * para abrir, Esc para cerrar.
 */
const props = defineProps({
  indice: { type: /** @type {import('vue').PropType<import('../busqueda.js').Entrada[]>} */ (Array), required: true },
})
const emit = defineEmits(['elegir'])
const abierto = defineModel({ type: Boolean, default: false })

const consulta = ref('')
const elegido = ref(0)
const resultados = computed(() => buscar(props.indice, consulta.value))
const lista = ref(/** @type {HTMLElement | null} */ (null))

watch(abierto, (valor) => {
  if (valor) {
    consulta.value = ''
  }
})
watch(resultados, () => {
  elegido.value = 0
})

/** @param {number} paso */
function mover(paso) {
  if (!resultados.value.length) {
    return
  }
  elegido.value = (elegido.value + paso + resultados.value.length) % resultados.value.length
  lista.value?.querySelectorAll('[role="option"]')[elegido.value]?.scrollIntoView({ block: 'nearest' })
}

/** @param {number} indice */
function abrir(indice) {
  const resultado = resultados.value[indice]
  if (resultado) {
    abierto.value = false
    emit('elegir', resultado.destino)
  }
}
</script>

<template>
  <v-dialog v-model="abierto" max-width="680" scrim="var(--texto)" aria-label="Buscar en la documentación">
    <v-card class="buscador">
      <v-text-field
        v-model="consulta"
        autofocus
        variant="plain"
        density="comfortable"
        hide-details
        placeholder="Clase, tabla, endpoint, permiso, concepto…"
        aria-label="Buscar en la documentación"
        role="combobox"
        aria-controls="resultados-de-documentacion"
        :aria-expanded="resultados.length > 0"
        :prepend-inner-icon="iconos.buscar"
        class="buscador__campo"
        @keydown.down.prevent="mover(1)"
        @keydown.up.prevent="mover(-1)"
        @keydown.enter.prevent="abrir(elegido)"
      />
      <v-divider />
      <div id="resultados-de-documentacion" ref="lista" class="buscador__lista" role="listbox">
        <p v-if="!consulta" class="buscador__vacio">
          Busque una clase, una tabla, un endpoint, un permiso o un concepto: «insertOrIgnore», «Reverb», «ejecuciones»,
          «idempotencia».
        </p>
        <p v-else-if="!resultados.length" class="buscador__vacio">Sin resultados.</p>
        <button
          v-for="(resultado, i) in resultados"
          v-else
          :key="`${resultado.donde}-${resultado.titulo}-${i}`"
          type="button"
          role="option"
          class="resultado"
          :aria-selected="i === elegido"
          @click="abrir(i)"
          @mousemove="elegido = i"
        >
          <span class="resultado__donde">{{ resultado.donde }}</span>
          <span class="resultado__titulo">
            <template v-for="(tramo, k) in marcar(resultado.titulo, consulta)" :key="k">
              <mark v-if="tramo.marcado">{{ tramo.texto }}</mark
              ><template v-else>{{ tramo.texto }}</template>
            </template>
          </span>
          <span v-if="resultado.texto" class="resultado__extracto">
            <template v-for="(tramo, k) in marcar(extracto(resultado.texto, consulta), consulta)" :key="k">
              <mark v-if="tramo.marcado">{{ tramo.texto }}</mark
              ><template v-else>{{ tramo.texto }}</template>
            </template>
          </span>
        </button>
      </div>
      <div class="buscador__pie">
        <span><kbd>↑</kbd> <kbd>↓</kbd> moverse</span>
        <span><kbd>Enter</kbd> abrir</span>
        <span class="buscador__total num">
          {{
            consulta
              ? `${resultados.length} resultado${resultados.length === 1 ? '' : 's'}`
              : `${indice.length} entradas`
          }}
        </span>
      </div>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.buscador {
  border: 1px solid var(--borde);
  box-shadow: var(--sombra-2) !important;
}

.buscador__campo {
  padding: 4px 12px;
}

.buscador__lista {
  max-height: 56vh;
  overflow-y: auto;
}

.buscador__vacio {
  margin: 0;
  padding: 20px 16px;
  color: var(--suave);
  font-size: 14px;
}

.resultado {
  display: grid;
  gap: 2px;
  width: 100%;
  padding: 10px 16px;
  color: var(--texto);
  font: 14px var(--fuente);
  text-align: left;
  background: var(--superficie);
  border: 0;
  cursor: pointer;
}

.resultado + .resultado {
  border-top: 1px solid var(--borde);
}

.resultado[aria-selected='true'] {
  background: var(--acento-fondo);
  box-shadow: inset 3px 0 0 var(--acento);
}

.resultado__donde {
  color: var(--tenue);
  font-size: 12px;
}

.resultado__extracto {
  overflow: hidden;
  color: var(--suave);
  font-size: 13px;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.resultado mark {
  color: inherit;
  background: var(--advertencia-fondo);
  border-radius: 2px;
  box-shadow: 0 0 0 1px var(--advertencia-borde);
}

.buscador__pie {
  display: flex;
  gap: 16px;
  padding: 8px 16px;
  color: var(--tenue);
  font-size: 12px;
  background: var(--sutil);
  border-top: 1px solid var(--borde);
}

.buscador__total {
  margin-left: auto;
}

kbd {
  padding: 1px 6px;
  font: 500 11px var(--mono);
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
}
</style>
