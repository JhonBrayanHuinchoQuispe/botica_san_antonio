<?php

namespace App\Http\Controllers\Venta;

use App\Http\Controllers\Controller;
use App\Services\LoteService;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VentaLoteController extends Controller
{
    /**
     * @var LoteService
     */
    protected $loteService;

    public function __construct(LoteService $loteService)
    {
        $this->loteService = $loteService;
    }

    /**
     * Procesar venta usando sistema FIFO
     */
    public function procesarVenta(Request $request)
    {
        // Validación de datos
        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|integer|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
            'observaciones' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        DB::beginTransaction();
        
        try {
            $producto = Producto::findOrFail($validatedData['producto_id']);
            
            // Verificar stock disponible
            if ($producto->stock_actual < $validatedData['cantidad']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente',
                    'data' => [
                        'stock_disponible' => $producto->stock_actual,
                        'cantidad_solicitada' => $validatedData['cantidad']
                    ]
                ], 400);
            }

            // Procesar venta usando FIFO o selección manual
            $resultadoVenta = $this->loteService->procesarVenta(
                $validatedData['producto_id'],
                $validatedData['cantidad'],
                ['observaciones' => $validatedData['observaciones'] ?? null],
                $validatedData['lote_id'] ?? null
            );

            if (!$resultadoVenta['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $resultadoVenta['message']
                ], 400);
            }

            DB::commit();

            // Log de auditoría
            $tipoVenta = isset($validatedData['lote_id']) ? 'Selección Manual' : 'FIFO';
            Log::info("Venta procesada ({$tipoVenta})", [
                'usuario_id' => auth()->id(),
                'producto_id' => $validatedData['producto_id'],
                'producto_nombre' => $producto->nombre,
                'cantidad_vendida' => $validatedData['cantidad'],
                'lote_manual_id' => $validatedData['lote_id'] ?? null,
                'lotes_utilizados' => count($resultadoVenta['lotes_utilizados']),
                'stock_anterior' => $resultadoVenta['stock_anterior'],
                'stock_nuevo' => $resultadoVenta['stock_nuevo']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Venta procesada correctamente',
                'data' => [
                    'producto' => $producto->nombre,
                    'cantidad_vendida' => $validatedData['cantidad'],
                    'lotes_utilizados' => $resultadoVenta['lotes_utilizados'],
                    'stock_anterior' => $resultadoVenta['stock_anterior'],
                    'stock_nuevo' => $resultadoVenta['stock_nuevo'],
                    'precio_promedio' => $resultadoVenta['precio_promedio']
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al procesar venta con sistema FIFO', [
                'usuario_id' => Auth::id(),
                'producto_id' => $validatedData['producto_id'] ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener información de lotes disponibles para venta
     */
    public function obtenerLotesDisponibles($productoId)
    {
        try {
            $producto = Producto::findOrFail($productoId);
            
            $lotesDisponibles = $this->loteService->obtenerLotesParaVenta($productoId, $producto->stock_actual);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'producto' => [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'stock_actual' => $producto->stock_actual
                    ],
                    'lotes_disponibles' => $lotesDisponibles,
                    'total_lotes' => count($lotesDisponibles),
                    'stock_total_lotes' => collect($lotesDisponibles)->sum('cantidad')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener lotes disponibles', [
                'producto_id' => $productoId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información de lotes'
            ], 500);
        }
    }

    /**
     * Simular venta para mostrar qué lotes se utilizarían
     */
    public function simularVenta(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|integer|exists:productos,id',
            'cantidad' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $productoId = $request->input('producto_id');
            $cantidad = $request->input('cantidad');
            
            $producto = Producto::findOrFail($productoId);
            
            // Verificar stock disponible
            if ($producto->stock_actual < $cantidad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente para la simulación',
                    'data' => [
                        'stock_disponible' => $producto->stock_actual,
                        'cantidad_solicitada' => $cantidad
                    ]
                ], 400);
            }

            // Obtener lotes que se utilizarían (simulación)
            $lotesParaVenta = $this->loteService->obtenerLotesParaVenta($productoId, $cantidad);
            
            $lotesUtilizados = [];
            $cantidadRestante = $cantidad;
            $precioTotal = 0;
            
            foreach ($lotesParaVenta as $lote) {
                if ($cantidadRestante <= 0) break;
                
                $cantidadDelLote = min($cantidadRestante, $lote['cantidad']);
                $precioLote = $cantidadDelLote * $lote['precio_venta_lote'];
                
                $lotesUtilizados[] = [
                    'lote_id' => $lote['id'],
                    'lote' => $lote['lote'],
                    'fecha_vencimiento' => $lote['fecha_vencimiento'],
                    'cantidad_utilizada' => $cantidadDelLote,
                    'cantidad_disponible' => $lote['cantidad'],
                    'precio_unitario' => $lote['precio_venta_lote'],
                    'precio_total_lote' => $precioLote,
                    'dias_para_vencer' => $lote['dias_para_vencer']
                ];
                
                $precioTotal += $precioLote;
                $cantidadRestante -= $cantidadDelLote;
            }
            
            $precioPromedio = $cantidad > 0 ? $precioTotal / $cantidad : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'producto' => [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'stock_actual' => $producto->stock_actual
                    ],
                    'cantidad_solicitada' => $cantidad,
                    'lotes_utilizados' => $lotesUtilizados,
                    'precio_total' => $precioTotal,
                    'precio_promedio' => $precioPromedio,
                    'stock_resultante' => $producto->stock_actual - $cantidad
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al simular venta', [
                'producto_id' => $request->input('producto_id'),
                'cantidad' => $request->input('cantidad'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al simular venta'
            ], 500);
        }
    }

    /**
     * Obtener resumen de lotes por producto
     */
    public function resumenLotes($productoId)
    {
        try {
            $producto = Producto::findOrFail($productoId);
            $infoLotes = $this->loteService->obtenerInfoLotes($productoId);

            return response()->json([
                'success' => true,
                'data' => [
                    'producto' => [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'stock_actual' => $producto->stock_actual
                    ],
                    'resumen_lotes' => $infoLotes
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener resumen de lotes', [
                'producto_id' => $productoId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen de lotes'
            ], 500);
        }
    }
}