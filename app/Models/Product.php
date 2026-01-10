<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand', 
        'description',
        'stock',
        'minStock',
        'expiryDate',
        'price',
        'costPrice',
        'barcode',
        'category',
        'laboratory',
        'batchNumber',
        'imageUrl'
    ];

    protected $casts = [
        'expiryDate' => 'date',
        'price' => 'decimal:2',
        'costPrice' => 'decimal:2',
        'stock' => 'integer',
        'minStock' => 'integer',
    ];

    // ==========================================
    // ACCESSORS Y MUTATORS
    // ==========================================

    /**
     * Verificar si el producto tiene stock bajo
     */
    public function getIsLowStockAttribute()
    {
        return $this->stock <= $this->minStock;
    }

    /**
     * Verificar si el producto está próximo a vencer
     */
    public function getIsExpiringSoonAttribute()
    {
        $daysUntilExpiry = Carbon::now()->diffInDays($this->expiryDate, false);
        return $daysUntilExpiry <= 30 && $daysUntilExpiry > 0;
    }

    /**
     * Verificar si el producto está vencido
     */
    public function getIsExpiredAttribute()
    {
        return $this->expiryDate->isBefore(Carbon::now());
    }

    /**
     * Verificar si el producto está agotado
     */
    public function getIsOutOfStockAttribute()
    {
        return $this->stock <= 0;
    }

    /**
     * Obtener días hasta el vencimiento
     */
    public function getDaysUntilExpiryAttribute()
    {
        return Carbon::now()->diffInDays($this->expiryDate, false);
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

    /**
     * Calcular el progreso del stock
     */
    public function getStockProgressAttribute()
    {
        if ($this->minStock == 0) return 1.0;
        $maxStock = $this->minStock * 3;
        return min($this->stock / $maxStock, 1.0);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Productos con stock bajo
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock <= minStock');
    }

    /**
     * Productos próximos a vencer
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiryDate', '<=', Carbon::now()->addDays($days))
                    ->where('expiryDate', '>', Carbon::now());
    }

    /**
     * Productos vencidos
     */
    public function scopeExpired($query)
    {
        return $query->where('expiryDate', '<', Carbon::now());
    }

    /**
     * Productos agotados
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stock', '<=', 0);
    }

    /**
     * Productos en stock
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Búsqueda por texto
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('brand', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%")
              ->orWhere('category', 'like', "%{$search}%")
              ->orWhere('laboratory', 'like', "%{$search}%");
        });
    }

    /**
     * Filtrar por categoría
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // ==========================================
    // MÉTODOS DE INSTANCIA
    // ==========================================

    /**
     * Agregar stock al producto
     */
    public function addStock($quantity, $batchNumber = null, $expiryDate = null)
    {
        $this->stock += $quantity;
        
        if ($batchNumber) {
            $this->batchNumber = $batchNumber;
        }
        
        if ($expiryDate) {
            $this->expiryDate = $expiryDate;
        }
        
        $this->save();
        
        return $this;
    }

    /**
     * Reducir stock del producto
     */
    public function reduceStock($quantity)
    {
        if ($quantity > $this->stock) {
            throw new \Exception('Stock insuficiente');
        }
        
        $this->stock -= $quantity;
        $this->save();
        
        return $this;
    }

    /**
     * Ajustar stock del producto
     */
    public function adjustStock($newQuantity, $reason = null)
    {
        $oldStock = $this->stock;
        $this->stock = $newQuantity;
        $this->save();
        
        // Aquí podrías registrar el ajuste en una tabla de auditoría
        // StockAdjustment::create([
        //     'product_id' => $this->id,
        //     'old_stock' => $oldStock,
        //     'new_stock' => $newQuantity,
        //     'difference' => $newQuantity - $oldStock,
        //     'reason' => $reason,
        //     'user_id' => auth()->id(),
        // ]);
        
        return $this;
    }

    /**
     * Verificar si necesita reabastecimiento
     */
    public function needsRestock()
    {
        return $this->is_low_stock || $this->is_out_of_stock;
    }

    /**
     * Calcular el valor total del inventario de este producto
     */
    public function getInventoryValueAttribute()
    {
        return $this->stock * ($this->costPrice ?? $this->price);
    }

    /**
     * Calcular la ganancia potencial
     */
    public function getPotentialProfitAttribute()
    {
        if (!$this->costPrice) return 0;
        return $this->stock * ($this->price - $this->costPrice);
    }

    // ==========================================
    // MÉTODOS ESTÁTICOS
    // ==========================================

    /**
     * Obtener estadísticas generales
     */
    public static function getStats()
    {
        return [
            'total_products' => static::count(),
            'in_stock' => static::inStock()->count(),
            'out_of_stock' => static::outOfStock()->count(),
            'low_stock' => static::lowStock()->count(),
            'expiring_soon' => static::expiringSoon()->count(),
            'expired' => static::expired()->count(),
            'total_inventory_value' => static::sum(\DB::raw('stock * COALESCE(costPrice, price)')),
        ];
    }

    /**
     * Buscar producto por código de barras
     */
    public static function findByBarcode($barcode)
    {
        return static::where('barcode', $barcode)->first();
    }

    /**
     * Obtener categorías únicas
     */
    public static function getCategories()
    {
        return static::whereNotNull('category')
                     ->distinct()
                     ->pluck('category')
                     ->filter()
                     ->sort()
                     ->values();
    }

    /**
     * Obtener laboratorios únicos
     */
    public static function getLaboratories()
    {
        return static::whereNotNull('laboratory')
                     ->distinct()
                     ->pluck('laboratory')
                     ->filter()
                     ->sort()
                     ->values();
    }

    // ==========================================
    // RELACIONES (para futuro)
    // ==========================================

    /**
     * Relación con ventas (cuando implementes)
     */
    // public function sales()
    // {
    //     return $this->hasMany(Sale::class);
    // }

    /**
     * Relación con ajustes de stock (cuando implementes)
     */
    // public function stockAdjustments()
    // {
    //     return $this->hasMany(StockAdjustment::class);
    // }
} 