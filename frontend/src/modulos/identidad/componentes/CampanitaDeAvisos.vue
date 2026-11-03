<script setup>
import { useRouter } from 'vue-router'
import { iconos } from '@/compartido/iconos.js'
import { tiempoRelativo } from '@/compartido/presentacion/fechas.js'
import { useAvisosPersonales } from '../composables/useAvisosPersonales.js'

/** Avisos de la persona (por ejemplo, los de una automatización). Abrir uno lo marca como leído. */
const { avisos, sinLeer, marcarLeido, marcarTodos } = useAvisosPersonales()
const enrutador = useRouter()

/** @param {import('../api/avisos.js').AvisoPersonal} aviso */
async function abrir(aviso) {
  if (!aviso.leido) {
    await marcarLeido(aviso.id)
  }
  if (aviso.incidenteId) {
    await enrutador.push({ name: 'incidente', params: { id: aviso.incidenteId } })
  }
}
</script>

<template>
  <v-menu location="bottom end" :close-on-content-click="false">
    <template #activator="{ props }">
      <v-btn
        v-bind="props"
        icon
        variant="text"
        :aria-label="sinLeer ? `Avisos: ${sinLeer} sin leer` : 'Avisos'"
        class="campanita"
      >
        <v-badge v-if="sinLeer" :content="sinLeer > 9 ? '9+' : sinLeer" color="error" floating>
          <v-icon :icon="iconos.avisos" />
        </v-badge>
        <v-icon v-else :icon="iconos.avisos" />
      </v-btn>
    </template>
    <v-card class="campanita__panel" min-width="340" max-width="380">
      <header class="campanita__cabeza">
        <b>Avisos</b>
        <v-btn v-if="sinLeer" variant="text" size="small" @click="marcarTodos()">Marcar todos como leídos</v-btn>
      </header>
      <p v-if="!avisos.length" class="campanita__vacio">No tiene avisos.</p>
      <v-list v-else density="compact" class="campanita__lista">
        <v-list-item
          v-for="aviso in avisos"
          :key="aviso.id"
          :class="{ 'campanita__aviso--nuevo': !aviso.leido }"
          @click="abrir(aviso)"
        >
          <v-list-item-title class="campanita__texto">{{ aviso.texto }}</v-list-item-title>
          <v-list-item-subtitle>{{ tiempoRelativo(aviso.creadoEn).texto }}</v-list-item-subtitle>
          <template v-if="!aviso.leido" #append>
            <span class="campanita__punto" aria-label="Sin leer" />
          </template>
        </v-list-item>
      </v-list>
    </v-card>
  </v-menu>
</template>

<style scoped>
.campanita {
  margin-right: 4px;
}

.campanita__cabeza {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px 6px 16px;
  border-bottom: 1px solid var(--borde);
}

.campanita__vacio {
  margin: 0;
  padding: 20px 16px;
  color: var(--suave);
  font-size: 13.5px;
}

.campanita__lista {
  max-height: 360px;
  overflow-y: auto;
}

.campanita__texto {
  font-size: 13.5px;
  white-space: normal;
}

.campanita__aviso--nuevo {
  background: var(--acento-fondo);
}

.campanita__punto {
  width: 8px;
  height: 8px;
  background: var(--acento);
  border-radius: 50%;
}
</style>
