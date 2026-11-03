import { iconos } from '@/compartido/iconos.js'

/** @type {import('../tipos.js').Plano} */
const difusion = {
  codigo: 'T-01',
  titulo: 'Difusión de cambios',
  nivel: 'C4 · 4',
  ancho: 1000,
  alto: 420,
  nodos: [
    {
      id: 'accion',
      x: 40,
      y: 50,
      w: 220,
      h: 62,
      tipo: 'Acciones',
      titulo: 'Cualquier cambio',
      sub: 'dentro de la transacción',
    },
    {
      id: 'avisar',
      x: 320,
      y: 50,
      w: 240,
      h: 62,
      tipo: 'AvisarCambios',
      titulo: 'Junta los cambios',
      sub: 'DB::afterCommit',
      clave: true,
    },
    { id: 'redis', x: 620, y: 50, w: 160, h: 62, tipo: 'Redis', titulo: 'Cola' },
    { id: 'cola', x: 820, y: 50, w: 150, h: 62, tipo: 'cola', titulo: 'queue:work' },
    { id: 'reverb', x: 780, y: 200, w: 190, h: 62, tipo: 'Reverb', titulo: 'WebSocket', sub: 'canales privados' },
    {
      id: 'auth',
      x: 420,
      y: 200,
      w: 280,
      h: 62,
      tipo: 'broadcasting/auth',
      titulo: 'Autoriza el canal',
      sub: 'mismo token Bearer',
    },
    {
      id: 'echo',
      x: 40,
      y: 200,
      w: 300,
      h: 62,
      tipo: 'plugins/tiempo-real.js',
      titulo: 'Echo escucha',
      sub: 'incidentes · usuario.{id}',
    },
    {
      id: 'cache',
      x: 40,
      y: 320,
      w: 300,
      h: 62,
      tipo: 'vue-query',
      titulo: 'Invalida claves',
      sub: 'pide solo lo que está en pantalla',
    },
    {
      id: 'respaldo',
      x: 420,
      y: 320,
      w: 280,
      h: 62,
      tipo: 'compartido/tiempo-real.js',
      titulo: 'Refresco de respaldo',
      sub: 'si no hay WebSocket',
    },
  ],
  aristas: [
    { de: 'accion', a: 'avisar', texto: 'registrar()' },
    { de: 'avisar', a: 'redis', texto: 'tras el commit' },
    { de: 'redis', a: 'cola' },
    { de: 'cola', a: 'reverb', texto: 'emite' },
    { de: 'echo', a: 'auth', texto: 'suscribirse' },
    { de: 'reverb', a: 'echo', modo: 'h', mx: 760 },
    { de: 'echo', a: 'cache' },
    { de: 'respaldo', a: 'cache', punteada: true },
  ],
}

/** @type {import('../tipos.js').Pagina} */
export const tiempoReal = {
  id: 'tiempo-real',
  titulo: 'Tiempo real y colas',
  grupo: 'tecnico',
  icono: iconos.avisos,
  entradilla:
    'Las pantallas se actualizan solas con WebSocket (Laravel Reverb) y lo que no necesita responder en el momento corre en la cola (Redis). Nada sale antes de confirmar la transacción.',
  secciones: [
    {
      titulo: 'Cómo viaja un cambio',
      extra: 'plano T-01',
      bloques: [
        { tipo: 'plano', plano: difusion },
        {
          tipo: 'nota',
          texto:
            '`AvisarCambios` junta los cambios de una petición y los envía en un solo mensaje cuando se confirma la transacción más externa: un lote de cien cambios produce un aviso, no cien. Si la transacción se revierte, no se avisa nada.',
        },
      ],
    },
    {
      titulo: 'Canales y eventos',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'private-incidentes',
              'Para quien ve todos los incidentes: `incidentes.cambiados` con los ids y el tipo de cambio.',
            ],
            [
              'private-usuario.{id}',
              'El canal personal: `incidentes.cambiados` para quien solo ve lo suyo, y `aviso.nuevo` para la campanita.',
            ],
            ['Autorización', '`POST /api/v1/broadcasting/auth` con el mismo token Bearer y por el mismo cliente HTTP.'],
            ['Entrada', 'El contenedor `web` reenvía `/app/` a Reverb; la API de publicación de Reverb no se expone.'],
          ],
        },
      ],
    },
    {
      titulo: 'Si algo falla',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Reverb caído',
              'El cambio ya está guardado: el error se registra y la petición responde igual (`rescue(..., report: true)`).',
            ],
            [
              'WebSocket que vuelve',
              'Al reconectar se refresca una vez todo lo de incidentes y la campanita, por si se perdieron avisos.',
            ],
            ['Sin WebSocket', 'Las consultas se refrescan solas cada cierto tiempo (la campanita, cada minuto).'],
            ['Trabajo que falla', 'Hasta 3 intentos con 5 segundos entre uno y otro; después queda en `failed_jobs`.'],
          ],
        },
      ],
    },
    {
      titulo: 'Lo que corre en la cola y en el programador',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['IncidentesCambiados', 'Difusión de cambios por WebSocket.'],
            ['AplicarAlCrearse', 'Automatizaciones al crearse un incidente.'],
            ['RevisarAutomatizacion', 'Automatizaciones por tiempo, única por regla, en lotes de 200.'],
            ['AvisoDeIncidente', 'Avisos de la campanita: base de datos más WebSocket.'],
            ['Programador', '`automatizaciones:revisar` cada 5 minutos y `sanctum:prune-expired` cada día.'],
          ],
        },
      ],
    },
  ],
}
