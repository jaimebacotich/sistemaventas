<?php

namespace Tests\Unit\Services;

use App\Models\Venta;
use App\Services\VentaService;
use Exception;
use Mockery;
use PHPUnit\Framework\TestCase;

class VentaServiceUnitTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_throws_exception_if_updating_non_editable_venta(): void
    {
        // Arrange
        $service = new VentaService;
        $venta = Mockery::mock(Venta::class);
        $venta->shouldReceive('getAttribute')->with('puede_editarse')->andReturn(false);

        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Solo las ventas pendientes pueden modificarse');

        // Act
        $service->actualizarVenta($venta, [], []);
    }

    public function test_throws_exception_if_deleting_non_pending_venta(): void
    {
        // Arrange
        $service = new VentaService;
        $venta = Mockery::mock(Venta::class);
        $venta->shouldReceive('getAttribute')->with('estado')->andReturn(Venta::ESTADO_COMPLETADA);

        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Solo las ventas pendientes pueden eliminarse.');

        // Act
        $service->eliminarVenta($venta);
    }
}
