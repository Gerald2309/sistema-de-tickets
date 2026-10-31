<script setup>
import EstadoVacio from '@/compartido/componentes/EstadoVacio.vue'
import { iconos } from '@/compartido/iconos.js'
</script>

<template>
  <div class="pagina">
    <h1 class="solo-lectores">Sin permiso</h1>
    <EstadoVacio
      :icono="iconos.roles"
      titulo="No tiene permiso para ver esta página"
      texto="Si cree que debería tenerlo, escriba a su administrador."
    >
      <v-btn variant="outlined" :to="{ name: 'inicio' }">Ir al inicio</v-btn>
    </EstadoVacio>
  </div>
</template>
