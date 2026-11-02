<script setup>
import { useRoute, useRouter } from 'vue-router'
import logoPsicol from '@/compartido/recursos/psicol-logo.png'
import { destinoSeguro } from '@/compartido/utilidades/destino.js'
import FormularioDeContrasena from '../componentes/FormularioDeContrasena.vue'
import { useSesion } from '../tienda/sesion.js'

/**
 * Cambio obligatorio: la contraseña la puso un administrador, así que la persona la cambia antes de usar el
 * sistema. Fuera de la estructura con menú, porque hasta entonces la API no atiende nada más.
 */
const sesion = useSesion()
const ruta = useRoute()
const enrutador = useRouter()

async function continuar() {
  await sesion.refrescarPerfil()
  await enrutador.replace(destinoSeguro(ruta.query.destino))
}
</script>

<template>
  <v-main class="cambio">
    <div class="cambio__caja tarjeta">
      <img class="cambio__logo" :src="logoPsicol" alt="Psicol" />
      <h1 class="cambio__titulo">Elija su propia contraseña</h1>
      <p class="cambio__ayuda">
        La contraseña con la que entró la asignó un administrador. Antes de continuar, cámbiela por una que solo usted
        conozca.
      </p>
      <FormularioDeContrasena @cambiada="continuar" />
      <v-btn variant="text" size="small" class="mt-2" @click="sesion.cerrar()">Salir</v-btn>
    </div>
  </v-main>
</template>

<style scoped>
.cambio {
  display: grid;
  place-items: center;
  min-height: 100vh;
  padding: 24px 16px;
  background: var(--fondo);
}

.cambio__caja {
  width: 100%;
  max-width: 460px;
  padding: 32px 28px;
}

.cambio__logo {
  height: 36px;
  margin-bottom: 18px;
}

.cambio__titulo {
  margin-bottom: 6px;
  font-size: 22px;
}

.cambio__ayuda {
  margin-bottom: 20px;
  color: var(--tenue);
}
</style>
