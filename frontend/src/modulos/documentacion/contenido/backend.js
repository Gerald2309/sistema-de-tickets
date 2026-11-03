import { iconos } from '@/compartido/iconos.js'

/** @type {import('../tipos.js').Plano} */
const capas = {
  codigo: 'B-01',
  titulo: 'Capas de un módulo',
  nivel: 'C4 · 3–4',
  ancho: 960,
  alto: 600,
  nodos: [
    { id: 'z-http', zona: true, x: 40, y: 36, w: 880, h: 120, titulo: 'ENTRADA · HTTP' },
    { id: 'ruta', x: 60, y: 66, w: 200, h: 70, tipo: 'routes/api.php', titulo: 'Ruta', sub: 'auth:sanctum · throttle' },
    {
      id: 'req',
      x: 290,
      y: 66,
      w: 190,
      h: 70,
      tipo: 'Http/Requests',
      titulo: 'FormRequest',
      sub: 'valida y normaliza',
    },
    {
      id: 'ctl',
      x: 510,
      y: 66,
      w: 190,
      h: 70,
      tipo: 'Http/Controllers',
      titulo: 'Controlador',
      sub: 'delgado: solo orquesta',
    },
    {
      id: 'res',
      x: 730,
      y: 66,
      w: 170,
      h: 70,
      tipo: 'Http/Resources',
      titulo: 'Resource',
      sub: 'forma de la respuesta',
    },
    { id: 'z-app', zona: true, x: 40, y: 186, w: 880, h: 120, titulo: 'APLICACIÓN' },
    {
      id: 'acc',
      x: 120,
      y: 216,
      w: 280,
      h: 70,
      tipo: 'Acciones',
      titulo: 'Casos de uso',
      sub: 'transacción · eventos',
      clave: true,
    },
    {
      id: 'con',
      x: 560,
      y: 216,
      w: 280,
      h: 70,
      tipo: 'Consultas',
      titulo: 'Lecturas',
      sub: 'SQL para listas y cifras',
    },
    { id: 'z-dom', zona: true, x: 40, y: 336, w: 430, h: 120, titulo: 'DOMINIO · PHP PURO' },
    {
      id: 'dom',
      x: 70,
      y: 366,
      w: 370,
      h: 70,
      tipo: 'Dominio',
      titulo: 'Reglas y valores',
      sub: 'sin Illuminate, sin modelos',
      clave: true,
    },
    { id: 'z-inf', zona: true, x: 490, y: 336, w: 430, h: 120, titulo: 'PERSISTENCIA' },
    { id: 'mod', x: 520, y: 366, w: 370, h: 70, tipo: 'Models', titulo: 'Eloquent', sub: 'tablas, relaciones, scopes' },
    { id: 'z-asinc', zona: true, x: 40, y: 486, w: 880, h: 90, titulo: 'ASÍNCRONO' },
    { id: 'eve', x: 70, y: 506, w: 250, h: 56, tipo: 'Eventos', titulo: 'Los disparan las acciones' },
    { id: 'oye', x: 350, y: 506, w: 250, h: 56, tipo: 'Oyentes · Trabajos', titulo: 'En la cola' },
    { id: 'not', x: 630, y: 506, w: 260, h: 56, tipo: 'Notificaciones', titulo: 'BD + WebSocket' },
  ],
  aristas: [
    { de: 'ruta', a: 'req' },
    { de: 'req', a: 'ctl' },
    { de: 'ctl', a: 'res' },
    { de: 'ctl', a: 'acc', texto: 'ejecutar()' },
    { de: 'ctl', a: 'con', texto: 'consultar' },
    { de: 'acc', a: 'dom', texto: 'decide' },
    { de: 'acc', a: 'mod', texto: 'guarda', modo: 'h', mx: 480 },
    { de: 'con', a: 'mod' },
    { de: 'eve', a: 'oye', punteada: true, texto: 'tras el commit' },
    { de: 'oye', a: 'not', punteada: true },
  ],
}

/** @type {import('../tipos.js').Pagina} */
export const backend = {
  id: 'backend',
  titulo: 'Arquitectura del backend',
  enMenu: 'Backend',
  grupo: 'tecnico',
  icono: iconos.backend,
  entradilla:
    'Laravel 13 con PHP 8.5. Cada módulo vive en `app/{Módulo}` y repite las mismas capas; una petición baja por ellas siempre en el mismo orden.',
  secciones: [
    {
      titulo: 'Capas de un módulo',
      extra: 'plano B-01',
      bloques: [
        { tipo: 'plano', plano: capas },
        {
          tipo: 'nota',
          texto:
            'Regla vigilada: ningún archivo de `app/*/Dominio` puede importar `Illuminate` ni `App\\*\\Models` (`backend/tests/Unit/ArquitecturaTest.php`).',
        },
      ],
    },
    {
      titulo: 'Responsabilidad de cada capa',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Ruta',
              'Declara método, URI, middleware (`auth:sanctum`, `throttle:api`) y la autorización con `can()`. El middleware `autorizacion.declarada` rechaza cualquier ruta que no la declare.',
            ],
            [
              'FormRequest',
              'Valida y normaliza. Nada inválido llega al caso de uso; responde 422 con el detalle por campo.',
            ],
            ['Controlador', 'Sin lógica de negocio: llama a una acción o una consulta y elige el Resource.'],
            [
              'Acciones',
              'Un caso de uso por clase. Abren la transacción, aplican reglas del dominio, guardan y registran eventos.',
            ],
            [
              'Consultas',
              'Lecturas complejas (listados, panel, indicadores) resueltas en SQL con índices, sin traer filas a PHP.',
            ],
            [
              'Dominio',
              'Enums, valores y reglas puras: estados, prioridades, plazos, vencimiento, flujos de automatización.',
            ],
            [
              'Eventos, oyentes, trabajos',
              'Lo que no necesita responder ya: automatizaciones, avisos y difusión en tiempo real, por la cola.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'Dependencias entre módulos',
      extra: 'tests/Unit/ArquitecturaTest.php',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['Auditoría', 'No puede usar a ningún otro módulo.'],
            ['Identidad', 'Puede usar Auditoría.'],
            ['Incidentes', 'Puede usar Identidad. No puede usar Auditoría ni Automatizaciones.'],
            ['Automatizaciones', 'Puede usar a los otros tres. Nadie la usa a ella.'],
          ],
        },
      ],
    },
  ],
}
