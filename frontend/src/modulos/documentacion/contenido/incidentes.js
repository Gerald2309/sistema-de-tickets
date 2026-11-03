import { iconos } from '@/compartido/iconos.js'

/** @type {import('../tipos.js').Plano} */
const cicloDeVida = {
  codigo: 'I-01',
  titulo: 'Ciclo de vida',
  nivel: 'Dominio',
  ancho: 1000,
  alto: 300,
  nodos: [
    { id: 'abierto', x: 40, y: 60, w: 180, h: 62, tipo: 'Estado', titulo: 'Abierto', sub: 'vence respuesta' },
    { id: 'progreso', x: 280, y: 60, w: 180, h: 62, tipo: 'Estado', titulo: 'En progreso', sub: 'alguien lo tomó' },
    { id: 'resuelto', x: 520, y: 60, w: 180, h: 62, tipo: 'Estado', titulo: 'Resuelto', sub: 'exige solución' },
    {
      id: 'cerrado',
      x: 760,
      y: 60,
      w: 180,
      h: 62,
      tipo: 'Estado final',
      titulo: 'Cerrado',
      sub: 'ya no se trabaja',
      clave: true,
    },
    { id: 'reabrir', x: 400, y: 180, w: 180, h: 50, tipo: 'Transición', titulo: 'Reabrir' },
  ],
  aristas: [
    { de: 'abierto', a: 'progreso', texto: 'tomar' },
    { de: 'progreso', a: 'resuelto', texto: 'resolver' },
    { de: 'resuelto', a: 'cerrado', texto: 'confirmar' },
    { de: 'resuelto', a: 'reabrir', punteada: true },
    { de: 'reabrir', a: 'progreso', punteada: true },
  ],
}

/** @type {import('../tipos.js').Pagina} */
export const incidentes = {
  id: 'incidentes',
  titulo: 'Incidentes',
  grupo: 'aplicacion',
  icono: iconos.lista,
  entradilla:
    'El corazón del sistema: el ciclo de vida, quién puede ver y tocar cada incidente, sus plazos, la conversación y la búsqueda. Vive en `app/Incidentes` y `src/modulos/incidentes`.',
  secciones: [
    {
      titulo: 'Ciclo de vida',
      extra: 'plano I-01 · Dominio/Estado.php',
      bloques: [
        { tipo: 'plano', plano: cicloDeVida },
        {
          tipo: 'nota',
          texto:
            'Las transiciones viven en el enum `Estado` (`siguientes()`, `pasarA()`): una transición que no está en la lista lanza `TransicionInvalida`. Cerrado es final; un resuelto puede volver a «en progreso» si no quedó bien.',
        },
      ],
    },
    {
      titulo: 'Quién ve y quién toca',
      extra: 'Dominio/AlcanceIncidentes.php',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Una sola definición',
              '`AlcanceIncidentes` es la única fuente de la regla: la consultan la Policy, los permisos por campo de las peticiones y la consulta del listado.',
            ],
            ['Ver', 'Toda acción exige poder verlo. Sin `ver_todos`, solo se ven los incidentes propios.'],
            [
              'Trabajar',
              'Un incidente lo trabaja solo quien lo tiene asignado: primero se toma y después se modifica.',
            ],
            [
              'Lo que corrige el solicitante',
              'La clasificación del suyo mientras nadie lo ha tomado, salvo lo que mueve los plazos: marcarlo como crítico o cambiar el vencimiento es de soporte.',
            ],
            [
              'Las palabras del solicitante',
              'Título y descripción solo los corrige quien lo reportó, y solo antes de que lo tomen; después quedan fijos.',
            ],
            [
              'Notas internas',
              'Los mensajes internos y el origen de los cambios automáticos nunca salen hacia quien no puede leer notas internas.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'Plazos de servicio',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Al crear',
              'Se calculan y guardan `vence_respuesta_en` y `fecha_vencimiento` según el acuerdo de servicio de la empresa (`acuerdos_de_servicio.plazos`, JSON por prioridad) y si cuenta horario laboral.',
            ],
            [
              'Primera respuesta',
              '`primera_respuesta_en` se marca con el primer mensaje visible de alguien que no es el solicitante (`EscribirMensaje`), o al resolverlo si nadie había escrito: desde ahí el plazo de respuesta ya no corre.',
            ],
            [
              'Vencido',
              'Sin resolver (abierto o en progreso) y con el plazo pasado: una comparación con un índice, no un cálculo.',
            ],
            ['Resolución', '`resuelto_en` registra cuándo se resolvió, para los indicadores del panel.'],
          ],
        },
      ],
    },
    {
      titulo: 'Conversación e historial',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Mensajes',
              '`mensajes_incidente` con `interno` para notas de soporte y adjuntos de imagen en `adjuntos_mensaje`. Escribir acepta `Idempotency-Key` (único por persona).',
            ],
            [
              'Paginación por cursor',
              'Conversación e historial se piden por cursor (`cursorPaginate`): «ver más» no cuenta ni salta filas, aunque el incidente tenga cientos.',
            ],
            ['Leído', '`lecturas_conversacion` guarda el último mensaje leído por persona: así se marcan los nuevos.'],
            [
              'Historial',
              'Cada cambio de campo queda en `historial_incidentes` con valor anterior y nuevo, quién y, si fue una regla, su origen.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'Búsqueda, similares y trabajo en lote',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['Búsqueda', 'Índice FULLTEXT sobre `(titulo, descripcion)` con `whereFullText` (`ListarIncidentes`).'],
            [
              'Casos similares',
              '`IncidentesSimilares` busca resueltos parecidos con el mismo FULLTEXT y muestra cómo se solucionaron.',
            ],
            [
              'Lote',
              '`PATCH /incidentes/lote` y `DELETE /incidentes/lote`: un solo aviso en tiempo real para todo el lote.',
            ],
            [
              'Exportar',
              '`GET /incidentes/exportar` en CSV o Excel, con los mismos filtros y el mismo alcance que la lista.',
            ],
            ['Borrado', 'Lógico (`deleted_at`): los índices lo incluyen para no leer filas borradas.'],
          ],
        },
      ],
    },
    {
      titulo: 'API',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['GET /incidentes', 'Listar con filtros, búsqueda, orden y paginación.'],
            ['POST /incidentes', 'Crear (201; con la misma `Idempotency-Key` devuelve el ya creado).'],
            ['GET · PATCH · DELETE /incidentes/{id}', 'Ver, actualizar y borrar uno.'],
            ['POST /incidentes/{id}/tomar', 'Tomarlo: queda asignado a quien lo pide.'],
            ['POST /incidentes/{id}/confirmacion', 'El solicitante confirma la solución (o la rechaza).'],
            ['GET · POST /incidentes/{id}/mensajes', 'Conversación, por cursor.'],
            ['GET /incidentes/{id}/historial', 'Historial, por cursor.'],
            ['GET /incidentes/{id}/similares', 'Casos resueltos parecidos.'],
            ['PUT /incidentes/{id}/lectura', 'Marcar la conversación como leída.'],
            ['GET /plazo-propuesto', 'El vencimiento que tendría con la prioridad elegida, para el formulario.'],
          ],
        },
      ],
    },
    {
      titulo: 'Permisos',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['crear_incidente', 'Reportar.'],
            ['ver_propios · ver_todos', 'Qué incidentes ve.'],
            ['editar_propios · editar_todos', 'Clasificar los suyos o los que atiende.'],
            ['cambiar_estado', 'Avanzar el estado y registrar la solución.'],
            ['reasignar', 'Elegir o cambiar a quién está asignado.'],
            ['borrar', 'Eliminar (queda en el historial).'],
            ['ver_historial', 'Ver el historial de cambios.'],
          ],
        },
      ],
    },
    {
      titulo: 'Pruebas',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Dominio (75)',
              'Estados, plazos, horario laboral, vencimiento, alcance, filtros y búsqueda, sin base de datos.',
            ],
            [
              'Integración (224)',
              'API, permisos por rol, conversación, lote, exportación, similares, idempotencia y tiempo real.',
            ],
          ],
        },
      ],
    },
  ],
}
