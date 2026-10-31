<script setup>
import { storeToRefs } from 'pinia'
import { computed } from 'vue'
import { iconos } from '@/compartido/iconos.js'
import logoPsicol from '@/compartido/recursos/psicol-logo.png'
import { useSesion } from '@/modulos/identidad/index.js'

defineProps({ menuPermanente: { type: Boolean, default: false } })
defineEmits(['alternar-menu', 'abrir-paleta'])

const sesion = useSesion()
const { perfil } = storeToRefs(sesion)
const iniciales = computed(() =>
  (perfil.value?.nombre ?? '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((parte) => parte[0]?.toUpperCase())
    .join(''),
)
const atajo = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform) ? '⌘ K' : 'Ctrl K'
</script>

<template>
  <v-app-bar flat height="56" class="barra">
    <v-app-bar-nav-icon
      v-if="!menuPermanente"
      :icon="iconos.menu"
      aria-label="Abrir el menú"
      @click="$emit('alternar-menu')"
    />
    <RouterLink :to="{ name: 'inicio' }" class="barra__marca" aria-label="Psicol – Mesa de ayuda, inicio">
      <img class="barra__logo" :src="logoPsicol" alt="Psicol" />
      <span class="barra__producto">Mesa de ayuda</span>
    </RouterLink>
    <v-spacer />
    <button type="button" class="barra__busqueda" @click="$emit('abrir-paleta')">
      <v-icon :icon="iconos.buscar" size="18" />
      <span>Buscar o ir a…</span>
      <kbd>{{ atajo }}</kbd>
    </button>
    <v-spacer />
    <v-menu location="bottom end">
      <template #activator="{ props }">
        <!-- El nombre accesible contiene el texto visible (WCAG 2.5.3); las iniciales son decorativas. -->
        <v-btn v-bind="props" variant="text" class="barra__persona">
          <span class="solo-lectores">Menú de su cuenta:</span>
          <v-avatar size="28" class="barra__avatar" aria-hidden="true">{{ iniciales }}</v-avatar>
          <span class="barra__nombre">
            <b>{{ perfil?.nombre }}</b>
            <small>{{ perfil?.rol.nombre }}</small>
          </span>
          <v-icon :icon="iconos.desplegar" size="18" end />
        </v-btn>
      </template>
      <v-list min-width="200">
        <v-list-item :prepend-icon="iconos.cuenta" title="Mi cuenta" :to="{ name: 'mi-cuenta' }" />
        <v-list-item :prepend-icon="iconos.salir" title="Cerrar sesión" @click="sesion.cerrar()" />
      </v-list>
    </v-menu>
  </v-app-bar>
</template>

<style scoped>
.barra {
  position: relative;
  background: var(--superficie) !important;
  border-bottom: 1px solid var(--borde);
}

/* Franja de la marca bajo la barra: uno de los tres lugares donde se usa el degradado. */
.barra::after {
  position: absolute;
  right: 0;
  bottom: -1px;
  left: 0;
  height: 3px;
  background: var(--degradado);
  content: '';
}

.barra__marca {
  display: flex;
  align-items: center;
  margin-left: 16px;
  color: inherit;
  text-decoration: none;
}

.barra__logo {
  height: 24px;
}

.barra__producto {
  margin-left: 14px;
  padding-left: 14px;
  color: var(--suave);
  font-size: 13px;
  border-left: 1px solid var(--borde);
}

.barra__busqueda {
  display: flex;
  gap: 8px;
  align-items: center;
  width: min(420px, 40vw);
  height: 36px;
  padding: 0 10px;
  color: var(--suave);
  font: inherit;
  font-size: 13.5px;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
  cursor: pointer;
  transition: border-color var(--rapida);
}

.barra__busqueda:hover {
  border-color: var(--borde-fuerte);
}

.barra__busqueda kbd {
  margin-left: auto;
  padding: 2px 6px;
  font: 500 11px var(--mono);
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
}

.barra__persona {
  height: 40px !important;
  margin-right: 8px;
  padding: 0 8px !important;
}

.barra__avatar {
  margin-right: 8px;
  color: var(--acento-texto);
  font-size: 12px;
  font-weight: 700;
  background: var(--acento-fondo);
}

.barra__nombre {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
  text-align: left;
}

.barra__nombre b {
  font-size: 13.5px;
  font-weight: 600;
}

.barra__nombre small {
  color: var(--suave);
  font-size: 12px;
  font-weight: 400;
}

@media (width <= 760px) {
  .barra__busqueda,
  .barra__producto,
  .barra__nombre {
    display: none;
  }
}
</style>
