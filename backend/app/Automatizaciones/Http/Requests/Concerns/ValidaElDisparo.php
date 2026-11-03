<?php

namespace App\Automatizaciones\Http\Requests\Concerns;

use App\Automatizaciones\Dominio\Disparo;
use App\Automatizaciones\Dominio\Excepciones\FlujoInvalido;
use Illuminate\Validation\Validator;

// El paso «Cuándo»: el disparador y la medida que usa. El dominio decide si tiene sentido.
trait ValidaElDisparo
{
    private ?Disparo $disparoLeido = null;

    protected function validarDisparo(Validator $validador): void
    {
        try {
            $this->disparoLeido = $this->leerDisparo();
        } catch (FlujoInvalido $error) {
            $validador->errors()->add($error->ruta, $error->getMessage());
        }
    }

    // Solo después de validar.
    public function disparo(): Disparo
    {
        return $this->disparoLeido ??= $this->leerDisparo();
    }

    private function leerDisparo(): Disparo
    {
        return Disparo::desde($this->input('disparador'), $this->only(['espera_minutos', 'porcentaje', 'plazo']));
    }
}
