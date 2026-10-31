<script setup>
/** Estado vacío o de error de página: qué pasó y qué se puede hacer (sistema de diseño, Avisos). */
defineProps({
  icono: { type: String, required: true },
  titulo: { type: String, required: true },
  texto: { type: String, default: '' },
})
</script>

<template>
  <div class="vacio">
    <v-icon :icon="icono" size="36" class="vacio__icono" />
    <b class="vacio__titulo">{{ titulo }}</b>
    <p v-if="texto" class="vacio__texto">{{ texto }}</p>
    <slot />
  </div>
</template>

<style scoped>
.vacio {
  padding: 36px 20px;
  text-align: center;
  background: var(--superficie);
  border: 1px dashed var(--borde-fuerte);
  border-radius: var(--radio);
}

.vacio__icono {
  color: var(--tenue);
}

.vacio__titulo {
  display: block;
  margin: 10px 0 4px;
  font-size: 15px;
}

.vacio__texto {
  margin: 0 0 14px;
  color: var(--suave);
  font-size: 13.5px;
}
</style>
