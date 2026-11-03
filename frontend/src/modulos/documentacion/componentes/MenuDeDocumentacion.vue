<script setup>
import { computed } from 'vue'
import { GRUPOS, PAGINAS } from '../contenido/index.js'

/** Menú de la documentación, con la misma forma que el de la aplicación: grupos y sección actual resaltada. */
const props = defineProps({ actual: { type: String, required: true } })

const grupos = computed(() =>
  GRUPOS.map((grupo) => ({ ...grupo, paginas: PAGINAS.filter((pagina) => pagina.grupo === grupo.id) })).filter(
    (grupo) => grupo.paginas.length,
  ),
)
</script>

<template>
  <nav class="menu" aria-label="Secciones de la documentación">
    <template v-for="grupo in grupos" :key="grupo.id">
      <h2 v-if="grupo.titulo" :id="`menu-${grupo.id}`" class="menu__grupo">{{ grupo.titulo }}</h2>
      <ul class="menu__lista" :aria-labelledby="grupo.titulo ? `menu-${grupo.id}` : undefined">
        <li v-for="pagina in grupo.paginas" :key="pagina.id">
          <RouterLink
            :to="{ name: 'documentacion', params: { pagina: pagina.id } }"
            class="menu__enlace"
            :class="{ 'menu__enlace--actual': pagina.id === props.actual }"
            :aria-current="pagina.id === props.actual ? 'page' : undefined"
          >
            <v-icon :icon="pagina.icono" size="20" class="menu__icono" />{{ pagina.enMenu ?? pagina.titulo }}
          </RouterLink>
        </li>
      </ul>
    </template>
  </nav>
</template>

<style scoped>
.menu {
  position: sticky;
  top: 56px;
  align-self: start;
  height: calc(100vh - 56px);
  overflow-y: auto;
  background: var(--sutil);
  border-right: 1px solid var(--borde);
}

.menu__lista {
  margin: 0;
  padding: 12px;
  list-style: none;
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
  gap: 16px;
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

.menu__enlace--actual {
  color: var(--acento-texto);
  font-weight: 600;
  background: var(--acento-fondo);
}

.menu__enlace--actual .menu__icono {
  color: var(--acento-texto);
}

.menu__enlace--actual::before {
  position: absolute;
  top: 6px;
  bottom: 6px;
  left: 0;
  width: 3px;
  background: var(--marca-cian);
  border-radius: 0 2px 2px 0;
  content: '';
}

@media (width <= 900px) {
  .menu {
    position: static;
    display: flex;
    height: auto;
    overflow-x: auto;
    border-right: 0;
    border-bottom: 1px solid var(--borde);
  }

  .menu__grupo {
    display: none;
  }

  .menu__lista {
    display: flex;
    padding: 8px;
  }

  .menu__enlace {
    gap: 8px;
    white-space: nowrap;
  }
}
</style>
