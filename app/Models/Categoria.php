<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\CategoriaFactory> */
    use HasFactory;

    protected $table = 'categorias';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Categoria>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Categoria>
     */
    public function scopeActivas(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('estado', true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Categoria>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Categoria>
     */
    public function scopeInactivas(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('estado', false);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Producto, $this>
     */
    public function productos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Producto::class);
    }
}
