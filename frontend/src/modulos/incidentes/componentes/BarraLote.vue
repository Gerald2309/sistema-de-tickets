<script setup>
import { ref } from 'vue'
import { iconos } from '@/compartido/iconos.js'
import { CATEGORIAS, ESTADOS, PRIORIDADES } from '../presentacion.js'
import DialogoResolver from './DialogoResolver.vue'
import MenuReasignar from './MenuReasignar.vue'

/**
 * Barra de acciones en lote (RF-F24): se despliega al seleccionar filas y ofrece solo lo que los permisos
 * permiten. Cambiar a Resuelto pide la solución antes de enviar.
 */
const props = defineProps({
  cantidad: { type: Number, required: true },
  permite: {
    type: /** @type {import('vue').PropType<{ estado: boolean, reasignar: boolean, clasificar: boolean, eliminar: boolean }>} */ (
      Object
    ),
    required: true,
  },
  pendiente: { type: Boolean, default: false },
  maximo: { type: Number, required: true },
})
const emit = defineEmits(['aplicar', 'eliminar', 'quitar'])

const resolviendo = ref(false)

/** @param {string} estado */
function cambiarEstado(estado) {
  if (estado === 'resuelto') {
    resolviendo.value = true
    return
  }
  emit('aplicar', { estado })
}

/** @param {string} solucion */
function resolver(solucion) {
  resolviendo.value = false
  emit('aplicar', { estado: 'resuelto', solucion })
}

/** @param {number} id */
function reasignar(id) {
  emit('aplicar', { asignadoId: id })
}

const excedido = () => props.cantidad > props.maximo
</script>

<template>
  <div class="plegable" :class="{ 'plegable--abierta': cantidad > 0 }" :inert="cantidad === 0 || undefined">
    <div>
      <div class="lote" role="region" aria-label="Acciones en lote">
        <b aria-live="polite">{{ cantidad }} {{ cantidad === 1 ? 'seleccionado' : 'seleccionados' }}</b>
        <span v-if="excedido()" class="lote__aviso">Máximo {{ maximo }} a la vez</span>

        <v-menu v-if="permite.estado">
          <template #activator="{ props: activador }">
            <v-btn
              v-bind="activador"
              size="small"
              variant="outlined"
              class="lote__boton"
              :prepend-icon="iconos.cambiarEstado"
              :disabled="pendiente || excedido()"
            >
              Cambiar estado
            </v-btn>
          </template>
          <v-list min-width="200">
            <v-list-subheader>Pasar a</v-list-subheader>
            <v-list-item
              v-for="(estado, valor) in ESTADOS"
              :key="valor"
              :title="estado.nombre"
              @click="cambiarEstado(valor)"
            >
              <template #prepend><span class="punto" :style="{ background: estado.color }" /></template>
            </v-list-item>
          </v-list>
        </v-menu>

        <v-menu v-if="permite.reasignar" :close-on-content-click="false">
          <template #activator="{ props: activador }">
            <v-btn
              v-bind="activador"
              size="small"
              variant="outlined"
              class="lote__boton"
              :prepend-icon="iconos.reasignar"
              :disabled="pendiente || excedido()"
            >
              Reasignar
            </v-btn>
          </template>
          <MenuReasignar @elegir="reasignar" />
        </v-menu>

        <template v-if="permite.clasificar">
          <v-menu>
            <template #activator="{ props: activador }">
              <v-btn
                v-bind="activador"
                size="small"
                variant="outlined"
                class="lote__boton"
                :disabled="pendiente || excedido()"
                >Prioridad</v-btn
              >
            </template>
            <v-list min-width="180">
              <v-list-item
                v-for="(prioridad, valor) in PRIORIDADES"
                :key="valor"
                :title="prioridad.nombre"
                @click="emit('aplicar', { prioridad: valor })"
              >
                <template #prepend
                  ><v-icon :icon="prioridad.icono" size="16" :style="{ color: prioridad.color }" class="mr-3"
                /></template>
              </v-list-item>
            </v-list>
          </v-menu>
          <v-menu>
            <template #activator="{ props: activador }">
              <v-btn
                v-bind="activador"
                size="small"
                variant="outlined"
                class="lote__boton"
                :prepend-icon="iconos.categoria"
                :disabled="pendiente || excedido()"
              >
                Categoría
              </v-btn>
            </template>
            <v-list min-width="200">
              <v-list-item
                v-for="(nombre, valor) in CATEGORIAS"
                :key="valor"
                :title="nombre"
                @click="emit('aplicar', { categoria: valor })"
              />
            </v-list>
          </v-menu>
        </template>

        <v-btn
          v-if="permite.eliminar"
          size="small"
          variant="outlined"
          color="error"
          class="lote__boton"
          :prepend-icon="iconos.eliminar"
          :disabled="pendiente || excedido()"
          @click="emit('eliminar')"
        >
          Eliminar
        </v-btn>

        <v-progress-circular
          v-if="pendiente"
          indeterminate
          size="18"
          width="2"
          color="primary"
          aria-label="Aplicando cambios"
        />
        <v-btn size="small" variant="text" class="ml-auto" @click="emit('quitar')">Quitar selección</v-btn>
      </div>
    </div>
  </div>

  <DialogoResolver v-model="resolviendo" :cantidad="cantidad" :pendiente="pendiente" @confirmar="resolver" />
</template>

<style scoped>
/* Se despliega animando la altura real con una rejilla (sistema de diseño, Barra de selección). */
.plegable {
  display: grid;
  grid-template-rows: 0fr;
  transition:
    grid-template-rows var(--media) var(--curva),
    margin var(--media) var(--curva);
}

.plegable--abierta {
  grid-template-rows: 1fr;
  margin-bottom: 12px;
}

.plegable > div {
  min-height: 0;
  overflow: hidden;
}

.lote {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  padding: 8px 12px;
  color: var(--acento-texto);
  font-size: 13.5px;
  background: var(--acento-fondo);
  border: 1px solid var(--acento-borde);
  border-radius: var(--radio);
}

.lote__boton {
  background: var(--superficie);
}

.lote__aviso {
  color: var(--error);
  font-weight: 500;
}

.punto {
  width: 8px;
  height: 8px;
  margin-right: 12px;
  border-radius: 50%;
}
</style>
