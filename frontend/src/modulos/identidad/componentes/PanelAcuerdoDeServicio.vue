<script setup>
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed, reactive, watch } from 'vue'
import BotonEnvio from '@/compartido/componentes/BotonEnvio.vue'
import InterruptorCompacto from '@/compartido/componentes/InterruptorCompacto.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import PanelLateral from '@/compartido/componentes/PanelLateral.vue'
import { useErroresDeFormulario } from '@/compartido/composables/useErroresDeFormulario.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { guardarAcuerdoDeServicio, obtenerAcuerdoDeServicio } from '../api/acuerdos.js'
import { clavesDeIdentidad } from '../api/claves.js'

/**
 * Plazos de servicio de una empresa: primera respuesta y resolución por prioridad, y si corren en horario
 * laboral. Rigen para los incidentes que se creen desde ahora. La respuesta se escribe en minutos y la
 * resolución en horas, las unidades en que se suelen acordar; la API guarda todo en minutos.
 */
const props = defineProps({
  empresa: {
    type: /** @type {import('vue').PropType<import('../tipos.js').Cliente | null>} */ (Object),
    default: null,
  },
})
const abierto = defineModel({ type: Boolean, default: false })

const queryClient = useQueryClient()
const notificaciones = useNotificaciones()

// Identidad no depende de incidentes: aquí solo hacen falta las claves que la API espera y su nombre.
const PRIORIDADES = Object.freeze([
  { clave: 'critica', nombre: 'Crítica' },
  { clave: 'alta', nombre: 'Alta' },
  { clave: 'media', nombre: 'Media' },
  { clave: 'baja', nombre: 'Baja' },
])
const MINUTOS_POR_HORA = 60

const servidor = useErroresDeFormulario(
  Object.fromEntries(
    PRIORIDADES.flatMap(({ clave }) => [
      [`plazos.${clave}.respuesta_minutos`, `${clave}.respuesta`],
      [`plazos.${clave}.resolucion_minutos`, `${clave}.resolucion`],
    ]),
  ),
)

const acuerdo = useQuery({
  queryKey: computed(() => clavesDeIdentidad.acuerdoDeServicio(props.empresa?.id ?? 0)),
  queryFn: ({ signal }) => obtenerAcuerdoDeServicio(/** @type {number} */ (props.empresa?.id), signal),
  enabled: computed(() => abierto.value && props.empresa !== null),
})

const datos = reactive({
  horarioLaboral: false,
  plazos: /** @type {Record<string, { respuesta: number | string, resolucion: number | string }>} */ ({}),
})

/** @returns {void} deja el formulario con lo que está guardado */
function cargar() {
  const vigente = acuerdo.data.value
  if (!vigente) {
    return
  }
  servidor.limpiar()
  datos.horarioLaboral = vigente.horarioLaboral
  datos.plazos = Object.fromEntries(
    Object.entries(vigente.plazos).map(([prioridad, plazo]) => [
      prioridad,
      { respuesta: plazo.respuestaMinutos, resolucion: plazo.resolucionMinutos / MINUTOS_POR_HORA },
    ]),
  )
}
watch([() => acuerdo.data.value, abierto], cargar, { immediate: true })

const guardado = useMutation({
  /** @param {import('../tipos.js').AcuerdoDeServicio} cambios */
  mutationFn: (cambios) => guardarAcuerdoDeServicio(/** @type {number} */ (props.empresa?.id), cambios),
  onSuccess: async () => {
    notificaciones.exito('Plazos guardados', props.empresa?.nombre)
    await queryClient.invalidateQueries({
      queryKey: clavesDeIdentidad.acuerdoDeServicio(/** @type {number} */ (props.empresa?.id)),
    })
  },
})

async function guardar() {
  servidor.limpiar()
  try {
    await guardado.mutateAsync({
      horarioLaboral: datos.horarioLaboral,
      plazos: Object.fromEntries(
        Object.entries(datos.plazos).map(([prioridad, plazo]) => [
          prioridad,
          {
            respuestaMinutos: Math.round(Number(plazo.respuesta)),
            resolucionMinutos: Math.round(Number(plazo.resolucion) * MINUTOS_POR_HORA),
          },
        ]),
      ),
    })
    abierto.value = false
  } catch (causa) {
    servidor.aplicar(causa)
  }
}
</script>

<template>
  <PanelLateral v-model="abierto" :titulo="`Plazos de ${empresa?.nombre ?? ''}`">
    <MensajeEnPagina v-if="acuerdo.isError.value" tipo="error" titulo="No se pudieron cargar los plazos">
      {{ acuerdo.error.value?.message }}
    </MensajeEnPagina>
    <MensajeEnPagina v-if="servidor.general.value" tipo="error" titulo="No se pudo guardar" class="mb-4">
      {{ servidor.general.value }}
    </MensajeEnPagina>
    <form v-if="acuerdo.data.value" id="formulario-acuerdo" class="acuerdo" novalidate @submit.prevent="guardar">
      <p class="nota">Rigen para los incidentes que se creen desde ahora; los que ya existen conservan su plazo.</p>
      <label class="acuerdo__horario">
        <span>
          <b>Contar solo en horario laboral</b>
          <span class="nota acuerdo__ayuda">Lunes a viernes de 8:00 a 18:00. Si no, el plazo corre las 24 horas.</span>
        </span>
        <InterruptorCompacto v-model="datos.horarioLaboral" etiqueta="Contar solo en horario laboral" />
      </label>
      <fieldset v-for="prioridad in PRIORIDADES" :key="prioridad.clave" class="acuerdo__prioridad">
        <legend>{{ prioridad.nombre }}</legend>
        <v-text-field
          v-model="datos.plazos[prioridad.clave].respuesta"
          label="Primera respuesta (minutos)"
          type="number"
          min="1"
          :error-messages="servidor.errores[`${prioridad.clave}.respuesta`]"
          @update:model-value="servidor.limpiarCampo(`${prioridad.clave}.respuesta`)"
        />
        <v-text-field
          v-model="datos.plazos[prioridad.clave].resolucion"
          label="Resolución (horas)"
          type="number"
          min="0.25"
          step="0.25"
          :error-messages="servidor.errores[`${prioridad.clave}.resolucion`]"
          @update:model-value="servidor.limpiarCampo(`${prioridad.clave}.resolucion`)"
        />
      </fieldset>
    </form>
    <template #pie>
      <v-btn variant="text" @click="abierto = false">Cancelar</v-btn>
      <BotonEnvio form="formulario-acuerdo" texto="Guardar plazos" :pendiente="guardado.isPending.value" />
    </template>
  </PanelLateral>
</template>

<style scoped>
.acuerdo {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.acuerdo .nota {
  margin: 0;
}

.acuerdo__horario {
  display: flex;
  gap: 16px;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
}

.acuerdo__horario b {
  font-weight: 500;
}

.acuerdo__ayuda {
  display: block;
}

.acuerdo__prioridad {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin: 0;
  padding: 0;
  border: 0;
}

.acuerdo__prioridad legend {
  margin-bottom: 8px;
  font-weight: 600;
}
</style>
