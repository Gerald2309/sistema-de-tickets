<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { iconos } from '@/compartido/iconos.js'
import { diaLargo } from '@/compartido/presentacion/fechas.js'

/**
 * Fecha y hora con atajos, calendario en español desde el lunes y horas cada 30 minutos (RF-F38, sistema de
 * diseño, Selector de fecha). Trabaja con un Date en la hora local; quien lo usa decide cómo enviarlo.
 * Lo elegido se aplica con «Aplicar»: cerrar o cancelar no cambia el valor.
 */
defineProps({
  etiqueta: { type: String, required: true },
  ayuda: { type: String, default: '' },
  errores: { type: /** @type {import('vue').PropType<string[]>} */ (Array), default: () => [] },
  deshabilitado: { type: Boolean, default: false },
})
const modelo = defineModel({ type: /** @type {import('vue').PropType<Date | null>} */ (Date), default: null })
const emit = defineEmits(['blur'])

const abierto = ref(false)
const dia = ref(/** @type {Date | null} */ (null))
const hora = ref('08:00')
const lista = ref(/** @type {HTMLElement | null} */ (null))

const dosCifras = (/** @type {number} */ n) => String(n).padStart(2, '0')
const horaDe = (/** @type {Date} */ fecha) => `${dosCifras(fecha.getHours())}:${dosCifras(fecha.getMinutes())}`

const HORAS = Array.from({ length: 48 }, (_, i) => `${dosCifras(Math.floor(i / 2))}:${i % 2 ? '30' : '00'}`)
// Un valor que llega con otra hora (17:15) también se puede conservar.
const horas = computed(() => (HORAS.includes(hora.value) ? HORAS : [...HORAS, hora.value].sort()))

const texto = computed(() => (modelo.value ? `${diaLargo(modelo.value)} · ${horaDe(modelo.value)}` : ''))

/** «Hora de Colombia (UTC−5)»: el nombre lo da el navegador; el desfase se calcula. */
const zona = (() => {
  const ahora = new Date()
  const nombre =
    new Intl.DateTimeFormat('es-CO', { timeZoneName: 'long' })
      .formatToParts(ahora)
      .find((parte) => parte.type === 'timeZoneName')?.value ?? 'Hora local'
  const minutos = -ahora.getTimezoneOffset()
  const desfase = `${minutos < 0 ? '−' : '+'}${Math.floor(Math.abs(minutos) / 60)}${minutos % 60 ? `:${dosCifras(Math.abs(minutos) % 60)}` : ''}`
  return `${nombre.charAt(0).toUpperCase()}${nombre.slice(1)} (UTC${desfase})`
})()

const ATAJOS = [
  { texto: 'Hoy 17:00', dias: 0, hora: '17:00' },
  { texto: 'Mañana 8:00', dias: 1, hora: '08:00' },
  { texto: 'En 3 días', dias: 3, hora: '08:00' },
  { texto: 'En 1 semana', dias: 7, hora: '08:00' },
]

/**
 * @param {number} dias
 * @param {string} conHora
 * @returns {Date}
 */
function combinar(dias, conHora) {
  const [h, m] = conHora.split(':').map(Number)
  const base = new Date()
  return new Date(base.getFullYear(), base.getMonth(), base.getDate() + dias, h, m)
}

/** @param {(typeof ATAJOS)[number]} atajo */
const atajoPasado = (atajo) => combinar(atajo.dias, atajo.hora).getTime() <= Date.now()

/** @param {(typeof ATAJOS)[number]} atajo */
function usarAtajo(atajo) {
  modelo.value = combinar(atajo.dias, atajo.hora)
  abierto.value = false
}

// Al abrir se copia el valor actual al borrador y la hora elegida queda a la vista.
watch(abierto, async (valor) => {
  if (!valor) {
    emit('blur')
    return
  }
  dia.value = modelo.value ? new Date(modelo.value) : new Date()
  hora.value = modelo.value ? horaDe(modelo.value) : '08:00'
  await nextTick()
  lista.value?.querySelector('[aria-pressed="true"]')?.scrollIntoView({ block: 'center' })
})

/** @returns {void} */
function aplicar() {
  if (dia.value) {
    const [h, m] = hora.value.split(':').map(Number)
    modelo.value = new Date(dia.value.getFullYear(), dia.value.getMonth(), dia.value.getDate(), h, m)
  }
  abierto.value = false
}
</script>

<template>
  <v-menu
    v-model="abierto"
    :close-on-content-click="false"
    location="bottom start"
    offset="4"
    :disabled="deshabilitado"
  >
    <template #activator="{ props: activador }">
      <!-- Un campo de texto admite aria-haspopup pero no aria-expanded (ARIA 1.2), y aria-controls y aria-owns apuntarían a un
           menú que aún no existe: se quitan de lo que pone el menú. Con teclado se abre con Intro, espacio o
           flecha abajo. -->
      <v-text-field
        v-bind="{
          ...activador,
          'aria-expanded': undefined,
          'aria-controls': undefined,
          'aria-owns': undefined,
          'aria-haspopup': 'dialog',
        }"
        :label="etiqueta"
        :model-value="texto"
        readonly
        :disabled="deshabilitado"
        :prepend-inner-icon="iconos.calendario"
        :append-inner-icon="iconos.desplegar"
        :hint="ayuda || zona"
        persistent-hint
        :error-messages="errores"
        class="campo-fecha"
        @keydown.enter.space.down.prevent="abierto = true"
      />
    </template>
    <v-card class="selector-fecha">
      <div class="selector-fecha__atajos">
        <button
          v-for="atajo in ATAJOS"
          :key="atajo.texto"
          type="button"
          :disabled="atajoPasado(atajo)"
          @click="usarAtajo(atajo)"
        >
          {{ atajo.texto }}
        </button>
      </div>
      <div class="selector-fecha__cuerpo">
        <v-date-picker
          v-model="dia"
          hide-header
          show-adjacent-months
          color="primary"
          :first-day-of-week="1"
          width="300"
        />
        <div class="selector-fecha__horas">
          <div id="titulo-horas" class="selector-fecha__titulo">Hora</div>
          <div ref="lista" class="selector-fecha__lista" role="group" aria-labelledby="titulo-horas">
            <button
              v-for="opcion in horas"
              :key="opcion"
              type="button"
              class="num"
              :aria-pressed="opcion === hora"
              @click="hora = opcion"
            >
              {{ opcion }}
            </button>
          </div>
        </div>
      </div>
      <div class="selector-fecha__pie">
        <span>{{ zona }}</span>
        <v-btn size="small" variant="text" @click="abierto = false">Cancelar</v-btn>
        <v-btn size="small" color="primary" variant="flat" :disabled="!dia" @click="aplicar">Aplicar</v-btn>
      </div>
    </v-card>
  </v-menu>
</template>

<style scoped>
.campo-fecha :deep(input) {
  cursor: pointer;
}

.selector-fecha {
  overflow: hidden;
  border: 1px solid var(--borde);
  border-radius: var(--radio) !important;
  box-shadow: var(--sombra-2) !important;
}

.selector-fecha__atajos {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  padding: 10px 12px;
  border-bottom: 1px solid var(--borde);
}

.selector-fecha__atajos button {
  padding: 4px 10px;
  color: var(--texto);
  font: inherit;
  font-size: 12.5px;
  font-weight: 500;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: var(--radio-chip);
  cursor: pointer;
  transition:
    background-color var(--rapida),
    border-color var(--rapida),
    color var(--rapida);
}

.selector-fecha__atajos button:hover:not(:disabled) {
  color: var(--acento-texto);
  background: var(--acento-fondo);
  border-color: var(--acento);
}

.selector-fecha__atajos button:disabled {
  color: var(--tenue);
  cursor: not-allowed;
}

.selector-fecha__cuerpo {
  display: flex;
}

.selector-fecha :deep(.v-picker) {
  border-radius: 0 !important;
  box-shadow: none !important;
}

.selector-fecha :deep(.v-date-picker-controls) {
  font-weight: 700;
}

.selector-fecha :deep(.v-date-picker-month__weekday) {
  color: var(--suave);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}

.selector-fecha :deep(.v-date-picker-month__day .v-btn) {
  font-weight: 500;
  font-variant-numeric: tabular-nums;
  border-radius: var(--radio) !important;
}

.selector-fecha__horas {
  display: flex;
  flex-direction: column;
  width: 96px;
  border-left: 1px solid var(--borde);
}

.selector-fecha__titulo {
  padding: 12px 12px 6px;
  color: var(--suave);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.selector-fecha__lista {
  display: flex;
  flex-direction: column;
  gap: 2px;
  max-height: 290px;
  padding: 0 8px 8px;
  overflow-y: auto;
}

.selector-fecha__lista button {
  padding: 6px 10px;
  color: var(--texto);
  font: inherit;
  font-size: 13px;
  font-weight: 500;
  text-align: left;
  background: transparent;
  border: 0;
  border-radius: var(--radio-chip);
  cursor: pointer;
  transition: background-color var(--rapida);
}

.selector-fecha__lista button:hover {
  background: var(--sutil);
}

.selector-fecha__lista button[aria-pressed='true'] {
  color: var(--superficie);
  font-weight: 700;
  background: var(--acento);
}

.selector-fecha__pie {
  display: flex;
  gap: 6px;
  align-items: center;
  padding: 8px 12px;
  color: var(--suave);
  font-size: 12px;
  background: var(--sutil);
  border-top: 1px solid var(--borde);
}

.selector-fecha__pie span {
  margin-right: auto;
}

@media (width <= 600px) {
  .selector-fecha__cuerpo {
    flex-direction: column;
  }

  .selector-fecha__horas {
    width: auto;
    border-top: 1px solid var(--borde);
    border-left: 0;
  }

  .selector-fecha__lista {
    flex-direction: row;
    max-height: none;
    overflow-x: auto;
  }
}
</style>
