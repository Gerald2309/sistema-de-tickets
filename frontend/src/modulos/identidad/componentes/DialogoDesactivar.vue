<script setup>
import { computed, ref, watch } from 'vue'
import { agruparPorRol, useBuscadorDePersonas } from '../composables/usePersonas.js'
import { useIncidentesAbiertos } from '../composables/useUsuarios.js'

/**
 * Desactivar a alguien: no podrá entrar y se cierran sus sesiones. Si tiene incidentes sin resolver asignados,
 * se elige qué pasa con ellos: vuelven a la cola sin asignar (lo seguro, por defecto) o pasan a otra persona.
 * Lo resuelto y lo cerrado conserva a su persona.
 */
const props = defineProps({
  usuario: {
    type: /** @type {import('vue').PropType<import('../tipos.js').Usuario | null>} */ (Object),
    default: null,
  },
  pendiente: { type: Boolean, default: false },
})
const abierto = defineModel({ type: Boolean, default: false })
const emit = defineEmits(['confirmar'])

const abiertos = useIncidentesAbiertos(computed(() => (abierto.value ? props.usuario : null)))
const total = computed(() => abiertos.data.value ?? 0)

const destino = ref(/** @type {'cola' | 'persona'} */ ('cola'))
const personaId = ref(/** @type {number | null} */ (null))
watch(abierto, (valor) => {
  if (valor) {
    destino.value = 'cola'
    personaId.value = null
  }
})

const buscador = useBuscadorDePersonas({ paraAsignar: true })
// Quien se desactiva no puede recibir sus propios incidentes.
const opciones = computed(() =>
  agruparPorRol(buscador.personas.value.filter((persona) => persona.id !== props.usuario?.id)),
)
/** @param {string | null} texto */
const buscar = (texto) => (buscador.texto.value = texto ?? '')

const listo = computed(() => !abiertos.isPending.value && (destino.value === 'cola' || personaId.value !== null))

function confirmar() {
  emit('confirmar', total.value > 0 && destino.value === 'persona' ? personaId.value : null)
}
</script>

<template>
  <v-dialog v-model="abierto" max-width="520">
    <v-card class="dialogo-desactivar">
      <form novalidate @submit.prevent="listo && confirmar()">
        <v-card-title class="dialogo-desactivar__titulo">¿Desactivar a {{ usuario?.nombre }}?</v-card-title>
        <v-card-text>
          <p>No podrá iniciar sesión y se cerrarán todas sus sesiones. Puede reactivarla después.</p>
          <p v-if="abiertos.isPending.value" class="nota" aria-busy="true">Revisando sus incidentes…</p>
          <template v-else-if="total > 0">
            <p class="dialogo-desactivar__pregunta">
              Tiene <b class="num">{{ total }}</b> incidente{{ total > 1 ? 's' : '' }} sin resolver asignado{{
                total > 1 ? 's' : ''
              }}. ¿Qué pasa con {{ total > 1 ? 'ellos' : 'él' }}?
            </p>
            <v-radio-group v-model="destino" hide-details density="compact">
              <v-radio value="cola" label="Devolverlos a la cola sin asignar, para que soporte los tome" />
              <v-radio value="persona" label="Pasarlos a otra persona" />
            </v-radio-group>
            <v-autocomplete
              v-if="destino === 'persona'"
              v-model="personaId"
              class="dialogo-desactivar__persona"
              label="Pasarlos a"
              :items="opciones"
              item-title="nombre"
              item-value="id"
              no-filter
              autofocus
              :loading="buscador.cargando.value"
              no-data-text="Escriba un nombre"
              @update:search="buscar"
            />
            <p class="nota">Lo resuelto y lo cerrado conserva a su persona: es su historia.</p>
          </template>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="abierto = false">Cancelar</v-btn>
          <v-btn type="submit" color="error" variant="flat" :disabled="!listo" :loading="pendiente">Desactivar</v-btn>
        </v-card-actions>
      </form>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.dialogo-desactivar__titulo {
  padding-top: 20px;
  font-weight: 700;
}

.dialogo-desactivar__pregunta {
  margin-top: 12px;
}

.dialogo-desactivar__persona {
  margin-top: 8px;
}
</style>
