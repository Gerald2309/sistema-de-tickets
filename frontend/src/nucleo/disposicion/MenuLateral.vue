<script setup>
import { useNavegacion } from './useNavegacion.js'

/** Menú lateral con solo las secciones que el rol permite (RF-F06, RF-F09). */
defineProps({ permanente: { type: Boolean, default: false } })
const abierto = defineModel({ type: Boolean, default: false })
const { deTrabajo, deAdministracion } = useNavegacion()
</script>

<template>
  <v-navigation-drawer v-model="abierto" :permanent="permanente" width="232" class="menu" aria-label="Secciones">
    <v-list nav class="pa-3">
      <v-list-item
        v-for="entrada in deTrabajo"
        :key="entrada.titulo"
        :prepend-icon="entrada.icono"
        :title="entrada.titulo"
        :to="entrada.destino"
      />
      <template v-if="deAdministracion.length">
        <div class="menu__grupo">Administración</div>
        <v-list-item
          v-for="entrada in deAdministracion"
          :key="entrada.titulo"
          :prepend-icon="entrada.icono"
          :title="entrada.titulo"
          :to="entrada.destino"
        />
      </template>
    </v-list>
  </v-navigation-drawer>
</template>

<style scoped>
.menu {
  background: var(--sutil) !important;
  border-right: 1px solid var(--borde) !important;
}

.menu__grupo {
  margin: 18px 12px 6px;
  color: var(--suave);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.menu :deep(.v-list-item) {
  position: relative;
  min-height: 36px !important;
  margin-bottom: 1px;
  color: var(--texto);
  font-weight: 500;
  border-radius: var(--radio) !important;
}

.menu :deep(.v-list-item:hover) {
  background: var(--resalte);
}

.menu :deep(.v-list-item--active) {
  color: var(--acento-texto) !important;
  font-weight: 600;
  background: var(--acento-fondo) !important;
}

.menu :deep(.v-list-item--active .v-list-item__overlay) {
  display: none;
}

/* Barra cian a la izquierda del ítem activo. */
.menu :deep(.v-list-item--active)::before {
  position: absolute;
  top: 6px;
  bottom: 6px;
  left: 0;
  width: 3px;
  background: var(--marca-cian);
  border-radius: 0 2px 2px 0;
  content: '';
}
</style>
