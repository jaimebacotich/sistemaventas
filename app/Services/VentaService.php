<?php

namespace App\Services;

use App\Models\Venta;
use Exception;
use Illuminate\Support\Facades\DB;

class VentaService
{
    /**
     * Crear una venta con sus detalles dentro de una transacción.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $detalles
     */
    public function crearVenta(array $data, array $detalles): Venta
    {
        return DB::transaction(function () use ($data, $detalles) {
            $data['codigo'] = Venta::generarCodigo();
            $data['estado'] = Venta::ESTADO_PENDIENTE;
            $data['porcentaje_impuesto'] ??= 0;
            $data['porcentaje_descuento'] ??= 0;

            $venta = Venta::create($data);

            foreach ($detalles as $detalle) {
                $venta->detalles()->create([
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'porcentaje_descuento' => $detalle['porcentaje_descuento'] ?? 0,
                ]);
            }

            $venta->load(['cliente.persona', 'detalles.producto']);

            AuditLogger::insercion("Venta creada via Servicio: {$venta->codigo} (Cliente ID: {$venta->cliente_id})", $detalles);

            return $venta;
        });
    }

    /**
     * Actualizar una venta existente.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $detalles
     */
    public function actualizarVenta(Venta $venta, array $data, array $detalles): Venta
    {
        if (! $venta->puede_editarse) {
            throw new Exception('Solo las ventas pendientes pueden modificarse');
        }

        return DB::transaction(function () use ($venta, $data, $detalles) {
            $data['porcentaje_impuesto'] ??= 0;
            $data['porcentaje_descuento'] ??= 0;

            $venta->update($data);

            $venta->detalles()->delete();

            foreach ($detalles as $detalle) {
                $venta->detalles()->create([
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'porcentaje_descuento' => $detalle['porcentaje_descuento'] ?? 0,
                ]);
            }

            $venta->load(['cliente.persona', 'detalles.producto']);
            AuditLogger::actualizacion("Venta actualizada via Servicio: {$venta->codigo}", $data);

            return $venta;
        });
    }

    /**
     * Completar una venta (reduce stock).
     */
    public function completarVenta(Venta $venta): Venta
    {
        $venta->completar();
        $venta->load(['cliente.persona', 'detalles.producto']);

        AuditLogger::actualizacion("Venta completada via Servicio: {$venta->codigo}");

        return $venta;
    }

    /**
     * Anular una venta (revierte stock).
     */
    public function anularVenta(Venta $venta): Venta
    {
        $venta->anular();
        $venta->load(['cliente.persona', 'detalles.producto']);

        AuditLogger::actualizacion("Venta anulada via Servicio: {$venta->codigo}");

        return $venta;
    }

    /**
     * Eliminar físicamente una venta pendiente.
     */
    public function eliminarVenta(Venta $venta): bool
    {
        if ($venta->estado !== Venta::ESTADO_PENDIENTE) {
            throw new Exception('Solo las ventas pendientes pueden eliminarse.');
        }

        return DB::transaction(function () use ($venta) {
            $venta->detalles()->delete();
            $res = $venta->delete();
            AuditLogger::eliminacion("Venta eliminada via Servicio ID {$venta->id}");

            return $res;
        });
    }
}
