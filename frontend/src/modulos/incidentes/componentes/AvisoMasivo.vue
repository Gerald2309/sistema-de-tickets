<script setup>
import { computed } from 'vue'
import { iconos } from '@/compartido/iconos.js'
import { rutaDelListado } from '../filtros.js'
import { nombreDeCategoria } from '../presentacion.js'

/**
 * Aviso de posible incidente masivo (RF-F18): superficie blanca y texto oscuro; el naranja solo en la franja y
 * el ícono. Llama la atención sin gritar (sistema de diseño).
 */
const props = defineProps({
  aviso: { type: /** @type {import('vue').PropType<import('../tipos.js').AvisoMasivo>} */ (Object), required: true },
})

const horas = computed(() => Math.max(1, Math.round((Date.now() - new Date(props.aviso.desde).getTime()) / 3_600_000)))
const hoy = new Date().toLocaleDateString('sv-SE')
</script>

<template>
  <section class="masivo" role="status">
    <span class="masivo__icono" aria-hidden="true"><v-icon :icon="iconos.masivo" size="18" /></span>
    <div class="masivo__texto">
      <b>Posible incidente masivo en {{ nombreDeCategoria(aviso.categoria) }}</b>
      <div class="masivo__detalle">
        {{ aviso.incidentes }} reportes de {{ aviso.personas }} personas en las últimas {{ horas }}
        {{ horas === 1 ? 'hora' : 'horas' }}.
        <template v-if="aviso.palabras.length">
          Palabras repetidas:
          <span v-for="palabra in aviso.palabras" :key="palabra" class="masivo__palabra">{{ palabra }}</span>
        </template>
      </div>
    </div>
    <v-btn
      size="small"
      variant="outlined"
      :append-icon="iconos.flecha"
      :to="rutaDelListado({ categoria: aviso.categoria, sobre: 'creacion', desde: hoy })"
    >
      Ver los {{ aviso.incidentes }} reportes
    </v-btn>
  </section>
</template>

<style scoped>
.masivo {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  align-items: center;
  padding: 12px 16px;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-left: 3px solid var(--marca-naranja);
  border-radius: var(--radio);
}

.masivo__icono {
  display: grid;
  flex: none;
  place-items: center;
  width: 34px;
  height: 34px;
  color: var(--advertencia-icono);
  background: var(--advertencia-fondo);
  border-radius: 50%;
}

.masivo__texto {
  flex: 1;
  min-width: 220px;
}

.masivo__texto b {
  font-size: 14px;
  font-weight: 700;
}

.masivo__detalle {
  margin-top: 2px;
  color: var(--suave);
  font-size: 13px;
}

.masivo__palabra {
  display: inline-block;
  margin-left: 6px;
  padding: 0 6px;
  color: var(--texto);
  font-size: 12px;
  font-weight: 500;
  background: var(--sutil);
  border: 1px solid var(--borde);
  border-radius: var(--radio-chip);
}
</style>
