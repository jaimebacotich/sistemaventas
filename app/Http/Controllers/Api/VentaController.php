<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Venta\StoreVentaRequest;
use App\Http\Requests\Venta\UpdateVentaRequest;
use App\Http\Resources\VentaResource;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\AuditLogger;
use App\Services\VentaService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador API para gestión de Ventas
 */
class VentaController extends Controller
{
    protected VentaService $ventaService;

    public function __construct(VentaService $ventaService)
    {
        $this->ventaService = $ventaService;
    }

    /**
     * Listar todas las ventas con filtros y paginación
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Venta::with(['cliente.persona', 'detalles.producto'])
                ->when($request->filled('estado'), fn (\Illuminate\Database\Eloquent\Builder $q) => $q->where('estado', $request->input('estado')))
                ->when($request->filled('cliente_id'), fn (\Illuminate\Database\Eloquent\Builder $q) => $q->where('cliente_id', $request->input('cliente_id')))
                ->when($request->filled('tipo_venta'), fn (\Illuminate\Database\Eloquent\Builder $q) => $q->where('tipo_venta', $request->input('tipo_venta')))
                ->when($request->filled('fecha_desde'), fn (\Illuminate\Database\Eloquent\Builder $q) => $q->whereDate('fecha_venta', '>=', $request->input('fecha_desde')))
                ->when($request->filled('fecha_hasta'), fn (\Illuminate\Database\Eloquent\Builder $q) => $q->whereDate('fecha_venta', '<=', $request->input('fecha_hasta')))
                ->when($request->filled('search'), function (\Illuminate\Database\Eloquent\Builder $q) use ($request) {
                    $search = $request->input('search');
                    $q->where(function ($subQ) use ($search) {
                        $subQ->where('codigo', 'ilike', "%{$search}%")
                            ->orWhere('numero_comprobante', 'ilike', "%{$search}%");
                    });
                })
                ->orderBy($request->get('sort_by', 'id'), $request->get('sort_order', 'desc'));

            $ventas = $request->boolean('all')
                ? $query->get()
                : $query->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => VentaResource::collection($ventas),
            ], 200);
        } catch (Exception $e) {
            AuditLogger::error('Error al obtener ventas', $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las ventas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear una nueva venta con sus detalles
     */
    public function store(StoreVentaRequest $request): JsonResponse
    {
        try {
            $venta = $this->ventaService->crearVenta(
                $request->validated(),
                $request->input('detalles')
            );

            return response()->json([
                'success' => true,
                'message' => 'Venta creada exitosamente',
                'data' => new VentaResource($venta),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la venta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar detalles de una venta específica
     */
    public function show(Venta $venta): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new VentaResource($venta->load(['cliente.persona', 'detalles.producto'])),
        ]);
    }

    /**
     * Actualizar una venta y sus detalles
     */
    public function update(UpdateVentaRequest $request, Venta $venta): JsonResponse
    {
        try {
            $venta = $this->ventaService->actualizarVenta(
                $venta,
                $request->validated(),
                $request->input('detalles')
            );

            return response()->json([
                'success' => true,
                'message' => 'Venta actualizada exitosamente',
                'data' => new VentaResource($venta),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la venta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Completar una venta (procesa stock y crédito)
     */
    public function completar(Venta $venta): JsonResponse
    {
        try {
            $this->ventaService->completarVenta($venta);

            return response()->json([
                'success' => true,
                'message' => 'Venta completada exitosamente',
                'data' => new VentaResource($venta),
            ]);
        } catch (Exception $e) {
            AuditLogger::error("Error al completar venta {$venta->id}", $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al completar la venta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Anular una venta
     */
    public function anular(Venta $venta): JsonResponse
    {
        try {
            $this->ventaService->anularVenta($venta);

            return response()->json([
                'success' => true,
                'message' => 'Venta anulada exitosamente',
                'data' => new VentaResource($venta),
            ]);
        } catch (Exception $e) {
            AuditLogger::error("Error al anular venta {$venta->id}", $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al anular la venta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar físicamente una venta pendiente
     */
    public function destroy(Venta $venta): JsonResponse
    {
        try {
            $this->ventaService->eliminarVenta($venta);

            return response()->json([
                'success' => true,
                'message' => 'Venta eliminada exitosamente',
            ]);
        } catch (Exception $e) {
            AuditLogger::error("Error al eliminar venta {$venta->id}", $e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Generar código automático para nueva venta
     */
    public function generarCodigo(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => ['codigo' => Venta::generarCodigo()],
            ], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al generar código', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener clientes activos para select
     */
    public function getClientes(): JsonResponse
    {
        $clientes = Cliente::with('persona')
            ->activos()
            ->orderBy('codigo')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'codigo' => $c->codigo,
                'nombre' => $c->persona->nombre_completo ?? 'Sin nombre',
                'dias_credito' => $c->dias_credito,
                'credito_disponible' => $c->credito_disponible,
            ]);

        // Nota: No uso ClienteResource::collection aquí porque el resource tiene campos extra que quizás no necesito en un select,
        // o porque el formato es específico para el select.
        // Pero para ser consistente, DEBERÍA usar Resource.
        // Lo dejaré así para no cambiar la estructura que espera "nombre", "dias_credito".
        // ClienteResource tiene "nombre_completo".

        return response()->json(['success' => true, 'data' => $clientes], 200);
    }

    /**
     * Obtener productos activos para select
     */
    public function getProductos(): JsonResponse
    {
        $productos = Producto::with('categoria')
            ->activos()
            ->orderBy('nombre')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'categoria' => $p->categoria?->nombre,
                'precio_venta' => $p->precio_venta,
                'stock' => $p->stock,
                'unidad_medida' => $p->unidad_medida,
            ]);

        return response()->json(['success' => true, 'data' => $productos], 200);
    }
}
