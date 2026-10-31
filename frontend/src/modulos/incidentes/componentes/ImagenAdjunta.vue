<script setup>
import { useQuery } from '@tanstack/vue-query'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import { iconos } from '@/compartido/iconos.js'
import { obtenerAdjunto } from '../api/incidentes.js'
import { claves } from '../api/claves.js'

/**
 * Miniatura de una imagen de la conversación; al pulsarla se ve completa. La imagen se pide con el token y se
 * muestra desde una dirección local (blob:), que se libera al dejar de usarla.
 */
const props = defineProps({
  incidenteId: { type: Number, required: true },
  mensajeId: { type: Number, required: true },
  adjunto: {
    type: /** @type {import('vue').PropType<import('../tipos.js').Adjunto>} */ (Object),
    required: true,
  },
})

// Una imagen enviada no cambia: se pide una vez.
const imagen = useQuery({
  queryKey: computed(() => claves.adjunto(props.incidenteId, props.mensajeId, props.adjunto.id)),
  queryFn: ({ signal }) => obtenerAdjunto(props.incidenteId, props.mensajeId, props.adjunto.id, signal),
  staleTime: Infinity,
})

const direccion = ref('')
const ampliada = ref(false)

function liberar() {
  if (direccion.value) {
    URL.revokeObjectURL(direccion.value)
  }
}

watch(
  () => imagen.data.value,
  (contenido) => {
    liberar()
    direccion.value = contenido ? URL.createObjectURL(contenido) : ''
  },
  { immediate: true },
)
onBeforeUnmount(liberar)
</script>

<template>
  <div class="adjunto">
    <button
      v-if="direccion"
      type="button"
      class="adjunto__boton"
      :aria-label="`Ver ${adjunto.nombre} en tamaño completo`"
      @click="ampliada = true"
    >
      <img :src="direccion" :alt="adjunto.nombre" class="adjunto__miniatura" />
    </button>
    <div
      v-else-if="imagen.isError.value"
      class="adjunto__estado"
      role="img"
      :aria-label="`${adjunto.nombre} no está disponible`"
    >
      <v-icon :icon="iconos.imagenNoDisponible" size="22" />
    </div>
    <EsqueletoDeCarga v-else ancho="96px" alto="96px" />

    <v-dialog v-model="ampliada" max-width="min(1100px, 95vw)">
      <v-card class="adjunto__dialogo">
        <v-card-title class="adjunto__titulo">
          <span class="adjunto__nombre">{{ adjunto.nombre }}</span>
          <v-btn :icon="iconos.cerrar" variant="text" size="small" aria-label="Cerrar" @click="ampliada = false" />
        </v-card-title>
        <img :src="direccion" :alt="adjunto.nombre" class="adjunto__completa" />
      </v-card>
    </v-dialog>
  </div>
</template>

<style scoped>
.adjunto__boton,
.adjunto__estado {
  display: grid;
  place-items: center;
  width: 96px;
  height: 96px;
  padding: 0;
  overflow: hidden;
  color: var(--suave);
  background: var(--sutil);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
}

.adjunto__boton {
  cursor: zoom-in;
}

.adjunto__boton:focus-visible {
  outline: none;
  box-shadow: var(--anillo-foco);
}

.adjunto__miniatura {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.adjunto__dialogo {
  border: 1px solid var(--borde);
}

.adjunto__titulo {
  display: flex;
  gap: 8px;
  align-items: center;
  justify-content: space-between;
  font-size: 15px;
}

.adjunto__nombre {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.adjunto__completa {
  display: block;
  max-width: 100%;
  max-height: 80vh;
  margin: 0 auto;
  object-fit: contain;
}
</style>
