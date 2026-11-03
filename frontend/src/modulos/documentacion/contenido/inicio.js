import { iconos } from '@/compartido/iconos.js'

/** @type {import('../tipos.js').Pagina} */
export const inicio = {
  id: 'inicio',
  titulo: 'Mesa de ayuda',
  enMenu: 'Inicio',
  grupo: 'inicio',
  icono: iconos.documentacion,
  entradilla:
    'Sistema de gestión de incidentes para Psicol: reportes con plazos de servicio por empresa, atención por niveles de soporte, reglas automáticas y avisos en tiempo real. API REST en Laravel 13 y PHP 8.5, interfaz en Vue 3 con Vuetify 4, MySQL 8.4, Redis y Reverb, todo en Docker.',
  secciones: [
    {
      titulo: 'Qué hace',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Incidentes',
              'Reporte, asignación, conversación con notas internas, historial de cambios, búsqueda y trabajo en lote.',
            ],
            [
              'Plazos de servicio',
              'Cada empresa tiene su acuerdo por prioridad, con o sin horario laboral; los vencidos se detectan solos.',
            ],
            [
              'Automatizaciones',
              'Reglas armadas con bloques que asignan, responden, avisan o cierran sin intervención.',
            ],
            [
              'Tiempo real',
              'Las pantallas y la campanita se actualizan al instante, con un respaldo si se pierde la conexión.',
            ],
            ['Panel y Mi día', 'La operación de hoy en cifras y la agenda personal de cada persona de soporte.'],
            [
              'Administración',
              'Usuarios, roles con permisos configurables, empresas y una auditoría de todo lo administrativo.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'Cómo está organizado el repositorio',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'backend/app',
              'Un módulo por carpeta: Identidad, Incidentes, Automatizaciones y Auditoría. Las rutas de la API están en `backend/routes/api.php`.',
            ],
            ['backend/database', 'Migraciones, fábricas y los datos de ejemplo de la demostración.'],
            ['backend/tests', 'Pruebas de las reglas de negocio (Unit) y de la API contra MySQL (Feature).'],
            [
              'frontend/src',
              'El núcleo (arranque, rutas y disposición), los mismos módulos que el backend y lo compartido; la entrada es `frontend/src/main.js`.',
            ],
            ['frontend/pruebas', 'Recorridos de punta a punta con Playwright y reglas de arquitectura.'],
            [
              'docs',
              'Modelo de amenazas (`docs/modelo-de-amenazas.md`) y propuestas de mejora (`docs/propuestas-de-mejora.md`).',
            ],
            [
              'Raíz',
              '`docker-compose.yml` levanta todo; la integración continua está en `.github/workflows/integracion-continua.yml`.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'Qué hay en esta documentación',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Mesa de ayuda y Administración',
              'Una página por pantalla: su captura, cómo funciona, la API que usa, sus datos y sus permisos.',
            ],
            ['Instalación', 'Levantarlo con Docker, las cuentas de prueba y cómo correr las pruebas.'],
            ['Arquitectura, Backend y Frontend', 'El sistema en planos C4 y las capas de cada lado.'],
            [
              'Recorrido de un incidente',
              'Un incidente atravesando el frontend, la API, la base de datos y el tiempo real, paso a paso.',
            ],
            ['Modelo de datos', 'Tablas, relaciones e índices, tomados del esquema real.'],
            ['Seguridad', 'Amenazas y controles, y un simulador de qué responde la API a cada rol.'],
            [
              'Tiempo real, Calidad y Despliegue',
              'WebSocket y colas, pruebas e integración continua, y cómo se opera en producción.',
            ],
            ['Decisiones y Glosario', 'Por qué se eligió cada cosa y los términos del dominio.'],
            ['Referencia de la API', 'Cada operación documentada desde el código, para probarla aquí mismo.'],
          ],
        },
        {
          tipo: 'nota',
          texto:
            'Busque cualquier clase, tabla, endpoint o concepto con Ctrl K. Las rutas de archivo enlazan al código en GitHub.',
        },
      ],
    },
  ],
}
