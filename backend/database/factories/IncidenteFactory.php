<?php

namespace Database\Factories;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Models\Incidente;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Genera incidentes con el aspecto de una mesa de soporte real: situaciones típicas con su
 * categoría y su solución, prioridad acorde a la gravedad, fechas coherentes con el estado y
 * vencimientos según la prioridad. La solución solo existe en los incidentes resueltos o cerrados.
 *
 * @extends Factory<Incidente>
 */
class IncidenteFactory extends Factory
{
    protected $model = Incidente::class;

    // Distribución de estados (% aproximado) y antigüedad máxima en días de cada uno.
    public const ESTADOS = [
        'abierto' => ['peso' => 30, 'dias' => 5],
        'en_progreso' => ['peso' => 25, 'dias' => 10],
        'resuelto' => ['peso' => 25, 'dias' => 30],
        'cerrado' => ['peso' => 20, 'dias' => 60],
    ];

    // Horas de plazo para resolver, según la prioridad.
    public const PLAZO_HORAS = [
        'critica' => 4,
        'alta' => 24,
        'media' => 72,
        'baja' => 168,
    ];

    // [título, descripción, categoría, prioridades posibles, solución]
    public const SITUACIONES = [
        ['No puedo conectarme a la VPN desde casa', 'Desde ayer la VPN corporativa rechaza mis credenciales con el error 691. Trabajo en remoto y necesito acceso a los servidores de archivos.', 'red', ['media', 'alta'], 'Se restableció el perfil de VPN del usuario y se actualizó el cliente a la última versión. Se verificó el acceso a los servidores de archivos desde la red doméstica.'],
        ['La impresora del segundo piso no imprime', 'La multifuncional de la zona de contabilidad muestra «Error 49.4C02» y los trabajos quedan en cola. Ya se reinició el equipo sin éxito.', 'hardware', ['baja', 'media'], 'Se liberó la cola de impresión, se reinstaló el controlador y se reemplazó el rodillo de arrastre. La impresora quedó imprimiendo con normalidad.'],
        ['El correo corporativo no sincroniza en el celular', 'Desde el cambio de contraseña de la semana pasada el correo no se actualiza en mi teléfono. En el portal web sí funciona.', 'correo', ['baja', 'media'], 'Se eliminó la cuenta del teléfono y se volvió a configurar con la contraseña nueva. La sincronización quedó restablecida.'],
        ['Lentitud extrema en el sistema de facturación', 'Al emitir facturas la pantalla tarda más de dos minutos en responder. Pasa desde las 8:00 y afecta a todo el equipo de ventas.', 'software', ['alta', 'critica'], 'Se reindexó la base de datos del módulo de facturación y se liberó espacio en el disco del servidor. Los tiempos de respuesta volvieron a la normalidad.'],
        ['Solicitud de acceso a la carpeta compartida de Contabilidad', 'Necesito permisos de lectura y escritura en la carpeta de Contabilidad del servidor de archivos para el cierre mensual. Autoriza la jefa del área.', 'accesos', ['baja', 'media'], 'Se agregó al usuario al grupo de seguridad de Contabilidad con la aprobación de la jefa del área. Se comprobó el acceso de lectura y escritura.'],
        ['Alta de usuario para nuevo ingreso', 'Se incorpora una analista al área de Recursos Humanos el próximo lunes. Requiere cuenta de dominio, correo y acceso al ERP.', 'accesos', ['media'], 'Se creó la cuenta de dominio, el buzón de correo y el usuario del ERP con el perfil de Recursos Humanos. Las credenciales se entregaron por el canal seguro.'],
        ['El portátil no enciende', 'El equipo asignado dejó de encender tras una caída de energía. La batería está cargada y el indicador no responde.', 'hardware', ['media', 'alta'], 'Se reemplazó el cargador y la batería, que había quedado dañada por la caída de energía. El equipo enciende y pasó las pruebas de diagnóstico.'],
        ['Pantalla azul recurrente en el equipo de diseño', 'El computador se reinicia con pantalla azul varias veces al día al abrir Photoshop. Ya se actualizaron los controladores de video.', 'software', ['media', 'alta'], 'Se actualizó el controlador de la tarjeta de video y se reinstaló Photoshop. No se repitieron los reinicios tras tres días de seguimiento.'],
        ['Correo sospechoso de phishing recibido', 'Llegó un mensaje que simula ser del banco pidiendo actualizar datos. Varios compañeros lo recibieron y uno hizo clic en el enlace.', 'seguridad', ['alta', 'critica'], 'Se bloqueó el remitente y el enlace en el filtro de correo, se restablecieron las credenciales de la persona que hizo clic y se envió un aviso al personal.'],
        ['Solicitud de instalación de Adobe Acrobat Pro', 'Necesito editar y firmar PDF de contratos a diario. Cuento con aprobación de mi jefe directo para la licencia.', 'software', ['baja'], 'Se instaló Adobe Acrobat Pro con la licencia asignada y se verificó la edición y la firma de PDF.'],
        ['Sin conexión a internet en la sala de reuniones B', 'El punto de red de la sala no entrega dirección IP y la señal WiFi es intermitente. Tenemos una reunión con clientes mañana a primera hora.', 'red', ['media', 'alta'], 'Se reemplazó el cable de red y se reconfiguró el puerto del switch. La sala quedó con conexión por cable y WiFi estable.'],
        ['El ERP no permite registrar pedidos de compra', 'Al guardar un pedido aparece «No se pudo completar la transacción». Compras no puede emitir órdenes desde esta mañana.', 'software', ['alta', 'critica'], 'Se reinició el servicio de transacciones del ERP y se corrigió un bloqueo en la tabla de pedidos. Compras volvió a emitir órdenes.'],
        ['Teléfono IP sin tono en recepción', 'El teléfono de la recepción no tiene tono ni registra llamadas entrantes. Se probó con otro cable de red.', 'telefonia', ['media'], 'Se volvió a registrar el teléfono en la central IP y se actualizó su firmware. Ya tiene tono y recibe llamadas.'],
        ['Restablecer contraseña de la cuenta de dominio', 'Bloqueé mi cuenta tras varios intentos fallidos y no puedo iniciar sesión en mi equipo.', 'accesos', ['baja', 'media'], 'Se desbloqueó la cuenta y se asignó una contraseña temporal que debió cambiarse en el primer inicio de sesión.'],
        ['Falla en la copia de seguridad nocturna del servidor de archivos', 'El trabajo de respaldo terminó con error anoche por falta de espacio en el destino. No hay copia completa desde hace dos días.', 'software', ['alta', 'critica'], 'Se amplió el espacio del destino de respaldo, se depuraron copias antiguas y se ejecutó una copia completa manual. El trabajo nocturno terminó sin errores.'],
        ['Monitor con parpadeo constante', 'El segundo monitor parpadea cada pocos segundos y causa dolor de cabeza. Ya se cambió el cable HDMI.', 'hardware', ['baja'], 'Se reemplazó el monitor por uno nuevo, porque el panel presentaba una falla. Se actualizó el inventario del equipo.'],
        ['Solicitud de segundo monitor para el puesto de trabajo', 'Por el volumen de conciliaciones solicito un segundo monitor. Adjunto la aprobación de la coordinación.', 'hardware', ['baja'], 'Se entregó e instaló un segundo monitor y se configuró la pantalla extendida.'],
        ['Excel se cierra solo al abrir el archivo de presupuesto', 'El libro de presupuesto se cierra al abrirlo y muestra «Excel ha dejado de funcionar». Otros archivos abren con normalidad.', 'software', ['media'], 'Se reparó la instalación de Office y se recuperó una copia anterior del libro. Se eliminaron los complementos que provocaban el cierre.'],
        ['No llegan los correos enviados a clientes externos', 'Varios clientes indican que no reciben nuestros mensajes. Al enviarlos no aparece ningún error, pero tampoco hay respuesta.', 'correo', ['alta'], 'Se corrigió el registro SPF del dominio, que estaba desactualizado. Los mensajes volvieron a entregarse a los clientes externos.'],
        ['Actualización de Windows bloqueada en el equipo de gerencia', 'El equipo lleva dos días en «Preparando Windows» y no permite iniciar sesión. Se necesita para la junta del jueves.', 'software', ['alta'], 'Se detuvo la actualización atascada, se restauró el sistema al punto anterior y se instaló el parche manualmente. El equipo inicia con normalidad.'],
        ['Permiso para acceder al sistema de nómina', 'Asumo funciones de apoyo en nómina y requiero acceso de consulta al módulo de liquidaciones, con autorización de la gerente.', 'accesos', ['media'], 'Se otorgó el rol de consulta del módulo de liquidaciones con la autorización de la gerente y se dejó registrada la aprobación.'],
        ['Teclado y mouse inalámbricos sin respuesta', 'Se cambiaron las baterías y se reconectó el receptor USB, pero el teclado y el mouse siguen sin responder.', 'hardware', ['baja'], 'Se reemplazó el receptor USB y se emparejaron de nuevo ambos dispositivos. Funcionan correctamente.'],
        ['Videoconferencias con audio entrecortado', 'En las reuniones de Teams la voz se corta y se escucha metálica. Ocurre desde la última actualización del cliente.', 'red', ['media'], 'Se priorizó el tráfico de Teams en el router y se actualizó el cliente. El audio se estabilizó en pruebas con tres reuniones.'],
        ['Servidor de aplicaciones sin respuesta', 'El servidor de aplicaciones dejó de responder y las áreas no pueden abrir el sistema interno. Requiere atención inmediata.', 'hardware', ['critica'], 'Se reinició el servidor tras agotarse la memoria por un proceso detenido y se aplicó un límite de recursos al servicio. Los sistemas internos se restablecieron.'],
        ['Cambio de tóner en la impresora de gerencia', 'La impresora de gerencia indica tóner bajo y las hojas salen con rayas. Hay repuesto en bodega.', 'hardware', ['baja'], 'Se reemplazó el cartucho de tóner y se limpió el fusor. Las impresiones salen sin rayas.'],
        ['Acceso denegado a la plataforma de capacitación', 'Al ingresar al portal de capacitaciones aparece «No tienes permisos». Debo completar el curso obligatorio antes de fin de mes.', 'accesos', ['baja', 'media'], 'Se asignó el curso obligatorio al perfil del usuario y se sincronizó la cuenta con el directorio.'],
        ['Equipo con virus detectado por el antivirus', 'El antivirus reportó un troyano en mi equipo y lo puso en cuarentena parcial. El equipo está lento y abre ventanas emergentes.', 'seguridad', ['alta'], 'Se aisló el equipo de la red, se ejecutó un análisis completo y se eliminó el troyano. Se cambiaron las contraseñas del usuario y se reinstaló el navegador.'],
        ['Configurar la firma institucional del correo', 'Solicito que se aplique la plantilla de firma institucional con mi nuevo cargo y teléfono.', 'correo', ['baja'], 'Se aplicó la plantilla de firma institucional con el cargo y el teléfono actualizados.'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$titulo, $descripcion, $categoria, $prioridades, $solucion] = fake()->randomElement(self::SITUACIONES);
        $prioridad = Prioridad::from(fake()->randomElement($prioridades));
        $estado = Estado::from(self::estadoAlAzar());

        $creado = now()->subMinutes(random_int(60, self::ESTADOS[$estado->value]['dias'] * 24 * 60));
        $actualizado = $estado === Estado::Abierto
            ? $creado
            : $creado->copy()->addSeconds(random_int(0, (int) $creado->diffInSeconds(now(), true)));

        return [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'prioridad' => $prioridad,
            'estado' => $estado,
            'categoria' => Categoria::from($categoria),
            'solucion' => in_array($estado, [Estado::Resuelto, Estado::Cerrado], true) ? $solucion : null,
            'usuario_creador_id' => User::factory(),
            'usuario_asignado_id' => null,
            'fecha_vencimiento' => $creado->copy()->addHours(self::PLAZO_HORAS[$prioridad->value]),
            'resuelto_en' => self::resueltoEn($estado, $creado, $actualizado),
            'created_at' => $creado,
            'updated_at' => $actualizado,
        ];
    }

    /**
     * El paso a resuelto, en el mismo punto donde el seeder lo deja en el historial: los pasos del ciclo se
     * reparten en partes iguales entre la creación y la última actualización.
     */
    private static function resueltoEn(Estado $estado, CarbonInterface $creado, CarbonInterface $actualizado): ?CarbonInterface
    {
        $pasos = (int) array_search($estado, Estado::cases(), true);
        $pasoResuelto = (int) array_search(Estado::Resuelto, Estado::cases(), true);

        if ($pasos < $pasoResuelto || $pasos === 0) {
            return null;
        }

        return $creado->copy()->addSeconds((int) ($creado->diffInSeconds($actualizado, true) * $pasoResuelto / $pasos));
    }

    private static function estadoAlAzar(): string
    {
        $azar = random_int(1, array_sum(array_column(self::ESTADOS, 'peso')));

        foreach (self::ESTADOS as $estado => $datos) {
            if (($azar -= $datos['peso']) <= 0) {
                return $estado;
            }
        }

        return array_key_last(self::ESTADOS);
    }
}
