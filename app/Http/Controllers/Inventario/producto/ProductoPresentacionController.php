<?php

namespace App\Http\Controllers\Inventario\producto;

use App\Http\Controllers\Controller;
use App\Models\ProductoPresentacion;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProductoPresentacionController extends Controller
{
    /**
     * Obtener todas las presentaciones de un producto específico.
     */
    public function index(Request $request, $productoId)
    {
        try {
            $loteId = $request->query('lote_id');
            $presentaciones = ProductoPresentacion::where('producto_id', $productoId)
                ->orderBy('unidades_por_presentacion', 'asc')
                ->get();

            // Si hay un lote_id, sobreescribir con datos del lote si existen
            if ($loteId) {
                $presentacionesLote = DB::table('lote_presentaciones')
                    ->where('producto_ubicacion_id', $loteId)
                    ->get()
                    ->keyBy('producto_presentacion_id');

                $presentaciones->transform(function ($pres) use ($presentacionesLote) {
                    if ($presentacionesLote->has($pres->id)) {
                        $presLote = $presentacionesLote->get($pres->id);
                        $pres->precio_venta_presentacion = $presLote->precio_venta;
                        if (isset($presLote->unidades_por_presentacion)) {
                            $pres->unidades_por_presentacion = $presLote->unidades_por_presentacion;
                        }
                    }
                    return $pres;
                });
            }

            // Si no hay presentaciones, devolver al menos la unidad base del producto
            if ($presentaciones->isEmpty()) {
                $producto = Producto::find($productoId);
                if ($producto) {
                    // Usar el campo legacy 'presentacion' si existe, sino 'Unidad'
                    $nombreBase = $producto->presentacion ?: 'Unidad';
                    
                    $precioBase = $producto->precio_venta;

                    // Si hay lote_id, intentar obtener el precio base de ese lote
                    if ($loteId) {
                        $lote = DB::table('producto_ubicaciones')->find($loteId);
                        if ($lote && $lote->precio_venta_lote > 0) {
                            $precioBase = $lote->precio_venta_lote;
                        }
                    }

                    $presentaciones = collect([
                        [
                            'id' => 0, // ID temporal
                            'producto_id' => $productoId,
                            'nombre_presentacion' => $nombreBase,
                            'unidades_por_presentacion' => 1,
                            'precio_venta_presentacion' => $precioBase
                        ]
                    ]);
                }
            }

            return response()->json(['success' => true, 'data' => $presentaciones]);
        } catch (\Exception $e) {
            Log::error("Error al obtener presentaciones para producto {$productoId}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener presentaciones.'], 500);
        }
    }

    /**
     * Obtener una presentación específica
     */
    public function show($id)
    {
        try {
            $presentacion = ProductoPresentacion::with('producto')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $presentacion->id,
                    'producto_id' => $presentacion->producto_id,
                    'producto_nombre' => $presentacion->producto->nombre,
                    'nombre' => $presentacion->nombre,
                    'cantidad_unidades' => $presentacion->cantidad_unidades,
                    'precio' => $presentacion->precio,
                    'codigo_barras' => $presentacion->codigo_barras,
                    'es_principal' => $presentacion->es_principal,
                    'activo' => $presentacion->activo
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Presentación no encontrada'
            ], 404);
        }
    }

    /**
     * Crear nueva presentación para un producto
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'producto_id' => 'required|exists:productos,id',
                'nombre_presentacion' => 'required|string|max:100',
                'unidades_por_presentacion' => 'required|integer|min:1',
                'precio_venta_presentacion' => 'required|numeric|min:0.01',
                'codigo_barras' => 'nullable|string|max:255|unique:producto_presentaciones,codigo_barras'
            ], [
                'producto_id.required' => 'El producto es obligatorio',
                'producto_id.exists' => 'El producto no existe',
                'nombre_presentacion.required' => 'El nombre de la presentación es obligatorio',
                'unidades_por_presentacion.required' => 'Las unidades son obligatorias',
                'unidades_por_presentacion.min' => 'Debe tener al menos 1 unidad',
                'precio_venta_presentacion.required' => 'El precio es obligatorio',
                'precio_venta_presentacion.min' => 'El precio debe ser mayor a 0',
                'codigo_barras.unique' => 'Este código de barras ya existe'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Crear la presentación
            $presentacion = ProductoPresentacion::create([
                'producto_id' => $request->producto_id,
                'nombre_presentacion' => $request->nombre_presentacion,
                'unidades_por_presentacion' => $request->unidades_por_presentacion,
                'precio_venta_presentacion' => $request->precio_venta_presentacion,
                'codigo_barras' => $request->codigo_barras,
                'activo' => true
            ]);

            // Actualizar flag en productos
            Producto::where('id', $request->producto_id)
                ->update(['tiene_presentaciones' => 1]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Presentación creada exitosamente',
                'data' => $presentacion
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando presentación: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la presentación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una presentación existente
     */
    public function update(Request $request, $id)
    {
        try {
            $presentacion = ProductoPresentacion::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'nombre_presentacion' => 'required|string|max:100',
                'unidades_por_presentacion' => 'required|integer|min:1',
                'precio_venta_presentacion' => 'required|numeric|min:0.01',
                'codigo_barras' => 'nullable|string|max:255|unique:producto_presentaciones,codigo_barras,' . $id,
                'activo' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $presentacion->update([
                'nombre_presentacion' => $request->nombre_presentacion,
                'unidades_por_presentacion' => $request->unidades_por_presentacion,
                'precio_venta_presentacion' => $request->precio_venta_presentacion,
                'codigo_barras' => $request->codigo_barras,
                'activo' => $request->activo ?? $presentacion->activo
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Presentación actualizada exitosamente',
                'data' => $presentacion
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando presentación: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la presentación'
            ], 500);
        }
    }

    /**
     * Eliminar (desactivar) una presentación
     */
    public function destroy($id)
    {
        try {
            $presentacion = ProductoPresentacion::findOrFail($id);

            // No permitir eliminar la presentación base si es la única activa
            if ($presentacion->es_presentacion_base) {
                $otrasActivas = ProductoPresentacion::where('producto_id', $presentacion->producto_id)
                    ->where('id', '!=', $id)
                    ->where('activo', true)
                    ->count();

                if ($otrasActivas == 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede eliminar la única presentación activa del producto'
                    ], 422);
                }
            }

            DB::beginTransaction();

            // Soft delete: solo marcar como inactiva
            $presentacion->update(['activo' => false]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Presentación eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error eliminando presentación: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la presentación'
            ], 500);
        }
    }
}
