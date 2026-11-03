<?php

namespace App\Automatizaciones\Http\Requests\Concerns;

use App\Automatizaciones\Consultas\ReferenciasDelFlujo;
use App\Automatizaciones\Dominio\Excepciones\FlujoInvalido;
use App\Automatizaciones\Dominio\Flujo;
use App\Automatizaciones\Dominio\LectorDeFlujo;
use Illuminate\Validation\Validator;

// Los pasos que envía el editor: su forma la revisa el dominio y lo que nombran (personas, roles, empresas),
// la base. Cada error queda en la ruta exacta del dato, para mostrarlo junto a su bloque.
trait ValidaLosPasos
{
    private ?Flujo $flujoLeido = null;

    protected function validarPasos(Validator $validador): void
    {
        try {
            $this->flujoLeido = LectorDeFlujo::leer($this->input('pasos'));
        } catch (FlujoInvalido $error) {
            $validador->errors()->add($error->ruta, $error->getMessage());

            return;
        }

        foreach (app(ReferenciasDelFlujo::class)->errores($this->flujoLeido) as $ruta => $mensaje) {
            $validador->errors()->add($ruta, $mensaje);
        }
    }

    // Solo después de validar: los pasos ya se leyeron sin errores.
    public function flujo(): Flujo
    {
        return $this->flujoLeido ??= LectorDeFlujo::leer($this->input('pasos'));
    }
}
