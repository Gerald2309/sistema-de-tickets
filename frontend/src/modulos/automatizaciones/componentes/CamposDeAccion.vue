<script setup>
import { CATEGORIAS, PRIORIDADES } from '@/modulos/incidentes/index.js'
import CampoDeMensaje from './CampoDeMensaje.vue'
import SelectorDePersona from './SelectorDePersona.vue'
import SelectorDeRol from './SelectorDeRol.vue'

/** Los datos de un paso «Entonces»: cada tipo de acción pide lo suyo. */
defineProps({
  paso: { type: /** @type {import('vue').PropType<import('../tipos.js').Accion>} */ (Object), required: true },
})
const emit = defineEmits({ cambiar: (/** @type {Partial<import('../tipos.js').Accion>} */ cambios) => !!cambios })

const prioridades = Object.entries(PRIORIDADES).map(([valor, { nombre }]) => ({ valor, nombre }))
const categorias = Object.entries(CATEGORIAS).map(([valor, nombre]) => ({ valor, nombre }))
</script>

<template>
  <div>
    <SelectorDePersona
      v-if="paso.accion === 'asignar'"
      etiqueta="Persona de soporte"
      para-asignar
      :model-value="paso.usuario_id ?? null"
      @update:model-value="emit('cambiar', { usuario_id: $event })"
    />

    <template v-else-if="paso.accion === 'avisar'">
      <v-btn-toggle
        :model-value="paso.destinatario"
        mandatory
        density="comfortable"
        variant="outlined"
        divided
        class="mb-4"
        aria-label="A quién avisar"
        @update:model-value="emit('cambiar', { destinatario: $event, id: null })"
      >
        <v-btn value="rol">A un rol</v-btn>
        <v-btn value="usuario">A una persona</v-btn>
      </v-btn-toggle>
      <SelectorDeRol
        v-if="paso.destinatario === 'rol'"
        etiqueta="Rol"
        :model-value="paso.id ?? null"
        @update:model-value="emit('cambiar', { id: $event })"
      />
      <SelectorDePersona
        v-else
        etiqueta="Persona"
        :model-value="paso.id ?? null"
        @update:model-value="emit('cambiar', { id: $event })"
      />
    </template>

    <v-select
      v-else-if="paso.accion === 'cambiar_prioridad'"
      label="Nueva prioridad"
      :items="prioridades"
      item-title="nombre"
      item-value="valor"
      :model-value="paso.prioridad"
      @update:model-value="emit('cambiar', { prioridad: $event })"
    />

    <v-select
      v-else-if="paso.accion === 'cambiar_categoria'"
      label="Nueva categoría"
      :items="categorias"
      item-title="nombre"
      item-value="valor"
      :model-value="paso.categoria"
      @update:model-value="emit('cambiar', { categoria: $event })"
    />

    <CampoDeMensaje
      v-else-if="paso.accion === 'nota_interna'"
      etiqueta="Texto de la nota"
      etiqueta-de-la-vista="Así se verá la nota"
      :model-value="paso.texto ?? ''"
      @update:model-value="emit('cambiar', { texto: $event })"
    />

    <CampoDeMensaje
      v-else
      :etiqueta="paso.accion === 'cerrar' ? 'Mensaje de cierre' : 'Mensaje'"
      :model-value="paso.mensaje ?? ''"
      @update:model-value="emit('cambiar', { mensaje: $event })"
    />
  </div>
</template>
