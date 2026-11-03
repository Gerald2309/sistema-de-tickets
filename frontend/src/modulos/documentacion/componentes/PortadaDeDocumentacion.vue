<script setup>
import { useRouter } from 'vue-router'
import ContadorAnimado from '@/compartido/componentes/ContadorAnimado.vue'
import { iconos } from '@/compartido/iconos.js'
import { PAGINAS } from '../contenido/index.js'
import { MAPA } from '../contenido/inicio.js'
import PlanoTecnico from './PlanoTecnico.vue'

/**
 * Portada de la documentación: qué es el sistema, sus cifras, un mapa navegable de las secciones y lo que hace,
 * con pequeñas animaciones. Las cifras salen del código (rutas de la API y pruebas) y del propio contenido.
 */
defineEmits(['buscar'])
const enrutador = useRouter()

const TECNOLOGIAS = ['Laravel 13', 'PHP 8.5', 'Vue 3', 'Vuetify 4', 'MySQL 8.4', 'Redis', 'Reverb', 'Docker']
const CIFRAS = [
  { valor: 74, rotulo: 'endpoints de la API', pagina: 'api' },
  { valor: 744, rotulo: 'pruebas automáticas', pagina: 'calidad' },
  { valor: PAGINAS.length, rotulo: 'páginas de documentación', pagina: 'arquitectura' },
  { valor: 4, rotulo: 'módulos con fronteras vigiladas', pagina: 'backend' },
]
const CAPACIDADES = [
  {
    titulo: 'Plazos que se vigilan solos',
    texto: 'Cada empresa con su acuerdo por prioridad. El plazo se consume y el sistema sabe cuándo algo vence.',
    pagina: 'incidentes',
    animacion: 'plazo',
  },
  {
    titulo: 'Reglas que actúan solas',
    texto: 'Un disparador, condiciones con sí y no, y acciones. Una sola vez por incidente.',
    pagina: 'automatizaciones',
    animacion: 'flujo',
  },
  {
    titulo: 'Todo en tiempo real',
    texto: 'Las pantallas y la campanita se actualizan al instante, con respaldo si cae la conexión.',
    pagina: 'tiempo-real',
    animacion: 'aviso',
  },
  {
    titulo: 'Cada quien ve lo suyo',
    texto: 'Permisos por rol, por incidente y por campo. Lo ajeno responde 404, como si no existiera.',
    pagina: 'seguridad',
    animacion: 'permisos',
  },
]

/** @param {string} pagina */
const ir = (pagina) => enrutador.push({ name: 'documentacion', params: { pagina } })
</script>

<template>
  <div class="portada">
    <section class="heroe">
      <span class="heroe__pastilla"><span class="heroe__punto" />Documentación técnica</span>
      <h1 class="heroe__titulo">Mesa de ayuda</h1>
      <p class="heroe__texto">
        Gestión de incidentes con plazos de servicio por empresa, atención por niveles, reglas automáticas y avisos en
        tiempo real. Cada pantalla, cada capa y cada decisión, explicadas con el código a un clic.
      </p>
      <button type="button" class="heroe__busqueda" @click="$emit('buscar')">
        <v-icon :icon="iconos.buscar" size="20" />
        <span>Busque una clase, una tabla, un endpoint o un concepto…</span>
        <kbd>Ctrl K</kbd>
      </button>
      <div class="heroe__acciones">
        <v-btn color="primary" variant="flat" @click="ir('arquitectura')">Ver la arquitectura</v-btn>
        <v-btn variant="outlined" @click="ir('recorrido')">Seguir un incidente</v-btn>
        <v-btn variant="text" color="primary" @click="ir('api')">Probar la API</v-btn>
      </div>
      <ul class="heroe__tecnologias" aria-label="Tecnologías">
        <li v-for="tecnologia in TECNOLOGIAS" :key="tecnologia">{{ tecnologia }}</li>
      </ul>
    </section>

    <section class="cifras" aria-label="El proyecto en cifras">
      <button v-for="cifra in CIFRAS" :key="cifra.rotulo" type="button" class="cifra" @click="ir(cifra.pagina)">
        <ContadorAnimado :valor="cifra.valor" class="cifra__valor num" />
        <span class="cifra__rotulo">{{ cifra.rotulo }}</span>
      </button>
    </section>

    <section class="tarjeta mapa" aria-labelledby="titulo-mapa">
      <header class="tarjeta__cabecera">
        <h2 id="titulo-mapa" class="tarjeta__titulo">Mapa del sistema</h2>
        <span class="mapa__ayuda">Pase el cursor para ver las conexiones; toque una pieza para abrirla</span>
      </header>
      <PlanoTecnico :plano="MAPA" @elegir="ir" />
    </section>

    <section class="capacidades" aria-label="Qué hace">
      <button
        v-for="capacidad in CAPACIDADES"
        :key="capacidad.titulo"
        type="button"
        class="capacidad"
        @click="ir(capacidad.pagina)"
      >
        <div class="escena" :class="`escena--${capacidad.animacion}`" aria-hidden="true">
          <template v-if="capacidad.animacion === 'plazo'">
            <span class="escena__etiqueta">INC-000042 · respuesta</span>
            <span class="escena__riel"><span class="escena__barra" /></span>
          </template>
          <template v-else-if="capacidad.animacion === 'flujo'">
            <span class="escena__nodo">Cuando</span><span class="escena__linea" /><span class="escena__nodo">Si</span
            ><span class="escena__linea" /><span class="escena__nodo escena__nodo--accion">Avisar</span>
          </template>
          <template v-else-if="capacidad.animacion === 'aviso'">
            <span class="escena__campana"><v-icon :icon="iconos.avisos" size="22" /><span class="escena__onda" /></span>
            <span class="escena__mensaje">Críticos a punto de vencer</span>
          </template>
          <template v-else>
            <span class="escena__codigo escena__codigo--ok">200</span>
            <span class="escena__codigo escena__codigo--no">403</span>
            <span class="escena__codigo escena__codigo--oculto">404</span>
          </template>
        </div>
        <b class="capacidad__titulo">{{ capacidad.titulo }}</b>
        <span class="capacidad__texto">{{ capacidad.texto }}</span>
        <span class="capacidad__enlace">Ver cómo funciona <v-icon :icon="iconos.flecha" size="14" /></span>
      </button>
    </section>
  </div>
</template>

<style scoped>
.portada {
  display: grid;
  gap: 20px;
  margin-bottom: 20px;
}

/* Portada con papel de plano: cuadrícula suave y la franja de la marca arriba. */
.heroe {
  position: relative;
  overflow: hidden;
  padding: 40px 36px 32px;
  background-color: var(--superficie);
  background-image:
    linear-gradient(var(--sutil) 1px, transparent 1px), linear-gradient(90deg, var(--sutil) 1px, transparent 1px),
    linear-gradient(var(--resalte) 1px, transparent 1px), linear-gradient(90deg, var(--resalte) 1px, transparent 1px);
  background-size:
    10px 10px,
    10px 10px,
    50px 50px,
    50px 50px;
  border: 1px solid var(--borde);
  border-radius: var(--radio);
}

.heroe::before {
  position: absolute;
  top: 0;
  right: 0;
  left: 0;
  height: 3px;
  background: var(--degradado);
  content: '';
}

.heroe__pastilla {
  display: inline-flex;
  gap: 8px;
  align-items: center;
  padding: 4px 12px;
  color: var(--suave);
  font-size: 12.5px;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: 99px;
}

.heroe__punto {
  width: 7px;
  height: 7px;
  background: var(--estado-resuelto);
  border-radius: 50%;
  animation: latir 2s infinite;
}

.heroe__titulo {
  margin: 14px 0 10px;
  font-size: clamp(34px, 5vw, 52px);
  font-weight: 800;
  line-height: 1.05;
  letter-spacing: -0.03em;
}

.heroe__texto {
  max-width: 680px;
  margin: 0 0 22px;
  color: var(--suave);
  font-size: 16px;
  line-height: 1.6;
}

.heroe__busqueda {
  display: flex;
  gap: 10px;
  align-items: center;
  width: min(640px, 100%);
  height: 48px;
  padding: 0 14px;
  color: var(--suave);
  font: 15px var(--fuente);
  background: var(--superficie);
  border: 1px solid var(--borde-fuerte);
  border-radius: var(--radio);
  box-shadow: var(--sombra-1);
  cursor: text;
  transition: border-color var(--rapida);
}

.heroe__busqueda:hover {
  border-color: var(--acento);
}

.heroe__busqueda kbd {
  margin-left: auto;
  padding: 2px 6px;
  font: 500 11px var(--mono);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
}

.heroe__acciones {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 16px;
}

.heroe__tecnologias {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 24px 0 0;
  padding: 0;
  list-style: none;
}

.heroe__tecnologias li {
  padding: 3px 10px;
  color: var(--acento-texto);
  font: 500 12px var(--mono);
  background: var(--acento-fondo);
  border: 1px solid var(--acento-borde);
  border-radius: var(--radio-chip);
}

.cifras {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.cifra {
  display: grid;
  gap: 2px;
  padding: 16px 18px;
  text-align: left;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
  cursor: pointer;
  transition:
    border-color var(--rapida),
    box-shadow var(--rapida);
}

.cifra:hover {
  border-color: var(--acento-borde);
  box-shadow: var(--sombra-1);
}

.cifra__valor {
  font-size: 30px;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.cifra__rotulo {
  color: var(--suave);
  font-size: 13px;
}

.mapa__ayuda {
  color: var(--tenue);
  font-size: 12.5px;
}

.capacidades {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.capacidad {
  display: grid;
  gap: 6px;
  align-content: start;
  padding: 0 0 16px;
  overflow: hidden;
  color: var(--texto);
  font: inherit;
  text-align: left;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
  cursor: pointer;
  transition:
    border-color var(--rapida),
    box-shadow var(--rapida),
    transform var(--rapida);
}

.capacidad:hover {
  border-color: var(--acento-borde);
  box-shadow: var(--sombra-2);
  transform: translateY(-2px);
}

.capacidad__titulo,
.capacidad__texto,
.capacidad__enlace {
  padding: 0 16px;
}

.capacidad__titulo {
  margin-top: 10px;
  font-size: 15px;
}

.capacidad__texto {
  color: var(--suave);
  font-size: 13.5px;
  line-height: 1.5;
}

.capacidad__enlace {
  display: inline-flex;
  gap: 4px;
  align-items: center;
  margin-top: 4px;
  color: var(--acento);
  font-size: 13px;
  font-weight: 600;
}

/* Escenas: una pieza viva de la aplicación en cada tarjeta. */
.escena {
  display: flex;
  gap: 8px;
  align-items: center;
  justify-content: center;
  height: 96px;
  padding: 0 16px;
  background: var(--sutil);
  border-bottom: 1px solid var(--borde);
}

.escena--plazo {
  flex-direction: column;
  align-items: stretch;
}

.escena__etiqueta {
  color: var(--tenue);
  font: 11px var(--mono);
}

.escena__riel {
  height: 8px;
  overflow: hidden;
  background: var(--resalte);
  border-radius: 8px;
}

.escena__barra {
  display: block;
  height: 100%;
  border-radius: 8px;
  animation: consumir 4.5s ease-in-out infinite;
}

.escena__nodo {
  padding: 5px 9px;
  font-size: 12px;
  background: var(--superficie);
  border: 1px solid var(--borde-fuerte);
  border-radius: var(--radio);
}

.escena__nodo--accion {
  animation: encender 3s infinite;
}

.escena__linea {
  position: relative;
  width: 22px;
  height: 2px;
  overflow: hidden;
  background: var(--borde-fuerte);
}

.escena__linea::after {
  position: absolute;
  inset: 0;
  width: 50%;
  background: var(--acento);
  animation: recorrer 1.5s linear infinite;
  content: '';
}

.escena__campana {
  position: relative;
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  color: var(--acento);
  background: var(--superficie);
  border: 1px solid var(--acento-borde);
  border-radius: 50%;
}

.escena__onda {
  position: absolute;
  inset: -1px;
  border: 2px solid var(--acento);
  border-radius: 50%;
  animation: onda 2s ease-out infinite;
}

.escena__mensaje {
  padding: 6px 10px;
  font-size: 12px;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
  animation: llegar 4s ease-out infinite;
}

.escena__codigo {
  padding: 4px 10px;
  font: 700 13px var(--mono);
  border-radius: var(--radio-chip);
  animation: turno 4.5s infinite;
}

.escena__codigo--ok {
  color: var(--exito-texto);
  background: var(--exito-fondo);
}

.escena__codigo--no {
  color: var(--estado-en-progreso-texto);
  background: var(--estado-en-progreso-fondo);
  animation-delay: 1.5s;
}

.escena__codigo--oculto {
  color: var(--estado-cerrado-texto);
  background: var(--estado-cerrado-fondo);
  animation-delay: 3s;
}

@keyframes consumir {
  0% {
    width: 5%;
    background: var(--estado-abierto);
  }

  60% {
    width: 82%;
    background: var(--prioridad-media);
  }

  85%,
  100% {
    width: 100%;
    background: var(--error);
  }
}

@keyframes recorrer {
  from {
    transform: translateX(-100%);
  }

  to {
    transform: translateX(200%);
  }
}

@keyframes encender {
  0%,
  55% {
    border-color: var(--borde-fuerte);
    box-shadow: none;
  }

  70%,
  100% {
    border-color: var(--acento);
    box-shadow: 0 0 0 3px var(--acento-fondo);
  }
}

@keyframes onda {
  from {
    opacity: 0.8;
    transform: scale(1);
  }

  to {
    opacity: 0;
    transform: scale(1.6);
  }
}

@keyframes llegar {
  0%,
  10% {
    opacity: 0;
    transform: translateY(-6px);
  }

  25%,
  90% {
    opacity: 1;
    transform: none;
  }

  100% {
    opacity: 0;
  }
}

@keyframes turno {
  0%,
  100% {
    opacity: 0.35;
    transform: none;
  }

  15%,
  30% {
    opacity: 1;
    transform: translateY(-3px);
  }
}

@keyframes latir {
  50% {
    opacity: 0.4;
  }
}

@media (prefers-reduced-motion: reduce) {
  .heroe__punto,
  .escena__barra,
  .escena__nodo--accion,
  .escena__linea::after,
  .escena__onda,
  .escena__mensaje,
  .escena__codigo {
    animation: none;
  }

  .escena__barra {
    width: 82%;
    background: var(--prioridad-media);
  }
}

@media (width <= 1100px) {
  .cifras,
  .capacidades {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (width <= 600px) {
  .heroe {
    padding: 28px 20px 24px;
  }

  .cifras,
  .capacidades {
    grid-template-columns: 1fr;
  }
}
</style>
