import { iconos } from '@/compartido/iconos.js'
import capturaDelEditor from '../recursos/automatizacion-editor.png'

/** @type {import('../tipos.js').Plano} */
const vidaDeUnaRegla = {
  codigo: 'AU-02',
  titulo: 'Vida de una regla',
  nivel: 'C4 · 4',
  ancho: 1000,
  alto: 860,
  nodos: [
    { id: 'z-ed', zona: true, x: 30, y: 30, w: 270, h: 750, titulo: 'EDITOR · VUE' },
    { id: 'z-srv', zona: true, x: 325, y: 30, w: 390, h: 750, titulo: 'SERVIDOR · LARAVEL' },
    { id: 'z-dat', zona: true, x: 735, y: 30, w: 235, h: 750, titulo: 'DATOS Y TIEMPO REAL' },
    { id: 'prueba', x: 50, y: 64, w: 230, h: 58, tipo: 'ResultadoDePrueba.vue', titulo: 'Probar' },
    { id: 'ed', x: 50, y: 144, w: 230, h: 58, tipo: 'VistaEditorDeAutomatizacion', titulo: 'Guardar cambios' },
    { id: 'campana', x: 50, y: 704, w: 230, h: 58, tipo: 'CampanitaDeAvisos.vue', titulo: 'Aviso en vivo' },
    { id: 'simular', x: 340, y: 64, w: 360, h: 58, tipo: 'Consultas', titulo: 'SimularAutomatizacion' },
    { id: 'req', x: 340, y: 144, w: 360, h: 58, tipo: 'Http/Requests', titulo: 'GuardarAutomatizacionRequest' },
    { id: 'lector', x: 340, y: 224, w: 170, h: 58, tipo: 'Dominio', titulo: 'LectorDeFlujo' },
    { id: 'refs', x: 530, y: 224, w: 170, h: 58, tipo: 'Consultas', titulo: 'ReferenciasDelFlujo' },
    { id: 'guardar', x: 340, y: 304, w: 360, h: 58, tipo: 'Acciones', titulo: 'GuardarAutomatizacion' },
    { id: 'estado', x: 340, y: 384, w: 360, h: 58, tipo: 'Acciones', titulo: 'CambiarEstado · RevisarEnseguida' },
    { id: 'revisar', x: 340, y: 464, w: 170, h: 58, tipo: 'Trabajos', titulo: 'RevisarAutomatizacion' },
    { id: 'consulta', x: 530, y: 464, w: 170, h: 58, tipo: 'Consultas', titulo: 'IncidentesPorDisparar' },
    { id: 'aplicar', x: 340, y: 544, w: 360, h: 58, tipo: 'Acciones', titulo: 'AplicarAutomatizaciones', clave: true },
    { id: 'decidir', x: 340, y: 624, w: 170, h: 58, tipo: 'Dominio', titulo: 'Flujo::accionesPara' },
    { id: 'ejecutar', x: 530, y: 624, w: 170, h: 58, tipo: 'Acciones', titulo: 'EjecutarAccion' },
    { id: 'regla', x: 750, y: 304, w: 205, h: 58, tipo: 'MySQL', titulo: 'automatizaciones' },
    { id: 'auditoria', x: 750, y: 384, w: 205, h: 58, tipo: 'MySQL', titulo: 'auditoria' },
    { id: 'ejec', x: 750, y: 544, w: 205, h: 58, tipo: 'MySQL', titulo: 'ejecuciones_de_…' },
    { id: 'mensajes', x: 750, y: 624, w: 205, h: 58, tipo: 'MySQL', titulo: 'mensajes_incidente' },
    { id: 'notif', x: 750, y: 704, w: 205, h: 58, tipo: 'MySQL + Reverb', titulo: 'notifications' },
  ],
  aristas: [
    { de: 'prueba', a: 'simular', texto: 'POST /prueba' },
    { de: 'ed', a: 'req', texto: 'PUT' },
    { de: 'req', a: 'lector' },
    { de: 'lector', a: 'refs' },
    { de: 'refs', a: 'guardar' },
    { de: 'guardar', a: 'regla', texto: 'JSON' },
    { de: 'guardar', a: 'estado' },
    { de: 'estado', a: 'auditoria', texto: 'activada' },
    { de: 'estado', a: 'revisar', texto: 'afterCommit' },
    { de: 'revisar', a: 'consulta', texto: 'ids' },
    { de: 'revisar', a: 'aplicar' },
    { de: 'aplicar', a: 'ejec', texto: 'insertOrIgnore' },
    { de: 'aplicar', a: 'decidir' },
    { de: 'decidir', a: 'ejecutar' },
    { de: 'ejecutar', a: 'mensajes', texto: 'nota' },
    { de: 'ejecutar', a: 'notif', texto: 'avisar' },
    { de: 'notif', a: 'campana', texto: 'aviso.nuevo' },
  ],
}

/** @type {import('../tipos.js').Paso[]} */
const pasosDeLaRegla = [
  {
    titulo: 'El editor arma el árbol',
    capa: 'front',
    nodos: ['ed', 'prueba'],
    aristas: [],
    archivo: 'frontend/src/modulos/automatizaciones/componentes/CaminoDelFlujo.vue',
    texto:
      'Cada bloque del lienzo es un paso del JSON. CaminoDelFlujo se dibuja a sí mismo para cada rama sí y no; el estado del editor se comparte por `provide`/`inject` para no pasar props por cada nivel.',
    codigo: `{ "tipo": "condicion", "campo": "prioridad", "operador": "es_una_de", "valores": ["critica"],
  "si": [ { "tipo": "accion", "accion": "avisar", "destinatario": "rol", "id": 1 },
          { "tipo": "accion", "accion": "nota_interna", "texto": "Crítico al 80 % …" } ],
  "no": [] }`,
  },
  {
    titulo: 'Probar antes de guardar',
    capa: 'back',
    nodos: ['prueba', 'simular'],
    aristas: ['prueba>simular'],
    archivo: 'backend/app/Automatizaciones/Consultas/SimularAutomatizacion.php',
    texto:
      'Sin guardar nada, recorre el flujo sobre los incidentes de los últimos 30 días (máximo 5.000): cuántos tomarían cada camino, ejemplos, y a cuántos les toca ahora mismo según el disparador, con la misma consulta que usará la revisión.',
  },
  {
    titulo: 'Validar la forma',
    capa: 'back',
    nodos: ['ed', 'req', 'lector'],
    aristas: ['ed>req', 'req>lector'],
    archivo: 'backend/app/Automatizaciones/Dominio/LectorDeFlujo.php',
    texto:
      'El FormRequest entrega el disparador a `Disparo::desde` (porcentaje entero entre 1 y 99, plazo de respuesta o de resolución) y los pasos al LectorDeFlujo, que los convierte en objetos del dominio. Si algo no cuadra, el dominio lanza FlujoInvalido con la ruta exacta del campo o del paso y la API responde 422.',
    codigo: `if ($seguidas > self::MAXIMO_DE_CONDICIONES_SEGUIDAS) {
    throw new FlujoInvalido($ruta, 'Admite como máximo 3 condiciones seguidas: si necesita más, cree otra automatización.');
}`,
  },
  {
    titulo: 'Validar contra la base',
    capa: 'back',
    nodos: ['refs'],
    aristas: ['lector>refs'],
    archivo: 'backend/app/Automatizaciones/Consultas/ReferenciasDelFlujo.php',
    texto:
      'Lo que el dominio no puede saber sin la base: que el rol al que se avisa exista y sirva para eso. Una consulta por cada paso que nombra a alguien.',
  },
  {
    titulo: 'Guardar',
    capa: 'back',
    nodos: ['guardar', 'regla'],
    aristas: ['refs>guardar', 'guardar>regla'],
    archivo: 'backend/app/Automatizaciones/Acciones/GuardarAutomatizacion.php',
    texto:
      'Se guarda la forma canónica del flujo (la que devuelve el dominio), con quien guardó como autor: sus acciones saldrán a nombre de esa persona. Queda en la auditoría como creada o modificada, con un detalle breve.',
  },
  {
    titulo: 'Activar',
    capa: 'back',
    nodos: ['estado', 'auditoria', 'revisar'],
    aristas: ['guardar>estado', 'estado>auditoria', 'estado>revisar'],
    archivo: 'backend/app/Automatizaciones/Acciones/RevisarEnseguida.php',
    texto:
      'Al activarla se registra en la auditoría y, como es una regla por tiempo, se encola su revisión en cuanto se confirma la transacción: no hay que esperar los 5 minutos del programador.',
  },
  {
    titulo: 'Buscar a quién le toca',
    capa: 'datos',
    nodos: ['revisar', 'consulta'],
    aristas: ['revisar>consulta'],
    archivo: 'backend/app/Automatizaciones/Consultas/IncidentesPorDisparar.php',
    texto:
      'Toda la decisión de «le llegó el momento» ocurre en SQL, sin traer incidentes a PHP. Los más antiguos primero, hasta 200 por revisión.',
    codigo: `WHERE estado IN ('abierto', 'en_progreso')
  AND primera_respuesta_en IS NULL
  AND vence_respuesta_en > :ahora
  AND TIMESTAMPDIFF(SECOND, created_at, :ahora) * 100
      >= TIMESTAMPDIFF(SECOND, created_at, vence_respuesta_en) * 80
  AND NOT EXISTS (SELECT 1 FROM ejecuciones_de_automatizacion
                  WHERE incidente_id = incidentes.id AND automatizacion_id = :regla)
ORDER BY id LIMIT 200`,
  },
  {
    titulo: 'Reservar el incidente',
    capa: 'datos',
    nodos: ['aplicar', 'ejec'],
    aristas: ['revisar>aplicar', 'aplicar>ejec'],
    archivo: 'backend/app/Automatizaciones/Acciones/AplicarAutomatizaciones.php',
    texto:
      'Dentro de una transacción, primero se inserta la ejecución. Si la fila ya existía (otro trabajo llegó antes o es un reintento), `insertOrIgnore` devuelve 0 y no se hace nada: la clave única decide quién actúa.',
    codigo: `if ($incidente === null || EjecucionDeAutomatizacion::query()->insertOrIgnore($ejecucion) === 0) {
    return;
}`,
  },
  {
    titulo: 'Decidir el camino',
    capa: 'back',
    nodos: ['decidir'],
    aristas: ['aplicar>decidir'],
    archivo: 'backend/app/Automatizaciones/Dominio/Flujo.php',
    texto:
      'HechosDelIncidente toma una foto del incidente (empresa, categoría, prioridad, palabras, consumo de cada plazo) y el dominio recorre el árbol: la condición «prioridad es una de crítica» se cumple y devuelve las acciones del camino sí.',
    codigo: `$hechos = HechosDelIncidente::de($incidente, Date::now()->toImmutable());
foreach ($automatizacion->flujo()->accionesPara($hechos) as $accion) {
    $this->ejecutarAccion->ejecutar($accion, $incidente->refresh(), $automatizacion);
}`,
  },
  {
    titulo: 'Actuar y avisar',
    capa: 'back',
    nodos: ['ejecutar', 'mensajes', 'notif', 'campana'],
    aristas: ['decidir>ejecutar', 'ejecutar>mensajes', 'ejecutar>notif', 'notif>campana'],
    archivo: 'backend/app/Automatizaciones/Acciones/EjecutarAccion.php',
    texto:
      '«Avisar» envía AvisoDeIncidente a todas las personas activas del rol: se guarda en `notifications`, se emite por su canal personal y la campanita lo muestra al instante. «Nota interna» escribe en la conversación con la regla como origen, que el solicitante no ve. La ejecución queda con 2 acciones hechas.',
    codigo: 'Notification::send($personas, new AvisoDeIncidente($incidente->id, $texto));',
  },
]

/** @type {import('../tipos.js').Plano} */
const ejecucion = {
  codigo: 'AU-01',
  titulo: 'Ejecución de reglas',
  nivel: 'C4 · 4',
  ancho: 1000,
  alto: 640,
  nodos: [
    {
      id: 'creado',
      x: 40,
      y: 50,
      w: 210,
      h: 62,
      tipo: 'Incidentes · evento',
      titulo: 'IncidenteCreado',
      sub: 'tras el commit',
    },
    { id: 'alcrear', x: 290, y: 50, w: 220, h: 62, tipo: 'Oyentes', titulo: 'AplicarAlCrearse', sub: 'en la cola' },
    {
      id: 'prog',
      x: 560,
      y: 50,
      w: 200,
      h: 62,
      tipo: 'Programador',
      titulo: 'automatizaciones:revisar',
      sub: 'cada 5 min',
    },
    {
      id: 'revisar',
      x: 560,
      y: 150,
      w: 200,
      h: 62,
      tipo: 'Trabajos',
      titulo: 'RevisarAutomatizacion',
      sub: 'única · lote 200',
    },
    {
      id: 'pordisparar',
      x: 800,
      y: 150,
      w: 170,
      h: 62,
      tipo: 'Consultas',
      titulo: 'IncidentesPorDisparar',
      sub: 'SQL',
    },
    {
      id: 'aplicar',
      x: 290,
      y: 260,
      w: 320,
      h: 62,
      tipo: 'Acciones',
      titulo: 'AplicarAutomatizaciones',
      sub: 'transacción por regla',
      clave: true,
    },
    {
      id: 'ejec',
      x: 700,
      y: 260,
      w: 270,
      h: 62,
      tipo: 'MySQL',
      titulo: 'ejecuciones_de_automatizacion',
      sub: 'único (regla, incidente)',
    },
    {
      id: 'flujo',
      x: 290,
      y: 370,
      w: 320,
      h: 62,
      tipo: 'Dominio · PHP puro',
      titulo: 'Flujo::recorrer(Hechos)',
      sub: 'qué camino y qué acciones',
    },
    {
      id: 'actualizar',
      x: 60,
      y: 480,
      w: 220,
      h: 62,
      tipo: 'Incidentes · acción',
      titulo: 'ActualizarIncidente',
      sub: 'asignar, prioridad, cerrar',
    },
    {
      id: 'mensaje',
      x: 320,
      y: 480,
      w: 220,
      h: 62,
      tipo: 'Incidentes · acción',
      titulo: 'EscribirMensaje',
      sub: 'responder, nota interna',
    },
    {
      id: 'aviso',
      x: 580,
      y: 480,
      w: 200,
      h: 62,
      tipo: 'Notificación',
      titulo: 'AvisoDeIncidente',
      sub: 'BD + campanita',
    },
  ],
  aristas: [
    { de: 'creado', a: 'alcrear', texto: 'cola' },
    { de: 'alcrear', a: 'aplicar', texto: 'ejecutar()' },
    { de: 'prog', a: 'revisar', texto: 'encola' },
    { de: 'revisar', a: 'pordisparar', texto: 'ids' },
    { de: 'revisar', a: 'aplicar', texto: 'aplicarA()' },
    { de: 'aplicar', a: 'ejec', texto: 'insertOrIgnore' },
    { de: 'aplicar', a: 'flujo' },
    { de: 'flujo', a: 'actualizar' },
    { de: 'flujo', a: 'mensaje' },
    { de: 'flujo', a: 'aviso' },
  ],
}

const efectos = ['aplicar', 'ejec', 'flujo', 'actualizar', 'mensaje', 'aviso']
const flechasDeEfecto = ['aplicar>ejec', 'aplicar>flujo', 'flujo>actualizar', 'flujo>mensaje', 'flujo>aviso']

/** @type {import('../tipos.js').Pagina} */
export const automatizaciones = {
  id: 'automatizaciones',
  titulo: 'Automatizaciones',
  grupo: 'administracion',
  icono: iconos.automatizaciones,
  entradilla:
    'Reglas que trabajan solas sobre los incidentes: un disparador, condiciones con camino sí y no, y acciones. Se arman con bloques, se prueban contra datos reales antes de activarlas y actúan una sola vez por incidente.',
  secciones: [
    {
      titulo: 'Una regla por dentro: «Críticos a punto de vencer»',
      extra: 'editor real',
      bloques: [
        { tipo: 'imagen', src: capturaDelEditor, alt: 'Editor de la automatización Críticos a punto de vencer' },
        {
          tipo: 'nota',
          texto:
            'Lo que muestra el editor es un árbol guardado como JSON: el disparador arriba, una condición con dos caminos y, en el camino «sí», dos acciones en orden. Abajo, la misma regla de punta a punta.',
        },
      ],
    },
    {
      titulo: 'Su vida, paso a paso',
      extra: 'plano AU-02',
      bloques: [{ tipo: 'recorrido', plano: vidaDeUnaRegla, pasos: pasosDeLaRegla }],
    },
    {
      titulo: 'Pruébela con un incidente',
      extra: 'misma lógica que IncidentesPorDisparar y Flujo',
      bloques: [{ tipo: 'simulador' }],
    },
    {
      titulo: 'Cómo se ejecuta',
      extra: 'plano AU-01',
      bloques: [
        {
          tipo: 'focos',
          plano: ejecucion,
          focos: [
            {
              etiqueta: 'Al crearse un incidente',
              nodos: ['creado', 'alcrear', ...efectos],
              aristas: ['creado>alcrear', 'alcrear>aplicar', ...flechasDeEfecto],
              nota: 'Al crearse: el evento sale solo si la transacción se confirmó; el oyente corre en la cola y aplica todas las reglas activas «al crearse», cada una en su transacción.',
            },
            {
              etiqueta: 'Por tiempo',
              nodos: ['prog', 'revisar', 'pordisparar', ...efectos],
              aristas: ['prog>revisar', 'revisar>pordisparar', 'revisar>aplicar', ...flechasDeEfecto],
              nota: 'Por tiempo: cada 5 minutos se encola una revisión por regla activa; la consulta trae hasta 200 incidentes a los que ya les llegó el momento y que la regla no ha tocado.',
            },
          ],
        },
      ],
    },
    {
      titulo: 'Garantías',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Una sola vez por incidente',
              'Índice único `una_ejecucion_por_incidente` (automatizacion_id, incidente_id) e `insertOrIgnore`: si dos trabajos llegan a la vez, solo uno inserta y actúa.',
            ],
            [
              'Una transacción por regla',
              'Si una acción no se puede hacer (cerrar uno que no está resuelto, asignar a quien ya no atiende), la regla se detiene ahí, conserva lo que ya hizo y anota el motivo en `ejecuciones.error`. Un error inesperado deshace la regla entera y la cola la reintenta.',
            ],
            [
              'Autor activo',
              'Si quien guardó la regla fue desactivado, la regla no actúa y la ejecución queda con el motivo.',
            ],
            [
              'Nada antes del commit',
              'IncidenteCreado implementa `ShouldDispatchAfterCommit`: una regla nunca actúa sobre un incidente que se revirtió.',
            ],
            [
              'Sin revisiones duplicadas',
              'El comando corre con `withoutOverlapping` y cada RevisarAutomatizacion es `ShouldBeUnique` por regla.',
            ],
            [
              'Estado vigente',
              'El trabajo vuelve a leer la regla: si la apagaron o la borraron mientras esperaba en la cola, no hace nada.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'Modelo del flujo',
      extra: 'pasos · JSON',
      bloques: [
        {
          tipo: 'codigo',
          codigo: `{
  "nombre": "Críticos a punto de vencer",
  "disparador": "sla_por_vencer", "porcentaje": 80, "plazo": "respuesta",
  "pasos": [{
    "tipo": "condicion", "campo": "prioridad", "operador": "es_una_de", "valores": ["critica"],
    "si": [
      { "tipo": "accion", "accion": "avisar", "destinatario": "rol", "id": 1 },
      { "tipo": "accion", "accion": "nota_interna", "texto": "Crítico al 80 % del plazo de respuesta: atenderlo ya." }
    ],
    "no": []
  }]
}`,
        },
        {
          tipo: 'filas',
          filas: [
            [
              'Forma',
              'Un árbol: acciones en orden que pueden terminar en una condición, y cada condición abre dos caminos, sí y no.',
            ],
            [
              'Disparadores',
              '`creacion`, `sla_por_vencer` (porcentaje y plazo), `sla_vencido` (plazo), `sin_respuesta` y `resuelto_sin_confirmar` (espera en minutos).',
            ],
            [
              'Condiciones',
              'Campos `empresa`, `categoria`, `prioridad`, `palabras`, `plazo_respuesta_consumido`, `plazo_resolucion_consumido`. Operadores `es_una_de`, `no_es_ninguna_de` y, donde hay orden, `al_menos`, `como_maximo`, `igual`.',
            ],
            [
              'Acciones',
              '`asignar`, `responder`, `avisar` (a una persona o un rol), `cambiar_prioridad`, `cambiar_categoria`, `nota_interna`, `cerrar`.',
            ],
            [
              'Variables',
              'Los mensajes admiten {nombre}, {número}, {título}, {empresa} y {prioridad} (PlantillaDeMensaje).',
            ],
            [
              'Límites',
              '3 condiciones seguidas, 40 pasos y 50 valores por condición (LectorDeFlujo). Un flujo inválido responde 422 con la ruta exacta del paso.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'Rendimiento',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Filtro en SQL',
              'IncidentesPorDisparar decide en la base a quién le toca: el porcentaje del plazo con `TIMESTAMPDIFF` y lo ya ejecutado con `NOT EXISTS`.',
            ],
            [
              'Lotes',
              'Cada revisión atiende hasta 200 incidentes por regla (`RevisarAutomatizacion::LOTE`); el resto queda para la siguiente, sin llenar la cola de golpe.',
            ],
            [
              'Índices',
              '`(disparador, activa)` para encontrar las reglas activas; el único de ejecuciones sirve también de búsqueda.',
            ],
            [
              'Prueba acotada',
              '«Probar» simula sobre los incidentes de los últimos 30 días, con un máximo de 5.000, y cuenta a cuántos les toca ahora mismo.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'Seguridad y trazabilidad',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Permiso',
              'Todas las rutas exigen `administrar_automatizaciones`; solo el rol Administrador lo tiene por defecto.',
            ],
            [
              'A nombre de quién',
              'Las acciones se registran a nombre de quien guardó la regla, con el nombre de la regla como origen.',
            ],
            [
              'Lo que ve el cliente',
              'El origen solo se muestra a quien puede leer notas internas; el solicitante nunca ve que fue una automatización.',
            ],
            [
              'Referencias válidas',
              'ReferenciasDelFlujo comprueba que existan las personas, roles y empresas del flujo antes de guardar.',
            ],
            ['Auditoría', 'Se registran creada, modificada, activada, desactivada y eliminada, con un detalle breve.'],
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
            ['GET /automatizaciones', 'Listar, con cuántas ejecuciones lleva cada una, cuántas con error y la última.'],
            ['POST /automatizaciones', 'Crear (201). 422 si el flujo es inválido.'],
            ['GET /automatizaciones/{id}', 'Ver una.'],
            ['PUT /automatizaciones/{id}', 'Guardar cambios.'],
            [
              'PATCH /automatizaciones/{id}/estado',
              'Activar o desactivar. Al activar una por tiempo se revisa enseguida.',
            ],
            [
              'POST /automatizaciones/prueba',
              'Probar sin guardar: caminos, cantidades, ejemplos y a cuántos les toca ahora.',
            ],
            ['DELETE /automatizaciones/{id}', 'Eliminar.'],
          ],
        },
      ],
    },
    {
      titulo: 'Datos',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'automatizaciones',
              'nombre, activa, disparador, espera_minutos, porcentaje, plazo, pasos (JSON), autor_id (restrict). Índice (disparador, activa).',
            ],
            [
              'ejecuciones_de_automatizacion',
              'automatizacion_id, incidente_id (cascade), acciones_hechas, error, created_at. Único (automatizacion_id, incidente_id).',
            ],
            [
              'historial_incidentes · mensajes_incidente',
              'Columna `origen`: el nombre de la regla que hizo el cambio.',
            ],
            ['notifications', 'Avisos de la campanita (notificaciones de Laravel).'],
          ],
        },
      ],
    },
    {
      titulo: 'Frontend',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['Vistas', 'VistaAutomatizaciones (lista) y VistaEditorDeAutomatizacion (editor).'],
            [
              'Editor',
              'CaminoDelFlujo es recursivo: dibuja cada rama sí y no; comparte el estado del editor con `provide`/`inject`.',
            ],
            [
              'Bloques',
              'BibliotecaDeBloques, TarjetaDePaso, UnionDelFlujo, CamposDelDisparo, CamposDeCondicion, CamposDeAccion, CampoDeMensaje.',
            ],
            [
              'Estado',
              'useAutomatizaciones y useEditorDeAutomatizacion, con vue-query y claves propias en `api/claves.js`.',
            ],
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
              'Dominio (26, sin base de datos)',
              'FlujoTest, LectorDeFlujoTest, DisparoTest, ConsumoDelPlazoTest, PlantillaDeMensajeTest, HechosTest.',
            ],
            ['Integración (29)', 'AplicarAlCrearseTest, RevisarPorTiempoTest, AutomatizacionesApiTest.'],
            [
              'Frontend',
              '`flujo.test.js`, `catalogo.test.js` y la vista; recorrido de punta a punta en `automatizaciones.spec.js`.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'Decisiones',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Flujo en JSON',
              'Un árbol se lee y se guarda entero; en tablas normalizadas habría que reconstruirlo en cada ejecución. El dominio lo valida antes de guardarlo.',
            ],
            [
              'Dominio en PHP puro',
              'Las reglas se prueban en milisegundos y no dependen de Laravel ni de los modelos.',
            ],
            [
              'Revisión cada 5 minutos',
              'Suficiente para plazos medidos en horas, sin cargar la base; al activar una regla se revisa en el acto.',
            ],
          ],
        },
      ],
    },
  ],
}
