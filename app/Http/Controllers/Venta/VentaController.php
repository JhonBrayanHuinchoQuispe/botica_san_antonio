<?php
namespace App\Http\Controllers\Venta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PuntoVenta\Venta;
use App\Models\PuntoVenta\VentaDetalle;
use App\Models\PuntoVenta\VentaDevolucion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VentaController extends Controller
{
    public function nueva()
    {
        return view('ventas.nueva');
    }

    public function historial(Request $request)
    {
        // Estadísticas
        $estadisticas = $this->obtenerEstadisticas();
        
        // Query base - cargar devoluciones para el historial
        $query = Venta::with(['usuario', 'cliente', 'detalles.producto', 'devoluciones'])
            ->orderBy('fecha_venta', 'desc');
        
        // Filtros
        if ($request->filled('metodo_pago')) {
            $query->where('metodo_pago', $request->metodo_pago);
        }
        
        if ($request->filled('tipo_comprobante')) {
            $query->where('tipo_comprobante', $request->tipo_comprobante);
        }
        
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_venta', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_venta', '<=', $request->fecha_hasta);
        }
        
        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_venta', 'like', "%{$search}%")
                  ->orWhere('cliente_razon_social', 'like', "%{$search}%")
                  ->orWhere('cliente_numero_documento', 'like', "%{$search}%")
                  ->orWhereHas('cliente', function($cq) use ($search) {
                      $cq->where('nombres', 'like', "%{$search}%")
                         ->orWhere('apellido_paterno', 'like', "%{$search}%")
                         ->orWhere('apellido_materno', 'like', "%{$search}%")
                         ->orWhere('dni', 'like', "%{$search}%");
                  })
                  ->orWhereHas('detalles.producto', function($productQuery) use ($search) {
                      $productQuery->where('nombre', 'like', "%{$search}%");
                  });
            });
        }
        
        // Paginación
        $ventas = $query->paginate(10);
        
        // Obtener usuarios para filtro
        $usuarios = User::orderBy('name')->get();
        
        return view('ventas.historial', compact('ventas', 'estadisticas', 'usuarios'));
    }

    public function devoluciones(Request $request)
    {
        // Estadísticas de devoluciones
        $estadisticas = $this->obtenerEstadisticasDevoluciones();
        
        // Si hay búsqueda de venta
        $venta = null;
        if ($request->filled('numero_venta')) {
            // Primero verificar si la venta existe
            $ventaExiste = Venta::where('numero_venta', $request->numero_venta)->first();
            
            \Log::info('Búsqueda de venta para devolución:', [
                'numero_venta' => $request->numero_venta,
                'venta_existe' => $ventaExiste ? 'SÍ' : 'NO',
                'estado_actual' => $ventaExiste ? $ventaExiste->estado : 'N/A'
            ]);
            
            if ($ventaExiste) {
                // Buscar ventas que pueden tener devoluciones (todas excepto canceladas)
                $venta = Venta::with(['detalles.producto', 'usuario', 'devoluciones'])
                    ->where('numero_venta', $request->numero_venta)
                    ->whereNotIn('estado', ['cancelada'])
                    ->first();
                    
                \Log::info('Resultado de búsqueda:', [
                    'venta_encontrada_para_devolucion' => $venta ? 'SÍ' : 'NO',
                    'estado_venta' => $venta ? $venta->estado : 'NO ENCONTRADA',
                    'numero_detalles' => $venta ? $venta->detalles->count() : 0,
                    'numero_devoluciones' => $venta ? $venta->devoluciones->count() : 0
                ]);
            }
        }
        
        return view('ventas.devoluciones', compact('estadisticas', 'venta'));
    }

    public function procesarDevolucion(Request $request)
    {
        // Validación mejorada con mensajes personalizados
        try {
            $request->validate([
                'venta_id' => 'required|exists:ventas,id',
                'productos' => 'required|array|min:1',
                'productos.*.detalle_id' => 'required|exists:venta_detalles,id',
                'productos.*.cantidad_devolver' => 'required|integer|min:1',
                'productos.*.motivo' => 'required|string|in:defectuoso,vencido,equivocacion,cliente_insatisfecho,cambio_opinion,otro',
                'productos.*.observaciones' => 'nullable|string|max:500',
            ], [
                'venta_id.required' => 'El ID de venta es requerido',
                'venta_id.exists' => 'La venta especificada no existe',
                'productos.required' => 'Debe seleccionar al menos un producto para devolver',
                'productos.array' => 'Los productos deben ser un array válido',
                'productos.min' => 'Debe seleccionar al menos un producto para devolver',
                'productos.*.detalle_id.required' => 'El ID del detalle del producto es requerido',
                'productos.*.detalle_id.exists' => 'El detalle del producto no existe',
                'productos.*.cantidad_devolver.required' => 'La cantidad a devolver es requerida',
                'productos.*.cantidad_devolver.integer' => 'La cantidad debe ser un número entero',
                'productos.*.cantidad_devolver.min' => 'La cantidad a devolver debe ser mayor a 0',
                'productos.*.motivo.required' => 'El motivo de devolución es requerido',
                'productos.*.motivo.in' => 'El motivo seleccionado no es válido',
                'productos.*.observaciones.max' => 'Las observaciones no pueden exceder 500 caracteres',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación en devolución:', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
                'usuario_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error de validación. Revise los datos enviados.',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $venta = Venta::with(['detalles.producto', 'devoluciones'])->findOrFail($request->venta_id);
            
            \Log::info('Procesando devolución:', [
                'venta_id' => $venta->id,
                'numero_venta' => $venta->numero_venta,
                'estado_actual' => $venta->estado,
                'fecha_venta' => $venta->fecha_venta,
                'total_productos_request' => count($request->productos)
            ]);
            
            // Verificar que la venta puede tener devoluciones
            if (in_array($venta->estado, ['cancelada'])) {
                \Log::warning('Intento de devolución en venta cancelada:', [
                    'venta_id' => $venta->id,
                    'numero_venta' => $venta->numero_venta,
                    'estado' => $venta->estado
                ]);
                throw new \Exception("No se pueden procesar devoluciones de ventas canceladas. Estado actual: {$venta->estado}");
            }
            
            $devolucionesCreadas = [];
            $totalDevolucion = 0;
            $productosDevueltos = 0;

            foreach ($request->productos as $productoData) {
                $detalle = VentaDetalle::with('producto')->findOrFail($productoData['detalle_id']);
                
                // Validar que pertenece a la venta
                if ($detalle->venta_id != $venta->id) {
                    throw new \Exception("El producto no pertenece a esta venta");
                }
                
                // Calcular cuánto se ha devuelto previamente de este detalle
                $cantidadPreviamenteDevuelta = $venta->devoluciones()
                    ->where('venta_detalle_id', $detalle->id)
                    ->sum('cantidad_devuelta');
                
                $cantidadRestante = $detalle->cantidad - $cantidadPreviamenteDevuelta;
                
                \Log::info("Validación de cantidades:", [
                    'producto' => $detalle->producto->nombre,
                    'detalle_id' => $detalle->id,
                    'cantidad_original' => $detalle->cantidad,
                    'cantidad_previamente_devuelta' => $cantidadPreviamenteDevuelta,
                    'cantidad_restante' => $cantidadRestante,
                    'cantidad_a_devolver' => $productoData['cantidad_devolver']
                ]);
                
                // Validar que no devuelva más de lo que queda disponible
                if ($productoData['cantidad_devolver'] > $cantidadRestante) {
                    throw new \Exception("No puedes devolver {$productoData['cantidad_devolver']} unidades de {$detalle->producto->nombre}. Solo quedan {$cantidadRestante} unidades disponibles para devolución.");
                }

                // Calcular monto de devolución
                $montoDevolucion = $productoData['cantidad_devolver'] * $detalle->precio_unitario;
                $totalDevolucion += $montoDevolucion;
                $productosDevueltos++;

                // Crear registro de devolución
                $devolucion = \App\Models\PuntoVenta\VentaDevolucion::create([
                    'venta_id' => $venta->id,
                    'venta_detalle_id' => $detalle->id,
                    'producto_id' => $detalle->producto_id,
                    'usuario_id' => auth()->id(),
                    'cantidad_original' => $detalle->cantidad,
                    'cantidad_devuelta' => $productoData['cantidad_devolver'],
                    'precio_unitario' => $detalle->precio_unitario,
                    'monto_devolucion' => $montoDevolucion,
                    'motivo' => $productoData['motivo'],
                    'observaciones' => $productoData['observaciones'] ?? null,
                    'fecha_devolucion' => now()
                ]);

                $devolucionesCreadas[] = $devolucion;

                // Actualizar stock del producto (devolver al inventario)
                $detalle->producto->increment('stock_actual', $productoData['cantidad_devolver']);
                
                // Recalcular el estado del producto después de actualizar el stock
                $detalle->producto->fresh()->recalcularEstado();

                // Crear registro de movimiento de stock
                \App\Models\MovimientoStock::registrarDevolucion(
                    $detalle->producto_id,
                    null, // ubicación_id - null porque es devolución general al inventario
                    $productoData['cantidad_devolver'],
                    "Devolución: " . ($productoData['observaciones'] ?? $productoData['motivo']),
                    [
                        'venta_id' => $venta->id,
                        'venta_detalle_id' => $detalle->id,
                        'stock_anterior' => $detalle->producto->stock_actual - $productoData['cantidad_devolver'],
                        'stock_nuevo' => $detalle->producto->stock_actual
                    ]
                );

                \Log::info("Devolución creada: {$productoData['cantidad_devolver']} unidades de {$detalle->producto->nombre}");
            }

            // Actualizar el estado de la venta basado en las devoluciones
            \Log::info("Verificando estado de devolución para venta {$venta->numero_venta}");
            $esDevueltaCompleta = $venta->verificarSiDevueltaCompleta();
            
            // Actualizar totales después de las devoluciones
            \Log::info("Actualizando totales para venta {$venta->numero_venta}");
            $totalesActualizados = $venta->actualizarTotales();
            
            \Log::info("Estado final de venta después de devolución:", [
                'numero_venta' => $venta->numero_venta,
                'estado_final' => $venta->estado,
                'es_devuelta_completa' => $esDevueltaCompleta,
                'total_original' => $venta->total,
                'total_actual' => $venta->total_actual,
                'monto_devuelto' => $venta->monto_total_devuelto
            ]);

            DB::commit();

            // Obtener información actualizada para la respuesta
            $venta->refresh();
            
            // Obtener resumen de devolución con manejo de errores
            try {
                $resumenDevolucion = $venta->resumen_devolucion;
                \Log::info("Resumen de devolución obtenido exitosamente", ['resumen' => $resumenDevolucion]);
            } catch (\Exception $resumenError) {
                \Log::error("Error al obtener resumen de devolución: " . $resumenError->getMessage());
                $resumenDevolucion = [
                    'tiene_devoluciones' => true,
                    'monto_devuelto' => $totalDevolucion,
                    'productos_con_devolucion' => $productosDevueltos,
                    'total_productos_originales' => $venta->detalles->count(),
                    'detalles_afectados' => []
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Devolución procesada exitosamente',
                'data' => [
                    'venta_id' => $venta->id,
                    'nuevo_estado' => $venta->estado,
                    'nuevo_estado_formateado' => $venta->estado_formateado,
                    'total_devolucion_actual' => (float) $totalDevolucion,
                    'productos_devueltos_ahora' => (int) $productosDevueltos,
                    'devoluciones_creadas' => count($devolucionesCreadas),
                    'resumen_general' => $resumenDevolucion,
                    'mensaje_estado' => $this->generarMensajeEstadoSeguro($venta),
                    'totales_actualizados' => $totalesActualizados,
                    'total_original' => (float) $venta->total,
                    'total_actual' => (float) $venta->total_actual,
                    'monto_total_devuelto' => (float) $venta->monto_total_devuelto
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error en devolución: ' . $e->getMessage(), [
                'venta_id' => $request->venta_id,
                'productos' => $request->productos,
                'usuario_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Determinar el código de error apropiado
            $statusCode = 422;
            $errorType = 'business_logic';
            
            if (strpos($e->getMessage(), 'No se pueden procesar devoluciones') !== false) {
                $errorType = 'invalid_state';
            } elseif (strpos($e->getMessage(), 'No puedes devolver') !== false) {
                $errorType = 'quantity_exceeded';
            } elseif (strpos($e->getMessage(), 'no pertenece a esta venta') !== false) {
                $errorType = 'invalid_product';
            }
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => $errorType,
                'errors' => [
                    'general' => [$e->getMessage()]
                ]
            ], $statusCode);
        }
    }

    private function generarMensajeEstadoSeguro($venta)
    {
        try {
            return $this->generarMensajeEstado($venta);
        } catch (\Exception $e) {
            \Log::error("Error al generar mensaje de estado: " . $e->getMessage());
            return "Devolución procesada exitosamente. Estado: {$venta->estado}";
        }
    }

    private function generarMensajeEstado($venta)
    {
        if ($venta->estado === 'devuelta') {
            return "La venta ha sido devuelta completamente. Todos los productos han sido devueltos al inventario.";
        } elseif ($venta->estado === 'parcialmente_devuelta') {
            $resumen = $venta->resumen_devolucion;
            $productosConDevolucion = $resumen['productos_con_devolucion'];
            $totalProductos = $resumen['total_productos_originales'];
            $montoDevuelto = number_format($resumen['monto_devuelto'], 2);
            
            return "La venta tiene devolución parcial: {$productosConDevolucion} de {$totalProductos} productos han sido devueltos. Monto devuelto: S/ {$montoDevuelto}.";
        } else {
            return "La venta se mantiene completada.";
        }
    }

    public function reportes(Request $request)
    {
        // Adaptar a nuevas claves: hoy, ultimos7, mes, anual
        $periodo = $request->get('periodo', 'mes');
        $mapaViejo = [ 'dia' => 'hoy', 'semana' => 'ultimos7', 'año' => 'anual' ];
        if (isset($mapaViejo[$periodo])) { $periodo = $mapaViejo[$periodo]; }
        if (!in_array($periodo, ['hoy', 'ultimos7', 'mes', 'anual'])) { $periodo = 'mes'; }

        // Obtener datos según el período
        $datos = $this->obtenerDatosReporte($periodo);

        // Si es AJAX, devolver JSON directamente para actualización del gráfico
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($datos);
        }

        // Obtener usuarios para filtro
        $usuarios = User::orderBy('name')->get();

        return view('ventas.reportes', compact('datos', 'periodo', 'usuarios'));
    }
    
    /**
     * API: Obtener datos del reporte en formato JSON
     */
    public function obtenerDatosReporteAPI(Request $request)
    {
        try {
            $periodo = $request->get('periodo', 'mes');
            $fechaInicio = $request->get('fecha_inicio');
            $fechaFin = $request->get('fecha_fin');
            $usuarioId = $request->get('usuario_id');
            
            // Obtener datos según el período y filtros
            $datos = $this->obtenerDatosReporte($periodo, $fechaInicio, $fechaFin, $usuarioId);
            
            return response()->json($datos);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo datos del reporte: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Error al obtener datos del reporte',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Series comparativas para el gráfico principal
     * Devuelve etiquetas y dos series: período actual vs período anterior
     */
    public function obtenerComparativoSeriesAPI(Request $request)
    {
        try {
            $periodo = $request->get('periodo', 'mes');
            $contra = $request->get('contra', 'mes_anterior');
            $agrupacion = $request->get('agrupacion', 'auto');

            $labels = [];
            $serieActual = [];
            $serieAnterior = [];
            $titulo = '';

            switch ($periodo) {
                case 'hoy': {
                    // Horas del día actual vs ayer
                    for ($h = 0; $h <= 23; $h++) {
                        $labels[] = sprintf('%02d:00', $h);
                        $inicioHoraAct = Carbon::today()->hour($h)->minute(0)->second(0);
                        $finHoraAct = Carbon::today()->hour($h)->minute(59)->second(59);
                        $inicioHoraPrev = Carbon::yesterday()->hour($h)->minute(0)->second(0);
                        $finHoraPrev = Carbon::yesterday()->hour($h)->minute(59)->second(59);
                        $serieActual[] = (float) Venta::activas()->whereBetween('fecha_venta', [$inicioHoraAct, $finHoraAct])->sum('total');
                        $serieAnterior[] = (float) Venta::activas()->whereBetween('fecha_venta', [$inicioHoraPrev, $finHoraPrev])->sum('total');
                    }
                    $titulo = 'Hoy vs Ayer';
                    break;
                }
                case 'ultimos7': {
                    // Últimos 7 días vs semana anterior
                    $inicioAct = Carbon::now()->subDays(6)->startOfDay();
                    $finAct = Carbon::now()->endOfDay();
                    $inicioPrev = Carbon::now()->subDays(13)->startOfDay();
                    $finPrev = Carbon::now()->subDays(7)->endOfDay();
                    $diaActual = $inicioAct->copy();
                    $diaAnterior = $inicioPrev->copy();
                    for ($i = 0; $i < 7; $i++) {
                        $labels[] = $diaActual->format('d/m');
                        $serieActual[] = (float) Venta::activas()->whereDate('fecha_venta', $diaActual)->sum('total');
                        $serieAnterior[] = (float) Venta::activas()->whereDate('fecha_venta', $diaAnterior)->sum('total');
                        $diaActual->addDay();
                        $diaAnterior->addDay();
                    }
                    $titulo = 'Últimos 7 días (' . $inicioAct->format('d/m') . ' – ' . $finAct->format('d/m') . ') vs Semana anterior (' . $inicioPrev->format('d/m') . ' – ' . $finPrev->format('d/m') . ')';
                    break;
                }
                case 'anual': {
                    // Meses del año actual vs anterior
                    $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
                    $anioAct = Carbon::now()->year;
                    $anioPrev = Carbon::now()->subYear()->year;
                    for ($m = 1; $m <= 12; $m++) {
                        $labels[] = $meses[$m-1];
                        $serieActual[] = (float) Venta::activas()->whereMonth('fecha_venta', $m)->whereYear('fecha_venta', $anioAct)->sum('total');
                        $serieAnterior[] = (float) Venta::activas()->whereMonth('fecha_venta', $m)->whereYear('fecha_venta', $anioPrev)->sum('total');
                    }
                    $titulo = Carbon::now()->year . ' vs ' . Carbon::now()->subYear()->year;
                    break;
                }
                case 'mes':
                default: {
                    // Mes actual vs mes anterior: agrupación por semanas (4 buckets) si auto/semanal
                    $inicioMesAct = Carbon::now()->startOfMonth();
                    $finMesAct = Carbon::now()->endOfMonth();
                    $inicioMesPrev = Carbon::now()->subMonth()->startOfMonth();
                    $finMesPrev = Carbon::now()->subMonth()->endOfMonth();

                    $mesesEsp = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
                    $titulo = ucfirst($mesesEsp[$inicioMesAct->month - 1]) . ' del ' . $inicioMesAct->year . ' - ' . ucfirst($mesesEsp[$inicioMesPrev->month - 1]) . ' del ' . $inicioMesPrev->year;

                    if ($agrupacion === 'diario') {
                        $diaAct = $inicioMesAct->copy();
                        $diaPrev = $inicioMesPrev->copy();
                        while ($diaAct->lte($finMesAct)) {
                            $labels[] = $diaAct->format('d/m');
                            $serieActual[] = (float) Venta::activas()->whereDate('fecha_venta', $diaAct)->sum('total');
                            $valorPrev = 0.0;
                            if ($diaPrev->lte($finMesPrev)) {
                                $valorPrev = (float) Venta::activas()->whereDate('fecha_venta', $diaPrev)->sum('total');
                                $diaPrev->addDay();
                            }
                            $serieAnterior[] = $valorPrev;
                            $diaAct->addDay();
                        }
                    } else { // semanal o auto
                        $sumAct = [0,0,0,0];
                        $sumPrev = [0,0,0,0];
                        $d = $inicioMesAct->copy();
                        while ($d->lte($finMesAct)) {
                            $idx = intdiv($d->day - 1, 7);
                            if ($idx > 3) $idx = 3;
                            $sumAct[$idx] += (float) Venta::activas()->whereDate('fecha_venta', $d)->sum('total');
                            $d->addDay();
                        }
                        $d2 = $inicioMesPrev->copy();
                        while ($d2->lte($finMesPrev)) {
                            $idx2 = intdiv($d2->day - 1, 7);
                            if ($idx2 > 3) $idx2 = 3;
                            $sumPrev[$idx2] += (float) Venta::activas()->whereDate('fecha_venta', $d2)->sum('total');
                            $d2->addDay();
                        }
                        $labels = ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4'];
                        $serieActual = $sumAct;
                        $serieAnterior = $sumPrev;
                    }
                    break;
                }
            }

            return response()->json([
                'labels' => $labels,
                'actual' => $serieActual,
                'prev' => $serieAnterior,
                'titulo' => $titulo
            ]);

        } catch (\Exception $e) {
            Log::error('Error en comparativo de reportes: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => 'No fue posible generar la comparación'
            ], 500);
        }
    }
    
    private function obtenerEstadisticas()
    {
        $hoy = Carbon::today();
        $ayer = Carbon::yesterday();
        $inicioMes = Carbon::now()->startOfMonth();
        $mesAnterior = Carbon::now()->subMonth()->startOfMonth();
        $finMesAnterior = Carbon::now()->subMonth()->endOfMonth();
        
        // Ventas de hoy (solo completadas, no devueltas)
        $ventasHoy = Venta::activas()
            ->whereDate('fecha_venta', $hoy)
            ->count();
            
        $ventasAyer = Venta::activas()
            ->whereDate('fecha_venta', $ayer)
            ->count();
        
        // Ventas del mes (solo completadas, no devueltas)
        $ventasMes = Venta::activas()
            ->whereBetween('fecha_venta', [$inicioMes, now()])
            ->count();
            
        $ventasMesAnterior = Venta::activas()
            ->whereBetween('fecha_venta', [$mesAnterior, $finMesAnterior])
            ->count();
        
        // Total de productos vendidos hoy (solo ventas activas)
        $productosVendidosHoy = VentaDetalle::whereHas('venta', function($q) use ($hoy) {
            $q->activas()
              ->whereDate('fecha_venta', $hoy);
        })->sum('cantidad');
        
        // Ingresos de hoy (solo ventas activas)
        $ingresosHoy = Venta::activas()
            ->whereDate('fecha_venta', $hoy)
            ->sum('total');
            
        // Ingresos del mes (solo ventas activas)
        $ingresosMes = Venta::activas()
            ->whereBetween('fecha_venta', [$inicioMes, now()])
            ->sum('total');
        
        return [
            'ventas_hoy' => $ventasHoy,
            'cambio_respecto_ayer' => $ventasHoy - $ventasAyer,
            'ventas_mes' => $ventasMes,
            'cambio_mes' => $ventasMes - $ventasMesAnterior,
            'productos_vendidos' => $productosVendidosHoy,
            'ingresos_hoy' => $ingresosHoy,
            'ingresos_mes' => $ingresosMes
        ];
    }

    private function obtenerEstadisticasDevoluciones()
    {
        $hoy = Carbon::today();
        $inicioMes = Carbon::now()->startOfMonth();
        
        // Obtener estadísticas reales de devoluciones
        $devolucionesHoy = VentaDevolucion::whereDate('fecha_devolucion', $hoy)->count();
        $devolucionesMes = VentaDevolucion::whereBetween('fecha_devolucion', [$inicioMes, now()])->count();
        $montoDevueltoHoy = VentaDevolucion::whereDate('fecha_devolucion', $hoy)->sum('monto_devolucion');
        $montoDevueltoMes = VentaDevolucion::whereBetween('fecha_devolucion', [$inicioMes, now()])->sum('monto_devolucion');
        
        return [
            'devoluciones_hoy' => $devolucionesHoy,
            'devoluciones_mes' => $devolucionesMes,
            'monto_devuelto_hoy' => $montoDevueltoHoy,
            'monto_devuelto_mes' => $montoDevueltoMes
        ];
    }

    private function obtenerDatosReporte($periodo, $fechaInicioCustom = null, $fechaFinCustom = null, $usuarioId = null)
    {
        // Rango de fechas según nuevo período
        if ($periodo === 'personalizado' && $fechaInicioCustom && $fechaFinCustom) {
            $fechaInicio = Carbon::parse($fechaInicioCustom)->startOfDay();
            $fechaFin = Carbon::parse($fechaFinCustom)->endOfDay();
        } else {
            switch ($periodo) {
                case 'hoy':
                    $fechaInicio = Carbon::today();
                    $fechaFin = Carbon::today()->endOfDay();
                    break;
                case 'ultimos7':
                    $fechaInicio = Carbon::now()->subDays(6)->startOfDay();
                    $fechaFin = Carbon::now()->endOfDay();
                    break;
                case 'anual':
                    $fechaInicio = Carbon::now()->startOfYear();
                    $fechaFin = Carbon::now()->endOfYear();
                    break;
                case 'mes':
                default:
                    $fechaInicio = Carbon::now()->startOfMonth();
                    $fechaFin = Carbon::now()->endOfMonth();
                    $periodo = 'mes';
                    break;
            }
        }

        // Query base para ventas (solo activas, no devueltas)
        $queryVentas = Venta::activas()
            ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin]);

        // Filtro por usuario/vendedor si se especifica
        if ($usuarioId) {
            $queryVentas->where('usuario_id', $usuarioId);
        }

        $ventas = $queryVentas->get();

        // Productos más vendidos (con filtro de usuario)
        $queryProductos = VentaDetalle::select('producto_id', DB::raw('SUM(cantidad) as total_vendido'))
            ->whereHas('venta', function($q) use ($fechaInicio, $fechaFin, $usuarioId) {
                $q->activas()->whereBetween('fecha_venta', [$fechaInicio, $fechaFin]);
                if ($usuarioId) {
                    $q->where('usuario_id', $usuarioId);
                }
            })
            ->with('producto')
            ->groupBy('producto_id')
            ->orderByDesc('total_vendido')
            ->limit(10);
            
        $productosMasVendidos = $queryProductos->get();

        // Marcas más compradas (con filtro de usuario)
        $queryMarcas = DB::table('venta_detalles')
            ->join('productos', 'venta_detalles.producto_id', '=', 'productos.id')
            ->join('ventas', 'venta_detalles.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
            ->whereBetween('ventas.fecha_venta', [$fechaInicio, $fechaFin]);
            
        if ($usuarioId) {
            $queryMarcas->where('ventas.usuario_id', $usuarioId);
        }

        $marcasMasCompradas = $queryMarcas->select(DB::raw("COALESCE(productos.marca, 'Sin marca') as marca"), DB::raw('SUM(venta_detalles.cantidad) as unidades'))
            ->groupBy('marca')
            ->orderByDesc('unidades')
            ->limit(10)
            ->get();

        // Ventas por método de pago (con filtro de usuario)
        $queryMetodos = Venta::select('metodo_pago', DB::raw('COUNT(*) as total'))
            ->activas()
            ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin]);
            
        if ($usuarioId) {
            $queryMetodos->where('usuario_id', $usuarioId);
        }
        
        $ventasPorMetodo = $queryMetodos->groupBy('metodo_pago')->get();

        // Serie para el gráfico según período
        $ingresosSerie = [];
        
        // Función helper para obtener ingresos en rango (aplicando filtros)
        $getIngresos = function($inicio, $fin) use ($usuarioId) {
            $q = Venta::activas()->whereBetween('fecha_venta', [$inicio, $fin]);
            if ($usuarioId) $q->where('usuario_id', $usuarioId);
            return $q->sum('total');
        };

        if ($periodo === 'hoy') {
            for ($h = 0; $h <= 23; $h++) {
                $inicioHora = Carbon::today()->hour($h)->minute(0)->second(0);
                $finHora = Carbon::today()->hour($h)->minute(59)->second(59);
                $ingreso = $getIngresos($inicioHora, $finHora);
                $ingresosSerie[] = [ 'fecha' => sprintf('%02d:00', $h), 'ingresos' => (float)$ingreso ];
            }
        } elseif ($periodo === 'ultimos7') {
            $dia = Carbon::now()->subDays(6)->startOfDay();
            for ($i = 0; $i < 7; $i++) {
                $finDia = $dia->copy()->endOfDay();
                $ingreso = $getIngresos($dia, $finDia);
                $ingresosSerie[] = [ 'fecha' => $dia->format('d/m'), 'ingresos' => (float)$ingreso ];
                $dia->addDay();
            }
        } elseif ($periodo === 'anual') {
            $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
            $añoActual = Carbon::now()->year;
            for ($m = 1; $m <= 12; $m++) {
                $inicioMes = Carbon::create($añoActual, $m, 1)->startOfDay();
                $finMes = Carbon::create($añoActual, $m, 1)->endOfMonth();
                $ingreso = $getIngresos($inicioMes, $finMes);
                $ingresosSerie[] = [ 'fecha' => $meses[$m-1], 'ingresos' => (float)$ingreso ];
            }
        } else { // 'mes' o 'personalizado' -> mostrar diario en el rango
            $dia = $fechaInicio->copy();
            // Limitar a 31 días para no saturar el gráfico si el rango es muy grande
            $diasDiferencia = $fechaInicio->diffInDays($fechaFin);
            
            if ($diasDiferencia <= 60) {
                while ($dia->lte($fechaFin)) {
                    $finDia = $dia->copy()->endOfDay();
                    $ingreso = $getIngresos($dia, $finDia);
                    $ingresosSerie[] = [ 'fecha' => $dia->format('d/m'), 'ingresos' => (float)$ingreso ];
                    $dia->addDay();
                }
            } else {
                // Agrupación mensual si el rango es muy grande
                $mesIter = $fechaInicio->copy()->startOfMonth();
                while ($mesIter->lte($fechaFin)) {
                    $finMes = $mesIter->copy()->endOfMonth();
                    if ($finMes->gt($fechaFin)) $finMes = $fechaFin->copy();
                    $ingreso = $getIngresos($mesIter, $finMes);
                    $ingresosSerie[] = [ 'fecha' => $mesIter->format('M Y'), 'ingresos' => (float)$ingreso ];
                    $mesIter->addMonth();
                }
            }
        }

        $totalIngresos = $ventas->sum('total');
        $promedioSerie = count($ingresosSerie) > 0 ? collect($ingresosSerie)->avg('ingresos') : 0;

        // Título descriptivo del período
        if ($periodo === 'personalizado') {
            $tituloPeriodo = $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y');
        } else {
            $tituloPeriodo = match($periodo) {
                'hoy' => 'Hoy',
                'ultimos7' => 'Últimos 7 días',
                'anual' => 'Este año',
                default => 'Este mes'
            };
        }

        // Comparativo (solo si no es personalizado complejo, simplificamos para no complicar lógica)
        $comparativo = ($periodo !== 'personalizado') ? $this->obtenerComparativoPeriodo($periodo) : null;

        return [
            'total_ventas' => $ventas->count(),
            'total_ingresos' => $totalIngresos,
            'ticket_promedio' => $ventas->count() > 0 ? ($totalIngresos / $ventas->count()) : 0,
            'productos_mas_vendidos' => $productosMasVendidos,
            'marcas_mas_compradas' => $marcasMasCompradas,
            'ventas_por_metodo' => $ventasPorMetodo,
            'ingresos_por_dia' => $ingresosSerie,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'promedio' => $promedioSerie,
            'tituloPeriodo' => $tituloPeriodo,
            'comparativo' => $comparativo
        ];
    }

    private function obtenerComparativoPeriodo($periodo)
    {
        $ahora = Carbon::now();
        switch ($periodo) {
            case 'hoy':
                $ayerInicio = Carbon::yesterday()->startOfDay();
                $ayerFin = Carbon::yesterday()->endOfDay();
                $ventasHoy = Venta::activas()->whereBetween('fecha_venta', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])->sum('total');
                $ventasAyer = Venta::activas()->whereBetween('fecha_venta', [$ayerInicio, $ayerFin])->sum('total');
                return [
                    'periodo_actual' => (float)$ventasHoy,
                    'periodo_anterior' => (float)$ventasAyer,
                    'porcentaje_cambio' => $ventasAyer > 0 ? (($ventasHoy - $ventasAyer) / $ventasAyer) * 100 : 0,
                    'etiqueta' => 'vs ayer'
                ];
            case 'ultimos7':
                $inicioActual = Carbon::now()->subDays(6)->startOfDay();
                $finActual = Carbon::now()->endOfDay();
                $inicioAnterior = Carbon::now()->subDays(13)->startOfDay();
                $finAnterior = Carbon::now()->subDays(7)->endOfDay();
                $ventasActual = Venta::activas()->whereBetween('fecha_venta', [$inicioActual, $finActual])->sum('total');
                $ventasAnterior = Venta::activas()->whereBetween('fecha_venta', [$inicioAnterior, $finAnterior])->sum('total');
                return [
                    'periodo_actual' => (float)$ventasActual,
                    'periodo_anterior' => (float)$ventasAnterior,
                    'porcentaje_cambio' => $ventasAnterior > 0 ? (($ventasActual - $ventasAnterior) / $ventasAnterior) * 100 : 0,
                    'etiqueta' => 'vs semana anterior'
                ];
            case 'anual':
                $anioActual = Carbon::now()->year;
                $anioAnterior = Carbon::now()->subYear()->year;
                $ventasActual = Venta::activas()->whereYear('fecha_venta', $anioActual)->sum('total');
                $ventasAnterior = Venta::activas()->whereYear('fecha_venta', $anioAnterior)->sum('total');
                return [
                    'periodo_actual' => (float)$ventasActual,
                    'periodo_anterior' => (float)$ventasAnterior,
                    'porcentaje_cambio' => $ventasAnterior > 0 ? (($ventasActual - $ventasAnterior) / $ventasAnterior) * 100 : 0,
                    'etiqueta' => 'vs año anterior'
                ];
            case 'mes':
            default:
                $inicioMesActual = Carbon::now()->startOfMonth();
                $finMesActual = Carbon::now()->endOfMonth();
                $inicioMesAnterior = Carbon::now()->subMonth()->startOfMonth();
                $finMesAnterior = Carbon::now()->subMonth()->endOfMonth();
                $ventasActual = Venta::activas()->whereBetween('fecha_venta', [$inicioMesActual, $finMesActual])->sum('total');
                $ventasAnterior = Venta::activas()->whereBetween('fecha_venta', [$inicioMesAnterior, $finMesAnterior])->sum('total');
                return [
                    'periodo_actual' => (float)$ventasActual,
                    'periodo_anterior' => (float)$ventasAnterior,
                    'porcentaje_cambio' => $ventasAnterior > 0 ? (($ventasActual - $ventasAnterior) / $ventasAnterior) * 100 : 0,
                    'etiqueta' => 'vs mes anterior'
                ];
        }
    }

    /**
     * Obtener detalle de venta para modal con información de devoluciones
     */
    public function obtenerDetalle($id)
    {
        try {
            $venta = Venta::with(['detalles.producto', 'usuario', 'cliente', 'devoluciones.producto', 'devoluciones.usuario'])
                ->findOrFail($id);

                return response()->json([
                    'success' => true,
                    'venta' => [
                    'id' => $venta->id,
                    'numero_venta' => $venta->numero_venta,
                    'fecha_venta' => $venta->fecha_venta,
                    'created_at' => $venta->created_at,
                    'subtotal' => $venta->subtotal,
                    'igv' => $venta->igv,
                    'total' => $venta->total,
                    'metodo_pago' => $venta->metodo_pago,
                    'tipo_comprobante' => $venta->tipo_comprobante,
                    'efectivo_recibido' => $venta->efectivo_recibido,
                    'vuelto' => $venta->vuelto,
                    'estado' => $venta->estado,
                    'estado_formateado' => $venta->estado_formateado,
                        'usuario' => $venta->usuario,
                        'cliente_razon_social' => $venta->cliente_razon_social,
                        'cliente_numero_documento' => $venta->cliente_numero_documento,
                        'cliente' => (function() use ($venta){
                            $nombre = null; $dni = null; $telefono = null;
                            if ($venta->relationLoaded('cliente') && $venta->cliente) {
                                $nombre = $venta->cliente->nombre_completo ?? $venta->cliente->razon_social ?? null;
                                $dni = $venta->cliente->dni ?? $venta->cliente->numero_documento ?? null;
                                $telefono = $venta->cliente->telefono ?? null;
                            }
                            if (!$nombre) { $nombre = $venta->cliente_razon_social; }
                            if (!$dni) { $dni = $venta->cliente_numero_documento; }
                            return [
                                'nombre_completo' => $nombre,
                                'dni' => $dni,
                                'telefono' => $telefono
                            ];
                        })(),
                    'tiene_devoluciones' => $venta->tiene_devoluciones,
                    'monto_total_devuelto' => $venta->monto_total_devuelto,
                    'productos_afectados_por_devolucion' => $venta->productos_afectados_por_devolucion,
                    // Información de descuentos
                    'tiene_descuento' => $venta->descuento_monto > 0,
                    'descuento_porcentaje' => $venta->descuento_porcentaje,
                    'descuento_monto' => $venta->descuento_monto,
                    'descuento_tipo' => $venta->descuento_tipo,
                    'descuento_razon' => $venta->descuento_razon,
                    'subtotal_original' => $venta->subtotal + $venta->descuento_monto, // Subtotal antes del descuento
                    'detalles' => $venta->detalles_con_devolucion->map(function ($detalle) {
                        return [
                            'id' => $detalle->id,
                            'cantidad' => $detalle->cantidad,
                            'cantidad_devuelta' => $detalle->cantidad_devuelta ?? 0,
                            'cantidad_restante' => $detalle->cantidad_restante ?? $detalle->cantidad,
                            'tiene_devolucion' => $detalle->tiene_devolucion ?? false,
                            'devolucion_completa' => $detalle->devolucion_completa ?? false,
                            'precio_unitario' => $detalle->precio_unitario,
                            'subtotal_original' => $detalle->subtotal,
                            'lotes_info' => $detalle->lotes_info,
                            'monto_devuelto' => ($detalle->cantidad_devuelta ?? 0) * $detalle->precio_unitario,
                            'producto' => [
                                'id' => $detalle->producto->id,
                                'nombre' => $detalle->producto->nombre,
                                'concentracion' => $detalle->producto->concentracion
                            ]
                        ];
                    }),
                    'devoluciones' => $venta->devoluciones->map(function ($devolucion) {
                        return [
                            'id' => $devolucion->id,
                            'fecha_devolucion' => $devolucion->fecha_formateada,
                            'cantidad_devuelta' => $devolucion->cantidad_devuelta,
                            'monto_devolucion' => $devolucion->monto_devolucion,
                            'motivo' => $devolucion->motivo,
                            'motivo_formateado' => $devolucion->motivo_formateado,
                            'observaciones' => $devolucion->observaciones,
                            'usuario' => $devolucion->usuario->name,
                            'producto' => [
                                'nombre' => $devolucion->producto->nombre,
                                'concentracion' => $devolucion->producto->concentracion
                            ]
                        ];
                    }),
                    'resumen_devolucion' => $venta->resumen_devolucion
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el detalle de la venta'
            ], 404);
        }
    }
}
