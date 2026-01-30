<?php

namespace Tests\Feature\Integration;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentaServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected VentaService $ventaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ventaService = app(VentaService::class);
    }

    public function test_can_create_venta_via_service(): void
    {
        // Arrange
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create(['stock' => 10, 'precio_venta' => 100]);

        $data = [
            'cliente_id' => $cliente->id,
            'tipo_comprobante' => Venta::COMPROBANTE_BOLETA,
            'numero_comprobante' => '001-0001',
            'fecha_venta' => now()->toDateString(),
            'tipo_venta' => Venta::TIPO_CONTADO,
            'total' => 200,
        ];

        $detalles = [
            [
                'producto_id' => $producto->id,
                'cantidad' => 2,
                'precio_unitario' => 100,
            ],
        ];

        // Act
        $venta = $this->ventaService->crearVenta($data, $detalles);

        // Assert
        $this->assertDatabaseHas('ventas', ['id' => $venta->id, 'codigo' => $venta->codigo]);
        $this->assertCount(1, $venta->detalles);
        $this->assertEquals(Venta::ESTADO_PENDIENTE, $venta->estado);
    }

    public function test_can_complete_venta_and_reduce_stock(): void
    {
        // Arrange
        $producto = Producto::factory()->create(['stock' => 10]);
        $venta = Venta::factory()->create(['estado' => Venta::ESTADO_PENDIENTE]);
        $venta->detalles()->create([
            'producto_id' => $producto->id,
            'cantidad' => 3,
            'precio_unitario' => 100,
        ]);

        // Act
        $this->ventaService->completarVenta($venta);

        // Assert
        $this->assertEquals(Venta::ESTADO_COMPLETADA, $venta->estado);
        $this->assertEquals(7, $producto->fresh()->stock);
    }
}
