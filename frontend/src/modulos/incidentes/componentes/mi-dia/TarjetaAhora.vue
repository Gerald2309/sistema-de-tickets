<script setup>
import { computed } from 'vue'
import { iconos } from '@/compartido/iconos.js'
import { haceCuanto } from '@/compartido/presentacion/fechas.js'
import { hora } from '../../mi-dia.js'
import CodigoTicket from '../CodigoTicket.vue'
import InsigniaEstado from '../InsigniaEstado.vue'

/**
 * Ahora y siguiente: lo que toca según la agenda y lo que viene. Sin plan para esta hora, «continuar donde
 * quedó»: el último incidente que la persona tocó.
 */
const props = defineProps({
  ahora: {
    type: /** @type {import('vue').PropType<import('../../api/mi-dia.js').EntradaDelPlan | null>} */ (Object),
    default: null,
  },
  siguiente: {
    type: /** @type {import('vue').PropType<import('../../api/mi-dia.js').EntradaDelPlan | null>} */ (Object),
    default: null,
  },
  ultimo: {
    type: /** @type {import('vue').PropType<import('../../api/mi-dia.js').UltimoTrabajado | null>} */ (Object),
    default: null,
  },
})

const incidente = computed(() => (props.ahora ? props.ahora.incidente : props.ultimo?.incidente) ?? null)
const contexto = computed(() => {
  if (props.ahora) {
    return `Ahora · ${hora(props.ahora.inicio)}–${hora(props.ahora.inicio + props.ahora.duracion)}`
  }
  if (!props.ultimo) {
    return ''
  }
  const cuando = haceCuanto(new Date(props.ultimo.momento).getTime())
  return `Último en el que trabajó · ${props.ultimo.accion === 'mensaje' ? 'le escribió' : 'lo actualizó'} ${cuando}`
})
</script>

<template>
  <section v-if="ahora || ultimo" class="tarjeta ahora" aria-label="Ahora">
    <span class="ahora__icono" aria-hidden="true"><v-icon :icon="iconos.continuar" size="18" /></span>
    <div class="ahora__texto">
      <span class="nota">{{ contexto }}</span>
      <span class="ahora__titulo">
        <template v-if="incidente"><CodigoTicket :codigo="incidente.codigo" /> {{ incidente.titulo }}</template>
        <template v-else>{{ ahora?.nota?.texto }}</template>
      </span>
      <span v-if="siguiente" class="nota ahora__siguiente">
        Siguiente · {{ hora(siguiente.inicio) }}:
        <template v-if="siguiente.incidente"
          ><CodigoTicket :codigo="siguiente.incidente.codigo" /> {{ siguiente.incidente.titulo }}</template
        >
        <template v-else>{{ siguiente.nota?.texto }}</template>
      </span>
    </div>
    <InsigniaEstado v-if="incidente" :estado="incidente.estado" />
    <v-btn
      v-if="incidente"
      variant="outlined"
      :to="{ name: 'incidente', params: { id: incidente.id } }"
      :aria-label="`Continuar con ${incidente.codigo}`"
      >Continuar</v-btn
    >
  </section>
</template>

<style scoped>
.ahora {
  display: flex;
  gap: 14px;
  align-items: center;
  margin-bottom: 16px;
  padding: 12px 16px;
}

.ahora__icono {
  display: grid;
  flex: none;
  place-items: center;
  width: 32px;
  height: 32px;
  color: var(--acento);
  background: var(--acento-fondo);
  border-radius: var(--radio);
}

.ahora__texto {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
}

.ahora__titulo,
.ahora__siguiente {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.ahora__titulo {
  font-weight: 600;
}

@media (width <= 700px) {
  .ahora {
    flex-wrap: wrap;
  }
}
</style>
