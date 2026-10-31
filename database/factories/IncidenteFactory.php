<?php

namespace Database\Factories;

use App\Dominio\Estado;
use App\Dominio\Prioridad;
use App\Models\Incidente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Genera incidentes con el aspecto de una mesa de soporte real: situaciones típicas,
 * prioridad acorde a la gravedad, fechas coherentes con el estado y vencimientos
 * según la prioridad.
 *
 * @extends Factory<Incidente>
 */
class IncidenteFactory extends Factory
{
    // Distribución de estados (% aproximado) y antigüedad máxima en días de cada uno.
    private const ESTADOS = [
        'abierto' => ['peso' => 30, 'dias' => 5],
        'en_progreso' => ['peso' => 25, 'dias' => 10],
        'resuelto' => ['peso' => 25, 'dias' => 30],
        'cerrado' => ['peso' => 20, 'dias' => 60],
    ];

    // Horas de plazo para resolver, según la prioridad.
    private const PLAZO_HORAS = [
        'critica' => 4,
        'alta' => 24,
        'media' => 72,
        'baja' => 168,
    ];

    private const SITUACIONES = [
        ['No puedo conectarme a la VPN desde casa', 'Desde ayer la VPN corporativa rechaza mis credenciales con el error 691. Trabajo en remoto y necesito acceso a los servidores de archivos.', ['media', 'alta']],
        ['La impresora del segundo piso no imprime', 'La multifuncional de la zona de contabilidad muestra «Error 49.4C02» y los trabajos quedan en cola. Ya se reinició el equipo sin éxito.', ['baja', 'media']],
        ['El correo corporativo no sincroniza en el celular', 'Desde el cambio de contraseña de la semana pasada el correo no se actualiza en mi teléfono. En el portal web sí funciona.', ['baja', 'media']],
        ['Lentitud extrema en el sistema de facturación', 'Al emitir facturas la pantalla tarda más de dos minutos en responder. Pasa desde las 8:00 y afecta a todo el equipo de ventas.', ['alta', 'critica']],
        ['Solicitud de acceso a la carpeta compartida de Contabilidad', 'Necesito permisos de lectura y escritura en la carpeta de Contabilidad del servidor de archivos para el cierre mensual. Autoriza la jefa del área.', ['baja', 'media']],
        ['Alta de usuario para nuevo ingreso', 'Se incorpora una analista al área de Recursos Humanos el próximo lunes. Requiere cuenta de dominio, correo y acceso al ERP.', ['media']],
        ['El portátil no enciende', 'El equipo asignado dejó de encender tras una caída de energía. La batería está cargada y el indicador no responde.', ['media', 'alta']],
        ['Pantalla azul recurrente en el equipo de diseño', 'El computador se reinicia con pantalla azul varias veces al día al abrir Photoshop. Ya se actualizaron los controladores de video.', ['media', 'alta']],
        ['Correo sospechoso de phishing recibido', 'Llegó un mensaje que simula ser del banco pidiendo actualizar datos. Varios compañeros lo recibieron y uno hizo clic en el enlace.', ['alta', 'critica']],
        ['Solicitud de instalación de Adobe Acrobat Pro', 'Necesito editar y firmar PDF de contratos a diario. Cuento con aprobación de mi jefe directo para la licencia.', ['baja']],
        ['Sin conexión a internet en la sala de reuniones B', 'El punto de red de la sala no entrega dirección IP y la señal WiFi es intermitente. Tenemos una reunión con clientes mañana a primera hora.', ['media', 'alta']],
        ['El ERP no permite registrar pedidos de compra', 'Al guardar un pedido aparece «No se pudo completar la transacción». Compras no puede emitir órdenes desde esta mañana.', ['alta', 'critica']],
        ['Teléfono IP sin tono en recepción', 'El teléfono de la recepción no tiene tono ni registra llamadas entrantes. Se probó con otro cable de red.', ['media']],
        ['Restablecer contraseña de la cuenta de dominio', 'Bloqueé mi cuenta tras varios intentos fallidos y no puedo iniciar sesión en mi equipo.', ['baja', 'media']],
        ['Falla en la copia de seguridad nocturna del servidor de archivos', 'El trabajo de respaldo terminó con error anoche por falta de espacio en el destino. No hay copia completa desde hace dos días.', ['alta', 'critica']],
        ['Monitor con parpadeo constante', 'El segundo monitor parpadea cada pocos segundos y causa dolor de cabeza. Ya se cambió el cable HDMI.', ['baja']],
        ['Solicitud de segundo monitor para el puesto de trabajo', 'Por el volumen de conciliaciones solicito un segundo monitor. Adjunto la aprobación de la coordinación.', ['baja']],
        ['Excel se cierra solo al abrir el archivo de presupuesto', 'El libro de presupuesto se cierra al abrirlo y muestra «Excel ha dejado de funcionar». Otros archivos abren con normalidad.', ['media']],
        ['No llegan los correos enviados a clientes externos', 'Varios clientes indican que no reciben nuestros mensajes. Al enviarlos no aparece ningún error, pero tampoco hay respuesta.', ['alta']],
        ['Actualización de Windows bloqueada en el equipo de gerencia', 'El equipo lleva dos días en «Preparando Windows» y no permite iniciar sesión. Se necesita para la junta del jueves.', ['alta']],
        ['Permiso para acceder al sistema de nómina', 'Asumo funciones de apoyo en nómina y requiero acceso de consulta al módulo de liquidaciones, con autorización de la gerente.', ['media']],
        ['Teclado y mouse inalámbricos sin respuesta', 'Se cambiaron las baterías y se reconectó el receptor USB, pero el teclado y el mouse siguen sin responder.', ['baja']],
        ['Videoconferencias con audio entrecortado', 'En las reuniones de Teams la voz se corta y se escucha metálica. Ocurre desde la última actualización del cliente.', ['media']],
        ['Servidor de aplicaciones sin respuesta', 'El servidor de aplicaciones dejó de responder y las áreas no pueden abrir el sistema interno. Requiere atención inmediata.', ['critica']],
        ['Cambio de tóner en la impresora de gerencia', 'La impresora de gerencia indica tóner bajo y las hojas salen con rayas. Hay repuesto en bodega.', ['baja']],
        ['Acceso denegado a la plataforma de capacitación', 'Al ingresar al portal de capacitaciones aparece «No tienes permisos». Debo completar el curso obligatorio antes de fin de mes.', ['baja', 'media']],
        ['Equipo con virus detectado por el antivirus', 'El antivirus reportó un troyano en mi equipo y lo puso en cuarentena parcial. El equipo está lento y abre ventanas emergentes.', ['alta']],
        ['Configurar la firma institucional del correo', 'Solicito que se aplique la plantilla de firma institucional con mi nuevo cargo y teléfono.', ['baja']],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$titulo, $descripcion, $prioridades] = fake()->randomElement(self::SITUACIONES);
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
            'usuario_creador_id' => User::factory(),
            'usuario_asignado_id' => null,
            'fecha_vencimiento' => $creado->copy()->addHours(self::PLAZO_HORAS[$prioridad->value]),
            'created_at' => $creado,
            'updated_at' => $actualizado,
        ];
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
