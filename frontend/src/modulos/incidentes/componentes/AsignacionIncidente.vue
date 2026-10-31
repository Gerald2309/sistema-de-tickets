<script setup>
import { ref } from 'vue'
import AvatarPersona from '@/compartido/componentes/AvatarPersona.vue'
import { iconos } from '@/compartido/iconos.js'
import MenuReasignar from './MenuReasignar.vue'

/**
 * Quién atiende el incidente (RF-09). Quien puede asignarlo elige aquí a la persona; `quitable` permite además
 * devolverlo a la cola. Cada cambio se envía solo, con `asignar`.
 */
const props = defineProps({
  asignado: {
    type: /** @type {import('vue').PropType<import('../tipos.js').Incidente['asignado']>} */ (Object),
    default: null,
  },
  editable: { type: Boolean, default: false },
  quitable: { type: Boolean, default: false },
  pendiente: { type: Boolean, default: false },
})
const emit = defineEmits(['asignar'])

const eligiendo = ref(false)

/** @param {number | null} asignadoId */
function asignar(asignadoId) {
  eligiendo.value = false
  if (asignadoId !== (props.asignado?.id ?? null)) {
    emit('asignar', asignadoId)
  }
}
</script>

<template>
  <section class="tarjeta" aria-labelledby="titulo-asignacion">
    <header class="tarjeta__cabecera">
      <h2 id="titulo-asignacion" class="tarjeta__titulo">Asignación</h2>
    </header>
    <div class="tarjeta__cuerpo asignacion">
      <AvatarPersona v-if="asignado" :nombre="asignado.nombre" />
      <span v-else class="nota">Sin asignar</span>
      <v-menu v-if="editable" v-model="eligiendo" :close-on-content-click="false" location="bottom end">
        <template #activator="{ props: activador }">
          <v-btn
            v-bind="activador"
            variant="outlined"
            size="small"
            :prepend-icon="iconos.reasignar"
            :loading="pendiente"
            >{{ asignado ? 'Reasignar' : 'Asignar' }}</v-btn
          >
        </template>
        <MenuReasignar :quitable="quitable && asignado !== null" @elegir="asignar" @quitar="asignar(null)" />
      </v-menu>
    </div>
  </section>
</template>

<style scoped>
.asignacion {
  display: flex;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
}
</style>
