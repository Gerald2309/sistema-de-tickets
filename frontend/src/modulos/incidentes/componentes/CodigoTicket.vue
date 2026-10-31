<script setup>
/** Código de ticket siempre completo (INC-000605), como las claves de Jira. */
defineProps({ codigo: { type: String, required: true } })
</script>

<template>
  <span class="codigo">{{ codigo }}</span>
</template>

<style scoped>
.codigo {
  color: var(--acento-texto);
  font-size: 12.5px;
  font-weight: 600;
  white-space: nowrap;
}
</style>
