<script setup>
import { computed, ref, watch } from 'vue'

/**
 * Pasar a Resuelto exige la solución (RF-F28). Si ya hay una guardada de una resolución anterior, se muestra
 * para confirmarla o reemplazarla. La solución solo viaja en la petición que resuelve.
 */
const props = defineProps({
  solucionAnterior: { type: String, default: '' },
  pendiente: { type: Boolean, default: false },
  error: { type: String, default: '' },
  cantidad: { type: Number, default: 1 },
})
const abierto = defineModel({ type: Boolean, default: false })
const emit = defineEmits(['confirmar'])

const MAXIMO = 10_000
const solucion = ref('')
watch(abierto, (valor) => valor && (solucion.value = props.solucionAnterior))

const valida = computed(() => solucion.value.trim().length > 0)
const titulo = computed(() => (props.cantidad > 1 ? `Resolver ${props.cantidad} incidentes` : 'Resolver el incidente'))
</script>

<template>
  <v-dialog v-model="abierto" max-width="560">
    <v-card class="resolver">
      <v-card-title class="resolver__titulo">{{ titulo }}</v-card-title>
      <v-card-text>
        <p class="resolver__ayuda">
          {{
            solucionAnterior
              ? 'Hay una solución guardada: confírmela o escriba una nueva.'
              : 'Describa qué se hizo para resolverlo.'
          }}
        </p>
        <v-textarea
          v-model="solucion"
          label="Solución *"
          rows="4"
          auto-grow
          :counter="MAXIMO"
          :maxlength="MAXIMO"
          :error-messages="error ? [error] : []"
          autofocus
        />
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="abierto = false">Cancelar</v-btn>
        <v-btn
          color="primary"
          variant="flat"
          :disabled="!valida"
          :loading="pendiente"
          @click="emit('confirmar', solucion.trim())"
        >
          Resolver
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.resolver {
  padding: 8px;
  border: 1px solid var(--borde);
}

.resolver__titulo {
  font-size: 17px;
  font-weight: 700;
}

.resolver__ayuda {
  margin: 0 0 14px;
  color: var(--suave);
}
</style>
