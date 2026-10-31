<script setup>
import { ref } from 'vue'
import logoPsicol from '@/compartido/recursos/psicol-logo.png'
import BotonEnvio from '@/compartido/componentes/BotonEnvio.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import { iconos } from '@/compartido/iconos.js'
import { useIngreso } from '../composables/useIngreso.js'

const { correo, contrasena, pendiente, error, segundosDeEspera, ingresar, motivoDeSalida } = useIngreso()
const verContrasena = ref(false)
const formulario = ref(/** @type {{ validate: () => Promise<{ valid: boolean }> } | null} */ (null))

const reglasCorreo = [
  (/** @type {string} */ valor) => !!valor.trim() || 'Escriba su correo.',
  (/** @type {string} */ valor) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor.trim()) || 'Escriba un correo válido.',
]
const reglasContrasena = [(/** @type {string} */ valor) => !!valor || 'Escriba su contraseña.']

async function enviar() {
  const { valid } = (await formulario.value?.validate()) ?? { valid: false }
  if (valid) {
    await ingresar()
  }
}
</script>

<template>
  <v-main class="ingreso">
    <div class="ingreso__rejilla">
      <section class="ingreso__marca" aria-hidden="true">
        <span class="mancha mancha--1" />
        <span class="mancha mancha--2" />
        <span class="mancha mancha--3" />
        <div>
          <div class="ingreso__producto">Psicol – Mesa de ayuda</div>
          <p v-revelar class="ingreso__lema">Creando entornos de trabajo productivos, saludables y felices.</p>
          <p class="ingreso__bajada entra" style="--i: 6">
            Gestione las solicitudes e incidentes de su operación en un solo lugar.
          </p>
        </div>
        <img class="ingreso__logo-gigante" :src="logoPsicol" alt="" />
        <small class="ingreso__pie">© Psicol · Modelo S³: Salud + Seguridad + Sigesa Plus</small>
      </section>

      <section class="ingreso__formulario">
        <div class="ingreso__caja">
          <img class="ingreso__logo entra" :src="logoPsicol" alt="Psicol" />
          <h1 v-revelar class="ingreso__titulo">Iniciar sesión</h1>
          <p class="ingreso__sub entra" style="--i: 3">Ingrese con su cuenta corporativa.</p>

          <Transition name="texto">
            <MensajeEnPagina
              v-if="motivoDeSalida === 'expirada' && !error"
              tipo="info"
              titulo="Su sesión expiró"
              class="mb-4"
            >
              Por seguridad, vuelva a ingresar.
            </MensajeEnPagina>
          </Transition>
          <Transition name="texto">
            <MensajeEnPagina
              v-if="error"
              :tipo="segundosDeEspera > 0 ? 'advertencia' : 'error'"
              :titulo="error"
              class="mb-4"
            >
              <span v-if="segundosDeEspera > 0" class="num"
                >Podrá intentarlo de nuevo en {{ segundosDeEspera }} s.</span
              >
            </MensajeEnPagina>
          </Transition>

          <v-form ref="formulario" validate-on="blur" @submit.prevent="enviar">
            <div class="entra" style="--i: 4">
              <v-text-field
                v-model="correo"
                label="Correo electrónico"
                type="email"
                autocomplete="username"
                :prepend-inner-icon="iconos.correo"
                :rules="reglasCorreo"
                autofocus
              />
            </div>
            <div class="entra" style="--i: 5">
              <v-text-field
                v-model="contrasena"
                label="Contraseña"
                :type="verContrasena ? 'text' : 'password'"
                autocomplete="current-password"
                :prepend-inner-icon="iconos.contrasena"
                :rules="reglasContrasena"
              >
                <template #append-inner>
                  <v-btn
                    :icon="verContrasena ? iconos.ocultar : iconos.ver"
                    variant="text"
                    size="small"
                    :aria-label="verContrasena ? 'Ocultar la contraseña' : 'Mostrar la contraseña'"
                    :aria-pressed="verContrasena"
                    @click="verContrasena = !verContrasena"
                  />
                </template>
              </v-text-field>
            </div>
            <div class="entra" style="--i: 6">
              <BotonEnvio
                texto="Ingresar"
                texto-pendiente="Ingresando…"
                :pendiente="pendiente"
                :deshabilitado="segundosDeEspera > 0"
                bloque
                flecha
                tamano="large"
                class="mt-2"
              />
            </div>
          </v-form>
          <p class="ingreso__sub ingreso__ayuda entra" style="--i: 7">
            ¿Problemas para ingresar? Escriba a su administrador.
          </p>
        </div>
      </section>
    </div>
  </v-main>
</template>

<style scoped>
.ingreso {
  background: var(--superficie);
}

.ingreso__rejilla {
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 100vh;
}

.ingreso__marca {
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 48px;
  overflow: hidden;
  color: var(--superficie);
  background: var(--degradado);
}

.ingreso__marca > * {
  position: relative;
  z-index: 1;
}

.ingreso__producto {
  margin-bottom: 14px;
  font-size: 13px;
  font-weight: 500;
  opacity: 0.9;
}

.ingreso__lema {
  max-width: 520px;
  margin: 0 0 14px;
  font-size: clamp(30px, 3.4vw, 46px);
  font-weight: 800;
  line-height: 1.08;
  letter-spacing: -0.03em;
}

.ingreso__bajada {
  max-width: 430px;
  margin: 0;
  font-size: 16px;
  opacity: 0.95;
}

.ingreso__pie {
  opacity: 0.9;
}

.ingreso__logo-gigante {
  position: absolute !important;
  bottom: 9%;
  left: 5%;
  z-index: 0 !important;
  width: 90%;
  filter: brightness(0) invert(1);
  opacity: 0.16;
  pointer-events: none;
}

.mancha {
  position: absolute !important;
  z-index: 0 !important;
  border-radius: 50%;
  filter: blur(50px);
  opacity: 0.3;
  animation: flota 14s ease-in-out infinite alternate;
}

.mancha--1 {
  top: -60px;
  left: -80px;
  width: 380px;
  height: 380px;
  background: var(--marca-cian);
}

.mancha--2 {
  top: 35%;
  right: -60px;
  width: 320px;
  height: 320px;
  background: var(--prioridad-media);
  animation-duration: 18s;
}

.mancha--3 {
  bottom: -100px;
  left: 25%;
  width: 300px;
  height: 300px;
  background: var(--marca-verde);
  animation-duration: 16s;
}

.ingreso__formulario {
  display: flex;
  align-items: center;
  justify-content: center;
}

.ingreso__caja {
  width: 100%;
  max-width: 380px;
  padding: 32px;
}

.ingreso__logo {
  height: 30px;
  margin-bottom: 40px;
}

.ingreso__titulo {
  margin: 0;
  font-size: 28px;
  font-weight: 700;
  line-height: 1.2;
  letter-spacing: -0.02em;
}

.ingreso__sub {
  margin: 6px 0 24px;
  color: var(--suave);
  font-size: 14px;
}

.ingreso__ayuda {
  margin-top: 18px;
  font-size: 13px;
}

@media (width <= 760px) {
  .ingreso__rejilla {
    grid-template-columns: 1fr;
  }

  .ingreso__marca {
    display: none;
  }
}
</style>
