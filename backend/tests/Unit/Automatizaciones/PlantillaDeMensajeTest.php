<?php

namespace Tests\Unit\Automatizaciones;

use App\Automatizaciones\Dominio\PlantillaDeMensaje;
use PHPUnit\Framework\TestCase;

class PlantillaDeMensajeTest extends TestCase
{
    public function test_rellena_cada_variable_con_su_valor(): void
    {
        $texto = PlantillaDeMensaje::rellenar('Hola {nombre}, tu caso {número} ({título}) quedó en prioridad {prioridad}.', [
            'nombre' => 'Marta',
            'número' => '#1024',
            'título' => 'Sin internet',
            'prioridad' => 'alta',
        ]);

        $this->assertSame('Hola Marta, tu caso #1024 (Sin internet) quedó en prioridad alta.', $texto);
    }

    public function test_un_valor_con_llaves_no_se_vuelve_a_rellenar(): void
    {
        $this->assertSame('Caso {nombre}', PlantillaDeMensaje::rellenar('Caso {título}', ['título' => '{nombre}', 'nombre' => 'Marta']));
    }

    public function test_detecta_las_variables_que_no_existen_sin_repetir(): void
    {
        $this->assertSame(['telefono', ''], PlantillaDeMensaje::desconocidas('{nombre} {telefono} {telefono} {}'));
        $this->assertSame([], PlantillaDeMensaje::desconocidas('Sin variables, o con una llave suelta {'));
    }
}
