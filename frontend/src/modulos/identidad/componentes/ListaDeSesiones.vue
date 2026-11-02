<script setup>
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import { iconos } from '@/compartido/iconos.js'
import { fechaCercana } from '@/compartido/presentacion/fechas.js'
import { useSesionesAbiertas } from '../composables/useSesionesAbiertas.js'
import { describirDispositivo } from '../presentacion.js'

/** Dónde tiene la persona la sesión abierta, para cerrar la que no reconozca. */
const { lista, hayOtras, cerrarUna, cerrarLasDemas } = useSesionesAbiertas()
</script>

<template>
  <div>
    <MensajeEnPagina
      v-if="lista.isError.value"
      tipo="error"
      titulo="No se pudieron cargar las sesiones"
      reintentable
      @reintentar="lista.refetch()"
    />
    <ul v-else class="sesiones" aria-label="Sesiones abiertas">
      <template v-if="lista.data.value">
        <li v-for="sesion in lista.data.value" :key="sesion.id" class="sesiones__fila">
          <v-icon :icon="iconos.dispositivo" size="20" class="sesiones__icono" />
          <div class="sesiones__datos">
            <b>{{ describirDispositivo(sesion.agente) }}</b>
            <v-chip v-if="sesion.actual" size="x-small" color="primary" variant="tonal" class="ml-2"
              >Esta sesión</v-chip
            >
            <div class="nota">
              IP <span class="num">{{ sesion.ip ?? 'desconocida' }}</span> · inició
              {{ fechaCercana(sesion.iniciadaEn) }} · último uso
              {{ fechaCercana(sesion.ultimoUsoEn ?? sesion.iniciadaEn) }}
            </div>
          </div>
          <v-btn
            v-if="!sesion.actual"
            variant="text"
            size="small"
            color="error"
            :loading="cerrarUna.isPending.value && cerrarUna.variables.value === sesion.id"
            :aria-label="`Cerrar la sesión de ${describirDispositivo(sesion.agente)}`"
            @click="cerrarUna.mutate(sesion.id)"
          >
            Cerrar
          </v-btn>
        </li>
      </template>
      <template v-else>
        <li v-for="n in 2" :key="n" class="sesiones__fila"><EsqueletoDeCarga ancho="220px" /></li>
      </template>
    </ul>
    <div v-if="hayOtras" class="formulario-por-secciones__pie">
      <v-btn
        variant="outlined"
        color="error"
        :loading="cerrarLasDemas.isPending.value"
        @click="cerrarLasDemas.mutate()"
      >
        Cerrar todas las demás
      </v-btn>
    </div>
  </div>
</template>

<style scoped>
.sesiones {
  padding: 0;
  margin: 0 0 8px;
  list-style: none;
}

.sesiones__fila {
  display: flex;
  gap: 12px;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid var(--borde);
}

.sesiones__fila:last-child {
  border-bottom: 0;
}

.sesiones__icono {
  flex: none;
  color: var(--tenue);
}

.sesiones__datos {
  flex: 1;
  min-width: 0;
}
</style>
