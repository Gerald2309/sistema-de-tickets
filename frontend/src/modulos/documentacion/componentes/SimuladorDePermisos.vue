<script setup>
import { computed, reactive } from 'vue'
import ControlSegmentado from '@/compartido/componentes/ControlSegmentado.vue'
import { iconos } from '@/compartido/iconos.js'
import { ACCIONES, evaluar } from '../permisos.js'
import { enlaceAlCodigo } from '../repositorio.js'
import TextoTecnico from './TextoTecnico.vue'

/**
 * Qué responde la API a una persona que intenta algo sobre un incidente, capa por capa. Es la misma regla que
 * aplica el backend (permisos.js), con los roles de ejemplo; no llama a la API.
 */
const situacion = reactive({ rol: 'solicitante', accion: 'ver', creador: 'otra', asignado: 'nadie' })

const ROLES = [
  { valor: 'administrador', etiqueta: 'Administrador' },
  { valor: 'soporte', etiqueta: 'Soporte' },
  { valor: 'solicitante', etiqueta: 'Solicitante' },
  { valor: 'sinSesion', etiqueta: 'Sin sesión' },
]
const CREADORES = [
  { valor: 'yo', etiqueta: 'Esta persona' },
  { valor: 'otra', etiqueta: 'Otra persona' },
]
const ASIGNADOS = [
  { valor: 'nadie', etiqueta: 'Nadie' },
  { valor: 'yo', etiqueta: 'Esta persona' },
  { valor: 'otra', etiqueta: 'Otra persona' },
]
const OPCIONES_DE_ACCION = Object.entries(ACCIONES).map(([valor, { titulo }]) => ({ valor, etiqueta: titulo }))

const resultado = computed(() => evaluar(/** @type {import('../permisos.js').Situacion} */ ({ ...situacion })))
const ruta = computed(() => ACCIONES[/** @type {keyof typeof ACCIONES} */ (situacion.accion)].ruta)
const exito = computed(() => resultado.value.estado < 400)
</script>

<template>
  <div class="simulador">
    <div class="controles">
      <span class="controles__rotulo">Quién lo intenta</span>
      <ControlSegmentado v-model="situacion.rol" :opciones="ROLES" etiqueta="Quién lo intenta" />
      <span class="controles__rotulo">Qué intenta</span>
      <v-select
        v-model="situacion.accion"
        :items="OPCIONES_DE_ACCION"
        item-title="etiqueta"
        item-value="valor"
        hide-details
        density="compact"
        aria-label="Qué intenta"
      />
      <template v-if="situacion.accion !== 'auditoria'">
        <span class="controles__rotulo">Quién reportó el incidente</span>
        <ControlSegmentado v-model="situacion.creador" :opciones="CREADORES" etiqueta="Quién reportó el incidente" />
        <span class="controles__rotulo">Quién lo atiende</span>
        <ControlSegmentado v-model="situacion.asignado" :opciones="ASIGNADOS" etiqueta="Quién lo atiende" />
      </template>
    </div>

    <div class="resultado" aria-live="polite">
      <p class="peticion">
        <code>{{ ruta }}</code>
      </p>
      <ol class="capas">
        <li v-for="(capa, i) in resultado.capas" :key="capa.nombre" class="capa" :class="`capa--${capa.resultado}`">
          <span class="capa__numero num">{{ i + 1 }}</span>
          <div>
            <b>{{ capa.nombre }}</b>
            <p class="capa__motivo"><TextoTecnico :texto="capa.motivo" /></p>
            <a
              v-if="capa.archivo && enlaceAlCodigo(capa.archivo)"
              :href="enlaceAlCodigo(capa.archivo) ?? undefined"
              target="_blank"
              rel="noopener"
              class="capa__archivo"
              >{{ capa.archivo }} <v-icon :icon="iconos.abrirAparte" size="12"
            /></a>
          </div>
          <span class="capa__marca">{{
            capa.resultado === 'pasa' ? '✓' : capa.resultado === 'falla' ? '✗' : '—'
          }}</span>
        </li>
      </ol>
      <p class="veredicto" :class="exito ? 'veredicto--exito' : 'veredicto--rechazo'">
        <b class="num">{{ resultado.estado }}</b> · {{ resultado.explicacion }}
      </p>
    </div>
  </div>
</template>

<style scoped>
.simulador {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr);
}

.controles {
  display: grid;
  gap: 8px;
  align-content: start;
  justify-items: start;
  padding: 16px;
  border-right: 1px solid var(--borde);
}

.controles :deep(.v-select) {
  width: 100%;
}

.controles__rotulo {
  margin-top: 6px;
  color: var(--suave);
  font-size: 13px;
}

.resultado {
  padding: 12px 0;
}

.peticion {
  margin: 0;
  padding: 0 16px 10px;
}

.peticion code {
  font: 600 12.5px var(--mono);
}

.capas {
  margin: 0;
  padding: 0;
  list-style: none;
}

.capa {
  display: grid;
  grid-template-columns: 26px 1fr auto;
  gap: 10px;
  align-items: start;
  padding: 10px 16px;
  font-size: 13.5px;
  border-top: 1px solid var(--borde);
}

.capa__numero {
  display: grid;
  place-items: center;
  width: 22px;
  height: 22px;
  font-size: 11px;
  font-weight: 600;
  background: var(--resalte);
  border-radius: 50%;
}

.capa__motivo {
  margin: 2px 0 0;
  color: var(--suave);
}

.capa__archivo {
  color: var(--acento);
  font: 11.5px var(--mono);
  text-decoration: none;
  overflow-wrap: anywhere;
}

.capa__marca {
  font-weight: 700;
}

.capa--pasa .capa__marca {
  color: var(--exito-texto);
}

.capa--falla .capa__marca {
  color: var(--error);
}

.capa--no-llega {
  opacity: 0.45;
}

.veredicto {
  margin: 12px 16px 4px;
  padding: 12px 14px;
  font-size: 14px;
  border-radius: var(--radio);
}

.veredicto--exito {
  color: var(--exito-texto);
  background: var(--exito-fondo);
  border: 1px solid var(--exito-borde);
}

.veredicto--rechazo {
  color: var(--error);
  background: var(--error-fondo);
  border: 1px solid var(--error-borde);
}

@media (width <= 900px) {
  .simulador {
    grid-template-columns: 1fr;
  }

  .controles {
    border-right: 0;
    border-bottom: 1px solid var(--borde);
  }
}
</style>
