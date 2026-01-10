<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoPresentacion extends Model
{
    use HasFactory;

    protected $table = 'producto_presentaciones';

    protected $fillable = [
        'producto_id',
        'unidad_venta',
        'factor_unidad_base',
        'precio_venta',
        'permite_fraccionamiento',
        'estado',
    ];

    protected $casts = [
        'factor_unidad_base' => 'integer',
        'precio_venta' => 'decimal:2',
        'permite_fraccionamiento' => 'boolean',
        'estado' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}