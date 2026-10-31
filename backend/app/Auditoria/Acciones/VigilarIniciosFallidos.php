<?php

namespace App\Auditoria\Acciones;

use App\Auditoria\Dominio\AccionAuditoria;
use App\Auditoria\Models\Auditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class VigilarIniciosFallidos
{
    public function __construct(private readonly RegistrarAuditoria $registrarAuditoria) {}

    /**
     * Tras un inicio de sesión fallido, mira si en la ventana ya se superó el umbral sobre esa cuenta (fuerza
     * bruta) o desde esa IP (rociado de contraseñas). Si es así, deja una alerta en la auditoría y en el log de
     * advertencias, para que un monitor la recoja. Una sola alerta por ventana y por cuenta o IP: un ataque
     * sostenido no inunda la auditoría.
     */
    public function ejecutar(?int $cuentaId, ?string $ip, Carbon $ahora): void
    {
        $desde = $ahora->copy()->subMinutes((int) config('seguridad.alertas.ventana_minutos'));

        if ($cuentaId !== null) {
            $this->vigilar(
                fn (Builder $consulta) => $consulta->where('usuario_afectado_id', $cuentaId),
                (int) config('seguridad.alertas.fallidos_por_cuenta'),
                $desde,
                'inicios fallidos sobre esta cuenta',
                $cuentaId,
                $ip,
            );
        }
        if ($ip !== null) {
            $this->vigilar(
                fn (Builder $consulta) => $consulta->where('ip', $ip),
                (int) config('seguridad.alertas.fallidos_por_ip'),
                $desde,
                'inicios fallidos desde esta IP',
                null,
                $ip,
            );
        }
    }

    /**
     * @param  callable(Builder<Auditoria>): mixed  $alcance  sobre qué cuenta o IP se cuenta
     */
    private function vigilar(callable $alcance, int $umbral, Carbon $desde, string $que, ?int $cuentaId, ?string $ip): void
    {
        $enLaVentana = fn (AccionAuditoria $accion) => Auditoria::query()
            ->where('accion', $accion)
            ->where('created_at', '>=', $desde)
            ->tap($alcance);

        $fallidos = $enLaVentana(AccionAuditoria::LoginFallido)->count();
        // Las alertas por IP no llevan cuenta: así una alerta sobre una cuenta no tapa la de su IP.
        $yaAlertado = $enLaVentana(AccionAuditoria::AlertaDeSeguridad)
            ->when($cuentaId === null, fn (Builder $consulta) => $consulta->whereNull('usuario_afectado_id'))
            ->exists();
        if ($fallidos < $umbral || $yaAlertado) {
            return;
        }

        $detalle = "{$fallidos} {$que} en ".config('seguridad.alertas.ventana_minutos').' minutos';
        $this->registrarAuditoria->ejecutar(AccionAuditoria::AlertaDeSeguridad, null, $cuentaId, $detalle, $ip);
        Log::warning('Alerta de seguridad: '.$detalle, ['cuenta' => $cuentaId, 'ip' => $ip]);
    }
}
