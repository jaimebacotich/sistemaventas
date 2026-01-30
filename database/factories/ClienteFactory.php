<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Persona;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'persona_id' => Persona::factory(),
            'codigo' => $this->faker->unique()->bothify('CLI######'),
            'tipo_cliente' => 'Regular',
            'limite_credito' => 1000,
            'credito_usado' => 0,
            'dias_credito' => 30,
            'estado' => true,
        ];
    }
}
