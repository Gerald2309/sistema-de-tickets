<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { iconos } from '@/compartido/iconos.js'
import logoPsicol from '@/compartido/recursos/psicol-logo.png'
import { construirIndice } from '../busqueda.js'
import BloqueDeDocumentacion from '../componentes/BloqueDeDocumentacion.vue'
import BuscadorDeDocumentacion from '../componentes/BuscadorDeDocumentacion.vue'
import MenuDeDocumentacion from '../componentes/MenuDeDocumentacion.vue'
import TextoTecnico from '../componentes/TextoTecnico.vue'
import { PAGINAS, PAGINA_INICIAL } from '../contenido/index.js'

/**
 * Documentación técnica: abierta a cualquiera, con la misma cara que la aplicación. La página sale de la
 * dirección (/documentacion/{pagina}); la sección, la fila y el paso, del buscador (#seccion-N, ?fila, ?paso).
 */
const props = defineProps({ pagina: { type: String, default: '' } })
const ruta = useRoute()
const enrutador = useRouter()

const actual = computed(() => PAGINAS.find((pagina) => pagina.id === props.pagina) ?? null)
const indice = construirIndice(PAGINAS)
const buscando = ref(false)
const atajo = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform) ? '⌘ K' : 'Ctrl K'

// Lo que pide la dirección para una sección: la fila a destacar y el paso a abrir.
const seccionPedida = computed(() => (ruta.hash.startsWith('#seccion-') ? Number(ruta.hash.slice(9)) : null))
/** @param {string | null | (string | null)[]} valor */
const numero = (valor) => (typeof valor === 'string' && /^\d+$/.test(valor) ? Number(valor) : undefined)

watch(
  actual,
  (pagina) => {
    if (!pagina) {
      enrutador.replace({ name: 'documentacion', params: { pagina: PAGINA_INICIAL } })
      return
    }
    document.title = `${pagina.titulo} · Documentación · Psicol – Mesa de ayuda`
  },
  { immediate: true, flush: 'post' },
)

/** @param {import('../busqueda.js').Destino} destino */
function irA(destino) {
  enrutador.push({
    name: 'documentacion',
    params: { pagina: destino.pagina },
    hash: destino.seccion === undefined ? '' : `#seccion-${destino.seccion}`,
    query: {
      ...(destino.fila === undefined ? {} : { fila: String(destino.fila) }),
      ...(destino.paso === undefined ? {} : { paso: String(destino.paso) }),
    },
  })
}

/** @param {KeyboardEvent} evento */
function alPulsarTecla(evento) {
  const escribiendo = /^(input|textarea|select)$/i.test(/** @type {HTMLElement} */ (evento.target)?.tagName ?? '')
  if ((evento.key === 'k' && (evento.ctrlKey || evento.metaKey)) || (evento.key === '/' && !escribiendo)) {
    evento.preventDefault()
    buscando.value = true
  }
}
onMounted(() => window.addEventListener('keydown', alPulsarTecla))
onBeforeUnmount(() => window.removeEventListener('keydown', alPulsarTecla))
</script>

<template>
  <div class="documentacion">
    <header class="barra">
      <RouterLink :to="{ name: 'documentacion', params: { pagina: PAGINA_INICIAL } }" class="barra__marca">
        <img class="barra__logo" :src="logoPsicol" alt="Psicol" />
        <span class="barra__producto">Mesa de ayuda · Documentación técnica</span>
      </RouterLink>
      <button type="button" class="barra__busqueda" @click="buscando = true">
        <v-icon :icon="iconos.buscar" size="18" />
        <span>Buscar en la documentación…</span>
        <kbd>{{ atajo }}</kbd>
      </button>
      <v-btn :to="{ name: 'inicio' }" color="primary" variant="flat" class="barra__app">Abrir la aplicación</v-btn>
    </header>

    <div class="cuerpo">
      <MenuDeDocumentacion :actual="actual?.id ?? ''" />
      <main v-if="actual" id="contenido" class="contenido">
        <div class="migas">
          Documentación ·
          {{
            { inicio: 'Inicio', aplicacion: 'Mesa de ayuda', administracion: 'Administración', tecnico: 'Técnico' }[
              actual.grupo
            ]
          }}
        </div>
        <h1 class="titulo-pagina">{{ actual.titulo }}</h1>
        <p class="entradilla"><TextoTecnico :texto="actual.entradilla" /></p>
        <section
          v-for="(seccion, i) in actual.secciones"
          :id="`seccion-${i}`"
          :key="`${actual.id}-${i}`"
          class="tarjeta seccion-doc"
          :aria-labelledby="`titulo-seccion-${i}`"
        >
          <header class="tarjeta__cabecera">
            <h2 :id="`titulo-seccion-${i}`" class="tarjeta__titulo">{{ seccion.titulo }}</h2>
            <span v-if="seccion.extra" class="seccion-doc__extra">{{ seccion.extra }}</span>
          </header>
          <BloqueDeDocumentacion
            v-for="(bloque, b) in seccion.bloques"
            :key="b"
            :bloque="bloque"
            :fila="seccionPedida === i ? numero(ruta.query.fila) : undefined"
            :paso="seccionPedida === i ? (numero(ruta.query.paso) ?? 0) : 0"
          />
        </section>
      </main>
    </div>

    <BuscadorDeDocumentacion v-model="buscando" :indice="indice" @elegir="irA" />
  </div>
</template>

<style scoped>
.documentacion {
  min-height: 100vh;
  background: var(--fondo);
}

.barra {
  position: sticky;
  top: 0;
  z-index: 5;
  display: flex;
  gap: 16px;
  align-items: center;
  height: 56px;
  padding: 0 16px;
  background: var(--superficie);
  border-bottom: 1px solid var(--borde);
}

/* Franja de la marca bajo la barra, como en la aplicación. */
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
  margin-left: 8px;
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
  margin-left: auto;
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

.cuerpo {
  display: grid;
  grid-template-columns: 232px minmax(0, 1fr);
}

.contenido {
  min-width: 0;
  max-width: 1180px;
  padding: 24px 28px 96px;
}

.entradilla {
  max-width: 820px;
  margin: 6px 0 20px;
  color: var(--suave);
  font-size: 14.5px;
  line-height: 1.6;
}

.seccion-doc {
  margin-bottom: 16px;
  scroll-margin-top: 72px;
}

.seccion-doc__extra {
  color: var(--tenue);
  font-size: 12.5px;
}

@media (width <= 900px) {
  .cuerpo {
    grid-template-columns: 1fr;
  }

  .contenido {
    padding: 20px 16px 64px;
  }

  .barra__producto,
  .barra__busqueda span {
    display: none;
  }

  .barra__busqueda {
    width: auto;
  }
}
</style>
