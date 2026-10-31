<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useDisplay } from 'vuetify'
import PilaAvisos from '@/compartido/componentes/PilaAvisos.vue'
import AvisoSinConexion from './AvisoSinConexion.vue'
import BarraSuperior from './BarraSuperior.vue'
import MenuLateral from './MenuLateral.vue'
import PaletaComandos from './PaletaComandos.vue'

/** Estructura de la aplicación con sesión: barra superior, menú lateral, contenido y piezas globales. */
const { width } = useDisplay()
// El menú queda fijo desde 1280 px (sistema de diseño, Espaciado y forma); por debajo, se despliega.
const menuPermanente = computed(() => width.value >= 1280)
const menuAbierto = ref(false)
const paletaAbierta = ref(false)
const ruta = useRoute()
const contenido = ref(/** @type {HTMLElement | null} */ (null))

/**
 * @param {KeyboardEvent} evento
 * @returns {void}
 */
function alPulsarTecla(evento) {
  if ((evento.ctrlKey || evento.metaKey) && evento.key.toLowerCase() === 'k') {
    evento.preventDefault()
    paletaAbierta.value = true
  }
}

onMounted(() => window.addEventListener('keydown', alPulsarTecla))
onBeforeUnmount(() => window.removeEventListener('keydown', alPulsarTecla))

// Al navegar, el foco va al contenido para que los lectores de pantalla anuncien la página nueva, y en
// pantallas pequeñas el menú se cierra.
watch(
  () => ruta.path,
  async () => {
    menuAbierto.value = false
    await nextTick()
    contenido.value?.focus({ preventScroll: true })
  },
)
</script>

<template>
  <a class="salto-al-contenido" href="#contenido">Saltar al contenido</a>
  <BarraSuperior
    :menu-permanente="menuPermanente"
    @alternar-menu="menuAbierto = !menuAbierto"
    @abrir-paleta="paletaAbierta = true"
  />
  <MenuLateral v-model="menuAbierto" :permanente="menuPermanente" />
  <v-main>
    <div id="contenido" ref="contenido" tabindex="-1" class="contenido">
      <AvisoSinConexion />
      <RouterView v-slot="{ Component }">
        <Transition name="pagina" mode="out-in">
          <component :is="Component" :key="ruta.path" />
        </Transition>
      </RouterView>
    </div>
  </v-main>
  <PaletaComandos v-model="paletaAbierta" />
  <PilaAvisos />
</template>

<style scoped>
.contenido {
  outline: none;
}

.pagina-enter-active {
  transition:
    opacity 0.3s var(--curva),
    transform 0.3s var(--curva);
}

.pagina-leave-active {
  transition: opacity 0.12s;
}

.pagina-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.pagina-leave-to {
  opacity: 0;
}
</style>
