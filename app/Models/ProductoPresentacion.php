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
        'nombre_presentacion',
        'unidades_por_presentacion',
        'precio_venta_presentacion',
    ];

    protected $casts = [
        'unidades_por_presentacion' => 'integer',
        'precio_venta_presentacion' => 'decimal:2',
    ];

    /**
     * Relación con el producto
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
