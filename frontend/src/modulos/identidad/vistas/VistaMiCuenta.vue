<script setup>
import { storeToRefs } from 'pinia'
import { ref } from 'vue'
import BotonEnvio from '@/compartido/componentes/BotonEnvio.vue'
import ListaPropiedades from '@/compartido/componentes/ListaPropiedades.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import { iconos } from '@/compartido/iconos.js'
import { LONGITUD_MAXIMA, LONGITUD_MINIMA, useCambioDeContrasena } from '../composables/useCambioDeContrasena.js'
import { useSesion } from '../tienda/sesion.js'

const { perfil } = storeToRefs(useSesion())
const {
  actual,
  nueva,
  repetida,
  pendiente,
  exito,
  errores,
  general,
  cumpleLongitud,
  coincide,
  listo,
  guardar,
  limpiarCampo,
} = useCambioDeContrasena()
const verNueva = ref(false)
</script>

<template>
  <div class="pagina pagina--angosta">
    <header class="cabecera">
      <div>
        <h1 v-revelar class="titulo-pagina">Mi cuenta</h1>
        <p class="subtitulo entra" style="--i: 2">Sus datos los administra Psicol; aquí puede cambiar su contraseña.</p>
      </div>
    </header>

    <div v-if="perfil" class="tarjeta entra" style="--i: 3">
      <div class="formulario-por-secciones">
        <section class="seccion">
          <div>
            <h2 class="seccion__titulo">Datos</h2>
            <p class="seccion__ayuda">Para corregirlos, escriba a su administrador.</p>
          </div>
          <ListaPropiedades
            :elementos="[
              { etiqueta: 'Nombre', valor: perfil.nombre },
              { etiqueta: 'Correo', valor: perfil.correo },
              { etiqueta: 'Rol', valor: perfil.rol.nombre },
              { etiqueta: 'Empresa', valor: perfil.cliente?.nombre ?? 'Personal de Psicol', tenue: !perfil.cliente },
            ]"
          />
        </section>

        <section class="seccion seccion--ultima">
          <div>
            <h2 class="seccion__titulo">Contraseña</h2>
            <p class="seccion__ayuda">Al cambiarla se cerrarán sus demás sesiones abiertas.</p>
          </div>
          <form novalidate @submit.prevent="guardar">
            <MensajeEnPagina v-if="general" tipo="error" titulo="No se pudo cambiar la contraseña" class="mb-4">
              {{ general }}
            </MensajeEnPagina>
            <v-text-field
              v-model="actual"
              label="Contraseña actual *"
              type="password"
              autocomplete="current-password"
              :error-messages="errores.actual"
              @update:model-value="limpiarCampo('actual')"
            />
            <v-text-field
              v-model="nueva"
              label="Nueva contraseña *"
              :type="verNueva ? 'text' : 'password'"
              autocomplete="new-password"
              :counter="LONGITUD_MAXIMA"
              :maxlength="LONGITUD_MAXIMA"
              :error-messages="errores.nueva"
              aria-describedby="requisito-contrasena"
              @update:model-value="limpiarCampo('nueva')"
            >
              <template #append-inner>
                <v-btn
                  :icon="verNueva ? iconos.ocultar : iconos.ver"
                  variant="text"
                  size="small"
                  :aria-label="verNueva ? 'Ocultar la contraseña' : 'Mostrar la contraseña'"
                  :aria-pressed="verNueva"
                  @click="verNueva = !verNueva"
                />
              </template>
            </v-text-field>
            <p
              id="requisito-contrasena"
              class="requisito"
              :class="{ 'requisito--cumplido': cumpleLongitud }"
              aria-live="polite"
            >
              <v-icon :icon="cumpleLongitud ? iconos.requisitoCumplido : iconos.requisitoPendiente" size="16" />
              Al menos {{ LONGITUD_MINIMA }} caracteres (<span class="num"
                >{{ nueva.length }}/{{ LONGITUD_MINIMA }}</span
              >). Una frase larga es más segura que símbolos raros.
            </p>
            <v-text-field
              v-model="repetida"
              label="Repita la nueva contraseña *"
              type="password"
              autocomplete="new-password"
              :error-messages="coincide ? [] : ['No coincide con la nueva contraseña.']"
            />
            <div class="formulario-por-secciones__pie">
              <BotonEnvio texto="Cambiar contraseña" :pendiente="pendiente" :exito="exito" :deshabilitado="!listo" />
            </div>
          </form>
        </section>
      </div>
    </div>
  </div>
</template>

<style scoped>
.requisito {
  display: flex;
  gap: 6px;
  align-items: center;
  margin: -6px 0 14px;
  color: var(--suave);
  font-size: 12.5px;
}

.requisito--cumplido {
  color: var(--exito-texto);
}
</style>
