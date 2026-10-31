<?php

namespace Tests\Unit\Dominio;

use App\Dominio\ClavePermiso;
use App\Dominio\ConjuntoPermisos;
use PHPUnit\Framework\TestCase;

class ConjuntoPermisosTest extends TestCase
{
    public function test_indica_si_tiene_un_permiso(): void
    {
        $permisos = new ConjuntoPermisos([ClavePermiso::VerTodos, ClavePermiso::Reasignar]);

        $this->assertTrue($permisos->tiene(ClavePermiso::VerTodos));
        $this->assertFalse($permisos->tiene(ClavePermiso::Borrar));
    }

    public function test_alguno_es_verdadero_si_tiene_al_menos_uno(): void
    {
        $permisos = new ConjuntoPermisos([ClavePermiso::VerPropios]);

        $this->assertTrue($permisos->alguno(ClavePermiso::VerTodos, ClavePermiso::VerPropios));
        $this->assertFalse($permisos->alguno(ClavePermiso::VerTodos, ClavePermiso::Borrar));
    }

    public function test_un_conjunto_vacio_no_tiene_ningun_permiso(): void
    {
        $permisos = new ConjuntoPermisos([]);

        $this->assertFalse($permisos->tiene(ClavePermiso::CrearIncidente));
        $this->assertFalse($permisos->alguno(ClavePermiso::CrearIncidente));
    }

    public function test_los_permisos_repetidos_no_alteran_el_resultado(): void
    {
        $permisos = new ConjuntoPermisos([ClavePermiso::Borrar, ClavePermiso::Borrar]);

        $this->assertTrue($permisos->tiene(ClavePermiso::Borrar));
    }
}
