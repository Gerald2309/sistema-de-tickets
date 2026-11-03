<script setup>
import { computed } from 'vue'
import { useRoles } from '@/modulos/identidad/index.js'

/** Elegir un rol de la lista. */
defineProps({ etiqueta: { type: String, required: true } })
const modelo = defineModel({ type: /** @type {import('vue').PropType<number | null>} */ (Number), default: null })

const consulta = useRoles()
const roles = computed(() => consulta.data.value ?? [])
</script>

<template>
  <v-select v-model="modelo" :label="etiqueta" :items="roles" item-title="nombre" item-value="id" />
</template>
