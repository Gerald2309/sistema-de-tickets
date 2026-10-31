<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNavegacion } from '@/nucleo/disposicion/useNavegacion.js'

// «/» lleva a la primera pantalla que la persona puede usar; sin ninguna, a Mi cuenta (RF-F06).
const enrutador = useRouter()
const { permitidas } = useNavegacion()

onMounted(() => enrutador.replace(permitidas.value[0]?.destino ?? { name: 'mi-cuenta' }))
</script>

<template>
  <div class="pagina" aria-busy="true" />
</template>
