<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ProductoUbicacion extends Model
{
    use HasFactory;

    // Constantes de Estado
    const ESTADO_ACTIVO = 'activo';
    const ESTADO_AGOTADO = 'agotado';
    const ESTADO_CUARENTENA = 'cuarentena';
    const ESTADO_VENCIDO = 'vencido';

    protected $table = 'producto_ubicaciones';

    protected $fillable = [
        'producto_id',
        'ubicacion_id',
        'cantidad',
        'fecha_ingreso',
        'fecha_vencimiento',
        'lote',
        'precio_compra_lote',
        'precio_venta_lote',
        'proveedor_id',
        'estado_lote',
        'cantidad_inicial',
        'cantidad_vendida',
        'observaciones'
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'cantidad_inicial' => 'integer',
        'cantidad_vendida' => 'integer',
        'fecha_ingreso' => 'date',
        'fecha_vencimiento' => 'date',
        'precio_compra_lote' => 'decimal:2',
        'precio_venta_lote' => 'decimal:2'
    ];

    // Relaciones
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(\App\Models\Proveedor::class);
    }

    public function movimientos()
    {
        return $this->hasMany(LoteMovimiento::class);
    }

    // Atributos calculados
    public function getDiasParaVencerAttribute()
    {
        if (!$this->fecha_vencimiento) {
            return null;
        }
        
        return now()->diffInDays($this->fecha_vencimiento, false);
    }

    public function getEstavenciendoAttribute()
    {
        $dias = $this->dias_para_vencer;
        return $dias !== null && $dias <= 30 && $dias > 0;
    }

    public function getEstaVencidoAttribute()
    {
        $dias = $this->dias_para_vencer;
        return $dias !== null && $dias < 0;
    }

    public function getEstadoVencimientoAttribute()
    {
        if ($this->esta_vencido) {
            return 'vencido';
        } elseif ($this->esta_venciendo) {
            return 'por_vencer';
        } else {
            return 'normal';
        }
    }

    // Scopes
    public function scopeDelProducto($query, $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    public function scopeDeLaUbicacion($query, $ubicacionId)
    {
        return $query->where('ubicacion_id', $ubicacionId);
    }

    public function scopeProximosAVencer($query, $dias = 30)
    {
        return $query->whereNotNull('fecha_vencimiento')
                    ->whereDate('fecha_vencimiento', '<=', now()->addDays($dias))
                    ->whereDate('fecha_vencimiento', '>', now());
    }

    public function scopeVencidos($query)
    {
        return $query->whereNotNull('fecha_vencimiento')
                    ->whereDate('fecha_vencimiento', '<', now());
    }

    public function scopeDelLote($query, $lote)
    {
        return $query->where('lote', $lote);
    }

    public function scopeConStock($query)
    {
        return $query->where('cantidad', '>', 0);
    }

    // Métodos estáticos útiles
    public static function obtenerTotalPorProducto($productoId)
    {
        return static::where('producto_id', $productoId)->sum('cantidad');
    }

    public static function obtenerUbicacionesDelProducto($productoId)
    {
        return static::with(['ubicacion.estante'])
                    ->where('producto_id', $productoId)
                    ->where('cantidad', '>', 0)
                    ->get();
    }

    public static function obtenerProductosSinUbicar()
    {
        $productosConUbicacion = static::distinct()->pluck('producto_id');
        return Producto::whereNotIn('id', $productosConUbicacion)->get();
    }
}
