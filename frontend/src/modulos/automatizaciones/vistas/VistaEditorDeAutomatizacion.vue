<script setup>
import { computed, provide, ref, watch } from 'vue'
import { onBeforeRouteLeave, useRouter } from 'vue-router'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import InterruptorCompacto from '@/compartido/componentes/InterruptorCompacto.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import { useConfirmacion } from '@/compartido/composables/useConfirmacion.js'
import { iconos } from '@/compartido/iconos.js'
import { tiempoRelativo } from '@/compartido/presentacion/fechas.js'
import { useBuscadorDePersonas, useEmpresas, useRoles } from '@/modulos/identidad/index.js'
import BibliotecaDeBloques from '../componentes/BibliotecaDeBloques.vue'
import CaminoDelFlujo from '../componentes/CaminoDelFlujo.vue'
import CamposDeAccion from '../componentes/CamposDeAccion.vue'
import CamposDeCondicion from '../componentes/CamposDeCondicion.vue'
import CamposDelDisparo from '../componentes/CamposDelDisparo.vue'
import ResultadoDePrueba from '../componentes/ResultadoDePrueba.vue'
import TarjetaDePaso from '../componentes/TarjetaDePaso.vue'
import { useEditorDeAutomatizacion } from '../composables/useEditorDeAutomatizacion.js'
import { AYUDA, DISPARADORES, bloqueDe, describirDisparo, fraseDeLaAutomatizacion, nuevoPaso } from '../catalogo.js'
import { EDITOR } from '../editor.js'
import { erroresDelPaso, insertar, personasNombradas, quitar as quitarDelFlujo, rutaDe } from '../flujo.js'

/** @typedef {import('../tipos.js').Paso} Paso */

/**
 * Editor de una automatización: el flujo a la izquierda y todo lo que se configura, en el panel de la derecha.
 * Con `id` null crea una nueva.
 */
const props = defineProps({ id: { type: Number, default: null } })

const { borrador, carga, errores, cambiado, guardar, guardando, probar, probando, resultado, olvidarRevision } =
  useEditorDeAutomatizacion(() => props.id)
const enrutador = useRouter()
const confirmacion = useConfirmacion()

const seleccionado = ref(/** @type {Paso | 'cuando' | null} */ ('cuando'))
const insertarEn = ref(/** @type {{ lista: Paso[], k: number, condicionesAntes: number } | null} */ (null))
const viendoPrueba = ref(false)

// Los nombres de lo que los pasos guardan como id.
const empresas = useEmpresas()
const personas = useBuscadorDePersonas({ incluirInactivas: true, elegidos: () => personasNombradas(borrador.pasos) })
const roles = useRoles()
/** @type {import('../tipos.js').Nombres} */
const nombres = {
  empresa: empresas.nombreDe,
  persona: personas.nombreDe,
  rol: (id) => roles.data.value?.find((rol) => rol.id === id)?.nombre,
}

/** @param {Paso | 'cuando'} paso */
function elegir(paso) {
  seleccionado.value = paso
  insertarEn.value = null
  viendoPrueba.value = false
}

/**
 * @param {Paso[]} lista
 * @param {number} i
 */
function quitar(lista, i) {
  if (lista[i] === seleccionado.value) {
    seleccionado.value = 'cuando'
  }
  quitarDelFlujo(lista, i)
}

/**
 * @param {Paso[]} lista
 * @param {number} k
 * @param {number} condicionesAntes
 */
function abrirBiblioteca(lista, k, condicionesAntes) {
  insertarEn.value = { lista, k, condicionesAntes }
  viendoPrueba.value = false
}

/** @param {{ tipo: 'condicion' | 'accion', clave: string }} bloque */
function anadir({ tipo, clave }) {
  if (!insertarEn.value) {
    return
  }
  const { lista, k } = insertarEn.value
  insertar(lista, k, nuevoPaso(tipo, clave))
  // El paso quedó en la lista reactiva: se elige ese, no el objeto original.
  elegir(lista[tipo === 'condicion' ? lista.length - 1 : k])
}

/** @param {Partial<Paso>} cambios */
function cambiarPaso(cambios) {
  if (seleccionado.value && seleccionado.value !== 'cuando') {
    Object.assign(seleccionado.value, cambios)
  }
}

provide(EDITOR, { seleccionado, insertarEn, elegir, quitar, abrirBiblioteca, nombres, errores })

// Lo revisado (errores, prueba) deja de valer apenas cambia algo.
watch(borrador, olvidarRevision, { deep: true })

const paso = computed(() => (seleccionado.value === 'cuando' ? null : seleccionado.value))
const panel = computed(() => {
  if (insertarEn.value) {
    return 'biblioteca'
  }
  if (viendoPrueba.value && resultado.value) {
    return 'prueba'
  }
  return paso.value ? 'paso' : 'cuando'
})
const CAMPOS_DEL_DISPARO = ['disparador', 'espera_minutos', 'porcentaje', 'plazo']
const erroresDelDisparo = computed(() => CAMPOS_DEL_DISPARO.flatMap((campo) => errores.value[campo] ?? []))
const erroresDelSeleccionado = computed(() => {
  const ruta = paso.value && rutaDe(borrador.pasos, paso.value)
  return ruta ? erroresDelPaso(errores.value, ruta) : []
})
const erroresGenerales = computed(() => [...(errores.value.pasos ?? []), ...(errores.value.activa ?? [])])
const uso = computed(() => {
  const guardada = carga.data.value
  if (!guardada?.aplicaciones) {
    return 'Todavía no se ha aplicado'
  }
  const veces = guardada.aplicaciones === 1 ? '1 vez' : `${guardada.aplicaciones} veces`
  const ultima = guardada.ultimaAplicacionEn ? `, la última ${tiempoRelativo(guardada.ultimaAplicacionEn).texto}` : ''
  return `Aplicada ${veces}${ultima}`
})

function verPrueba() {
  insertarEn.value = null
  viendoPrueba.value = true
  probar()
}

async function guardarYSeguir() {
  const guardada = await guardar()
  if (guardada && props.id === null) {
    await enrutador.replace({ name: 'automatizacion', params: { id: guardada.id } })
  }
}

onBeforeRouteLeave(() =>
  !cambiado.value || guardando.value
    ? true
    : confirmacion.pedir({
        titulo: '¿Salir sin guardar?',
        texto: 'Los cambios de esta automatización se perderán.',
        accion: 'Salir sin guardar',
        peligrosa: true,
      }),
)
</script>

<template>
  <div class="editor">
    <header class="editor__barra">
      <div class="editor__titulo">
        <RouterLink :to="{ name: 'automatizaciones' }" class="migas">Administración / Automatizaciones</RouterLink>
        <input
          v-model="borrador.nombre"
          class="editor__nombre"
          aria-label="Nombre de la automatización"
          maxlength="120"
          :aria-invalid="!!errores.nombre"
        />
        <span v-if="errores.nombre" class="editor__error">{{ errores.nombre[0] }}</span>
      </div>
      <div class="editor__estado">
        <InterruptorCompacto v-model="borrador.activa" etiqueta="Activa" />
        <span>{{ borrador.activa ? 'Activa' : 'Apagada' }} · {{ uso }}</span>
      </div>
      <v-btn variant="outlined" :prepend-icon="iconos.probar" :loading="probando" @click="verPrueba">Probar</v-btn>
      <v-btn variant="outlined" :to="{ name: 'automatizaciones' }">Cancelar</v-btn>
      <v-btn color="primary" variant="flat" :disabled="!cambiado" :loading="guardando" @click="guardarYSeguir">
        Guardar cambios
      </v-btn>
    </header>

    <MensajeEnPagina v-if="carga.isError.value" tipo="error" titulo="No se pudo cargar la automatización" class="ma-6">
      {{ carga.error.value?.message }}
    </MensajeEnPagina>
    <div v-else-if="id !== null && carga.isPending.value" class="editor__cargando">
      <EsqueletoDeCarga v-for="n in 3" :key="n" ancho="290px" alto="64px" />
    </div>

    <div v-else class="editor__rejilla">
      <main class="lienzo" aria-label="Flujo de la automatización">
        <MensajeEnPagina
          v-for="mensaje in erroresGenerales"
          :key="mensaje"
          tipo="error"
          titulo="Falta algo"
          class="mb-4"
        >
          {{ mensaje }}
        </MensajeEnPagina>
        <div class="flujo">
          <TarjetaDePaso
            tipo="cuando"
            :icono="iconos.disparador"
            :titulo="DISPARADORES[borrador.disparo.disparador]?.nombre ?? borrador.disparo.disparador"
            :texto="describirDisparo(borrador.disparo)"
            :seleccionada="seleccionado === 'cuando' && panel === 'cuando'"
            :con-error="erroresDelDisparo.length > 0"
            :quitable="false"
            @elegir="elegir('cuando')"
          />
          <CaminoDelFlujo :pasos="borrador.pasos" ruta="pasos" :condiciones-antes="0" />
        </div>
      </main>

      <aside class="panel" aria-label="Configuración del paso">
        <template v-if="panel === 'biblioteca'">
          <header class="panel__cabeza">
            <span class="panel__tipo">Añadir un paso</span>
            <h2>Elija un bloque</h2>
          </header>
          <div class="panel__cuerpo">
            <BibliotecaDeBloques :condiciones-antes="insertarEn?.condicionesAntes ?? 0" @elegir="anadir" />
          </div>
          <footer class="panel__pie"><v-btn variant="outlined" @click="insertarEn = null">Cancelar</v-btn></footer>
        </template>

        <template v-else-if="panel === 'prueba' && resultado">
          <header class="panel__cabeza">
            <span class="panel__tipo">Prueba · nada se modifica</span>
            <h2>Con los incidentes recientes</h2>
          </header>
          <div class="panel__cuerpo"><ResultadoDePrueba :resultado="resultado" /></div>
          <footer class="panel__pie">
            <v-btn variant="outlined" @click="viendoPrueba = false">Volver a editar</v-btn>
          </footer>
        </template>

        <template v-else>
          <header class="panel__cabeza">
            <span class="panel__tipo">{{ paso ? (paso.tipo === 'condicion' ? 'Si' : 'Entonces') : 'Cuándo' }}</span>
            <h2>{{ paso ? bloqueDe(paso).titulo : DISPARADORES[borrador.disparo.disparador]?.nombre }}</h2>
          </header>
          <div class="panel__cuerpo">
            <MensajeEnPagina
              v-for="mensaje in paso ? erroresDelSeleccionado : erroresDelDisparo"
              :key="mensaje"
              tipo="error"
              titulo="Revise este paso"
              class="mb-4"
            >
              {{ mensaje }}
            </MensajeEnPagina>
            <CamposDeCondicion
              v-if="paso?.tipo === 'condicion'"
              :key="rutaDe(borrador.pasos, paso) ?? ''"
              :paso="paso"
              @cambiar="cambiarPaso"
            />
            <CamposDeAccion
              v-else-if="paso"
              :key="rutaDe(borrador.pasos, paso) ?? ''"
              :paso="paso"
              @cambiar="cambiarPaso"
            />
            <CamposDelDisparo v-else :disparo="borrador.disparo" @cambiar="borrador.disparo = $event" />
            <p class="ayuda">
              <v-icon :icon="iconos.info" size="16" />
              {{ AYUDA[paso ? (paso.tipo === 'condicion' ? 'si' : 'entonces') : 'cuando'] }}
            </p>
          </div>
          <footer class="panel__pie">
            <span class="panel__tipo">En una frase</span>
            {{ fraseDeLaAutomatizacion(borrador.disparo, borrador.pasos, nombres) }}
          </footer>
        </template>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.editor {
  display: grid;
  grid-template-rows: auto 1fr;
  height: calc(100dvh - 56px);
}

.editor__barra {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
  padding: 14px 28px;
  background: var(--superficie);
  border-bottom: 1px solid var(--borde);
}

.editor__titulo {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 240px;
}

.migas {
  color: var(--suave);
  font-size: 12px;
  text-decoration: none;
}

.editor__nombre {
  padding: 2px 4px;
  margin-left: -4px;
  color: var(--texto);
  font: inherit;
  font-size: 18px;
  font-weight: 700;
  background: none;
  border: 1px solid transparent;
  border-radius: var(--radio);
}

.editor__nombre:hover,
.editor__nombre:focus-visible {
  border-color: var(--borde-fuerte);
  outline: none;
}

.editor__error {
  color: var(--error);
  font-size: 12px;
}

.editor__estado {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-right: 8px;
  color: var(--suave);
  font-size: 13px;
}

.editor__cargando {
  display: flex;
  flex-direction: column;
  gap: 40px;
  align-items: center;
  padding: 40px;
}

.editor__rejilla {
  display: grid;
  grid-template-columns: 1fr 380px;
  min-height: 0;
}

/* Lienzo punteado: los puntos ubican el flujo sin competir con él. */
.lienzo {
  padding: 32px 16px 80px;
  overflow: auto;
  background-color: var(--fondo);
  background-image: radial-gradient(var(--borde-fuerte) 1px, transparent 1px);
  background-size: 22px 22px;
}

.flujo {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: max-content;
  min-width: 100%;
}

.panel {
  display: flex;
  flex-direction: column;
  min-height: 0;
  overflow: auto;
  background: var(--superficie);
  border-left: 1px solid var(--borde);
}

.panel__cabeza {
  padding: 20px 22px 16px;
  border-bottom: 1px solid var(--borde);
}

.panel__cabeza h2 {
  margin: 2px 0 0;
  font-size: 16px;
  font-weight: 700;
}

.panel__tipo {
  display: block;
  color: var(--tenue);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.panel__cuerpo {
  flex: 1;
  padding: 18px 22px;
}

.panel__pie {
  padding: 14px 22px;
  color: var(--suave);
  font-size: 13px;
  background: var(--fondo);
  border-top: 1px solid var(--borde);
}

.ayuda {
  display: flex;
  gap: 8px;
  margin: 8px 0 0;
  padding: 10px 12px;
  color: var(--suave);
  font-size: 12.5px;
  background: var(--fondo);
  border-radius: var(--radio);
}

@media (width <= 900px) {
  .editor {
    height: auto;
  }

  .editor__rejilla {
    grid-template-columns: 1fr;
  }

  .panel {
    border-top: 1px solid var(--borde);
    border-left: 0;
  }
}
</style>
