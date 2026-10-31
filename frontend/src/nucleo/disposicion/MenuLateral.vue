<script setup>
import { useNavegacion } from './useNavegacion.js'

/**
 * Menú lateral con solo las secciones que el rol permite (RF-F06, RF-F09). Listas y enlaces nativos: es un
 * grupo de enlaces de navegación, no un widget de lista con su propio teclado.
 */
defineProps({ permanente: { type: Boolean, default: false } })
const abierto = defineModel({ type: Boolean, default: false })
const { deTrabajo, deAdministracion } = useNavegacion()
</script>

<template>
  <!-- Fijo, se muestra siempre; desplegable, solo cuando se abre (Vuetify oculta incluso el fijo si el modelo es falso). -->
  <v-navigation-drawer
    :model-value="permanente || abierto"
    :permanent="permanente"
    width="232"
    class="menu"
    tag="nav"
    aria-label="Secciones"
    @update:model-value="abierto = $event"
  >
    <ul class="menu__lista">
      <li v-for="entrada in deTrabajo" :key="entrada.titulo">
        <RouterLink :to="entrada.destino" class="menu__enlace">
          <v-icon :icon="entrada.icono" size="20" class="menu__icono" />{{ entrada.titulo }}
        </RouterLink>
      </li>
    </ul>
    <template v-if="deAdministracion.length">
      <h2 id="menu-administracion" class="menu__grupo">Administración</h2>
      <ul class="menu__lista" aria-labelledby="menu-administracion">
        <li v-for="entrada in deAdministracion" :key="entrada.titulo">
          <RouterLink :to="entrada.destino" class="menu__enlace">
            <v-icon :icon="entrada.icono" size="20" class="menu__icono" />{{ entrada.titulo }}
          </RouterLink>
        </li>
      </ul>
    </template>
  </v-navigation-drawer>
</template>

<style scoped>
.menu {
  background: var(--sutil) !important;
  border-right: 1px solid var(--borde) !important;
}

.menu__lista {
  margin: 0;
  padding: 12px;
  list-style: none;
}

.menu__lista + .menu__grupo {
  margin-top: 6px;
}

.menu__grupo {
  margin: 6px 24px 0;
  color: var(--suave);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.menu__grupo + .menu__lista {
  padding-top: 6px;
}

.menu__enlace {
  position: relative;
  display: flex;
  gap: 32px;
  align-items: center;
  min-height: 36px;
  margin-bottom: 1px;
  padding: 0 8px;
  color: var(--texto);
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  border-radius: var(--radio);
  transition: background-color var(--rapida);
}

.menu__enlace:hover {
  background: var(--resalte);
}

.menu__icono {
  color: var(--suave);
}

/* Sección actual: fondo de acento y barra cian a la izquierda (sistema de diseño, Navegación). */
.menu__enlace.router-link-active {
  color: var(--acento-texto);
  font-weight: 600;
  background: var(--acento-fondo);
}

.menu__enlace.router-link-active .menu__icono {
  color: var(--acento-texto);
}

.menu__enlace.router-link-active::before {
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
