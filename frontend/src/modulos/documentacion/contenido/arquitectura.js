import { iconos } from '@/compartido/iconos.js'

/** @type {import('../tipos.js').Plano} */
const contexto = {
  codigo: 'A-01',
  titulo: 'Contexto',
  nivel: 'C4 · 1',
  ancho: 960,
  alto: 430,
  nodos: [
    { id: 'sol', x: 60, y: 46, w: 230, h: 66, tipo: 'Persona', titulo: 'Solicitante', sub: 'reporta y sigue lo suyo' },
    { id: 'sop', x: 365, y: 46, w: 230, h: 66, tipo: 'Persona', titulo: 'Soporte N1–N3', sub: 'atiende y resuelve' },
    {
      id: 'adm',
      x: 670,
      y: 46,
      w: 230,
      h: 66,
      tipo: 'Persona',
      titulo: 'Administración',
      sub: 'reglas, permisos, auditoría',
    },
    {
      id: 'cf',
      x: 365,
      y: 180,
      w: 230,
      h: 60,
      tipo: 'Sistema externo',
      titulo: 'Cloudflare Tunnel',
      sub: 'HTTPS hacia el servidor',
    },
    {
      id: 'mesa',
      x: 290,
      y: 300,
      w: 380,
      h: 70,
      tipo: 'Sistema',
      titulo: 'Mesa de ayuda',
      sub: 'incidentes · plazos · reglas · tiempo real',
      clave: true,
    },
  ],
  aristas: [
    { de: 'sol', a: 'cf', texto: 'navegador' },
    { de: 'sop', a: 'cf', texto: 'navegador' },
    { de: 'adm', a: 'cf', texto: 'navegador' },
    { de: 'cf', a: 'mesa', texto: 'túnel cifrado' },
  ],
}

/** @type {import('../tipos.js').Plano} */
const contenedores = {
  codigo: 'A-02',
  titulo: 'Contenedores',
  nivel: 'C4 · 2',
  ancho: 960,
  alto: 600,
  nodos: [
    { id: 'z-php', zona: true, x: 40, y: 250, w: 880, h: 110, titulo: 'IMAGEN PHP 8.5 · MISMO CÓDIGO' },
    {
      id: 'web',
      x: 100,
      y: 40,
      w: 230,
      h: 66,
      tipo: 'Contenedor · web',
      titulo: 'Interfaz Vue 3',
      sub: 'nginx: build + proxy',
    },
    {
      id: 'api',
      x: 100,
      y: 160,
      w: 230,
      h: 60,
      tipo: 'Contenedor · api',
      titulo: 'nginx de la API',
      sub: '/api → PHP-FPM',
    },
    {
      id: 'reverb',
      x: 640,
      y: 160,
      w: 230,
      h: 60,
      tipo: 'Contenedor · reverb',
      titulo: 'Reverb',
      sub: 'WebSocket :8080',
    },
    {
      id: 'app',
      x: 60,
      y: 280,
      w: 200,
      h: 66,
      tipo: 'Contenedor · app',
      titulo: 'Laravel 13',
      sub: 'php-fpm',
      clave: true,
    },
    {
      id: 'cola',
      x: 280,
      y: 280,
      w: 200,
      h: 66,
      tipo: 'Contenedor · cola',
      titulo: 'Trabajos',
      sub: 'queue:work --tries=3',
    },
    {
      id: 'prog',
      x: 500,
      y: 280,
      w: 200,
      h: 66,
      tipo: 'Contenedor · programador',
      titulo: 'Programador',
      sub: 'schedule:work',
    },
    { id: 'db', x: 180, y: 420, w: 220, h: 66, tipo: 'Contenedor · db', titulo: 'MySQL 8.4', sub: 'datos' },
    {
      id: 'redis',
      x: 520,
      y: 420,
      w: 220,
      h: 66,
      tipo: 'Contenedor · redis',
      titulo: 'Redis 7.4',
      sub: 'colas · caché',
    },
  ],
  aristas: [
    { de: 'web', a: 'api', texto: '/api/' },
    { de: 'web', a: 'reverb', texto: '/app/ · WebSocket' },
    { de: 'api', a: 'app', texto: 'FastCGI' },
    { de: 'app', a: 'db', texto: 'SQL' },
    { de: 'app', a: 'redis', texto: 'encola' },
    { de: 'cola', a: 'redis', punteada: true, modo: 'h', mx: 500 },
    { de: 'cola', a: 'reverb', texto: 'emite eventos' },
    { de: 'prog', a: 'redis', texto: 'cada 5 min', punteada: true },
    { de: 'cola', a: 'db' },
  ],
}

/** @type {import('../tipos.js').Plano} */
const componentes = {
  codigo: 'A-03',
  titulo: 'Componentes',
  nivel: 'C4 · 3',
  ancho: 960,
  alto: 470,
  nodos: [
    {
      id: 'aut',
      x: 360,
      y: 40,
      w: 240,
      h: 70,
      tipo: 'Módulo',
      titulo: 'Automatizaciones',
      sub: 'puede usar a los otros tres',
      clave: true,
    },
    { id: 'inc', x: 100, y: 200, w: 240, h: 70, tipo: 'Módulo', titulo: 'Incidentes', sub: 'ciclo de vida · plazos' },
    {
      id: 'ide',
      x: 360,
      y: 340,
      w: 240,
      h: 70,
      tipo: 'Módulo',
      titulo: 'Identidad',
      sub: 'personas · roles · permisos',
    },
    { id: 'aud', x: 620, y: 200, w: 240, h: 70, tipo: 'Módulo', titulo: 'Auditoría', sub: 'no depende de nadie' },
  ],
  aristas: [
    { de: 'aut', a: 'inc', texto: 'usa' },
    { de: 'aut', a: 'aud', texto: 'usa' },
    { de: 'aut', a: 'ide', texto: 'usa' },
    { de: 'inc', a: 'ide', texto: 'usa' },
    { de: 'ide', a: 'aud', texto: 'registra' },
  ],
}

/** @type {import('../tipos.js').Pagina} */
export const arquitectura = {
  id: 'arquitectura',
  titulo: 'Arquitectura',
  grupo: 'tecnico',
  icono: iconos.arquitectura,
  entradilla:
    'El sistema en tres niveles de zoom, siguiendo el modelo C4: del contexto a los contenedores y de ahí a los módulos.',
  secciones: [
    {
      titulo: 'Planos C4',
      bloques: [
        {
          tipo: 'planos',
          vistas: [
            {
              etiqueta: '1 · Contexto',
              plano: contexto,
              nota: 'Quién usa el sistema y por dónde entra. Todo el tráfico llega por un túnel de Cloudflare: el servidor no expone puertos.',
            },
            {
              etiqueta: '2 · Contenedores',
              plano: contenedores,
              nota: 'Lo que se despliega: cada caja es un contenedor de Docker Compose. Los cuatro de PHP comparten la misma imagen y el mismo código.',
            },
            {
              etiqueta: '3 · Componentes',
              plano: componentes,
              nota: 'Los cuatro módulos del backend y en qué sentido pueden depender. Lo vigila `tests/Unit/ArquitecturaTest.php`: una flecha al revés hace fallar la CI.',
            },
          ],
        },
      ],
    },
    {
      titulo: 'Principios',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Monolito modular',
              'Un solo despliegue, dividido en módulos con fronteras vigiladas por pruebas en el back y en el front.',
            ],
            [
              'Dominio aislado',
              'Las reglas de negocio no dependen de Laravel ni de los modelos: se prueban en milisegundos.',
            ],
            [
              'Nada sale antes del commit',
              'Eventos, avisos y trabajos se disparan solo cuando la transacción se confirma.',
            ],
            [
              'Idempotencia',
              'Crear incidentes y mensajes acepta `Idempotency-Key`; las automatizaciones actúan una vez por incidente.',
            ],
            ['Tiempo real con respaldo', 'WebSocket con Reverb; si se cae, la interfaz se pone al día sola.'],
          ],
        },
      ],
    },
  ],
}
