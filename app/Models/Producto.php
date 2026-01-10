<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use App\Models\ProductoUbicacion;
use App\Models\MovimientoStock;
use App\Models\Categoria;
use App\Models\Presentacion;
use App\Models\Proveedor;
use OwenIt\Auditing\Contracts\Auditable;

class Producto extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'codigo_barras',
        'lote',
        'categoria',
        'marca',
        'presentacion',
        'concentracion',
        'proveedor_id',
        'stock_actual',
        'stock_minimo',
        'ubicacion',
        'ubicacion_almacen',
        'fecha_fabricacion',
        'fecha_vencimiento',
        'precio_compra',
        'precio_venta',
        'imagen',
        'estado'
    ];

    protected $casts = [
        'stock_actual' => 'integer',
        'stock_minimo' => 'integer',
        'stock_unidades' => 'integer',
        'stock_presentaciones' => 'integer',
        'unidad_minima_venta' => 'integer',
        'unidades_por_presentacion' => 'integer',
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'precio_unidad' => 'decimal:2',
        'precio_presentacion' => 'decimal:2',
        'permite_venta_unitaria' => 'boolean',
        'permite_venta_presentacion' => 'boolean',
        'fecha_fabricacion' => 'date',
        'fecha_vencimiento' => 'date',
        'proveedor_id' => 'integer'
    ];

    protected $appends = ['imagen_url', 'is_low_stock', 'is_expiring_soon', 'is_expired', 'is_out_of_stock', 'status', 'status_text', 'status_color'];

    public $timestamps = true;

    // Relaciones con el almacén
    public function ubicaciones()
    {
        return $this->hasMany(ProductoUbicacion::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoStock::class);
    }

    public function categoria_model()
    {
        // productos.categoria (string nombre) -> categorias.nombre
        return $this->belongsTo(Categoria::class, 'categoria', 'nombre');
    }

    public function presentacion_model()
    {
        // productos.presentacion (string nombre) -> presentaciones.nombre
        return $this->belongsTo(Presentacion::class, 'presentacion', 'nombre');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    // Métodos útiles para el almacén
    public function getStockEnAlmacenAttribute()
    {
        return $this->ubicaciones()->sum('cantidad');
    }

    public function getUbicacionesOcupadasAttribute()
    {
        return $this->ubicaciones()->where('cantidad', '>', 0)->count();
    }

    /**
     * Resolver el usuario para la auditoría (Fix manual para evitar error de UserResolver)
     */
    protected function resolveUser()
    {
        return \Illuminate\Support\Facades\Auth::user();
    }

    public function getEstaUbicadoAttribute()
    {
        return $this->ubicaciones()->where('cantidad', '>', 0)->exists();
    }

    public function getPrimeraUbicacionAttribute()
    {
        return $this->ubicaciones()
                    ->with('ubicacion.estante')
                    ->where('cantidad', '>', 0)
                    ->first();
    }

    public function getTodasLasUbicacionesAttribute()
    {
        return $this->ubicaciones()
                    ->with('ubicacion.estante')
                    ->where('cantidad', '>', 0)
                    ->get();
    }

    // Scopes para el almacén
    public function scopeUbicados($query)
    {
        return $query->whereHas('ubicaciones', function($q) {
            $q->where('cantidad', '>', 0);
        });
    }

    public function scopeSinUbicar($query)
    {
        return $query->whereDoesntHave('ubicaciones', function($q) {
            $q->where('cantidad', '>', 0);
        });
    }

    public function scopeSinUbicarEnAlmacen($query)
    {
        return $query->where(function($q) {
            $q->whereNull('ubicacion_almacen');
        });
    }

    public function scopeProximosAVencer($query, $dias = 30)
    {
        return $query->whereHas('ubicaciones', function($q) use ($dias) {
            $q->whereNotNull('fecha_vencimiento')
              ->whereDate('fecha_vencimiento', '<=', now()->addDays($dias))
              ->whereDate('fecha_vencimiento', '>', now());
        });
    }

    public function scopeVencidos($query)
    {
        return $query->whereHas('ubicaciones', function($q) {
            $q->whereNotNull('fecha_vencimiento')
              ->whereDate('fecha_vencimiento', '<', now());
        });
    }

    // Método para sincronizar stock con ubicaciones
    public function actualizarStockDesdeUbicaciones()
    {
        $stockTotal = $this->ubicaciones()->sum('cantidad');
        $this->update(['stock_actual' => $stockTotal]);
        
        // Recalcular el estado después de actualizar el stock
        $this->fresh()->recalcularEstado();
        
        return $stockTotal;
    }

    // Método para verificar estado basado en ubicaciones
    public function actualizarEstadoDesdeUbicaciones()
    {
        $stockTotal = $this->stock_en_almacen;
        $tieneVencidos = $this->ubicaciones()->whereDate('fecha_vencimiento', '<', now())->exists();
        $tieneProximosAVencer = $this->ubicaciones()
            ->whereDate('fecha_vencimiento', '<=', now()->addDays(90))
            ->whereDate('fecha_vencimiento', '>', now())
            ->exists();

        if ($tieneVencidos) {
            $estado = 'Vencido';
        } elseif ($tieneProximosAVencer) {
            $estado = 'Por vencer';
        } elseif ($stockTotal <= $this->stock_minimo) {
            $estado = 'Bajo stock';
        } else {
            $estado = 'Normal';
        }

        $this->update(['estado' => $estado]);
        return $estado;
    }

    // Método para actualizar ubicación del almacén
    public function actualizarUbicacionAlmacen()
    {
        // Obtener la primera ubicación con stock > 0
        $primeraUbicacion = $this->ubicaciones()
            ->with('ubicacion.estante')
            ->where('cantidad', '>', 0)
            ->first();

        if ($primeraUbicacion && $primeraUbicacion->ubicacion && $primeraUbicacion->ubicacion->estante) {
            $ubicacionAlmacen = $primeraUbicacion->ubicacion->estante->nombre . ' - ' . $primeraUbicacion->ubicacion->codigo;
            $this->update(['ubicacion_almacen' => $ubicacionAlmacen]);
        } else {
            // Si no tiene ubicaciones, marcar como sin ubicar
            $this->update(['ubicacion_almacen' => null]);
        }

        return $this->ubicacion_almacen;
    }

    // ==========================================
    // ACCESSORS PARA COMPATIBILIDAD CON FRONTEND
    // ==========================================

    /**
     * Verificar si el producto tiene stock bajo
     */
    public function getIsLowStockAttribute()
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    /**
     * Verificar si el producto está próximo a vencer
     */
    public function getIsExpiringSoonAttribute()
    {
        if (!$this->fecha_vencimiento) return false;
        $daysUntilExpiry = now()->diffInDays($this->fecha_vencimiento, false);
        return $daysUntilExpiry <= 30 && $daysUntilExpiry > 0;
    }

    /**
     * Verificar si el producto está vencido
     */
    public function getIsExpiredAttribute()
    {
        if (!$this->fecha_vencimiento) return false;
        return $this->fecha_vencimiento->isBefore(now());
    }

    /**
     * Verificar si el producto está agotado
     */
    public function getIsOutOfStockAttribute()
    {
        return $this->stock_actual <= 0;
    }

    /**
     * Obtener el estado del producto
     */
    public function getStatusAttribute()
    {
        if ($this->is_expired) return 'expired';
        if ($this->is_out_of_stock) return 'out_of_stock';
        if ($this->is_low_stock) return 'low_stock';
        if ($this->is_expiring_soon) return 'expiring_soon';
        return 'in_stock';
    }

    /**
     * Obtener el color del estado
     */
    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'expired':
                return '#dc2626'; // rojo
            case 'out_of_stock':
                return '#6b7280'; // gris
            case 'low_stock':
                return '#ea580c'; // naranja
            case 'expiring_soon':
                return '#d97706'; // amarillo
            default:
                return '#16a34a'; // verde
        }
    }

    /**
     * Obtener el texto del estado
     */
    public function getStatusTextAttribute()
    {
        switch ($this->status) {
            case 'expired':
                return 'Vencido';
            case 'out_of_stock':
                return 'Agotado';
            case 'low_stock':
                return 'Stock Bajo';
            case 'expiring_soon':
                return 'Por Vencer';
            default:
                return 'En Stock';
        }
    }

    // Método helper para obtener la URL de la imagen del producto
    public function getImagenUrlAttribute()
    {
        if ($this->imagen) {
            $raw = $this->imagen;
            // Si ya es URL absoluta
            if (preg_match('/^https?:\/\//i', $raw)) {
                return $raw;
            }

            $rel = ltrim($raw, '/');
            // 1) storage/app/public
            if (Storage::disk('public')->exists($rel)) {
                return asset('storage/' . $rel);
            }

            // 2) public/* (incluye public/productos/<archivo> si solo guardaron el nombre)
            $candidates = [$rel];
            if (strpos($rel, '/') === false) {
                $candidates[] = 'productos/' . $rel;
            }
            // Si el valor tiene prefijo storage/, probar también sin el prefijo
            if (str_starts_with($rel, 'storage/')) {
                $stripped = substr($rel, strlen('storage/'));
                $candidates[] = $stripped;
                $candidates[] = 'storage/' . $stripped;
            } else {
                $candidates[] = 'storage/' . $rel;
            }
            foreach ($candidates as $candidate) {
                $p = public_path($candidate);
                if (file_exists($p)) {
                    return asset($candidate);
                }
            }
        }
        return asset('assets/images/default-product.svg');
    }

    // Método helper para verificar si la imagen existe
    public function imagenExiste()
    {
        if (!$this->imagen) {
            return false;
        }
        return file_exists(storage_path('app/public/' . $this->imagen));
    }

    // Accessor para fecha de fabricación solo fecha
    public function getFechaFabricacionSoloFechaAttribute()
    {
        return $this->fecha_fabricacion ? $this->fecha_fabricacion->format('Y-m-d') : null;
    }

    // Accessor para fecha de vencimiento solo fecha
    public function getFechaVencimientoSoloFechaAttribute()
    {
        return $this->fecha_vencimiento ? $this->fecha_vencimiento->format('Y-m-d') : null;
    }

    // Método para convertir cantidad de unidades a presentaciones
    public function convertirAPresentaciones($cantidadUnidades)
    {
        if (!$this->unidades_por_presentacion || $this->unidades_por_presentacion <= 0) {
            return 0;
        }
        return floor($cantidadUnidades / $this->unidades_por_presentacion);
    }

    // Método para convertir cantidad de presentaciones a unidades
    public function convertirAUnidades($cantidadPresentaciones)
    {
        if (!$this->unidades_por_presentacion || $this->unidades_por_presentacion <= 0) {
            return 0;
        }
        return $cantidadPresentaciones * $this->unidades_por_presentacion;
    }

    // Método para verificar y actualizar stock después de una venta
    public function actualizarStockVenta($cantidad, $tipo = 'unidad')
    {
        if ($cantidad <= 0) {
            throw new \Exception('La cantidad debe ser mayor a cero');
        }

        if ($tipo === 'presentacion') {
            $cantidad = $this->convertirAUnidades($cantidad);
        }

        if ($this->stock_unidades < $cantidad) {
            throw new \Exception('Stock insuficiente');
        }

        $this->stock_unidades -= $cantidad;
        $this->stock_presentaciones = $this->convertirAPresentaciones($this->stock_unidades);
        $this->stock_actual = $this->stock_unidades;
        $this->save();
        
        // Recalcular el estado después de actualizar el stock
        $this->recalcularEstado();
    }

    // Método para agregar stock desde compra, convirtiendo si es necesario
    public function agregarStock($cantidad, $tipo = 'presentacion')
    {
        if ($cantidad <= 0) {
            throw new \Exception('La cantidad debe ser mayor a cero');
        }

        if ($tipo === 'presentacion') {
            $cantidad = $this->convertirAUnidades($cantidad);
        }

        $this->stock_unidades += $cantidad;
        $this->stock_presentaciones = $this->convertirAPresentaciones($this->stock_unidades);
        $this->stock_actual = $this->stock_unidades;
        $this->save();
        
        // Recalcular el estado después de actualizar el stock
        $this->recalcularEstado();
    }

    /**
     * Recalcular y actualizar el estado del producto basado en stock y fecha de vencimiento
     */
    public function recalcularEstado()
    {
        $ahora = now();
        
        // 1. Verificar si está vencido
        if ($this->fecha_vencimiento && $ahora->gt($this->fecha_vencimiento)) {
            $estado = 'Vencido';
        }
        // 2. Verificar si está agotado (stock 0)
        elseif ($this->stock_actual <= 0) {
            $estado = 'Agotado';
        }
        // 3. Verificar si está próximo a vencer (30 días) - tiene prioridad sobre stock bajo
        elseif ($this->fecha_vencimiento) {
            $diasParaVencer = $ahora->diffInDays($this->fecha_vencimiento, false);
            if ($diasParaVencer <= 30 && $diasParaVencer > 0) {
                $estado = 'Por vencer';
            }
            // 4. Verificar si tiene stock bajo
            elseif ($this->stock_actual <= $this->stock_minimo) {
                $estado = 'Bajo stock';
            }
            else {
                $estado = 'Normal';
            }
        }
        // 4. Verificar si tiene stock bajo (cuando no hay fecha de vencimiento)
        elseif ($this->stock_actual <= $this->stock_minimo) {
            $estado = 'Bajo stock';
        }
        // 5. Estado normal
        else {
            $estado = 'Normal';
        }
        
        // Actualizar solo si el estado cambió
        if ($this->estado !== $estado) {
            $this->update(['estado' => $estado]);
        }
        
        return $estado;
    }
}
