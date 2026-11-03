import { iconos } from '@/compartido/iconos.js'

/** @type {import('../tipos.js').Pagina} */
export const decisiones = {
  id: 'decisiones',
  titulo: 'Decisiones de arquitectura',
  enMenu: 'Decisiones',
  grupo: 'tecnico',
  icono: iconos.condicion,
  entradilla:
    'Las decisiones grandes, cada una con lo que se eligió, lo que se descartó y lo que implica. Cada una está respaldada por código y por una prueba que la vigila.',
  secciones: [
    {
      titulo: 'ADR-01 · Monolito modular',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['Contexto', 'Un equipo pequeño y un dominio que todavía cambia.'],
            ['Decisión', 'Un solo despliegue, dividido en cuatro módulos con dependencias en un solo sentido.'],
            [
              'Descartado',
              'Microservicios: más infraestructura y llamadas de red sin un beneficio real a este tamaño.',
            ],
            [
              'Consecuencia',
              '`ArquitecturaTest` y `arquitectura.test.js` vigilan las fronteras; un módulo se podría separar si algún día lo necesita.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'ADR-02 · Reglas de negocio en su propia capa',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Decisión',
              'Las reglas de negocio (estados y transiciones, plazos, alcance, flujos de automatización) viven en `app/*/Dominio` y no dependen del framework.',
            ],
            [
              'Alcance',
              'Es solo esa capa: rutas, validación, controladores, acciones, consultas, modelos, colas y eventos son Laravel.',
            ],
            ['Descartado', 'Reglas dentro de modelos o controladores: difíciles de probar sin base de datos.'],
            [
              'Consecuencia',
              'Las pruebas del dominio corren sin base de datos, en milisegundos; las acciones traducen entre modelos y dominio.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'ADR-03 · Plazos guardados, no calculados',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['Decisión', 'Las fechas límite se calculan al crear el incidente y se guardan con él.'],
            ['Descartado', 'Calcularlas al consultar: no se podría filtrar «vencidos» con un índice.'],
            ['Consecuencia', 'Vencidos y por vencer son comparaciones indexadas, aunque haya cien mil incidentes.'],
          ],
        },
      ],
    },
    {
      titulo: 'ADR-04 · Nada sale antes del commit',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Decisión',
              'Eventos, avisos y trabajos se disparan después de confirmar la transacción (`afterCommit`, `ShouldDispatchAfterCommit`).',
            ],
            ['Descartado', 'Disparar en el momento: alguien podría enterarse de un cambio que luego se revierte.'],
            ['Consecuencia', 'Lo que se ve en tiempo real siempre existe en la base.'],
          ],
        },
      ],
    },
    {
      titulo: 'ADR-05 · Idempotencia en lo que se crea',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Decisión',
              'Crear incidentes y mensajes acepta `Idempotency-Key`; las automatizaciones reservan con un índice único.',
            ],
            ['Descartado', 'Confiar en que el cliente no reintente.'],
            ['Consecuencia', 'Un reintento por mala red o un trabajo repetido no duplica nada.'],
          ],
        },
      ],
    },
    {
      titulo: 'ADR-06 · Tiempo real con respaldo',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Decisión',
              'WebSocket propio con Reverb; si se cae, la interfaz se pone al día con refrescos periódicos.',
            ],
            [
              'Descartado',
              'Un servicio externo de WebSocket (otra dependencia y otro proveedor) o solo consultar cada tanto.',
            ],
            ['Consecuencia', 'Cambios al instante cuando todo va bien y una interfaz correcta cuando no.'],
          ],
        },
      ],
    },
    {
      titulo: 'ADR-07 · Flujos de automatización en JSON',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Decisión',
              'El árbol de cada regla se guarda entero en una columna JSON, validado por el dominio antes de guardarse.',
            ],
            [
              'Descartado',
              'Tablas normalizadas de pasos y condiciones: habría que reconstruir el árbol en cada ejecución.',
            ],
            ['Consecuencia', 'Leer y guardar una regla es una sola fila; la validez la garantiza `LectorDeFlujo`.'],
          ],
        },
      ],
    },
    {
      titulo: 'ADR-08 · Frontend en JavaScript con tipos JSDoc',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Decisión',
              'Vue 3 con Vuetify, JavaScript y tipos en JSDoc revisados por `vue-tsc` en la integración continua.',
            ],
            ['Descartado', 'TypeScript con compilación: más configuración para el mismo control de tipos.'],
            [
              'Consecuencia',
              'Los errores de tipos fallan la CI igual que en TypeScript, sin paso de compilación propio.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'ADR-09 · Documentación como datos',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Decisión',
              'Esta documentación vive en el repositorio como datos (`modulos/documentacion/contenido`) que dibujan los componentes.',
            ],
            ['Descartado', 'Un portal aparte o archivos sueltos que se desactualizan solos.'],
            [
              'Consecuencia',
              'El buscador indexa lo mismo que se ve, y una prueba comprueba que cada flecha y cada paso de los planos apunte a algo que existe.',
            ],
          ],
        },
      ],
    },
  ],
}
