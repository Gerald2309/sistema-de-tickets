<script setup>
import { iconos } from '@/compartido/iconos.js'

/**
 * Scalar dentro de la página. Lo sirve el backend en el mismo origen (/api/v1/documentacion), con su propia
 * política de seguridad: solo puede incrustarlo una página de este mismo sitio (frame-ancestors 'self').
 */
const DIRECCION = '/api/v1/documentacion'
</script>

<template>
  <div>
    <div class="aviso">
      <span>Para enviar peticiones: pida un token con <code>POST /auth/login</code> y péguelo como Bearer.</span>
      <a :href="DIRECCION" target="_blank" rel="noopener" class="enlace-sutil">
        Abrir aparte <v-icon :icon="iconos.abrirAparte" size="14" />
      </a>
    </div>
    <iframe :src="DIRECCION" title="Referencia de la API (Scalar)" class="visor" loading="lazy" />
  </div>
</template>

<style scoped>
.aviso {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  color: var(--suave);
  font-size: 13px;
  background: var(--sutil);
  border-bottom: 1px solid var(--borde);
}

.visor {
  display: block;
  width: 100%;
  height: calc(100vh - 160px);
  min-height: 560px;
  border: 0;
}
</style>
