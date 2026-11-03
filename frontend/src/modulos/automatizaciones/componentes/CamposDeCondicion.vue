<script setup>
import { computed, ref } from 'vue'
import { iconos } from '@/compartido/iconos.js'
import { useEmpresas } from '@/modulos/identidad/index.js'
import { CATEGORIAS, PRIORIDADES } from '@/modulos/incidentes/index.js'
import { CONDICIONES, comparaConUnValor, operadoresDe, valorParaComparar } from '../catalogo.js'

/**
 * Un paso «Si»: cómo compara (una lista de opciones o, donde hay orden, ≥, ≤ o = con un valor) y con qué.
 */
const props = defineProps({
  paso: { type: /** @type {import('vue').PropType<import('../tipos.js').Condicion>} */ (Object), required: true },
})
const emit = defineEmits({ cambiar: (/** @type {Partial<import('../tipos.js').Condicion>} */ cambios) => !!cambios })

const { empresas } = useEmpresas()
const buscado = ref('')

const conPalabras = computed(() => props.paso.campo === 'palabras')
const conUnValor = computed(() => comparaConUnValor(props.paso.operador))
const conPorcentaje = computed(() => props.paso.campo.startsWith('plazo_'))
const prioridades = Object.entries(PRIORIDADES).map(([valor, { nombre }]) => ({ valor, nombre }))

/**
 * Al pasar de una lista a comparar con un valor queda uno solo; si no había, el más común.
 * @param {import('../tipos.js').Condicion['operador']} operador
 */
function cambiarOperador(operador) {
  const valores =
    comparaConUnValor(operador) && props.paso.valores.length !== 1
      ? [valorParaComparar(props.paso.campo)]
      : props.paso.valores
  emit('cambiar', { operador, valores })
}

/** @type {import('vue').ComputedRef<Array<{ valor: number | string, nombre: string }>>} */
const opciones = computed(() => {
  switch (props.paso.campo) {
    case 'empresa':
      return empresas.value.map((empresa) => ({ valor: empresa.id, nombre: empresa.nombre }))
    case 'prioridad':
      return Object.entries(PRIORIDADES).map(([valor, { nombre }]) => ({ valor, nombre }))
    default:
      return Object.entries(CATEGORIAS).map(([valor, nombre]) => ({ valor, nombre }))
  }
})
const visibles = computed(() =>
  opciones.value.filter((opcion) => opcion.nombre.toLowerCase().includes(buscado.value.trim().toLowerCase())),
)

/** @param {unknown[]} palabras sin espacios de más ni vacías */
const cambiarPalabras = (palabras) =>
  emit('cambiar', { valores: palabras.map((palabra) => String(palabra).trim()).filter(Boolean) })

/**
 * Marca o desmarca una opción; los valores quedan en el orden de la lista.
 * @param {number | string} valor
 * @param {boolean} marcada
 */
function marcar(valor, marcada) {
  const valores = opciones.value
    .map((opcion) => opcion.valor)
    .filter((otro) => (otro === valor ? marcada : props.paso.valores.includes(otro)))
  emit('cambiar', { valores })
}
</script>

<template>
  <div>
    <v-select
      :label="CONDICIONES[paso.campo].sujeto"
      :items="operadoresDe(paso.campo)"
      item-title="nombre"
      item-value="valor"
      :model-value="paso.operador"
      @update:model-value="cambiarOperador"
    />
    <v-text-field
      v-if="conPorcentaje"
      label="Porcentaje del plazo"
      hint="100 % es cuando se vence; más de 100, que ya está vencido."
      persistent-hint
      type="number"
      min="0"
      max="1000"
      suffix="%"
      :model-value="paso.valores[0]"
      @update:model-value="emit('cambiar', { valores: [Math.max(0, Math.round(Number($event)))] })"
    />
    <v-select
      v-else-if="conUnValor"
      label="Prioridad"
      :items="prioridades"
      item-title="nombre"
      item-value="valor"
      :model-value="paso.valores[0]"
      @update:model-value="emit('cambiar', { valores: [$event] })"
    />
    <v-combobox
      v-else-if="conPalabras"
      label="Palabras o frases"
      hint="Escriba una y pulse Enter. No importan las mayúsculas ni las tildes."
      persistent-hint
      multiple
      chips
      closable-chips
      :model-value="paso.valores"
      @update:model-value="cambiarPalabras"
    />
    <template v-else>
      <v-text-field
        v-if="paso.campo === 'empresa'"
        v-model="buscado"
        label="Buscar empresa"
        :prepend-inner-icon="iconos.buscar"
        hide-details
        class="mb-2"
      />
      <fieldset class="opciones">
        <legend class="solo-lectores">{{ CONDICIONES[paso.campo].titulo }}</legend>
        <v-checkbox
          v-for="opcion in visibles"
          :key="opcion.valor"
          :label="opcion.nombre"
          :model-value="paso.valores.includes(opcion.valor)"
          density="compact"
          hide-details
          @update:model-value="marcar(opcion.valor, !!$event)"
        />
        <p v-if="!visibles.length" class="opciones__vacio">Nada coincide con la búsqueda.</p>
      </fieldset>
      <p class="contador">{{ paso.valores.length }} de {{ opciones.length }} elegidas</p>
    </template>
  </div>
</template>

<style scoped>
.opciones {
  max-height: 280px;
  margin: 0;
  padding: 2px 10px;
  overflow-y: auto;
  border: 1px solid var(--borde);
  border-radius: var(--radio);
}

.opciones__vacio {
  margin: 10px 0;
  color: var(--suave);
  font-size: 13px;
}

.contador {
  margin: 6px 0 16px;
  color: var(--tenue);
  font-size: 12px;
}
</style>
