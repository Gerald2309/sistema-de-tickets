<script setup>
import { storeToRefs } from 'pinia'
import { computed } from 'vue'
import ListaPropiedades from '@/compartido/componentes/ListaPropiedades.vue'
import { fechaHora } from '@/compartido/presentacion/fechas.js'
import FormularioDeContrasena from '../componentes/FormularioDeContrasena.vue'
import ListaDeSesiones from '../componentes/ListaDeSesiones.vue'
import { useSesion } from '../tienda/sesion.js'

const { perfil } = storeToRefs(useSesion())

// El inicio anterior al de esta sesión: si no lo reconoce, puede cerrar sesiones y cambiar la contraseña.
const inicioAnterior = computed(() => {
  const anterior = perfil.value?.inicioAnterior
  return anterior
    ? `${fechaHora(anterior.en)}${anterior.ip ? ` · IP ${anterior.ip}` : ''}`
    : 'Este es su primer ingreso'
})
</script>

<template>
  <div class="pagina pagina--angosta">
    <header class="cabecera">
      <div>
        <h1 v-revelar class="titulo-pagina">Mi cuenta</h1>
        <p class="subtitulo entra" style="--i: 2">
          Sus datos los administra Psicol; aquí puede revisar sus sesiones y cambiar su contraseña.
        </p>
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
              { etiqueta: 'Ingreso anterior', valor: inicioAnterior, tenue: !perfil.inicioAnterior },
            ]"
          />
        </section>

        <section class="seccion">
          <div>
            <h2 class="seccion__titulo">Sesiones abiertas</h2>
            <p class="seccion__ayuda">Si no reconoce alguna, ciérrela y cambie su contraseña.</p>
          </div>
          <ListaDeSesiones />
        </section>

        <section class="seccion seccion--ultima">
          <div>
            <h2 class="seccion__titulo">Contraseña</h2>
            <p class="seccion__ayuda">Al cambiarla se cerrarán sus demás sesiones abiertas.</p>
          </div>
          <FormularioDeContrasena />
        </section>
      </div>
    </div>
  </div>
</template>
