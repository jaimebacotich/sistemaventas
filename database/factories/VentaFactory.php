<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

class VentaFactory extends Factory
{
    protected $model = Venta::class;

    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'codigo' => $this->faker->unique()->bothify('VENT######'),
            'tipo_venta' => 'Contado',
            'tipo_comprobante' => 'Boleta',
            'fecha_venta' => now(),
            'subtotal' => 100,
            'porcentaje_impuesto' => 0,
            'impuesto' => 0,
            'porcentaje_descuento' => 0,
            'descuento' => 0,
            'total' => 100,
            'estado' => 'Pendiente',
        ];
    }
}
