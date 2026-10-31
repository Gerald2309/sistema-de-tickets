<script setup>
import CortinaPagina from '@/compartido/componentes/CortinaPagina.vue'
</script>

<template>
  <v-app>
    <RouterView />
    <!-- Fuera de las pantallas: cubre también el paso del inicio de sesión a la aplicación. -->
    <CortinaPagina />
  </v-app>
</template>
