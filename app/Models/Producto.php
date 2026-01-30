<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property string $descripcion
 * @property int $categoria_id
 * @property float $precio_compra
 * @property float $precio_venta
 * @property int $stock
 * @property int $stock_minimo
 * @property bool $estado
 */
class Producto extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\ProductoFactory> */
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'categoria_id',
        'precio_compra',
        'precio_venta',
        'stock',
        'stock_minimo',
        'unidad_medida',
        'imagen',
        'estado',
    ];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'stock' => 'integer',
        'stock_minimo' => 'integer',
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['margen_utilidad', 'tiene_stock_bajo'];

    // Relación con categoría
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Categoria, $this>
     */
    public function categoria(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    // Scope para productos activos
    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Producto>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Producto>
     */
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('estado', true);
    }

    // Scope para productos inactivos
    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Producto>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Producto>
     */
    public function scopeInactivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('estado', false);
    }

    // Scope para productos con stock bajo
    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Producto>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Producto>
     */
    public function scopeStockBajo(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereColumn('stock', '<=', 'stock_minimo');
    }

    // Accessor para margen de utilidad
    public function getMargenUtilidadAttribute(): float
    {
        if ($this->precio_compra > 0) {
            return round((($this->precio_venta - $this->precio_compra) / $this->precio_compra) * 100, 2);
        }

        return 0;
    }

    // Accessor para verificar stock bajo
    public function getTieneStockBajoAttribute(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }

    // Generar código automático
    public static function generarCodigo(): string
    {
        $ultimo = self::orderBy('id', 'desc')->first();
        $numero = $ultimo ? (int) substr($ultimo->codigo, 4) + 1 : 1;

        return 'PROD'.str_pad((string) $numero, 6, '0', STR_PAD_LEFT);
    }
}
