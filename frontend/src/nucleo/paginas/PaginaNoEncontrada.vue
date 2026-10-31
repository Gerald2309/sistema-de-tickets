<script setup>
import EstadoVacio from '@/compartido/componentes/EstadoVacio.vue'
import { iconos } from '@/compartido/iconos.js'
</script>

<template>
  <div class="pagina">
    <h1 class="solo-lectores">No encontrado</h1>
    <EstadoVacio
      :icono="iconos.vacio"
      titulo="No encontramos lo que busca"
      texto="Puede que no exista o que no tenga acceso."
    >
      <v-btn variant="outlined" :to="{ name: 'inicio' }">Ir al inicio</v-btn>
    </EstadoVacio>
  </div>
</template>
