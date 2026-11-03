<?php

namespace Database\Factories;

use App\Automatizaciones\Dominio\Disparador;
use App\Automatizaciones\Models\Automatizacion;
use App\Identidad\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Automatizacion>
 */
class AutomatizacionFactory extends Factory
{
    protected $model = Automatizacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->sentence(3),
            'activa' => true,
            'disparador' => Disparador::Creacion,
            'pasos' => [],
            'autor_id' => User::factory(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $pasos
     */
    public function conPasos(array $pasos): static
    {
        return $this->state(['pasos' => $pasos]);
    }

    public function inactiva(): static
    {
        return $this->state(['activa' => false]);
    }
}
