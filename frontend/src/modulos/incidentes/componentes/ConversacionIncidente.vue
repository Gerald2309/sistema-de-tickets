<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import AvatarPersona from '@/compartido/componentes/AvatarPersona.vue'
import BotonEnvio from '@/compartido/componentes/BotonEnvio.vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import InterruptorCompacto from '@/compartido/componentes/InterruptorCompacto.vue'
import { iconos } from '@/compartido/iconos.js'
import { fechaHora } from '@/compartido/presentacion/fechas.js'
import { useSesion } from '@/modulos/identidad/index.js'
import { LIMITES_DE_MENSAJE, TIPOS_ACEPTADOS, tamanoLegible } from '../conversacion.js'
import { useConversacion } from '../composables/useConversacion.js'
import { puedeTomar } from '../permisos.js'
import ImagenAdjunta from './ImagenAdjunta.vue'

/**
 * Conversación entre quien reportó el incidente y quien lo atiende, con imágenes. Las notas internas se
 * distinguen y el solicitante nunca las recibe. Como un chat: una ventana de alto fijo que abre en lo último y
 * trae los mensajes anteriores por tramos, así una conversación larga no estira la página.
 */
const props = defineProps({
  incidente: {
    type: /** @type {import('vue').PropType<import('../tipos.js').Incidente>} */ (Object),
    required: true,
  },
})

const sesion = useSesion()
const conversacion = useConversacion(() => props.incidente)
const { borrador, permisos } = conversacion
const selector = ref(/** @type {HTMLInputElement | null} */ (null))

const mensajes = conversacion.enOrden
const ventana = ref(/** @type {HTMLElement | null} */ (null))

// Lo último a la vista: al abrir y cuando llega un mensaje nuevo al final.
watch(
  () => mensajes.value.at(-1)?.id,
  async () => {
    await nextTick()
    ventana.value?.scrollTo({ top: ventana.value.scrollHeight })
  },
)

/** Los anteriores entran arriba sin mover lo que la persona está leyendo. */
async function verAnteriores() {
  const desdeElFinal = ventana.value ? ventana.value.scrollHeight - ventana.value.scrollTop : 0
  await conversacion.verAnteriores()
  await nextTick()
  ventana.value?.scrollTo({ top: ventana.value.scrollHeight - desdeElFinal })
}
const lleno = computed(() => borrador.imagenes.length >= LIMITES_DE_MENSAJE.imagenes)

// Por qué la persona no puede escribir, si es el caso.
const motivoDeSoloLectura = computed(() => {
  if (props.incidente.estado === 'cerrado') {
    return 'El incidente está cerrado: la conversación es de solo lectura.'
  }
  if (puedeTomar(sesion, props.incidente)) {
    return 'Tome el incidente para responder al solicitante.'
  }
  return 'Escriben aquí quien reportó el incidente y quien lo atiende.'
})

// Vistas previas de las imágenes elegidas; cada dirección local se libera cuando deja de usarse.
const vistasPrevias = ref(/** @type {string[]} */ ([]))
const liberarVistasPrevias = () => vistasPrevias.value.forEach((direccion) => URL.revokeObjectURL(direccion))
watch(
  () => [...borrador.imagenes],
  (imagenes) => {
    liberarVistasPrevias()
    vistasPrevias.value = imagenes.map((imagen) => URL.createObjectURL(imagen))
  },
)
onBeforeUnmount(liberarVistasPrevias)

/** @param {Event} evento */
function alElegir(evento) {
  const campo = /** @type {HTMLInputElement} */ (evento.target)
  conversacion.adjuntar([...(campo.files ?? [])])
  // Se vacía para poder elegir otra vez el mismo archivo.
  campo.value = ''
}

/** Una captura pegada en el campo se adjunta como imagen. @param {ClipboardEvent} evento */
function alPegar(evento) {
  const imagenes = [...(evento.clipboardData?.files ?? [])]
  if (imagenes.length > 0) {
    evento.preventDefault()
    conversacion.adjuntar(imagenes)
  }
}
</script>

<template>
  <section class="tarjeta" aria-labelledby="titulo-conversacion">
    <header class="tarjeta__cabecera">
      <h2 id="titulo-conversacion" class="tarjeta__titulo">Conversación</h2>
    </header>

    <div class="tarjeta__cuerpo conversacion">
      <div v-if="conversacion.mensajes.isPending.value" class="conversacion__cargando">
        <EsqueletoDeCarga v-for="n in 2" :key="n" :ancho="`${80 - n * 15}%`" alto="48px" />
      </div>
      <p v-else-if="!mensajes.length" class="nota">Aún no hay mensajes.</p>
      <div
        v-else
        ref="ventana"
        class="conversacion__ventana"
        tabindex="0"
        role="region"
        aria-label="Ventana de la conversación"
      >
        <div v-if="conversacion.hayAnteriores.value" class="conversacion__anteriores">
          <v-btn variant="text" size="small" :loading="conversacion.cargandoAnteriores.value" @click="verAnteriores"
            >Ver mensajes anteriores</v-btn
          >
        </div>
        <TransitionGroup name="mensaje" tag="ol" class="hilo" aria-label="Mensajes">
          <li
            v-for="mensaje in mensajes"
            :key="mensaje.id"
            class="mensaje"
            :class="{ 'mensaje--propio': mensaje.autor.id === sesion.perfil?.id, 'mensaje--interno': mensaje.interno }"
          >
            <div class="mensaje__cabecera">
              <AvatarPersona :nombre="mensaje.autor.nombre" />
              <span class="nota">{{ fechaHora(mensaje.creadoEn) }}</span>
              <span v-if="mensaje.interno" class="mensaje__etiqueta">
                <v-icon :icon="iconos.notaInterna" size="14" />Nota interna
              </span>
            </div>
            <p v-if="mensaje.cuerpo" class="mensaje__cuerpo">{{ mensaje.cuerpo }}</p>
            <div v-if="mensaje.adjuntos.length" class="mensaje__adjuntos">
              <ImagenAdjunta
                v-for="adjunto in mensaje.adjuntos"
                :key="adjunto.id"
                :incidente-id="incidente.id"
                :mensaje-id="mensaje.id"
                :adjunto="adjunto"
              />
            </div>
          </li>
        </TransitionGroup>
      </div>

      <form v-if="permisos.escribir" class="redactor" novalidate @submit.prevent="conversacion.enviar">
        <v-textarea
          v-model="borrador.cuerpo"
          label="Escriba un mensaje"
          rows="2"
          auto-grow
          max-rows="10"
          :counter="LIMITES_DE_MENSAJE.caracteres"
          :maxlength="LIMITES_DE_MENSAJE.caracteres"
          hint="Ctrl + Enter para enviar. Puede pegar capturas de pantalla."
          @paste="alPegar"
          @keydown.ctrl.enter.prevent="conversacion.enviar"
          @keydown.meta.enter.prevent="conversacion.enviar"
        />

        <ul v-if="borrador.imagenes.length" class="redactor__imagenes" aria-label="Imágenes por enviar">
          <li
            v-for="(imagen, indice) in borrador.imagenes"
            :key="vistasPrevias[indice] ?? indice"
            class="redactor__imagen"
          >
            <img v-if="vistasPrevias[indice]" :src="vistasPrevias[indice]" alt="" class="redactor__miniatura" />
            <span class="redactor__datos">
              <span class="redactor__nombre">{{ imagen.name }}</span>
              <span class="nota">{{ tamanoLegible(imagen.size) }}</span>
            </span>
            <v-btn
              :icon="iconos.cerrar"
              variant="text"
              size="x-small"
              :aria-label="`Quitar ${imagen.name}`"
              @click="conversacion.quitar(indice)"
            />
          </li>
        </ul>
        <p v-for="rechazo in conversacion.rechazos.value" :key="rechazo" class="redactor__rechazo" role="alert">
          {{ rechazo }}
        </p>

        <div class="redactor__acciones">
          <v-btn variant="text" :prepend-icon="iconos.adjuntar" :disabled="lleno" @click="selector?.click()">
            Adjuntar imagen
          </v-btn>
          <input
            ref="selector"
            type="file"
            class="redactor__selector"
            multiple
            :accept="TIPOS_ACEPTADOS"
            @change="alElegir"
          />
          <label v-if="permisos.notaInterna" class="redactor__interno">
            <InterruptorCompacto v-model="borrador.interno" />
            <span>Nota interna</span>
          </label>
          <BotonEnvio
            texto="Enviar"
            texto-pendiente="Enviando…"
            :pendiente="conversacion.enviando.value"
            :deshabilitado="!conversacion.listo.value"
            class="redactor__enviar"
          />
        </div>
      </form>
      <p v-else class="nota conversacion__solo-lectura">{{ motivoDeSoloLectura }}</p>
    </div>
  </section>
</template>

<style scoped>
.conversacion {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.conversacion__cargando {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* Alto fijo con su propio desplazamiento, como una ventana de chat. */
.conversacion__ventana {
  max-height: 480px;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.conversacion__ventana:focus-visible {
  outline: 2px solid var(--acento);
  outline-offset: 2px;
}

.conversacion__anteriores {
  display: flex;
  justify-content: center;
  margin-bottom: 8px;
}

.conversacion__solo-lectura {
  margin: 0;
  padding-top: 12px;
  border-top: 1px solid var(--borde);
}

.hilo {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.mensaje {
  max-width: min(85%, 640px);
  padding: 10px 12px;
  background: var(--sutil);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
}

.mensaje--propio {
  align-self: flex-end;
  background: var(--acento-fondo);
  border-color: var(--acento-borde);
}

.mensaje--interno {
  background: var(--advertencia-fondo);
  border-color: var(--advertencia-borde);
  border-style: dashed;
}

.mensaje__cabecera {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.mensaje__etiqueta {
  display: inline-flex;
  gap: 4px;
  align-items: center;
  color: var(--advertencia);
  font-size: 12px;
  font-weight: 600;
}

.mensaje__cuerpo {
  margin: 6px 0 0;
  line-height: 1.55;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.mensaje__adjuntos {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}

.redactor {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding-top: 12px;
  border-top: 1px solid var(--borde);
}

.redactor__imagenes {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.redactor__imagen {
  display: flex;
  gap: 8px;
  align-items: center;
  max-width: 260px;
  padding: 4px 4px 4px 6px;
  background: var(--sutil);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
}

.redactor__miniatura {
  flex: none;
  width: 36px;
  height: 36px;
  object-fit: cover;
  border-radius: calc(var(--radio) / 2);
}

.redactor__datos {
  display: flex;
  flex-direction: column;
  min-width: 0;
  font-size: 13px;
}

.redactor__nombre {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.redactor__rechazo {
  margin: 0;
  color: var(--error);
  font-size: 13px;
}

.redactor__acciones {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
}

.redactor__selector {
  display: none;
}

.redactor__interno {
  display: inline-flex;
  gap: 8px;
  align-items: center;
  font-size: 14px;
  cursor: pointer;
}

.redactor__enviar {
  margin-left: auto;
}

.mensaje-enter-active {
  transition:
    opacity 0.5s var(--curva),
    transform 0.5s var(--curva);
}

.mensaje-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
</style>
