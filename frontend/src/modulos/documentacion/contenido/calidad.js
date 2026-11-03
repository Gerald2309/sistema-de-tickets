import { iconos } from '@/compartido/iconos.js'

/** @type {import('../tipos.js').Pagina} */
export const calidad = {
  id: 'calidad',
  titulo: 'Calidad: pruebas e integración continua',
  enMenu: 'Calidad',
  grupo: 'tecnico',
  icono: iconos.exito,
  entradilla:
    'Cada push y cada pull request pasan por tres trabajos en GitHub Actions (`.github/workflows/integracion-continua.yml`). Si uno falla, el cambio no entra.',
  secciones: [
    {
      titulo: 'Lo que vigila cada cambio',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Backend',
              'Estilo con Pint, análisis estático con Larastan nivel 8, `composer audit` sin avisos y PHPUnit sobre la base `incidentes_test`.',
            ],
            [
              'Frontend',
              '`npm audit` sin avisos altos ni críticos; ESLint, Prettier y Stylelint; tipos con `vue-tsc` sobre JSDoc; Vitest con cobertura mínima del 80 % de líneas; compilación con presupuesto del paquete.',
            ],
            [
              'Punta a punta',
              'La aplicación completa en Docker y Playwright en Chromium, Firefox y WebKit, con revisión de accesibilidad con axe.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'Cuántas pruebas hay',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Backend',
              '505 pruebas en 75 archivos: dominio sin base de datos (Unit) e integración contra MySQL (Feature).',
            ],
            [
              'Frontend',
              '238 pruebas en 48 archivos: lógica pura y vistas montadas con la aplicación real y la API simulada con MSW.',
            ],
            ['Punta a punta', '18 recorridos en 7 archivos, en tres navegadores.'],
          ],
        },
      ],
    },
    {
      titulo: 'Reglas que se prueban solas',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Arquitectura del backend',
              '`ArquitecturaTest`: los módulos dependen en un solo sentido y cada capa usa solo lo que le corresponde.',
            ],
            [
              'Arquitectura del frontend',
              '`arquitectura.test.js`: núcleo, módulos y compartido; un módulo usa a otro solo por su `index.js`.',
            ],
            [
              'Contrato de la API',
              '`ContratoDeApiTest`: toda ruta declara su permiso; 401 y 404 sin filtrar información.',
            ],
            ['Colores', 'Stylelint prohíbe colores literales fuera de `tokens.css`.'],
            [
              'Presupuesto del paquete',
              'El JavaScript que hace falta para mostrar el inicio de sesión no pasa de 200 KB con gzip; si los pasa, la compilación falla.',
            ],
          ],
        },
      ],
    },
  ],
}
