<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Estante;
use App\Models\Ubicacion;
use App\Models\ProductoUbicacion;
use App\Models\LoteMovimiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class LoteService
{
    /**
     * Crear un nuevo lote al recibir mercadería
     */
    public function crearLote(array $datos): ProductoUbicacion
    {
        return DB::transaction(function () use ($datos) {
            // Nota: Se permite crear lotes con cualquier fecha de vencimiento
            // El sistema alertará sobre productos próximos a vencer, pero no impedirá su registro

            // Fallback: si no existe la tabla de lotes, devolver instancia en memoria
            if (!Schema::hasTable('producto_ubicaciones')) {
                Log::warning('LoteService: tabla producto_ubicaciones no existe; omitiendo creación de lote');
                $stub = new ProductoUbicacion([
                    'id' => 0,
                    'producto_id' => $datos['producto_id'],
                    'ubicacion_id' => $datos['ubicacion_id'] ?? null,
                    'cantidad' => $datos['cantidad'],
                    'fecha_ingreso' => now(),
                    'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? null,
                    'lote' => $datos['lote'] ?? null,
                    'observaciones' => $datos['observaciones'] ?? null,
                ]);
                // Importante: no intentar registrar movimientos ni guardar
                return $stub;
            }
            // Obtener ubicación (si no se proporciona, dejar como null - SIN AUTO ASIGNACIÓN)
            $ubicacionId = $datos['ubicacion_id'] ?? null;
            
            // Crear payload compatible con distintos esquemas
            $payload = [
                'producto_id' => $datos['producto_id'],
                'ubicacion_id' => $ubicacionId,
                'cantidad' => $datos['cantidad'],
                'fecha_ingreso' => now(),
                'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? null,
                'lote' => $datos['lote'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null
            ];

            // Campos adicionales si existen en la tabla
            if (Schema::hasColumn('producto_ubicaciones', 'cantidad_inicial')) {
                $payload['cantidad_inicial'] = $datos['cantidad'];
            }
            if (Schema::hasColumn('producto_ubicaciones', 'cantidad_vendida')) {
                $payload['cantidad_vendida'] = 0;
            }
            if (Schema::hasColumn('producto_ubicaciones', 'precio_compra_lote')) {
                $payload['precio_compra_lote'] = $datos['precio_compra'] ?? null;
            }
            if (Schema::hasColumn('producto_ubicaciones', 'precio_venta_lote')) {
                $payload['precio_venta_lote'] = $datos['precio_venta'] ?? null;
            }
            if (Schema::hasColumn('producto_ubicaciones', 'proveedor_id')) {
                $payload['proveedor_id'] = $datos['proveedor_id'] ?? null;
            }
            if (Schema::hasColumn('producto_ubicaciones', 'estado_lote')) {
                $payload['estado_lote'] = ProductoUbicacion::ESTADO_ACTIVO;
            }

            // Crear el lote
            $lote = ProductoUbicacion::create($payload);

            // Registrar movimiento
            $this->registrarMovimiento($lote, 'entrada', $datos['cantidad'], 0, $datos['cantidad'], 'Entrada de mercadería');

            // Actualizar stock total del producto
            $this->actualizarStockProducto($datos['producto_id']);

            // Crear notificación si el lote está próximo a vencer
            $this->verificarYCrearNotificacionVencimiento($lote);

            Log::info("Nuevo lote creado", [
                'lote_id' => $lote->id,
                'producto_id' => $datos['producto_id'],
                'cantidad' => $datos['cantidad'],
                'lote' => $datos['lote']
            ]);

            return $lote;
        });
    }

    /**
     * Incrementar stock de un lote existente
     */
    public function incrementLoteStock(int $loteId, int $cantidad, ?float $precioCompra = null, ?float $precioVenta = null): ProductoUbicacion
    {
        return DB::transaction(function () use ($loteId, $cantidad, $precioCompra, $precioVenta) {
            $lote = ProductoUbicacion::findOrFail($loteId);

            $cantidadAnterior = $lote->cantidad;
            $cantidadNueva = $cantidadAnterior + $cantidad;
            
            $updates = ['cantidad' => $cantidadNueva];
            
            // Actualizar precios si se proporcionan
            if ($precioCompra !== null) {
                if (Schema::hasColumn('producto_ubicaciones', 'precio_compra_lote')) {
                    $updates['precio_compra_lote'] = $precioCompra;
                }
            }
            if ($precioVenta !== null) {
                if (Schema::hasColumn('producto_ubicaciones', 'precio_venta_lote')) {
                    $updates['precio_venta_lote'] = $precioVenta;
                }
            }
            
            // Reactivar lote si estaba agotado
            if ($lote->estado_lote === ProductoUbicacion::ESTADO_AGOTADO && $cantidadNueva > 0) {
                $updates['estado_lote'] = ProductoUbicacion::ESTADO_ACTIVO;
            }

            $lote->update($updates);

            // Registrar movimiento
            $this->registrarMovimiento(
                $lote, 
                'entrada', 
                $cantidad, 
                $cantidadAnterior, 
                $cantidadNueva, 
                'Incremento de stock en lote existente'
            );

            // Actualizar stock total del producto
            $this->actualizarStockProducto($lote->producto_id);

            Log::info("Stock incrementado en lote existente", [
                'lote_id' => $lote->id,
                'cantidad_agregada' => $cantidad,
                'nuevo_total' => $cantidadNueva
            ]);

            return $lote;
        });
    }

    /**
     * Obtener lotes disponibles para venta (FIFO)
     */
    public function obtenerLotesParaVenta(int $productoId, int $cantidadRequerida): array
    {
        $lotes = ProductoUbicacion::where('producto_id', $productoId)
            ->where('estado_lote', ProductoUbicacion::ESTADO_ACTIVO)
            ->where('cantidad', '>', 0)
            ->where(function($query) {
                $query->whereNull('fecha_vencimiento')
                      ->orWhere('fecha_vencimiento', '>=', now()->toDateString());
            })
            ->orderBy('fecha_vencimiento', 'asc') // FIFO: primero los que vencen antes
            ->orderBy('fecha_ingreso', 'asc')    // En caso de misma fecha, primero los más antiguos
            ->get();

        $lotesParaVenta = [];
        $cantidadRestante = $cantidadRequerida;

        foreach ($lotes as $lote) {
            if ($cantidadRestante <= 0) break;

            $cantidadDelLote = min($cantidadRestante, $lote->cantidad);
            
            $lotesParaVenta[] = [
                'lote' => $lote,
                'cantidad_a_usar' => $cantidadDelLote,
                'precio_venta' => $lote->precio_venta_lote ?? $lote->producto->precio_venta
            ];

            $cantidadRestante -= $cantidadDelLote;
        }

        return [
            'lotes' => $lotesParaVenta,
            'cantidad_disponible' => $cantidadRequerida - $cantidadRestante,
            'cantidad_faltante' => $cantidadRestante
        ];
    }

    /**
     * Procesar venta usando lógica FIFO o lote específico
     */
    public function procesarVenta(int $productoId, int $cantidad, array $datosVenta = [], ?int $loteEspecificoId = null): array
    {
        return DB::transaction(function () use ($productoId, $cantidad, $datosVenta, $loteEspecificoId) {
            // Mantener consistencia del stock total:
            // - Los lotes (producto_ubicaciones) representan stock ubicado
            // - productos.stock_actual puede incluir stock sin ubicar
            // En ventas, debemos disminuir el stock total aunque el stock en lotes baje.
            $producto = Producto::where('id', $productoId)->lockForUpdate()->first();
            $stockTotalAntes = $producto ? (int) $producto->stock_actual : 0;

            $queryStockAntes = ProductoUbicacion::where('producto_id', $productoId);
            if (Schema::hasColumn('producto_ubicaciones', 'estado_lote')) {
                $queryStockAntes->where('estado_lote', 'activo');
            }
            $stockEnLotesAntes = (int) $queryStockAntes->sum('cantidad');
            $stockSinUbicar = max(0, $stockTotalAntes - $stockEnLotesAntes);

            $lotesADescontar = [];
            
            if ($loteEspecificoId) {
                // Procesar lote específico
                $lote = ProductoUbicacion::where('id', $loteEspecificoId)
                    ->where('producto_id', $productoId)
                    ->firstOrFail();
                
                // Validar que el lote esté activo para la venta
                if ($lote->estado_lote !== ProductoUbicacion::ESTADO_ACTIVO) {
                    throw new \Exception("El lote seleccionado no está activo para venta. Estado actual: {$lote->estado_lote}");
                }

                // BLOQUEO ESTRICTO: Validar fecha de vencimiento
                if ($lote->fecha_vencimiento && $lote->fecha_vencimiento->isPast()) {
                    throw new \Exception("¡BLOQUEO DE SEGURIDAD! El lote {$lote->lote} ya venció el {$lote->fecha_vencimiento->format('d/m/Y')}. No se puede vender por normativa sanitaria.");
                }
                
                if ($lote->cantidad < $cantidad) {
                    throw new \Exception("Stock insuficiente en el lote seleccionado {$lote->lote}. Disponible: {$lote->cantidad}, Requerido: {$cantidad}");
                }
                
                $lotesADescontar[] = [
                    'lote' => $lote,
                    'cantidad_a_usar' => $cantidad,
                    'precio_venta' => $lote->precio_venta_lote ?? $lote->producto->precio_venta
                ];
            } else {
                // Procesar FIFO (automático)
                $resultado = $this->obtenerLotesParaVenta($productoId, $cantidad);
                
                if ($resultado['cantidad_faltante'] > 0) {
                    throw new \Exception("Stock insuficiente. Disponible: {$resultado['cantidad_disponible']}, Requerido: {$cantidad}");
                }
                
                $lotesADescontar = $resultado['lotes'];
            }

            $lotesUsados = [];
            
            foreach ($lotesADescontar as $loteInfo) {
                $lote = $loteInfo['lote'];
                $cantidadUsada = $loteInfo['cantidad_a_usar'];
                
                // Actualizar cantidades del lote
                $cantidadAnterior = $lote->cantidad;
                $cantidadNueva = $cantidadAnterior - $cantidadUsada;
                $cantidadVendidaNueva = $lote->cantidad_vendida + $cantidadUsada;
                
                $lote->update([
                    'cantidad' => $cantidadNueva,
                    'cantidad_vendida' => $cantidadVendidaNueva,
                    'estado_lote' => $cantidadNueva <= 0 ? 'agotado' : 'activo'
                ]);

                // Registrar movimiento
                $this->registrarMovimiento(
                    $lote, 
                    'venta', 
                    $cantidadUsada, 
                    $cantidadAnterior, 
                    $cantidadNueva,
                    'Venta de producto',
                    $datosVenta
                );

                $lotesUsados[] = [
                    'lote_id' => $lote->id,
                    'lote_codigo' => $lote->lote,
                    'cantidad_usada' => $cantidadUsada,
                    'precio_venta' => $loteInfo['precio_venta'],
                    'fecha_vencimiento' => $lote->fecha_vencimiento
                ];
            }

            // Actualizar stock total del producto
            $this->actualizarStockProducto($productoId, $stockSinUbicar);

            return $lotesUsados;
        });
    }

    /**
     * Obtener información de lotes de un producto
     */
    public function obtenerInfoLotes(int $productoId): array
    {
        $lotes = ProductoUbicacion::where('producto_id', $productoId)
            ->where('cantidad', '>', 0)
            ->orderBy('fecha_vencimiento', 'asc')
            ->orderBy('fecha_ingreso', 'asc')
            ->get();

        $stockTotal = $lotes->sum('cantidad');
        $proximoVencer = $lotes->where('fecha_vencimiento', '<=', now()->addDays(30))->sum('cantidad');
        $vencidos = $lotes->where('fecha_vencimiento', '<', now())->sum('cantidad');

        return [
            'stock_total' => $stockTotal,
            'total_lotes' => $lotes->count(),
            'proximo_vencer' => $proximoVencer,
            'vencidos' => $vencidos,
            'lotes' => $lotes->map(function ($lote) {
                return [
                    'id' => $lote->id,
                    'lote' => $lote->lote,
                    'cantidad' => $lote->cantidad,
                    'cantidad_inicial' => $lote->cantidad_inicial,
                    'cantidad_vendida' => $lote->cantidad_vendida,
                    'fecha_ingreso' => $lote->fecha_ingreso,
                    'fecha_vencimiento' => $lote->fecha_vencimiento,
                    'dias_para_vencer' => $lote->fecha_vencimiento ? now()->diffInDays($lote->fecha_vencimiento, false) : null,
                    'estado' => $this->determinarEstadoLote($lote),
                    'precio_compra' => $lote->precio_compra_lote,
                    'precio_venta' => $lote->precio_venta_lote,
                    'ubicacion' => $lote->ubicacion ? $lote->ubicacion->codigo : null
                ];
            })
        ];
    }

    /**
     * Cambiar el estado de un lote (Ej. poner en cuarentena)
     */
    public function cambiarEstadoLote(int $loteId, string $nuevoEstado, string $motivo, ?int $userId = null): ProductoUbicacion
    {
        return DB::transaction(function () use ($loteId, $nuevoEstado, $motivo, $userId) {
            $lote = ProductoUbicacion::findOrFail($loteId);
            $estadoAnterior = $lote->estado_lote;

            // Validar estados permitidos
            $estadosValidos = [
                ProductoUbicacion::ESTADO_ACTIVO,
                ProductoUbicacion::ESTADO_AGOTADO,
                ProductoUbicacion::ESTADO_CUARENTENA,
                ProductoUbicacion::ESTADO_VENCIDO
            ];

            if (!in_array($nuevoEstado, $estadosValidos)) {
                throw new \InvalidArgumentException("Estado no válido: {$nuevoEstado}");
            }

            $lote->update(['estado_lote' => $nuevoEstado]);

            // Registrar movimiento de auditoría
            $this->registrarMovimiento(
                $lote,
                'ajuste', // Usamos 'ajuste' como tipo genérico para cambios de estado
                0, // No cambia cantidad
                $lote->cantidad,
                $lote->cantidad,
                "Cambio de estado: {$estadoAnterior} -> {$nuevoEstado}. Motivo: {$motivo}",
                ['usuario_id' => $userId ?? \Illuminate\Support\Facades\Auth::id()]
            );

            // Si se reactiva o desactiva, actualizar stock visible del producto
            $this->actualizarStockProducto($lote->producto_id);

            Log::info("Estado de lote actualizado", [
                'lote_id' => $lote->id,
                'estado_anterior' => $estadoAnterior,
                'nuevo_estado' => $nuevoEstado,
                'motivo' => $motivo
            ]);

            return $lote;
        });
    }

    /**
     * Determinar el estado de un lote
     */
    private function determinarEstadoLote(ProductoUbicacion $lote): string
    {
        if ($lote->cantidad <= 0) {
            return 'agotado';
        }

        if ($lote->fecha_vencimiento) {
            $diasParaVencer = now()->diffInDays($lote->fecha_vencimiento, false);
            
            if ($diasParaVencer < 0) {
                return 'vencido';
            } elseif ($diasParaVencer <= 30) {
                return 'por_vencer';
            }
        }

        return 'normal';
    }

    /**
     * Marcar lotes vencidos
     */
    public function marcarLotesVencidos(): int
    {
        $lotesVencidos = ProductoUbicacion::where('fecha_vencimiento', '<', now())
            ->where('estado_lote', '!=', 'vencido')
            ->get();

        $contador = 0;
        foreach ($lotesVencidos as $lote) {
            $lote->update(['estado_lote' => 'vencido']);
            
            $this->registrarMovimiento(
                $lote, 
                'vencimiento', 
                0, 
                0, 
                0,
                'Lote marcado como vencido automáticamente'
            );
            
            $contador++;
        }

        if ($contador > 0) {
            Log::info("Marcados {$contador} lotes como vencidos");
        }

        return $contador;
    }

    /**
     * Actualizar stock total del producto basado en sus lotes
     */
    private function actualizarStockProducto(int $productoId, ?int $stockSinUbicar = null): void
    {
        $query = ProductoUbicacion::where('producto_id', $productoId);
        if (Schema::hasColumn('producto_ubicaciones', 'estado_lote')) {
            $query->where('estado_lote', 'activo');
        }
        $stockEnLotes = $query->sum('cantidad');

        $producto = Producto::find($productoId);
        if ($producto) {
            // Si se provee $stockSinUbicar, significa que estamos sincronizando tras una VENTA.
            // En ese caso el stock total debe bajar/subir según la suma de lotes + stock sin ubicar.
            if ($stockSinUbicar !== null) {
                $nuevoStockTotal = max(0, (int) $stockEnLotes + (int) $stockSinUbicar);
                if ((int) $producto->stock_actual !== $nuevoStockTotal) {
                    $producto->update(['stock_actual' => $nuevoStockTotal]);
                }
            } else {
                // Para entradas/incrementos: mantener el comportamiento anterior (no bajar stock total)
                // para no “perder” stock sin ubicar si existe.
                if ($stockEnLotes > 0 && $stockEnLotes > (int) $producto->stock_actual) {
                    $producto->update(['stock_actual' => (int) $stockEnLotes]);
                }
            }

            // Recalcular el estado del producto después de actualizar el stock
            $producto->fresh()->recalcularEstado();
        }
    }

    /**
     * Registrar movimiento de lote
     */
    private function registrarMovimiento(
        ProductoUbicacion $lote, 
        string $tipo, 
        int $cantidad, 
        int $cantidadAnterior = 0, 
        int $cantidadNueva = 0, 
        string $motivo = null,
        array $datosAdicionales = []
    ): void {
        if (Schema::hasTable('lote_movimientos')) {
            // Preparar datos adicionales
            if ($lote->precio_venta_lote) {
                $datosAdicionales['precio_unitario'] = $lote->precio_venta_lote;
            }

            LoteMovimiento::create([
                'producto_ubicacion_id' => $lote->id,
                'tipo_movimiento' => $tipo,
                'cantidad' => $cantidad,
                'cantidad_anterior' => $cantidadAnterior,
                'cantidad_nueva' => $cantidadNueva,
                'motivo' => $motivo,
                'usuario_id' => \Illuminate\Support\Facades\Auth::id(),
                'datos_adicionales' => !empty($datosAdicionales) ? $datosAdicionales : null
            ]);
        } else {
            Log::warning('Tabla lote_movimientos no existe; se omite registro de movimiento');
        }
    }

    /**
     * Obtener próximos a vencer para alertas
     * Retorna una colección de modelos ProductoUbicacion
     */
    public function obtenerProximosAVencer(int $dias = 30)
    {
        return ProductoUbicacion::with(['producto', 'ubicacion'])
            ->where('fecha_vencimiento', '<=', now()->addDays($dias))
            ->where('fecha_vencimiento', '>', now())
            ->where('cantidad', '>', 0)
            ->where('estado_lote', ProductoUbicacion::ESTADO_ACTIVO)
            ->orderBy('fecha_vencimiento', 'asc')
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
    }

    /**
     * Obtener ubicación por defecto cuando no se especifica una
     */
    private function obtenerUbicacionPorDefecto(): int
    {
        // Logging para diagnóstico
        Log::info('Iniciando búsqueda de ubicación por defecto');

        if (!Schema::hasTable('ubicaciones')) {
            Log::warning('La tabla ubicaciones no existe; no se puede asignar ubicación por defecto');
            throw new \Exception('Sistema de ubicaciones no configurado');
        }

        // Buscar una ubicación activa disponible
        $ubicacion = Ubicacion::where('activo', 1)
            ->orderBy('id')
            ->first();

        if ($ubicacion) {
            Log::info('Ubicación por defecto seleccionada', ['ubicacion_id' => $ubicacion->id]);
            return (int) $ubicacion->id;
        }

        Log::warning('No hay ubicaciones activas; intentando crear una por defecto');

        // Asegurar existencia de estantes
        if (!Schema::hasTable('estantes')) {
            Log::warning('La tabla estantes no existe; no se puede crear ubicación por defecto');
            throw new \Exception('Sistema de estantes no configurado');
        }

        // Obtener o crear un estante por defecto
        $estante = Estante::where('activo', 1)->orderBy('id')->first();
        if (!$estante) {
            // Construir atributos dinámicamente según columnas disponibles
            $attrs = [
                'nombre' => 'General',
                'activo' => true,
            ];
            if (Schema::hasColumn('estantes', 'descripcion')) $attrs['descripcion'] = null;
            if (Schema::hasColumn('estantes', 'capacidad_total')) $attrs['capacidad_total'] = 1;
            if (Schema::hasColumn('estantes', 'numero_niveles')) $attrs['numero_niveles'] = 1;
            if (Schema::hasColumn('estantes', 'numero_posiciones')) $attrs['numero_posiciones'] = 1;
            if (Schema::hasColumn('estantes', 'ubicacion_fisica')) $attrs['ubicacion_fisica'] = 'Almacén';
            if (Schema::hasColumn('estantes', 'tipo')) $attrs['tipo'] = 'almacen';

            $estante = Estante::create($attrs);
            Log::info('Estante por defecto creado', ['estante_id' => $estante->id, 'attrs' => $attrs]);
        }

        // Ver si la creación automática del modelo generó ubicaciones
        $ubicacion = Ubicacion::where('estante_id', $estante->id)
            ->where('activo', 1)
            ->orderBy('id')
            ->first();

        // Si aún no existe, crear manualmente una ubicación mínima
        if (!$ubicacion) {
            $ubicacionAttrs = [
                'estante_id' => $estante->id,
                'nivel' => 1,
                'posicion' => 1,
                'codigo' => '1-1',
                'activo' => true
            ];
            if (Schema::hasColumn('ubicaciones', 'capacidad_maxima')) $ubicacionAttrs['capacidad_maxima'] = 1;

            $ubicacion = Ubicacion::create($ubicacionAttrs);
            Log::info('Ubicación por defecto creada', ['ubicacion_id' => $ubicacion->id, 'attrs' => $ubicacionAttrs]);
        }

        return (int) $ubicacion->id;
    }

    /**
     * Simular una venta para mostrar qué lotes se utilizarían
     */
    public function simularVenta(int $productoId, int $cantidad): array
    {
        $resultado = $this->obtenerLotesParaVenta($productoId, $cantidad);
        
        if (empty($resultado)) {
            return [
                'success' => false,
                'message' => 'No hay lotes disponibles para este producto',
                'lotes_utilizados' => [],
                'cantidad_total' => 0,
                'precio_total' => 0
            ];
        }
        
        $lotesUtilizados = [];
        $cantidadRestante = $cantidad;
        $precioTotal = 0;
        
        foreach ($resultado as $lote) {
            if ($cantidadRestante <= 0) break;
            
            $cantidadDelLote = min($cantidadRestante, $lote['cantidad']);
            $precioLote = $cantidadDelLote * $lote['precio_venta_lote'];
            
            $lotesUtilizados[] = [
                'lote_id' => $lote['id'],
                'lote_codigo' => $lote['lote'],
                'cantidad_usada' => $cantidadDelLote,
                'precio_unitario' => $lote['precio_venta_lote'],
                'precio_total_lote' => $precioLote,
                'fecha_vencimiento' => $lote['fecha_vencimiento'],
                'fecha_ingreso' => $lote['fecha_ingreso']
            ];
            
            $precioTotal += $precioLote;
            $cantidadRestante -= $cantidadDelLote;
        }
        
        return [
            'success' => true,
            'lotes_utilizados' => $lotesUtilizados,
            'cantidad_total' => $cantidad - $cantidadRestante,
            'precio_total' => $precioTotal,
            'precio_promedio' => $cantidad > 0 ? $precioTotal / $cantidad : 0,
            'cantidad_faltante' => $cantidadRestante
        ];
    }

    /**
     * Verificar y crear notificación si el lote está próximo a vencer
     */
    private function verificarYCrearNotificacionVencimiento(ProductoUbicacion $lote): void
    {
        if (!$lote->fecha_vencimiento) {
            return;
        }

        $fechaVencimiento = Carbon::parse($lote->fecha_vencimiento);
        $diasRestantes = now()->diffInDays($fechaVencimiento, false);

        // Crear notificación si está próximo a vencer (0-90 días)
        if ($diasRestantes >= 0 && $diasRestantes <= 90) {
            $producto = $lote->producto;
            if (!$producto) {
                return;
            }

            // Verificar si ya existe una notificación reciente para este producto
            $existeNotificacion = \App\Models\Notification::where('type', \App\Models\Notification::TYPE_PRODUCTO_VENCIMIENTO)
                ->where('data->producto_id', $producto->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (!$existeNotificacion) {
                \App\Models\Notification::createProximoVencer(
                    \Illuminate\Support\Facades\Auth::id() ?? 1,
                    $producto,
                    $diasRestantes
                );

                Log::info("Notificación de vencimiento creada", [
                    'producto_id' => $producto->id,
                    'lote_id' => $lote->id,
                    'dias_restantes' => $diasRestantes
                ]);
            }
        }
        // Crear notificación si ya está vencido
        elseif ($diasRestantes < 0) {
            $producto = $lote->producto;
            if (!$producto) {
                return;
            }

            $existeNotificacion = \App\Models\Notification::where('type', \App\Models\Notification::TYPE_PRODUCTO_VENCIDO)
                ->where('data->producto_id', $producto->id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if (!$existeNotificacion) {
                \App\Models\Notification::createProductoVencido(
                    \Illuminate\Support\Facades\Auth::id() ?? 1,
                    $producto
                );

                Log::info("Notificación de producto vencido creada", [
                    'producto_id' => $producto->id,
                    'lote_id' => $lote->id
                ]);
            }
        }
    }
}