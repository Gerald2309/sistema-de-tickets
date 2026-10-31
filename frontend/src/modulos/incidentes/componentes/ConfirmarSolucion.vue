<script setup>
import { computed, ref } from 'vue'
import { iconos } from '@/compartido/iconos.js'

/**
 * Responder si la solución sirvió, en un paso: tocar una estrella cierra el incidente con esa calificación y
 * «Sí, quedó» lo cierra sin calificar. «No, sigue pasando» abre aquí mismo el motivo, que es obligatorio.
 * Lo usan el detalle y las tarjetas del inicio.
 */
defineProps({ pendiente: { type: Boolean, default: false } })
const emit = defineEmits(['confirmar'])

const MAXIMO_MOTIVO = 10_000
const ESTRELLAS = [1, 2, 3, 4, 5]
const explicando = ref(false)
// Al pasar el puntero o el foco, se iluminan las estrellas hasta esa: se ve qué calificación se enviará.
const resaltada = ref(0)
const motivo = ref('')
const motivoTocado = ref(false)

const errorDelMotivo = computed(() =>
  motivoTocado.value && !motivo.value.trim() ? ['Cuéntenos qué sigue pasando, para que soporte pueda retomarlo.'] : [],
)

/** @param {number | null} calificacion */
const cerrar = (calificacion) => emit('confirmar', { solucionado: true, calificacion })

function devolver() {
  motivoTocado.value = true
  if (motivo.value.trim()) {
    emit('confirmar', { solucionado: false, motivo: motivo.value.trim() })
  }
}

function cancelar() {
  explicando.value = false
  motivoTocado.value = false
}
</script>

<template>
  <div v-if="!explicando" class="respuesta">
    <div class="respuesta__calificar">
      <!-- Cada estrella es un botón: con teclado se recorren con Tab y nada se envía hasta pulsar una. -->
      <div
        class="respuesta__estrellas"
        role="group"
        aria-label="Quedó solucionado: califique la atención para cerrarlo"
        @mouseleave="resaltada = 0"
      >
        <v-btn
          v-for="estrella in ESTRELLAS"
          :key="estrella"
          :icon="estrella <= resaltada ? iconos.estrella : iconos.estrellaVacia"
          variant="text"
          density="compact"
          size="small"
          class="respuesta__estrella"
          :aria-label="`Cerrar y calificar ${estrella} de 5`"
          :disabled="pendiente"
          @mouseenter="resaltada = estrella"
          @focus="resaltada = estrella"
          @blur="resaltada = 0"
          @click="cerrar(estrella)"
        />
      </div>
      <span class="nota">Califique para cerrarlo</span>
    </div>
    <div class="respuesta__botones">
      <v-btn
        color="primary"
        variant="flat"
        size="small"
        :prepend-icon="iconos.exito"
        :loading="pendiente"
        @click="cerrar(null)"
        >Sí, quedó</v-btn
      >
      <v-btn variant="text" size="small" :disabled="pendiente" @click="explicando = true">No, sigue pasando</v-btn>
    </div>
  </div>

  <form v-else class="respuesta__motivo" novalidate @submit.prevent="devolver">
    <v-textarea
      v-model="motivo"
      label="¿Qué sigue pasando? *"
      rows="2"
      auto-grow
      autofocus
      :counter="MAXIMO_MOTIVO"
      :maxlength="MAXIMO_MOTIVO"
      :error-messages="errorDelMotivo"
      @blur="motivoTocado = true"
    />
    <div class="respuesta__botones respuesta__botones--fin">
      <v-btn variant="text" size="small" @click="cancelar">Cancelar</v-btn>
      <v-btn type="submit" color="primary" variant="flat" size="small" :loading="pendiente">Devolver a soporte</v-btn>
    </div>
  </form>
</template>

<style scoped>
.respuesta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 16px;
  align-items: center;
  justify-content: space-between;
}

.respuesta__calificar {
  display: flex;
  gap: 6px;
  align-items: center;
}

.respuesta__estrellas {
  display: flex;
}

.respuesta__estrella {
  color: var(--marca-naranja);
}

.respuesta__botones {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.respuesta__botones--fin {
  justify-content: flex-end;
}
</style>
