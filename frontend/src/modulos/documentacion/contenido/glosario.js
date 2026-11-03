import { iconos } from '@/compartido/iconos.js'

/** @type {import('../tipos.js').Pagina} */
export const glosario = {
  id: 'glosario',
  titulo: 'Glosario',
  grupo: 'tecnico',
  icono: iconos.palabras,
  entradilla: 'Los términos del dominio, con el nombre que tienen en el código.',
  secciones: [
    {
      titulo: 'Términos',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Incidente',
              'Una solicitud o falla reportada. Tabla `incidentes`; su código visible, como `INC-000042`, se deriva del id y no se guarda.',
            ],
            ['Solicitante', 'Quien reporta. Solo ve sus incidentes (`ver_propios`).'],
            [
              'Soporte',
              'Quien atiende: N1, N2 y N3 tienen los mismos permisos y se distinguen para repartir el trabajo.',
            ],
            ['Tomar', 'Asignarse un incidente libre: solo quien lo tiene asignado lo trabaja.'],
            [
              'Acuerdo de servicio (SLA)',
              'Los plazos de respuesta y resolución de una empresa por prioridad (`acuerdos_de_servicio`).',
            ],
            ['Plazo de respuesta', 'Hasta cuándo debe llegar la primera respuesta (`vence_respuesta_en`).'],
            ['Plazo de resolución', 'Hasta cuándo debe resolverse (`fecha_vencimiento`).'],
            [
              'Plazo consumido',
              'Qué porcentaje del tiempo entre la creación y el límite ya pasó; al 100 % el incidente está vencido.',
            ],
            ['Vencido', 'Sin resolver (abierto o en progreso) y con el plazo pasado.'],
            [
              'Horario laboral',
              'Lunes a viernes de 8:00 a 18:00: si el acuerdo lo usa, el plazo solo corre en esas horas.',
            ],
            ['Nota interna', 'Mensaje de la conversación que solo ve soporte (`interno`).'],
            [
              'Confirmar la solución',
              'El solicitante acepta lo resuelto, que se cierra con una calificación opcional de 1 a 5, o lo rechaza con un motivo y vuelve a «en progreso».',
            ],
            ['Automatización', 'Regla con disparador, condiciones y acciones que actúa sola (`automatizaciones`).'],
            [
              'Disparador',
              'Lo que pone en marcha una regla: al crearse, SLA por vencer, vencido, sin respuesta o resuelto sin confirmar.',
            ],
            [
              'Ejecución',
              'Una vez que una regla actuó sobre un incidente; nunca dos sobre el mismo (`ejecuciones_de_automatizacion`).',
            ],
            ['Origen', 'El nombre de la regla que hizo un cambio o escribió un mensaje; solo lo ve soporte.'],
            ['Aviso', 'Notificación personal de la campanita (`notifications`), en vivo por el canal `usuario.{id}`.'],
            ['Alcance', 'Qué incidentes ve y puede tocar cada persona (`AlcanceIncidentes`).'],
            ['Clave de idempotencia', 'Identificador de un envío (`Idempotency-Key`): repetirlo no crea un duplicado.'],
            ['Mi día', 'La agenda personal de soporte: plan por horas, notas y bloqueos.'],
          ],
        },
      ],
    },
  ],
}
