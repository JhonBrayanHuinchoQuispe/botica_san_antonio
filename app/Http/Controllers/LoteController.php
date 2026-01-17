<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoUbicacion;
use App\Models\Notification;
use App\Models\User;
use App\Services\LoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LoteController extends Controller
{
    protected $loteService;

    public function __construct(LoteService $loteService)
    {
        $this->loteService = $loteService;
    }

    /**
     * Verificar y generar alertas para un lote modificado
     */
    private function verificarAlertasLote($lote)
    {
        try {
            if (!$lote || !$lote->producto) return;
            
            // Solo generar alertas si el lote está activo y tiene stock
            if ($lote->estado_lote !== 'activo' || $lote->cantidad <= 0) return;
            
            $producto = $lote->producto;
            $userIds = User::pluck('id');
            
            // Verificar vencimiento
            if ($lote->fecha_vencimiento) {
                $dias = Carbon::now()->diffInDays($lote->fecha_vencimiento, false);
                
                foreach ($userIds as $userId) {
                    // 1. Vencido
                    if ($dias < 0) {
                        $exists = Notification::where('type', Notification::TYPE_PRODUCTO_VENCIDO)
                            ->where('user_id', $userId)
                            ->where('data->lote_id', $lote->id)
                            ->unread()
                            ->exists();
                            
                        if (!$exists) {
                            Notification::createProductoVencido($userId, $producto, $lote->id);
                        }
                    }
                    // 2. Por vencer (<= 90 días)
                    elseif ($dias <= 90) {
                        $exists = Notification::where('type', Notification::TYPE_PRODUCTO_VENCIMIENTO)
                            ->where('user_id', $userId)
                            ->where('data->lote_id', $lote->id)
                            ->unread()
                            ->exists();
                            
                        if (!$exists) {
                            Notification::createProximoVencer($userId, $producto, $dias, $lote->id);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error generando alerta inmediata lote: '.$e->getMessage());
        }
    }

    /**
     * Mostrar los lotes de un producto específico
     */
    public function index($productoId)
    {
        try {
            $producto = Producto::with(['categoria_model', 'presentacion_model'])->findOrFail($productoId);
            
            // Obtener todos los lotes del producto
            $lotes = ProductoUbicacion::with(['ubicacion', 'proveedor'])
                ->where('producto_id', $productoId)
                ->orderBy('fecha_vencimiento', 'asc')
                ->orderBy('fecha_ingreso', 'asc')
                ->get()
                ->map(function ($lote) {
                    // Calcular días para vencer
                    if ($lote->fecha_vencimiento) {
                        $lote->dias_para_vencer = Carbon::now()->diffInDays($lote->fecha_vencimiento, false);
                    } else {
                        $lote->dias_para_vencer = null;
                    }
                    
                    return $lote;
                });

            // Obtener resumen de lotes usando el servicio
            $infoLotes = $this->loteService->obtenerInfoLotes($productoId);
            $resumenLotes = [
                'total_lotes' => $lotes->count(),
                'lotes_activos' => $lotes->where('estado_lote', 'activo')->count(),
                'proximos_vencer' => $infoLotes['lotesProximosVencer'] ?? 0,
                'vencidos' => $lotes->where('estado_lote', 'vencido')->count(),
            ];

            return view('inventario.lotes.index', compact('producto', 'lotes', 'resumenLotes'));
            
        } catch (\Exception $e) {
            Log::error('Error al mostrar lotes del producto: ' . $e->getMessage());
            return redirect()->route('inventario.productos.botica')
                ->with('error', 'Error al cargar los lotes del producto');
        }
    }

    /**
     * Obtener información de lotes para AJAX
     */
    public function obtenerLotes($productoId)
    {
        try {
            $infoLotes = $this->loteService->obtenerInfoLotes($productoId);
            
            return response()->json([
                'success' => true,
                'data' => $infoLotes
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener información de lotes: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información de lotes'
            ], 500);
        }
    }

    /**
     * Obtener lotes disponibles para venta
     */
    public function lotesDisponibles($productoId)
    {
        try {
            $lotes = $this->loteService->obtenerLotesDisponibles($productoId);
            
            return response()->json([
                'success' => true,
                'data' => $lotes
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener lotes disponibles: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener lotes disponibles'
            ], 500);
        }
    }

    /**
     * Simular una venta para ver qué lotes se usarían
     */
    public function simularVenta(Request $request)
    {
        try {
            $request->validate([
                'producto_id' => 'required|exists:productos,id',
                'cantidad' => 'required|numeric|min:1'
            ]);

            $simulacion = $this->loteService->simularVenta(
                $request->producto_id,
                $request->cantidad
            );
            
            return response()->json([
                'success' => true,
                'data' => $simulacion
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al simular venta: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al simular la venta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajustar stock de un lote específico
     */
    public function ajustarStock(Request $request, $loteId)
    {
        try {
            $request->validate([
                'nueva_cantidad' => 'required|numeric|min:0',
                'motivo' => 'required|string',
                'observaciones' => 'nullable|string|max:500'
            ]);

            $lote = ProductoUbicacion::findOrFail($loteId);
            $cantidadAnterior = $lote->cantidad;
            $nuevaCantidad = $request->nueva_cantidad;
            $diferencia = $nuevaCantidad - $cantidadAnterior;

            // Actualizar el lote
            $lote->update([
                'cantidad' => $nuevaCantidad
            ]);

            // Registrar el movimiento
            $this->loteService->registrarMovimiento([
                'producto_ubicacion_id' => $loteId,
                'tipo_movimiento' => $diferencia > 0 ? 'ajuste_positivo' : 'ajuste_negativo',
                'cantidad' => abs($diferencia),
                'motivo' => $request->motivo,
                'observaciones' => $request->observaciones,
                'usuario_id' => auth()->id()
            ]);

            // Actualizar stock total del producto
            $producto = $lote->producto;
            $stockTotal = ProductoUbicacion::where('producto_id', $producto->id)
                ->where('estado_lote', 'activo')
                ->sum('cantidad');
            
            $producto->update(['stock_actual' => $stockTotal]);
            
            // Recalcular el estado del producto después de actualizar el stock
            $producto->fresh()->recalcularEstado();

            // Verificar alertas inmediatas tras ajuste de stock
            $this->verificarAlertasLote($lote);

            return response()->json([
                'success' => true,
                'message' => 'Stock ajustado correctamente',
                'data' => [
                    'cantidad_anterior' => $cantidadAnterior,
                    'cantidad_nueva' => $nuevaCantidad,
                    'diferencia' => $diferencia
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al ajustar stock del lote: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al ajustar el stock: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener movimientos de un lote
     */
    public function movimientos($loteId)
    {
        try {
            $lote = ProductoUbicacion::with(['producto', 'ubicacion'])->findOrFail($loteId);
            $movimientos = $this->loteService->obtenerMovimientosLote($loteId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'lote' => $lote,
                    'movimientos' => $movimientos
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener movimientos del lote: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener movimientos del lote'
            ], 500);
        }
    }

    /**
     * Crear un nuevo lote manualmente (Ajuste de entrada)
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'producto_id' => 'required|exists:productos,id',
                'lote' => 'required|string|max:100',
                'fecha_vencimiento' => 'nullable|date',
                'cantidad' => 'required|integer|min:1',
                'observaciones' => 'nullable|string'
            ]);

            $lote = $this->loteService->crearLote([
                'producto_id' => $request->producto_id,
                'lote' => $request->lote,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'cantidad' => $request->cantidad,
                'observaciones' => $request->observaciones,
                // Ubicacion por defecto se maneja en servicio si es null
            ]);

            // Verificar alertas inmediatas
            $this->verificarAlertasLote($lote);

            return response()->json([
                'success' => true,
                'message' => 'Lote creado correctamente',
                'data' => $lote
            ]);

        } catch (\Exception $e) {
            Log::error('Error al crear lote manual: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el lote'
            ], 500);
        }
    }

    /**
     * Actualizar metadatos de un lote (código, vencimiento)
     */
    public function update(Request $request, $loteId)
    {
        try {
            $request->validate([
                'lote' => 'required|string|max:100',
                'fecha_vencimiento' => 'nullable|date'
            ]);

            $lote = ProductoUbicacion::findOrFail($loteId);
            
            // Verificar unicidad si cambia el código (opcional, depende de reglas de negocio)
            // Por ahora permitimos duplicados en diferentes productos, pero advertimos si es el mismo producto?
            // Dejamos flexible.

            $lote->update([
                'lote' => $request->lote,
                'fecha_vencimiento' => $request->fecha_vencimiento
            ]);

            // Verificar alertas inmediatas
            $this->verificarAlertasLote($lote);

            return response()->json([
                'success' => true,
                'message' => 'Lote actualizado correctamente',
                'data' => $lote
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al actualizar lote: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el lote'
            ], 500);
        }
    }

    /**
     * Eliminar un lote
     */
    public function destroy($loteId)
    {
        try {
            $lote = ProductoUbicacion::findOrFail($loteId);
            
            // Validar si tiene stock
            if ($lote->cantidad > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar un lote con stock. Ajuste el stock a 0 primero.'
                ], 400);
            }

            // Opcional: Verificar si tiene movimientos (ventas, etc)
            // Si tiene movimientos, quizás solo marcar como inactivo o eliminado lógico?
            // Por simplicidad, permitimos eliminar si stock es 0.
            
            $lote->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lote eliminado correctamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al eliminar lote: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el lote'
            ], 500);
        }
    }

    /**
     * Marcar lotes como vencidos manualmente
     */
    public function marcarVencidos(Request $request)
    {
        try {
            $request->validate([
                'lote_ids' => 'required|array',
                'lote_ids.*' => 'exists:producto_ubicaciones,id'
            ]);

            $lotesActualizados = 0;
            
            foreach ($request->lote_ids as $loteId) {
                $resultado = $this->loteService->marcarLoteVencido($loteId);
                if ($resultado) {
                    $lotesActualizados++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Se marcaron {$lotesActualizados} lotes como vencidos",
                'lotes_actualizados' => $lotesActualizados
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al marcar lotes como vencidos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar lotes como vencidos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado de un lote manualmente
     */
    public function cambiarEstado(Request $request, $loteId)
    {
        try {
            $request->validate([
                'estado' => 'required|string|in:activo,por_vencer,vencido,agotado,cuarentena,mermas'
            ]);

            $lote = ProductoUbicacion::findOrFail($loteId);
            $lote->update([
                'estado_lote' => $request->estado
            ]);
            
            // Recalcular estado del producto si es necesario
            if ($lote->producto) {
                // Verificar si el modelo tiene el método (para evitar errores si no está implementado)
                if (method_exists($lote->producto, 'recalcularEstado')) {
                    $lote->producto->fresh()->recalcularEstado();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Estado del lote actualizado correctamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del lote: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del lote'
            ], 500);
        }
    }

    /**
     * Generar reporte de lotes próximos a vencer
     */
    public function reporteProximosVencer(Request $request)
    {
        try {
            $diasAnticipacion = $request->get('dias', 30);
            
            $lotes = ProductoUbicacion::with(['producto', 'ubicacion', 'proveedor'])
                ->where('estado_lote', 'activo')
                ->where('cantidad', '>', 0)
                ->whereNotNull('fecha_vencimiento')
                ->whereDate('fecha_vencimiento', '<=', Carbon::now()->addDays($diasAnticipacion))
                ->orderBy('fecha_vencimiento', 'asc')
                ->get()
                ->map(function ($lote) {
                    $lote->dias_para_vencer = Carbon::now()->diffInDays($lote->fecha_vencimiento, false);
                    $lote->valor_total = $lote->cantidad * $lote->precio_compra_lote;
                    return $lote;
                });

            $resumen = [
                'total_lotes' => $lotes->count(),
                'cantidad_total' => $lotes->sum('cantidad'),
                'valor_total' => $lotes->sum('valor_total'),
                'urgentes' => $lotes->where('dias_para_vencer', '<=', 7)->count(),
                'moderados' => $lotes->whereBetween('dias_para_vencer', [8, 15])->count(),
                'normales' => $lotes->whereBetween('dias_para_vencer', [16, 30])->count()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'lotes' => $lotes,
                    'resumen' => $resumen
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al generar reporte de lotes próximos a vencer: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte'
            ], 500);
        }
    }
}