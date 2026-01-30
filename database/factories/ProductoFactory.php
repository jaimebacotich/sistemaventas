<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->bothify('PROD######'),
            'nombre' => $this->faker->words(3, true),
            'descripcion' => $this->faker->sentence(),
            'categoria_id' => Categoria::factory(),
            'precio_compra' => $this->faker->randomFloat(2, 10, 50),
            'precio_venta' => $this->faker->randomFloat(2, 60, 100),
            'stock' => $this->faker->numberBetween(10, 100),
            'stock_minimo' => 5,
            'unidad_medida' => 'Unidad',
            'estado' => true,
        ];
    }
}
