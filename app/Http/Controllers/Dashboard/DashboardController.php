<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\PuntoVenta\Venta;
use App\Models\Compra;
use App\Models\User;
use App\Services\QueryOptimizationService;
use App\Services\LoteService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


class DashboardController extends Controller
{
    protected $queryOptimizationService;
    protected $loteService;

    public function __construct(QueryOptimizationService $queryOptimizationService, LoteService $loteService)
    {
        $this->queryOptimizationService = $queryOptimizationService;
        $this->loteService = $loteService;
    }

    public function index()
    {
        return view('dashboard.index');
    }

    public function analisis(Request $request)
    {
        $ventaDateExprStr = Schema::hasColumn('ventas', 'fecha_venta') ? 'COALESCE(fecha_venta, created_at)' : 'created_at';
        $ventaDateExprRaw = DB::raw($ventaDateExprStr);
        // Obtener período seleccionado
        $periodo = $request->get('periodo', 'hoy');
        
        // 📊 DATOS PARA EL DASHBOARD DE ANÁLISIS - OPTIMIZADO
        
        // Usar el servicio optimizado para obtener datos del dashboard
        $dashboardData = $this->queryOptimizationService->getDashboardDataOptimizado();
        
        // 1. INVENTARIO (usando datos optimizados)
        $totalProductos = Producto::count();
        $productosStockBajo = $dashboardData['productos_stock_critico']->where('stock_actual', '<=', 5)->count();
        $productosVencidos = Producto::where('fecha_vencimiento', '<', Carbon::now('America/Lima'))->count();
        $productosProximosVencer = $dashboardData['productos_proximos_vencer']->count();
        
        // Cambio en stock últimos 10 días (simulado por ahora, puedes implementar tabla de movimientos)
        $cambioStock = -$productosStockBajo; // Negativo porque bajó el stock
        
        // 2. VENTAS (usando datos optimizados)
        $ventasHoy = $dashboardData['ventas_hoy'];
        $ventasMes = $dashboardData['ventas_mes'];
        
        $ingresosMes = $dashboardData['ventas_mes'];
        
        $mesAnterior = Carbon::now('America/Lima')->subMonth();
        $ventasMesAnterior = Venta::whereRaw('MONTH(' . $ventaDateExprStr . ') = ? AND YEAR(' . $ventaDateExprStr . ') = ?', [$mesAnterior->month, $mesAnterior->year])
                                  ->where('estado', 'completada')
                                  ->count();
        
        $cambioVentas = $ventasMes - $ventasMesAnterior;
        
        // 3. COMPRAS/GASTOS - Basado en productos agregados
        $productosEsteMes = Producto::whereMonth('created_at', Carbon::now('America/Lima')->month)
                                  ->whereYear('created_at', Carbon::now('America/Lima')->year)
                                  ->get();
        
        $gastosMes = $productosEsteMes->sum(function($producto) {
            return $producto->precio_compra * $producto->stock_actual;
        });
        
        // 4. RENDIMIENTO (Ganancias estimadas)
        $costosVentas = 0;
        if (DB::getSchemaBuilder()->hasTable('venta_detalles')) {
            // Usar columna de costo disponible; si no existe 'precio_compra', fallback a 0
            $tienePrecioCompra = DB::getSchemaBuilder()->hasColumn('productos', 'precio_compra');
            if ($tienePrecioCompra) {
                $costosVentas = DB::table('venta_detalles')
                    ->join('productos', 'venta_detalles.producto_id', '=', 'productos.id')
                    ->join('ventas', 'venta_detalles.venta_id', '=', 'ventas.id')
                    ->where('ventas.estado', 'completada')
                    ->whereRaw('MONTH(' . (Schema::hasColumn('ventas','fecha_venta') ? 'COALESCE(ventas.fecha_venta, ventas.created_at)' : 'ventas.created_at') . ') = ?', [Carbon::now('America/Lima')->month])
                    ->sum(DB::raw('venta_detalles.cantidad * productos.precio_compra'));
            } else {
                // Evitar error 500 cuando no existe la columna
                $costosVentas = 0;
            }
        }
        
        $rendimiento = $ingresosMes - $costosVentas;
        $rendimientoAnterior = $rendimiento * 0.85; // Estimación para calcular cambio
        $cambioRendimiento = $rendimiento - $rendimientoAnterior;
        
        // 5. DATOS PARA EL GRÁFICO según período
        $ventasPorDia = $this->obtenerVentasPorPeriodo($periodo);
        
        // 6. TÍTULO DEL PERÍODO
        $tituloPeriodo = $this->obtenerTituloPeriodo($periodo);
        
        // 7. CATEGORÍAS MÁS VENDIDAS (TOP 5)
        $categoriasMasVendidas = $this->obtenerCategoriasMasVendidas();
        
        // 8. PORCENTAJES DE CAMBIO VS PERÍODO ANTERIOR
        $cambiosComparativos = $this->obtenerCambiosComparativos($periodo);
        
        // 6. PRODUCTOS MÁS VENDIDOS (TOP 3)
        $productosMasVendidos = collect();
        if (DB::getSchemaBuilder()->hasTable('venta_detalles')) {
            $productosMasVendidos = DB::table('venta_detalles')
                ->join('productos', 'venta_detalles.producto_id', '=', 'productos.id')
                ->join('ventas', 'venta_detalles.venta_id', '=', 'ventas.id')
                ->where('ventas.estado', 'completada')
                ->whereRaw('MONTH(' . (Schema::hasColumn('ventas','fecha_venta') ? 'COALESCE(ventas.fecha_venta, ventas.created_at)' : 'ventas.created_at') . ') = ?', [Carbon::now('America/Lima')->month])
                ->select(
                    'productos.id',
                    'productos.nombre',
                    'productos.marca',
                    'productos.categoria',
                    'productos.precio_venta',
                    DB::raw('SUM(venta_detalles.cantidad) as total_vendido'),
                    DB::raw('SUM(venta_detalles.subtotal) as ingresos_producto')
                )
                ->groupBy('productos.id', 'productos.nombre', 'productos.marca', 'productos.categoria', 'productos.precio_venta')
                ->orderBy('total_vendido', 'desc')
                ->limit(3)
                ->get();
        }
        
        // 7. PRODUCTOS CON STOCK CRÍTICO (TOP 3)
        $productosStockCritico = Producto::where('stock_actual', '<=', 5)
            ->orWhereColumn('stock_actual', '<=', 'stock_minimo')
            ->orderBy('stock_actual', 'asc')
            ->limit(3)
            ->get();
        
        // 8. USUARIOS ACTIVOS DEL SISTEMA (TOP 3 últimos que han hecho login)
        $usuariosActivos = User::whereNotNull('last_login_at')
            ->orderBy('last_login_at', 'desc')
            ->limit(3)
            ->get();
        
        // 9. ALERTAS DE LOTES POR VENCER (Proactivo - 90 días)
        $alertasLotes = $this->loteService->obtenerProximosAVencer(90);

        // Si es una petición AJAX, devolver solo los datos del gráfico
        if ($request->ajax()) {
            return response()->json([
                'ventasPorDia' => $ventasPorDia,
                'totalVentas' => collect($ventasPorDia)->sum('total'),
                'totalCantidad' => collect($ventasPorDia)->sum('ventas'),
                'promedioDiario' => collect($ventasPorDia)->avg('total'),
                'periodo' => $periodo,
                'tituloPeriodo' => $this->obtenerTituloPeriodo($periodo),
                'categoriasMasVendidas' => $this->obtenerCategoriasMasVendidas(),
                'cambiosComparativos' => $this->obtenerCambiosComparativos($periodo),
                'alertasLotes' => $alertasLotes // Incluir alertas en JSON para widgets dinámicos
            ]);
        }
        
        return view('dashboard.analisis', compact(
            // Datos principales
            'totalProductos', 'productosStockBajo', 'productosVencidos', 'productosProximosVencer', 'cambioStock',
            'ventasHoy', 'ventasMes', 'ingresosMes', 'cambioVentas',
            'gastosMes',
            'rendimiento', 'cambioRendimiento',
            
            // Datos para gráficos y tablas
            'ventasPorDia',
            'productosMasVendidos',
            'productosStockCritico',
            'usuariosActivos',
            'periodo',
            'tituloPeriodo',
            'categoriasMasVendidas',
            'cambiosComparativos',
            'alertasLotes' // Nueva variable
        ));
    }
    
    private function obtenerVentasPorPeriodo($periodo)
    {
        $ventaDateExprStr = Schema::hasColumn('ventas', 'fecha_venta') ? 'COALESCE(fecha_venta, created_at)' : 'created_at';
        $ventaDateExprRaw = DB::raw($ventaDateExprStr);
        switch ($periodo) {
            case 'hoy':
                $fechas = [];
                $hoy = Carbon::now('America/Lima');
                
                // Mostrar solo de 6 AM a 11 PM (cada 2 horas para mejor visualización)
                for ($hora = 6; $hora <= 23; $hora += 2) {
                    $inicioHora = $hoy->copy()->hour($hora)->minute(0)->second(0);
                    $finHora = $hoy->copy()->hour($hora + 1)->minute(59)->second(59);
                    
                    $total = Venta::whereBetween($ventaDateExprRaw, [$inicioHora, $finHora])
                                  ->where('estado', 'completada')
                                  ->sum('total');
                    $ventas = Venta::whereBetween($ventaDateExprRaw, [$inicioHora, $finHora])
                                  ->where('estado', 'completada')
                                  ->count();
                    
                    // Formato de rango de horas
                    $horaInicio = $inicioHora->format('g A');
                    $horaFin = $finHora->format('g A');
                    $horaFormato = $horaInicio . ' - ' . $horaFin;
                    
                    $fechas[] = [
                        'fecha' => $horaFormato,
                        'total' => (float) $total,
                        'ventas' => $ventas
                    ];
                }
                return $fechas;
                
            case 'ultimos7':
                $fechas = [];
                // Últimos 7 días (incluye hoy)
                for ($i = 6; $i >= 0; $i--) {
                    $dia = Carbon::now('America/Lima')->subDays($i);
                    $total = Venta::whereDate($ventaDateExprRaw, $dia->toDateString())
                               ->where('estado', 'completada')
                               ->sum('total');
                    $ventas = Venta::whereDate($ventaDateExprRaw, $dia->toDateString())
                                  ->where('estado', 'completada')
                                  ->count();
                    $fechas[] = [
                        'fecha' => $dia->format('d/m'),
                        'total' => (float) $total,
                        'ventas' => $ventas
                    ];
                }
                return $fechas;
                
            case 'mes':
                $fechas = [];
                // Este mes, agregado por día
                $mesActual = Carbon::now('America/Lima');
                $inicioMes = $mesActual->copy()->startOfMonth();
                $finMes = $mesActual->copy()->endOfMonth();

                $diaIter = $inicioMes->copy();
                while ($diaIter->lte($finMes)) {
                    $total = Venta::whereDate($ventaDateExprRaw, $diaIter->toDateString())
                               ->where('estado', 'completada')
                               ->sum('total');
                    $ventas = Venta::whereDate($ventaDateExprRaw, $diaIter->toDateString())
                                  ->where('estado', 'completada')
                                  ->count();
                    $fechas[] = [
                        'fecha' => $diaIter->format('d/m'),
                        'total' => (float) $total,
                        'ventas' => $ventas
                    ];
                    $diaIter->addDay();
                }
                return $fechas;
                
            case 'anual':
                $fechas = [];
                $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                          'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                
                $añoActual = Carbon::now('America/Lima')->year;
                
                for ($mes = 1; $mes <= 12; $mes++) {
                    $total = Venta::whereRaw('MONTH(' . $ventaDateExprStr . ') = ? AND YEAR(' . $ventaDateExprStr . ') = ?', [$mes, $añoActual])
                                ->where('estado', 'completada')
                                ->sum('total');
                    $ventas = Venta::whereRaw('MONTH(' . $ventaDateExprStr . ') = ? AND YEAR(' . $ventaDateExprStr . ') = ?', [$mes, $añoActual])
                                  ->where('estado', 'completada')
                                  ->count();
        
                    $fechas[] = [
                        'fecha' => substr($meses[$mes - 1], 0, 3), // Abreviar a 3 letras
                        'total' => (float) $total,
                        'ventas' => $ventas
                    ];
                }
                return $fechas;
                
            default:
                return $this->obtenerVentasPorPeriodo('hoy');
        }
    }
    
    private function obtenerTituloPeriodo($periodo)
    {
        switch ($periodo) {
            case 'hoy':
                $fecha = Carbon::now('America/Lima');
                $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 
                          'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
                
                $diaSemana = $diasSemana[$fecha->dayOfWeek];
                $dia = $fecha->day;
                $mes = $meses[$fecha->month - 1];
                $año = $fecha->year;
                
                return $diaSemana . ' ' . $dia . ' de ' . $mes . ' del ' . $año;
                
            case 'ultimos7':
                $fin = Carbon::now('America/Lima');
                $inicio = $fin->copy()->subDays(6);
                return 'Últimos 7 días (' . $inicio->format('d/m') . ' – ' . $fin->format('d/m') . ')';
                       
            case 'mes':
                $mesActual = Carbon::now('America/Lima');
                $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 
                          'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
                $mesNombre = $meses[$mesActual->month - 1];
                return 'Este mes de ' . $mesNombre . ' ' . $mesActual->year;
                
            case 'anual':
                $añoActual = Carbon::now('America/Lima')->year;
                return 'Meses del año ' . $añoActual;
                
            default:
                                 return '';
         }
     }
     
     private function obtenerCategoriasMasVendidas()
     {
         if (!DB::getSchemaBuilder()->hasTable('venta_detalles')) {
             return collect();
         }
         
         // Primero intentar obtener datos de los últimos 30 días
         $categorias = DB::table('venta_detalles')
             ->join('productos', 'venta_detalles.producto_id', '=', 'productos.id')
             ->join('ventas', 'venta_detalles.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
            ->whereRaw((Schema::hasColumn('ventas','fecha_venta') ? 'COALESCE(ventas.fecha_venta, ventas.created_at)' : 'ventas.created_at') . ' >= ?', [Carbon::now('America/Lima')->subDays(30)])
             ->select(
                 'productos.categoria',
                 DB::raw('SUM(venta_detalles.cantidad) as cantidad_vendida'),
                 DB::raw('SUM(venta_detalles.subtotal) as total_ingresos')
             )
             ->groupBy('productos.categoria')
             ->orderBy('cantidad_vendida', 'desc')
             ->limit(5)
             ->get();
         
         // Si no hay datos recientes, obtener datos de todas las ventas
         if ($categorias->isEmpty()) {
             $categorias = DB::table('venta_detalles')
                 ->join('productos', 'venta_detalles.producto_id', '=', 'productos.id')
                 ->join('ventas', 'venta_detalles.venta_id', '=', 'ventas.id')
                 ->where('ventas.estado', 'completada')
                 ->select(
                     'productos.categoria',
                     DB::raw('SUM(venta_detalles.cantidad) as cantidad_vendida'),
                     DB::raw('SUM(venta_detalles.subtotal) as total_ingresos')
                 )
                 ->groupBy('productos.categoria')
                 ->orderBy('cantidad_vendida', 'desc')
                 ->limit(5)
                 ->get();
         }
         
         return $categorias;
     }
     
    private function obtenerCambiosComparativos($periodo)
    {
        $fechaActual = Carbon::now('America/Lima');
        $cambios = [];
        $ventaDateExprStr = Schema::hasColumn('ventas', 'fecha_venta') ? 'COALESCE(fecha_venta, created_at)' : 'created_at';
        $ventaDateExprRaw = DB::raw($ventaDateExprStr);
        
        switch ($periodo) {
            case 'hoy':
                $ayer = $fechaActual->copy()->subDay();
               $ventasHoy = Venta::whereDate($ventaDateExprRaw, $fechaActual->toDateString())->where('estado', 'completada')->sum('total');
               $ventasAyer = Venta::whereDate($ventaDateExprRaw, $ayer->toDateString())->where('estado', 'completada')->sum('total');
                
                $cambios = [
                    'periodo_actual' => $ventasHoy,
                    'periodo_anterior' => $ventasAyer,
                    'porcentaje_cambio' => $ventasAyer > 0 ? (($ventasHoy - $ventasAyer) / $ventasAyer) * 100 : 0,
                    'etiqueta' => 'vs ayer'
                ];
                break;
                
            case 'ultimos7':
                $inicioSemanaActual = $fechaActual->copy()->startOfWeek();
                // Comparar últimos 7 días vs 7 días anteriores
                $finSemanaActual = $fechaActual->copy();
                $inicioSemanaAnterior = $fechaActual->copy()->subDays(13);
                $finSemanaAnterior = $fechaActual->copy()->subDays(7);

               $ventasSemanaActual = Venta::whereBetween($ventaDateExprRaw, [$inicioSemanaActual->copy()->subDays(6), $finSemanaActual])->where('estado', 'completada')->sum('total');
               $ventasSemanaAnterior = Venta::whereBetween($ventaDateExprRaw, [$inicioSemanaAnterior, $finSemanaAnterior])->where('estado', 'completada')->sum('total');
                
                $cambios = [
                    'periodo_actual' => $ventasSemanaActual,
                    'periodo_anterior' => $ventasSemanaAnterior,
                    'porcentaje_cambio' => $ventasSemanaAnterior > 0 ? (($ventasSemanaActual - $ventasSemanaAnterior) / $ventasSemanaAnterior) * 100 : 0,
                    'etiqueta' => 'vs 7 días previos'
                ];
                break;
                
            case 'mes':
                $mesActual = $fechaActual->month;
                $añoActual = $fechaActual->year;
                $mesAnterior = $fechaActual->copy()->subMonth();
                
               $ventasMesActual = Venta::whereRaw('MONTH(' . $ventaDateExprStr . ') = ? AND YEAR(' . $ventaDateExprStr . ') = ?', [$mesActual, $añoActual])->where('estado', 'completada')->sum('total');
               $ventasMesAnterior = Venta::whereRaw('MONTH(' . $ventaDateExprStr . ') = ? AND YEAR(' . $ventaDateExprStr . ') = ?', [$mesAnterior->month, $mesAnterior->year])->where('estado', 'completada')->sum('total');
                
                $cambios = [
                    'periodo_actual' => $ventasMesActual,
                    'periodo_anterior' => $ventasMesAnterior,
                    'porcentaje_cambio' => $ventasMesAnterior > 0 ? (($ventasMesActual - $ventasMesAnterior) / $ventasMesAnterior) * 100 : 0,
                    'etiqueta' => 'vs mes pasado'
                ];
                break;
                
            case 'anual':
                $añoActual = $fechaActual->year;
                $añoAnterior = $fechaActual->copy()->subYear()->year;
                
               $ventasAñoActual = Venta::whereYear($ventaDateExprRaw, $añoActual)->where('estado', 'completada')->sum('total');
               $ventasAñoAnterior = Venta::whereYear($ventaDateExprRaw, $añoAnterior)->where('estado', 'completada')->sum('total');
                
                $cambios = [
                    'periodo_actual' => $ventasAñoActual,
                    'periodo_anterior' => $ventasAñoAnterior,
                    'porcentaje_cambio' => $ventasAñoAnterior > 0 ? (($ventasAñoActual - $ventasAñoAnterior) / $ventasAñoAnterior) * 100 : 0,
                    'etiqueta' => 'vs año pasado'
                ];
                break;
                 
             default:
                 $cambios = [
                     'periodo_actual' => 0,
                     'periodo_anterior' => 0,
                     'porcentaje_cambio' => 0,
                     'etiqueta' => ''
                 ];
         }
         
         return $cambios;
    }



    /**
     * Obtener datos del dashboard para la aplicación móvil
     */
    public function getDashboardData()
    {
        try {
            Log::info('=== INICIANDO OBTENCIÓN DE DATOS DEL DASHBOARD ===');
            
            // Obtener métricas básicas
            $totalProductos = $this->obtenerTotalProductos();
            $productosStockBajo = $this->obtenerProductosStockBajo();
            $productosProximosVencer = $this->obtenerProductosProximosVencer();
            
            // Obtener ventas
            $ventasHoy = $this->obtenerVentasHoy();
            $ventasMes = $this->obtenerVentasMes();
            $ventasMesAnterior = $this->obtenerVentasMesAnterior();
            
            // Obtener productos más vendidos
            $topProductos = $this->obtenerTop3ProductosMasVendidos();
            Log::info('Top productos obtenidos: ' . $topProductos->count() . ' productos');
            Log::info('Top productos data: ' . json_encode($topProductos->toArray()));
            
            // Obtener productos con stock crítico
            $productosCriticos = $this->obtenerTop3ProductosStockCritico();
            
            // Generar alertas recientes
            $alertasRecientes = $this->generarAlertasRecientes($productosStockBajo, $productosProximosVencer);
            
            $data = [
                'totalProducts' => $totalProductos,
                'lowStockProducts' => $productosStockBajo,
                'expiringProducts' => $productosProximosVencer,
                'dailySales' => $ventasHoy,
                'totalSales' => $ventasMes,
                'previousMonthSales' => $ventasMesAnterior,
                'topProducts' => $topProductos,
                'criticalProducts' => $productosCriticos,
                'recentAlerts' => $alertasRecientes,
                'lastUpdated' => now()->toISOString()
            ];
            
            Log::info('=== DATOS DEL DASHBOARD PREPARADOS ===');
            Log::info('Total productos: ' . $totalProductos);
            Log::info('Ventas hoy: ' . $ventasHoy);
            Log::info('Ventas mes: ' . $ventasMes);
            Log::info('Top productos count: ' . $topProductos->count());
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener datos del dashboard: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar alertas recientes basadas en los datos actuales
     */
    private function generarAlertasRecientes($productosStockBajo, $productosProximosVencer)
    {
        $alertas = [];
        
        // Alertas de stock bajo
        if ($productosStockBajo > 0) {
            $alertas[] = [
                'type' => 'low_stock',
                'title' => 'Stock Bajo',
                'description' => "{$productosStockBajo} productos con stock bajo",
                'time' => 'Ahora'
            ];
        }
        
        // Alertas de productos próximos a vencer
        if ($productosProximosVencer > 0) {
            $alertas[] = [
                'type' => 'expiring',
                'title' => 'Productos por Vencer',
                'description' => "{$productosProximosVencer} productos próximos a vencer",
                'time' => 'Hoy'
            ];
        }
        
        // Alerta de actualización de precios (simulada)
        $alertas[] = [
            'type' => 'price_update',
            'title' => 'Actualización de Precios',
            'description' => 'Revisar precios actualizados',
            'time' => '2h'
        ];
        
        return array_slice($alertas, 0, 3); // Máximo 3 alertas
    }

    /**
     * Métodos auxiliares para obtener datos específicos del dashboard móvil
     */
    private function obtenerTotalProductos()
    {
        return Producto::count();
    }

    private function obtenerProductosStockBajo()
    {
        // Contar productos que están específicamente marcados como 'Bajo stock'
        return Producto::where('estado', 'Bajo stock')->count();
    }

    private function obtenerProductosProximosVencer()
    {
        // Contar productos que están específicamente marcados como 'Por vencer'
        return Producto::where('estado', 'Por vencer')->count();
    }

    private function obtenerVentasHoy()
    {
        $hoy = Carbon::now('America/Lima')->toDateString();
        $ventaDateExprStr = Schema::hasColumn('ventas', 'fecha_venta') ? 'COALESCE(fecha_venta, created_at)' : 'created_at';
        $ventaDateExprRaw = DB::raw($ventaDateExprStr);
        $ventas = Venta::whereDate($ventaDateExprRaw, $hoy)
            ->where('estado', 'completada')
            ->sum('total');
        
        Log::info("Ventas de hoy ({$hoy}): {$ventas}");
        return $ventas;
    }

    private function obtenerVentasMes()
    {
        $mesActual = Carbon::now('America/Lima');
        $ventaDateExprStr = Schema::hasColumn('ventas', 'fecha_venta') ? 'COALESCE(fecha_venta, created_at)' : 'created_at';
        $ventas = Venta::whereRaw('MONTH(' . $ventaDateExprStr . ') = ? AND YEAR(' . $ventaDateExprStr . ') = ?', [$mesActual->month, $mesActual->year])
            ->where('estado', 'completada')
            ->sum('total');
        
        Log::info("Ventas del mes ({$mesActual->format('Y-m')}): {$ventas}");
        return $ventas;
    }

    private function obtenerVentasMesAnterior()
    {
        $mesAnterior = Carbon::now('America/Lima')->subMonth();
        $ventaDateExprStr = Schema::hasColumn('ventas', 'fecha_venta') ? 'COALESCE(fecha_venta, created_at)' : 'created_at';
        return Venta::whereRaw('MONTH(' . $ventaDateExprStr . ') = ? AND YEAR(' . $ventaDateExprStr . ') = ?', [$mesAnterior->month, $mesAnterior->year])
            ->where('estado', 'completada')
            ->sum('total');
    }

    private function obtenerTop3ProductosMasVendidos()
    {
        if (!DB::getSchemaBuilder()->hasTable('venta_detalles')) {
            return collect();
        }

        // Obtener productos más vendidos de los últimos 30 días para datos más actuales
        $fechaInicio = Carbon::now('America/Lima')->subDays(30);
        
        $productos = DB::table('venta_detalles')
            ->join('productos', 'venta_detalles.producto_id', '=', 'productos.id')
            ->join('ventas', 'venta_detalles.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
            ->where('ventas.fecha_venta', '>=', $fechaInicio)
            ->select(
                'productos.id',
                'productos.nombre',
                'productos.marca',
                'productos.categoria',
                'productos.precio_venta',
                DB::raw('SUM(venta_detalles.cantidad) as total_vendido'),
                DB::raw('SUM(venta_detalles.subtotal) as ingresos_producto')
            )
            ->groupBy('productos.id', 'productos.nombre', 'productos.marca', 'productos.categoria', 'productos.precio_venta')
            ->orderBy('total_vendido', 'desc')
            ->limit(3)
            ->get();

        // Si no hay ventas en los últimos 30 días, obtener de todo el tiempo
        if ($productos->isEmpty()) {
            $productos = DB::table('venta_detalles')
                ->join('productos', 'venta_detalles.producto_id', '=', 'productos.id')
                ->join('ventas', 'venta_detalles.venta_id', '=', 'ventas.id')
                ->where('ventas.estado', 'completada')
                ->select(
                    'productos.id',
                    'productos.nombre',
                    'productos.marca',
                    'productos.categoria',
                    'productos.precio_venta',
                    DB::raw('SUM(venta_detalles.cantidad) as total_vendido'),
                    DB::raw('SUM(venta_detalles.subtotal) as ingresos_producto')
                )
                ->groupBy('productos.id', 'productos.nombre', 'productos.marca', 'productos.categoria', 'productos.precio_venta')
                ->orderBy('total_vendido', 'desc')
                ->limit(3)
                ->get();
        }

        return $productos;
    }

    private function obtenerTop3ProductosStockCritico()
    {
        return Producto::where('stock_actual', '<=', 5)
            ->orWhereColumn('stock_actual', '<=', 'stock_minimo')
            ->orderBy('stock_actual', 'asc')
            ->limit(3)
            ->get();
    }
}