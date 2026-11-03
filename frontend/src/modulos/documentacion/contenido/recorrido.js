import { iconos } from '@/compartido/iconos.js'

/** @type {import('../tipos.js').Plano} */
const altaDeIncidente = {
  codigo: 'R-01',
  titulo: 'Alta de un incidente',
  nivel: 'C4 · 4',
  ancho: 1000,
  alto: 720,
  nodos: [
    { id: 'z-nav', zona: true, x: 30, y: 30, w: 290, h: 600, titulo: 'NAVEGADOR · VUE' },
    { id: 'z-srv', zona: true, x: 350, y: 30, w: 360, h: 600, titulo: 'SERVIDOR · LARAVEL' },
    { id: 'z-inf', zona: true, x: 740, y: 30, w: 230, h: 600, titulo: 'INFRAESTRUCTURA' },
    { id: 'vista', x: 50, y: 64, w: 250, h: 58, tipo: 'VistaFormulario.vue', titulo: 'Formulario' },
    { id: 'useform', x: 50, y: 144, w: 250, h: 58, tipo: 'useFormularioIncidente', titulo: 'useMutation' },
    { id: 'apifront', x: 50, y: 224, w: 250, h: 58, tipo: 'api/incidentes.js', titulo: 'crearIncidente()' },
    { id: 'axios', x: 50, y: 304, w: 250, h: 58, tipo: 'api/cliente.js', titulo: 'axios + Bearer' },
    { id: 'cache', x: 50, y: 464, w: 250, h: 58, tipo: 'vue-query', titulo: 'Invalida y recarga' },
    { id: 'echo', x: 50, y: 544, w: 250, h: 58, tipo: 'plugins/tiempo-real.js', titulo: 'Echo escucha' },
    { id: 'ruta', x: 370, y: 64, w: 320, h: 58, tipo: 'Incidentes/rutas.php', titulo: 'auth:sanctum · can(crear)' },
    { id: 'request', x: 370, y: 144, w: 320, h: 58, tipo: 'CrearIncidenteRequest', titulo: 'Valida y normaliza' },
    { id: 'ctl', x: 370, y: 224, w: 320, h: 58, tipo: 'IncidenteController@store', titulo: 'Orquesta y responde' },
    {
      id: 'accion',
      x: 370,
      y: 304,
      w: 320,
      h: 58,
      tipo: 'Acciones/CrearIncidente',
      titulo: 'Transacción',
      clave: true,
    },
    { id: 'plazos', x: 370, y: 384, w: 150, h: 58, tipo: 'Plazos de la empresa', titulo: 'Fechas límite' },
    { id: 'hist', x: 540, y: 384, w: 150, h: 58, tipo: 'RegistrarHistorial', titulo: 'Historial' },
    {
      id: 'commit',
      x: 370,
      y: 464,
      w: 320,
      h: 58,
      tipo: 'AvisarCambios · IncidenteCreado',
      titulo: 'Esperan al commit',
    },
    { id: 'mysql', x: 760, y: 304, w: 190, h: 58, tipo: 'MySQL', titulo: 'incidentes + historial' },
    { id: 'reglas', x: 760, y: 384, w: 190, h: 58, tipo: 'AplicarAlCrearse', titulo: 'Automatizaciones' },
    { id: 'redis', x: 760, y: 464, w: 190, h: 58, tipo: 'Redis', titulo: 'Cola de trabajos' },
    { id: 'reverb', x: 760, y: 544, w: 190, h: 58, tipo: 'Reverb', titulo: 'private-incidentes' },
  ],
  aristas: [
    { de: 'vista', a: 'useform' },
    { de: 'useform', a: 'apifront' },
    { de: 'apifront', a: 'axios' },
    { de: 'axios', a: 'ruta', texto: 'POST', modo: 'h', mx: 325 },
    { de: 'ruta', a: 'request' },
    { de: 'request', a: 'ctl' },
    { de: 'ctl', a: 'accion' },
    { de: 'accion', a: 'plazos' },
    { de: 'accion', a: 'hist' },
    { de: 'accion', a: 'mysql', texto: 'INSERT' },
    { de: 'accion', a: 'commit' },
    { de: 'commit', a: 'redis', texto: 'encola' },
    { de: 'redis', a: 'reglas', punteada: true, texto: 'ejecuta' },
    { de: 'redis', a: 'reverb', texto: 'emite' },
    { de: 'reverb', a: 'echo', texto: '.incidentes.cambiados' },
    { de: 'echo', a: 'cache' },
    { de: 'ctl', a: 'useform', punteada: true, texto: '201', modo: 'h', mx: 345 },
  ],
}

/** @type {import('../tipos.js').Paso[]} */
const pasos = [
  {
    titulo: 'El formulario arma el pedido',
    capa: 'front',
    nodos: ['vista', 'useform'],
    aristas: ['vista>useform'],
    archivo: 'frontend/src/modulos/incidentes/composables/useFormularioIncidente.js',
    texto:
      'La vista valida los campos y el composable lanza una mutación de vue-query. La clave de idempotencia se genera una sola vez por formulario: si la red falla y se reintenta, el servidor reconoce el mismo envío.',
    codigo: `const guardado = useMutation({
  mutationFn: (datos) => crearIncidente(datos, claveDeIdempotencia),
})`,
  },
  {
    titulo: 'La llamada a la API',
    capa: 'front',
    nodos: ['apifront', 'axios'],
    aristas: ['useform>apifront', 'apifront>axios'],
    archivo: 'frontend/src/modulos/incidentes/api/incidentes.js',
    texto:
      'Un adaptador convierte el modelo de la interfaz en el cuerpo que espera la API. El cliente HTTP agrega el token Bearer solo a peticiones del mismo origen.',
    codigo: `export async function crearIncidente(campos, claveDeIdempotencia) {
  const { data } = await http.post('incidentes', aCuerpoDeIncidente(campos), {
    headers: { 'Idempotency-Key': claveDeIdempotencia },
  })
  return aIncidente(data.data)
}`,
  },
  {
    titulo: 'Autenticación y permiso',
    capa: 'back',
    nodos: ['ruta'],
    aristas: ['axios>ruta'],
    archivo: 'backend/app/Incidentes/rutas.php',
    texto:
      'La ruta vive en el módulo y `backend/routes/api.php` la carga dentro del grupo con el middleware común: Sanctum valida el token, el limitador frena abusos y la política decide si esta persona puede crear incidentes. Toda ruta declara su autorización: el middleware `autorizacion.declarada` rechaza la que la olvide.',
    codigo: `// backend/routes/api.php
Route::middleware(['auth:sanctum', 'throttle:api', 'autorizacion.declarada'])->group(function () {
    foreach (['Identidad', 'Incidentes', 'Automatizaciones', 'Auditoria'] as $modulo) {
        require app_path("{$modulo}/rutas.php");
    }
});

// backend/app/Incidentes/rutas.php
Route::post('incidentes', [IncidenteController::class, 'store'])->can('crear', Incidente::class);`,
  },
  {
    titulo: 'Validación',
    capa: 'back',
    nodos: ['request'],
    aristas: ['ruta>request'],
    archivo: 'backend/app/Incidentes/Http/Requests/CrearIncidenteRequest.php',
    texto:
      'El FormRequest valida tipos, longitudes y catálogos, y entrega solo los datos permitidos. Si algo falla, responde 422 con el detalle por campo y nada llega al caso de uso.',
  },
  {
    titulo: 'El controlador orquesta',
    capa: 'back',
    nodos: ['ctl'],
    aristas: ['request>ctl'],
    archivo: 'backend/app/Incidentes/Http/Controllers/IncidenteController.php',
    texto:
      'No tiene lógica de negocio: llama al caso de uso y da forma a la respuesta. Un reintento con la misma clave recibe el incidente ya creado, marcado con una cabecera.',
    codigo: `$incidente = $crearIncidente->ejecutar($request->user(), $request->datos(), $request->claveDeIdempotencia());
return $incidente->wasRecentlyCreated
    ? $respuesta->setStatusCode(Response::HTTP_CREATED)
    : $respuesta->header('Idempotent-Replayed', 'true');`,
  },
  {
    titulo: 'Plazos y guardado en una transacción',
    capa: 'back',
    nodos: ['accion', 'plazos', 'mysql'],
    aristas: ['ctl>accion', 'accion>plazos', 'accion>mysql'],
    archivo: 'backend/app/Incidentes/Acciones/CrearIncidente.php',
    texto:
      'Se calculan las fechas límite de respuesta y resolución según el acuerdo de servicio de la empresa y la prioridad, y se guardan con el incidente. Así filtrar vencidos entre cien mil es una consulta indexada, no un cálculo.',
    codigo: `$limites = $this->plazosDeLaEmpresa->limites($creador->cliente_id, $prioridad, Date::now()->toImmutable());
$incidente = Incidente::create($datos + [
    'fecha_vencimiento' => $limites['resolucion'],
    'vence_respuesta_en' => $limites['respuesta'],
    'estado' => Estado::Abierto,
    'clave_de_idempotencia' => $claveDeIdempotencia,
]);`,
  },
  {
    titulo: 'Historial',
    capa: 'back',
    nodos: ['hist', 'mysql'],
    aristas: ['accion>hist'],
    archivo: 'backend/app/Incidentes/Acciones/RegistrarHistorial.php',
    texto:
      'Dentro de la misma transacción queda la entrada «creado» en `historial_incidentes`. Si algo falla después, no queda un incidente sin historial ni un historial sin incidente.',
  },
  {
    titulo: 'Avisos que esperan al commit',
    capa: 'back',
    nodos: ['commit'],
    aristas: ['accion>commit'],
    archivo: 'backend/app/Incidentes/Acciones/AvisarCambios.php',
    texto:
      'Nada sale hacia afuera mientras la transacción esté abierta: si se revierte, nadie se entera de un incidente que no existe. AvisarCambios junta los cambios de la petición en un solo mensaje; IncidenteCreado implementa `ShouldDispatchAfterCommit`.',
    codigo: `$this->avisarCambios->registrar($incidente, TipoDeCambio::Creado);
event(new IncidenteCreado($incidente->id));
// AvisarCambios: DB::afterCommit(fn () => $this->enviar());`,
  },
  {
    titulo: 'La cola trabaja',
    capa: 'infra',
    nodos: ['redis', 'reglas'],
    aristas: ['commit>redis', 'redis>reglas'],
    archivo: 'docker-compose.yml · cola',
    texto:
      'Tras el commit, el aviso y el oyente van a la cola en Redis. El contenedor de trabajos los toma (hasta 3 intentos) y aplica las automatizaciones activas «al crearse», una sola vez por incidente.',
    codigo: 'php artisan queue:work --tries=3 --backoff=5 --max-time=3600',
  },
  {
    titulo: 'Respuesta 201',
    capa: 'back',
    nodos: ['ctl', 'useform'],
    aristas: ['ctl>useform'],
    archivo: 'backend/app/Incidentes/Http/Resources/IncidenteResource.php',
    texto:
      'La persona que lo creó recibe el incidente con su código y sus plazos. La interfaz navega al detalle con los datos ya en caché.',
  },
  {
    titulo: 'Tiempo real para los demás',
    capa: 'infra',
    nodos: ['reverb', 'echo'],
    aristas: ['redis>reverb', 'reverb>echo'],
    archivo: 'backend/app/Incidentes/Eventos/IncidentesCambiados.php',
    texto:
      'Reverb emite `incidentes.cambiados` por el canal privado de incidentes (quien ve todo) y por el canal personal de los involucrados. El canal se autoriza con el mismo token, contra `/api/v1/broadcasting/auth`.',
    codigo: "canalDeIncidentes.listen('.incidentes.cambiados', alAvisar)",
  },
  {
    titulo: 'Las pantallas se actualizan solas',
    capa: 'front',
    nodos: ['echo', 'cache'],
    aristas: ['echo>cache'],
    archivo: 'frontend/src/nucleo/plugins/tiempo-real.js',
    texto:
      'Al llegar el aviso se invalidan las claves de caché de incidentes y vue-query vuelve a pedir solo lo que está en pantalla. Si el WebSocket se cae y vuelve, se refresca todo una vez; sin WebSocket, hay un refresco de respaldo.',
  },
]

/** @type {import('../tipos.js').Pagina} */
export const recorrido = {
  id: 'recorrido',
  titulo: 'Recorrido de un incidente',
  grupo: 'tecnico',
  icono: iconos.recorrido,
  entradilla:
    'Qué pasa, archivo por archivo, desde que alguien pulsa «Crear» hasta que la pantalla de los demás se actualiza sola. Avance paso a paso.',
  secciones: [
    { titulo: 'Plano', extra: 'plano R-01', bloques: [{ tipo: 'recorrido', plano: altaDeIncidente, pasos }] },
  ],
}
