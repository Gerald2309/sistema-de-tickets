<?php

namespace App\Incidentes\Dominio;

use DateTimeInterface;

// Lo que pide una persona al listar incidentes, ya validado. Todos los filtros son opcionales y se combinan (AND).
// $vencidosAl es el instante contra el que se evalúa «vencido»: lo fija quien construye los filtros, no el reloj.
final readonly class FiltrosDeIncidentes
{
    public const POR_PAGINA_PREDETERMINADO = 15;

    public const POR_PAGINA_MAXIMO = 100;

    /**
     * @param  list<string>  $terminos  palabras que deben aparecer en el título o la descripción
     */
    public function __construct(
        public array $terminos = [],
        public ?Prioridad $prioridad = null,
        public ?Estado $estado = null,
        public ?Categoria $categoria = null,
        public ?int $usuarioAsignadoId = null,
        public ?int $usuarioCreadorId = null,
        public ?VentanaDeVencimiento $porVencer = null,
        public ?DateTimeInterface $vencidosAl = null,
        public ?DateTimeInterface $fechaDesde = null,
        public ?DateTimeInterface $fechaHasta = null,
        public CampoFecha $campoFecha = CampoFecha::Vencimiento,
        public CampoOrden $ordenarPor = CampoOrden::Id,
        public Direccion $direccion = Direccion::Descendente,
        public int $porPagina = self::POR_PAGINA_PREDETERMINADO,
    ) {}
}
