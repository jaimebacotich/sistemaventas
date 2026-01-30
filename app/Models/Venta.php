<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Modelo Venta
 *
 * Representa una venta realizada a un cliente.
 */
/**
 * @property int $id
 * @property string $codigo
 * @property int $cliente_id
 * @property string $tipo_comprobante
 * @property string $numero_comprobante
 * @property \Illuminate\Support\Carbon $fecha_venta
 * @property-read \Illuminate\Support\Carbon|null $fecha_vencimiento
 * @property string $tipo_venta
 * @property float $subtotal
 * @property float $porcentaje_impuesto
 * @property float $impuesto
 * @property float $porcentaje_descuento
 * @property float $descuento
 * @property float $total
 * @property string $estado
 * @property-read \App\Models\Cliente $cliente
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DetalleVenta> $detalles
 * @property-read bool $es_credito
 * @property-read bool $puede_editarse
 */
class Venta extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\VentaFactory> */
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'cliente_id',
        'codigo',
        'tipo_venta',
        'tipo_comprobante',
        'numero_comprobante',
        'fecha_venta',
        'fecha_vencimiento',
        'subtotal',
        'porcentaje_impuesto',
        'impuesto',
        'porcentaje_descuento',
        'descuento',
        'total',
        'estado',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_venta' => 'date',
        'fecha_vencimiento' => 'date',
        'subtotal' => 'decimal:2',
        'porcentaje_impuesto' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'porcentaje_descuento' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $appends = [
        'cantidad_items',
        'es_credito',
        'puede_editarse',
    ];

    // Constantes
    public const TIPO_CONTADO = 'Contado';

    public const TIPO_CREDITO = 'Credito';

    public const COMPROBANTE_FACTURA = 'Factura';

    public const COMPROBANTE_BOLETA = 'Boleta';

    public const COMPROBANTE_NOTA = 'Nota';

    public const COMPROBANTE_OTRO = 'Otro';

    public const ESTADO_PENDIENTE = 'Pendiente';

    public const ESTADO_COMPLETADA = 'Completada';

    public const ESTADO_ANULADA = 'Anulada';

    public const CODIGO_PREFIJO = 'VENT';

    /*
    |--------------------------------------------------------------------------
    | RELACIONES ELOQUENT
    |--------------------------------------------------------------------------
    */

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Cliente, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\DetalleVenta, $this>
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Venta>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Venta>
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Venta>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Venta>
     */
    public function scopeCompletadas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_COMPLETADA);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Venta>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Venta>
     */
    public function scopeAnuladas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_ANULADA);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Venta>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Venta>
     */
    public function scopePorCliente(Builder $query, int $clienteId): Builder
    {
        return $query->where('cliente_id', $clienteId);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Venta>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Venta>
     */
    public function scopeEntreFechas(Builder $query, string $desde, string $hasta): Builder
    {
        return $query->whereBetween('fecha_venta', [$desde, $hasta]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Venta>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Venta>
     */
    public function scopeConRelaciones(Builder $query): Builder
    {
        return $query->with(['cliente.persona', 'detalles.producto']);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS (GETTERS)
    |--------------------------------------------------------------------------
    */

    public function getCantidadItemsAttribute(): int
    {
        return $this->detalles->count();
    }

    public function getEsCreditoAttribute(): bool
    {
        return $this->tipo_venta === self::TIPO_CREDITO;
    }

    public function getPuedeEditarseAttribute(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS ESTÁTICOS
    |--------------------------------------------------------------------------
    */

    public static function generarCodigo(): string
    {
        return DB::transaction(function () {
            $ultimo = self::lockForUpdate()->orderBy('id', 'desc')->first();
            $numero = $ultimo ? (int) substr($ultimo->codigo, strlen(self::CODIGO_PREFIJO)) + 1 : 1;

            return self::CODIGO_PREFIJO.str_pad((string) $numero, 6, '0', STR_PAD_LEFT);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE NEGOCIO
    |--------------------------------------------------------------------------
    */

    public function calcularTotales(): bool
    {
        $this->load('detalles');

        $subtotal = $this->detalles->sum('subtotal');
        $descuento = $subtotal * ($this->porcentaje_descuento / 100);
        $baseImponible = $subtotal - $descuento;
        $impuesto = $baseImponible * ($this->porcentaje_impuesto / 100);
        $total = $baseImponible + $impuesto;

        $this->subtotal = round($subtotal, 2);
        $this->descuento = round($descuento, 2);
        $this->impuesto = round($impuesto, 2);
        $this->total = round($total, 2);

        return $this->save();
    }

    /**
     * Completa la venta
     * - Cambia estado a Completada
     * - Reduce stock de productos
     * - Usa crédito del cliente (si aplica)
     * - Actualiza última venta y total de compras del cliente
     */
    public function completar(): bool
    {
        if ($this->estado !== self::ESTADO_PENDIENTE) {
            throw new \Exception('Solo las ventas pendientes pueden completarse');
        }

        return DB::transaction(function () {
            // Reducir stock de productos
            foreach ($this->detalles as $detalle) {
                /** @var \App\Models\Producto $producto */
                $producto = $detalle->producto;

                // Validar stock suficiente
                if ($producto->stock < $detalle->cantidad) {
                    throw new \Exception(
                        "Stock insuficiente para el producto '{$producto->nombre}'. ".
                            "Disponible: {$producto->stock}, Solicitado: {$detalle->cantidad}"
                    );
                }

                $producto->stock -= $detalle->cantidad;
                $producto->save();
            }

            // Si es a crédito, usar crédito del cliente
            if ($this->es_credito) {
                $this->cliente->usarCredito($this->total);
            }

            // Actualizar datos del cliente            // Actualizar estadísticas del cliente
            $this->cliente->actualizarUltimaCompra($this->fecha_venta);
            $this->cliente->incrementarTotalCompras($this->total);

            $this->estado = self::ESTADO_COMPLETADA;

            return $this->save();
        });
    }

    /**
     * Anula la venta
     * - Cambia estado a Anulada
     * - Si estaba completada, devuelve stock y crédito
     */
    public function anular(): bool
    {
        if ($this->estado === self::ESTADO_ANULADA) {
            throw new \Exception('La venta ya está anulada');
        }

        return DB::transaction(function () {
            if ($this->estado === self::ESTADO_COMPLETADA) {
                // Devolver stock de productos
                foreach ($this->detalles as $detalle) {
                    /** @var \App\Models\Producto $producto */
                    $producto = $detalle->producto;
                    $producto->stock += $detalle->cantidad;
                    $producto->save();
                }

                // Si era a crédito, liberar crédito del cliente
                if ($this->es_credito) {
                    $this->cliente->liberarCredito($this->total);
                }
            }

            $this->estado = self::ESTADO_ANULADA;

            return $this->save();
        });
    }

    public function estaVencida(): bool
    {
        if (! $this->es_credito || ! $this->fecha_vencimiento) {
            return false;
        }

        return $this->fecha_vencimiento->isPast();
    }

    public function diasParaVencimiento(): ?int
    {
        if (! $this->es_credito || ! $this->fecha_vencimiento) {
            return null;
        }

        return (int) now()->diffInDays($this->fecha_vencimiento, false);
    }
}
