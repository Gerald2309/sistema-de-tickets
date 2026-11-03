import { iconos } from '@/compartido/iconos.js'

/** @type {import('../tipos.js').Plano} */
const estructura = {
  codigo: 'F-01',
  titulo: 'Interfaz Vue 3',
  nivel: 'C4 · 3–4',
  ancho: 960,
  alto: 600,
  nodos: [
    { id: 'z-nuc', zona: true, x: 40, y: 36, w: 880, h: 110, titulo: 'NÚCLEO · src/nucleo' },
    { id: 'main', x: 60, y: 64, w: 190, h: 64, tipo: 'main.js', titulo: 'Arranque', sub: 'monta plugins' },
    { id: 'enr', x: 270, y: 64, w: 200, h: 64, tipo: 'enrutador', titulo: 'Rutas + guardas', sub: 'meta.permiso' },
    { id: 'plu', x: 490, y: 64, w: 200, h: 64, tipo: 'plugins', titulo: 'Vuetify · Query', sub: 'tiempo-real (Echo)' },
    { id: 'dis', x: 710, y: 64, w: 190, h: 64, tipo: 'disposicion', titulo: 'Barra y menú', sub: 'campanita' },
    {
      id: 'z-mod',
      zona: true,
      x: 40,
      y: 176,
      w: 880,
      h: 250,
      titulo: 'MÓDULOS · src/modulos/{identidad, incidentes, automatizaciones, auditoria, documentacion}',
    },
    { id: 'vis', x: 70, y: 210, w: 190, h: 64, tipo: 'vistas/*.vue', titulo: 'Vista', sub: 'una por ruta' },
    { id: 'cmp', x: 70, y: 330, w: 190, h: 64, tipo: 'componentes/*.vue', titulo: 'Componentes', sub: 'presentación' },
    {
      id: 'cps',
      x: 300,
      y: 210,
      w: 210,
      h: 64,
      tipo: 'composables/use*.js',
      titulo: 'Estado del servidor',
      sub: 'useQuery · useMutation',
      clave: true,
    },
    { id: 'tie', x: 300, y: 330, w: 210, h: 64, tipo: 'tienda/*.js', titulo: 'Pinia', sub: 'solo estado local' },
    { id: 'apim', x: 550, y: 210, w: 170, h: 64, tipo: 'api/*.js', titulo: 'Llamadas', sub: 'adaptadores DTO' },
    { id: 'cla', x: 550, y: 330, w: 170, h: 64, tipo: 'api/claves.js', titulo: 'Claves de caché', sub: 'invalidación' },
    {
      id: 'reg',
      x: 750,
      y: 210,
      w: 150,
      h: 64,
      tipo: 'plazos.js, permisos.js…',
      titulo: 'Lógica pura',
      sub: 'sin Vue, con pruebas',
    },
    { id: 'z-com', zona: true, x: 40, y: 456, w: 880, h: 110, titulo: 'COMPARTIDO · src/compartido' },
    {
      id: 'cli',
      x: 60,
      y: 484,
      w: 230,
      h: 64,
      tipo: 'api/cliente.js',
      titulo: 'axios',
      sub: 'token Bearer · errores',
      clave: true,
    },
    { id: 'tr', x: 310, y: 484, w: 190, h: 64, tipo: 'tiempo-real.js', titulo: 'Respaldo', sub: 'refresco si cae WS' },
    { id: 'ui', x: 520, y: 484, w: 190, h: 64, tipo: 'componentes', titulo: 'UI común', sub: 'tarjetas, tablas' },
    { id: 'est', x: 730, y: 484, w: 170, h: 64, tipo: 'estilos', titulo: 'tokens.css', sub: 'colores Psicol' },
  ],
  aristas: [
    { de: 'enr', a: 'vis', texto: 'carga perezosa' },
    { de: 'vis', a: 'cmp' },
    { de: 'vis', a: 'cps' },
    { de: 'cps', a: 'apim' },
    { de: 'cps', a: 'cla' },
    { de: 'cps', a: 'tie', punteada: true },
    { de: 'apim', a: 'cli', texto: 'http', modo: 'h', mx: 530 },
    { de: 'plu', a: 'cla', punteada: true, modo: 'h', mx: 700 },
  ],
}

/** @type {import('../tipos.js').Pagina} */
export const frontend = {
  id: 'frontend',
  titulo: 'Arquitectura del frontend',
  enMenu: 'Frontend',
  grupo: 'tecnico',
  icono: iconos.frontend,
  entradilla:
    'Vue 3 con Vuetify 4, en JavaScript con tipos JSDoc revisados por `vue-tsc`. Los mismos módulos que el backend, con las mismas fronteras.',
  secciones: [
    {
      titulo: 'Estructura',
      extra: 'plano F-01',
      bloques: [
        { tipo: 'plano', plano: estructura },
        {
          tipo: 'nota',
          texto:
            'El estado que viene del servidor vive en vue-query, no en Pinia: Pinia guarda solo estado de la interfaz (pestañas abiertas, sesión).',
        },
      ],
    },
    {
      titulo: 'Librerías y para qué',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['vue-router', 'Rutas por módulo, cargadas perezosamente; cada una declara su permiso en `meta.permiso`.'],
            [
              '@tanstack/vue-query',
              'Caché del estado del servidor, reintentos, cancelación e invalidación por claves.',
            ],
            ['pinia', 'Estado local de la interfaz.'],
            ['axios', 'Un solo cliente: token Bearer, mismo origen y errores normalizados.'],
            ['laravel-echo + pusher-js', 'WebSocket con Reverb; los canales se autorizan con el mismo token.'],
            ['vuetify', 'Componentes con el tema y los tokens de Psicol.'],
          ],
        },
      ],
    },
    {
      titulo: 'Dependencias entre módulos',
      extra: 'pruebas/unitarias/arquitectura.test.js',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['identidad', 'No usa a ningún otro módulo.'],
            ['incidentes', 'Puede usar identidad.'],
            ['auditoria', 'Puede usar identidad.'],
            ['automatizaciones', 'Puede usar identidad e incidentes.'],
            ['documentacion', 'No usa a ningún otro módulo: solo describe.'],
            [
              'Reglas comunes',
              'Un módulo usa a otro solo por su `index.js`; compartido no conoce a la aplicación; solo las carpetas `api/` usan el cliente HTTP.',
            ],
          ],
        },
      ],
    },
  ],
}
