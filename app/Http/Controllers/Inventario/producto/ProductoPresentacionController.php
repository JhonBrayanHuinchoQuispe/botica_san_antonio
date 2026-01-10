<?php
namespace App\Http\Controllers\Inventario\producto;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductoPresentacion;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class ProductoPresentacionController extends Controller
{
    /**
     * Listar presentaciones de un producto.
     */
    public function index(Request $request)
    {
        $productoId = $request->query('producto_id');
        if (!$productoId) {
            return response()->json(['success' => false, 'message' => 'producto_id es requerido'], 422);
        }
        $presentaciones = ProductoPresentacion::where('producto_id', $productoId)
            ->orderBy('id', 'asc')
            ->get();

        // Calcular stock por presentación (a partir del stock en unidad base del producto)
        $producto = Producto::find($productoId);
        $stockBase = $producto ? (int) $producto->stock_actual : 0;
        $presentaciones = $presentaciones->map(function ($p) use ($stockBase) {
            $p->stock_unidades_base = $stockBase;
            $p->stock_presentacion = $p->factor_unidad_base > 0
                ? intdiv($stockBase, max(1, $p->factor_unidad_base))
                : 0;
            return $p;
        });

        return response()->json(['success' => true, 'data' => $presentaciones]);
    }

    /**
     * Crear nueva presentación para un producto.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'unidad_venta' => 'required|string|max:50',
            'factor_unidad_base' => 'required|integer|min:1',
            'precio_venta' => 'nullable|numeric|min:0',
            'permite_fraccionamiento' => 'boolean',
            'estado' => 'boolean',
        ]);

        $presentacion = ProductoPresentacion::create($validated);
        return response()->json(['success' => true, 'data' => $presentacion]);
    }

    /**
     * Mostrar una presentación.
     */
    public function show($id)
    {
        $presentacion = ProductoPresentacion::findOrFail($id);
        $producto = Producto::find($presentacion->producto_id);
        $stockBase = $producto ? (int) $producto->stock_actual : 0;
        $presentacion->stock_unidades_base = $stockBase;
        $presentacion->stock_presentacion = $presentacion->factor_unidad_base > 0
            ? intdiv($stockBase, max(1, $presentacion->factor_unidad_base))
            : 0;
        return response()->json(['success' => true, 'data' => $presentacion]);
    }

    /**
     * Actualizar una presentación.
     */
    public function update(Request $request, $id)
    {
        $presentacion = ProductoPresentacion::findOrFail($id);
        $validated = $request->validate([
            'unidad_venta' => 'sometimes|required|string|max:50',
            'factor_unidad_base' => 'sometimes|required|integer|min:1',
            'precio_venta' => 'nullable|numeric|min:0',
            'permite_fraccionamiento' => 'boolean',
            'estado' => 'boolean',
        ]);
        $presentacion->update($validated);
        return response()->json(['success' => true, 'data' => $presentacion]);
    }

    /**
     * Eliminar una presentación.
     */
    public function destroy($id)
    {
        $presentacion = ProductoPresentacion::findOrFail($id);
        $presentacion->delete();
        return response()->json(['success' => true]);
    }
}