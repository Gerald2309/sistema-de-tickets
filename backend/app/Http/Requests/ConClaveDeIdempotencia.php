<?php

namespace App\Http\Requests;

use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

/**
 * Cabecera opcional `Idempotency-Key`: el cliente genera un UUID por envío y lo repite si reintenta, y la API
 * devuelve lo que ya creó en lugar de crearlo dos veces (doble clic, red que se corta). La usan las peticiones
 * que crean algo que no debe duplicarse.
 */
trait ConClaveDeIdempotencia
{
    public const CABECERA_DE_IDEMPOTENCIA = 'Idempotency-Key';

    public function claveDeIdempotencia(): ?string
    {
        return $this->header(self::CABECERA_DE_IDEMPOTENCIA);
    }

    /**
     * @return callable(Validator): void
     */
    protected function validarClaveDeIdempotencia(): callable
    {
        return function (Validator $validador): void {
            $clave = $this->claveDeIdempotencia();
            if ($clave !== null && ! Str::isUuid($clave)) {
                $validador->errors()->add(self::CABECERA_DE_IDEMPOTENCIA, trans('incidentes.clave_de_idempotencia_invalida'));
            }
        };
    }
}
