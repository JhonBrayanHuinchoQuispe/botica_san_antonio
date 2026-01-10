<?php

namespace App\Repositories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductoRepository
{
    protected $model;
    
    public function __construct(Producto $model)
    {
        $this->model = $model;
    }
    
    /**
     * Obtener todos los productos con relaciones
     */
    public function getAllWithRelations(array $relations = [])
    {
        return $this->model->with($relations)->get();
    }
    
    /**
     * Obtener productos paginados con filtros
     */
    public function getPaginatedWithFilters(array $filters = [], int $perPage = 15)
    {
        $query = $this->model->query();
        
        // Aplicar filtros
        if (!empty($filters['categoria'])) {
            $query->where('categoria', $filters['categoria']);
        }
        
        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }
        
        if (!empty($filters['marca'])) {
            $query->where('marca', $filters['marca']);
        }
        
        if (!empty($filters['busqueda'])) {
            $query->where(function($q) use ($filters) {
                $q->where('nombre', 'like', '%' . $filters['busqueda'] . '%')
                  ->orWhere('codigo_barras', 'like', '%' . $filters['busqueda'] . '%')
                  ->orWhere('lote', 'like', '%' . $filters['busqueda'] . '%');
            });
        }
        
        if (!empty($filters['fecha_vencimiento_desde'])) {
            $query->where('fecha_vencimiento', '>=', $filters['fecha_vencimiento_desde']);
        }
        
        if (!empty($filters['fecha_vencimiento_hasta'])) {
            $query->where('fecha_vencimiento', '<=', $filters['fecha_vencimiento_hasta']);
        }
        
        // Ordenamiento
        $orderBy = $filters['order_by'] ?? 'nombre';
        $orderDirection = $filters['order_direction'] ?? 'asc';
        $query->orderBy($orderBy, $orderDirection);
        
        return $query->paginate($perPage);
    }
    
    /**
     * Buscar productos por término
     */
    public function search(string $term, int $limit = 20)
    {
        return $this->model
            ->where('nombre', 'like', '%' . $term . '%')
            ->orWhere('codigo_barras', 'like', '%' . $term . '%')
            ->orWhere('lote', 'like', '%' . $term . '%')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Obtener productos con bajo stock
     */
    public function getLowStock()
    {
        return $this->model
            ->whereRaw('stock_actual <= stock_minimo')
            ->orderBy('stock_actual', 'asc')
            ->get();
    }
    
    /**
     * Obtener productos próximos a vencer
     */
    public function getExpiringSoon(int $days = 30)
    {
        $fechaLimite = Carbon::now()->addDays($days);
        
        return $this->model
            ->where('fecha_vencimiento', '<=', $fechaLimite)
            ->where('fecha_vencimiento', '>', Carbon::now())
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();
    }
    
    /**
     * Obtener productos vencidos
     */
    public function getExpired()
    {
        return $this->model
            ->where('fecha_vencimiento', '<', Carbon::now())
            ->orderBy('fecha_vencimiento', 'desc')
            ->get();
    }
    
    /**
     * Obtener productos por categoría
     */
    public function getByCategory(string $categoria)
    {
        return $this->model
            ->where('categoria', $categoria)
            ->orderBy('nombre')
            ->get();
    }
    
    /**
     * Obtener productos por marca
     */
    public function getByBrand(string $marca)
    {
        return $this->model
            ->where('marca', $marca)
            ->orderBy('nombre')
            ->get();
    }
    
    /**
     * Crear nuevo producto
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }
    
    /**
     * Actualizar producto
     */
    public function update(int $id, array $data)
    {
        $producto = $this->model->findOrFail($id);
        $producto->update($data);
        return $producto;
    }
    
    /**
     * Eliminar producto
     */
    public function delete(int $id)
    {
        $producto = $this->model->findOrFail($id);
        return $producto->delete();
    }
    
    /**
     * Obtener producto por código de barras
     */
    public function getByBarcode(string $barcode)
    {
        return $this->model->where('codigo_barras', $barcode)->first();
    }
    
    /**
     * Obtener estadísticas de productos
     */
    public function getStatistics()
    {
        return [
            'total' => $this->model->count(),
            'bajo_stock' => $this->model->whereRaw('stock_actual <= stock_minimo')->count(),
            'vencidos' => $this->model->where('fecha_vencimiento', '<', Carbon::now())->count(),
            'por_vencer' => $this->model->whereBetween('fecha_vencimiento', [
                Carbon::now(),
                Carbon::now()->addDays(30)
            ])->count(),
            'sin_stock' => $this->model->where('stock_actual', 0)->count(),
            'valor_inventario' => $this->model->sum(DB::raw('stock_actual * precio_compra'))
        ];
    }
    
    /**
     * Obtener productos más vendidos
     */
    public function getMostSold(int $limit = 10)
    {
        return $this->model
            ->select('productos.*', DB::raw('SUM(venta_detalles.cantidad) as total_vendido'))
            ->join('venta_detalles', 'productos.id', '=', 'venta_detalles.producto_id')
            ->groupBy('productos.id')
            ->orderBy('total_vendido', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Obtener productos con movimientos recientes
     */
    public function getWithRecentMovements(int $days = 7)
    {
        $fechaDesde = Carbon::now()->subDays($days);
        
        return $this->model
            ->select('productos.*')
            ->join('movimientos_stock', 'productos.id', '=', 'movimientos_stock.producto_id')
            ->where('movimientos_stock.created_at', '>=', $fechaDesde)
            ->distinct()
            ->orderBy('movimientos_stock.created_at', 'desc')
            ->get();
    }
    
    /**
     * Actualizar stock masivo
     */
    public function updateStockBatch(array $updates)
    {
        DB::beginTransaction();
        
        try {
            foreach ($updates as $update) {
                $this->model
                    ->where('id', $update['id'])
                    ->update(['stock_actual' => $update['stock']]);
            }
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}