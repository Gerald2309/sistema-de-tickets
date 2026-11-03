<?php

namespace App\Automatizaciones\Dominio\Excepciones;

use DomainException;

// Una acción que tenía sentido al guardar la automatización ya no se puede hacer: por ejemplo, la persona
// a la que asignaba dejó de atender incidentes.
final class AccionNoAplicable extends DomainException {}
