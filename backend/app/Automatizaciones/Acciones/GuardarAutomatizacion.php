<?php

namespace App\Automatizaciones\Acciones;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Automatizaciones\Dominio\Disparo;
use App\Automatizaciones\Dominio\Flujo;
use App\Automatizaciones\Models\Automatizacion;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\DB;

// Crea o reemplaza una automatización. Quien la guarda pasa a ser su autor: lo que haga desde ahora queda a
// su nombre. La auditoría anota, en una línea, qué cambió.
class GuardarAutomatizacion
{
    public function __construct(private readonly RegistrarAuditoria $registrarAuditoria) {}

    /**
     * @param  Automatizacion|null  $automatizacion  null para crear una nueva
     */
    public function ejecutar(User $autor, ?Automatizacion $automatizacion, string $nombre, bool $activa, Disparo $disparo, Flujo $flujo, ?string $ip): Automatizacion
    {
        return DB::transaction(function () use ($autor, $automatizacion, $nombre, $activa, $disparo, $flujo, $ip) {
            $antes = $automatizacion === null ? null : [
                'nombre' => $automatizacion->nombre,
                'activa' => $automatizacion->activa,
                'disparo' => $automatizacion->disparo()->aArreglo(),
                // Releídos por el dominio: MySQL guarda el JSON con las claves en otro orden.
                'pasos' => $automatizacion->flujo()->aArreglo(),
            ];

            $guardada = $automatizacion ?? new Automatizacion;
            $guardada->fill(['nombre' => $nombre, 'activa' => $activa, 'pasos' => $flujo->aArreglo(), 'autor_id' => $autor->id, ...$disparo->aArreglo()]);
            $guardada->save();

            $antes === null ? $this->auditarCreacion($autor, $guardada, $ip) : $this->auditarCambios($autor, $guardada, $antes, $ip);

            return $guardada;
        });
    }

    private function auditarCreacion(User $autor, Automatizacion $creada, ?string $ip): void
    {
        $detalle = "«{$creada->nombre}»".($creada->activa ? ', activa' : ', apagada');

        $this->registrarAuditoria->ejecutar(AccionAuditoria::AutomatizacionCreada, $autor->id, null, $detalle, $ip);
    }

    /**
     * @param  array{nombre: string, activa: bool, disparo: array<string, mixed>, pasos: list<array<string, mixed>>}  $antes
     */
    private function auditarCambios(User $autor, Automatizacion $guardada, array $antes, ?string $ip): void
    {
        $cambios = array_keys(array_filter([
            "el nombre (antes «{$antes['nombre']}»)" => $antes['nombre'] !== $guardada->nombre,
            'cuándo se dispara' => $antes['disparo'] !== $guardada->disparo()->aArreglo(),
            'los pasos' => $antes['pasos'] !== $guardada->flujo()->aArreglo(),
        ]));

        if ($cambios !== []) {
            $detalle = "«{$guardada->nombre}»: cambió ".self::enUnaFrase($cambios);
            $this->registrarAuditoria->ejecutar(AccionAuditoria::AutomatizacionModificada, $autor->id, null, $detalle, $ip);
        }

        if ($antes['activa'] !== $guardada->activa) {
            $accion = $guardada->activa ? AccionAuditoria::AutomatizacionActivada : AccionAuditoria::AutomatizacionDesactivada;
            $this->registrarAuditoria->ejecutar($accion, $autor->id, null, "«{$guardada->nombre}»", $ip);
        }
    }

    /**
     * «a», «a y b», «a, b y c».
     *
     * @param  non-empty-list<string>  $partes
     */
    private static function enUnaFrase(array $partes): string
    {
        $ultima = array_pop($partes);

        return $partes === [] ? $ultima : implode(', ', $partes)." y {$ultima}";
    }
}
