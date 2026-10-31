<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { iconos } from '@/compartido/iconos.js'
import { useSesion } from '@/modulos/identidad/index.js'
import { codigoDe, idDeCodigo } from '@/modulos/incidentes/index.js'
import { useNavegacion } from './useNavegacion.js'

/**
 * Paleta de comandos (RF-F10): Ctrl K abre una búsqueda que lleva a cualquier pantalla o acción permitida.
 * Enter ejecuta el primer resultado; Esc cierra y Vuetify devuelve el foco a donde estaba.
 */
const abierta = defineModel({ type: Boolean, default: false })
const enrutador = useRouter()
const sesion = useSesion()
const { permitidas } = useNavegacion()
const consulta = ref('')

/** @typedef {{ titulo: string, icono: string, ejecutar: () => void }} Comando */

/** @type {import('vue').ComputedRef<Comando[]>} */
const comandos = computed(() => [
  ...permitidas.value.map((entrada) => ({
    titulo: entrada.titulo,
    icono: entrada.icono,
    ejecutar: () => enrutador.push(entrada.destino),
  })),
  { titulo: 'Mi cuenta', icono: iconos.cuenta, ejecutar: () => enrutador.push({ name: 'mi-cuenta' }) },
  { titulo: 'Cerrar sesión', icono: iconos.salir, ejecutar: () => sesion.cerrar() },
])

const resultados = computed(() => {
  const texto = normalizar(consulta.value)
  const coincidencias = comandos.value.filter((comando) => normalizar(comando.titulo).includes(texto))
  // Un código (INC-000605 o 605) ofrece abrir ese incidente, primero (RF-F10).
  const id = idDeCodigo(consulta.value)
  if (id === null || !sesion.tieneAlguno('ver_propios', 'ver_todos')) {
    return coincidencias
  }
  const abrir = {
    titulo: `Abrir ${codigoDe(id)}`,
    icono: iconos.incidente,
    ejecutar: () => enrutador.push({ name: 'incidente', params: { id } }),
  }
  return [abrir, ...coincidencias]
})

/**
 * @param {string} texto
 * @returns {string} sin mayúsculas ni tildes, para que «auditoria» encuentre «Auditoría»
 */
function normalizar(texto) {
  return texto
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .toLowerCase()
    .trim()
}

/**
 * @param {Comando | undefined} comando
 * @returns {void}
 */
function ejecutar(comando) {
  if (!comando) {
    return
  }
  abierta.value = false
  comando.ejecutar()
}

watch(abierta, (valor) => {
  if (valor) {
    consulta.value = ''
  }
})
</script>

<template>
  <v-dialog v-model="abierta" max-width="560" scrim="rgba(16,24,40,.35)" aria-label="Buscar o ir a">
    <v-card class="paleta">
      <v-text-field
        v-model="consulta"
        autofocus
        variant="plain"
        density="comfortable"
        hide-details
        placeholder="Buscar pantallas o acciones…"
        aria-label="Buscar pantallas o acciones"
        :prepend-inner-icon="iconos.buscar"
        class="paleta__busqueda"
        @keydown.enter.prevent="ejecutar(resultados[0])"
      />
      <v-divider />
      <v-list max-height="320" class="pa-2">
        <v-list-subheader>Ir a</v-list-subheader>
        <v-list-item
          v-for="comando in resultados"
          :key="comando.titulo"
          :prepend-icon="comando.icono"
          :title="comando.titulo"
          @click="ejecutar(comando)"
        />
        <div v-if="!resultados.length" class="paleta__vacio">Sin coincidencias.</div>
      </v-list>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.paleta {
  border: 1px solid var(--borde);
  box-shadow: var(--sombra-2) !important;
}

.paleta__busqueda {
  padding: 4px 12px;
}

.paleta__vacio {
  padding: 20px;
  color: var(--suave);
  text-align: center;
}
</style>
