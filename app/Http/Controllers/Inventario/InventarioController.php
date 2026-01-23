<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\ProductoUbicacion;
use App\Services\ImageOptimizationService;
use App\Services\QueryOptimizationService;
use App\Services\LoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use ZipArchive;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use App\Services\CloudinaryService;

class InventarioController extends Controller
{
    protected $queryOptimizationService;
    protected $loteService;

    public function __construct(QueryOptimizationService $queryOptimizationService, LoteService $loteService)
    {
        $this->queryOptimizationService = $queryOptimizationService;
        $this->loteService = $loteService;
    }

    public function cambiarEstadoLote(Request $request, $loteId)
    {
        $validator = Validator::make($request->all(), [
            'nuevo_estado' => 'required|string|in:activo,agotado,cuarentena,vencido',
            'motivo' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $lote = $this->loteService->cambiarEstadoLote(
                $loteId,
                $request->input('nuevo_estado'),
                $request->input('motivo'),
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Estado del lote actualizado correctamente',
                'data' => $lote
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de lote: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Obtener parámetros de paginación
        $perPage = $request->get('per_page', 10); // Por defecto 10 productos por página
        $search = $request->get('search', '');
        $estado = $request->get('estado', 'todos');
        
        // Validar que per_page sea un valor válido
        $validPerPage = [5, 10, 25, 50, 100];
        if (!in_array($perPage, $validPerPage)) {
            $perPage = 10;
        }
        
        // Usar el servicio de optimización para obtener productos con eager loading
        $query = $this->queryOptimizationService->getProductosOptimizados([
            'search' => $search,
            'estado' => $estado
        ]);
        
        // Agregar eager loading de relaciones necesarias
        $query->with(['presentaciones', 'ubicaciones.ubicacion.estante', 'proveedor']);
        
        // Aplicar paginación
        $productos = $query->paginate($perPage)->withQueryString();
        
        // Agregar información de ubicaciones a cada producto
        $productos->getCollection()->transform(function ($producto) {
            $ubicacionesConStock = $producto->ubicaciones->where('cantidad', '>', 0);
            $stockEnUbicaciones = $ubicacionesConStock->sum('cantidad');
            $stockSinUbicar = max(0, $producto->stock_actual - $stockEnUbicaciones);
            
            // Agrupar por ubicación física real (mismo estante y código)
            $ubicacionesAgrupadas = $ubicacionesConStock->groupBy(function ($ubicacion) {
                $estante = $ubicacion->ubicacion?->estante;
                return ($estante?->nombre ?? 'Sin asignar') . ' - ' . ($ubicacion->ubicacion?->codigo ?? 'N/A');
            });
            
            // Contar ubicaciones físicas únicas
            $producto->total_ubicaciones = $ubicacionesAgrupadas->count();
            $producto->stock_en_ubicaciones = $stockEnUbicaciones;
            $producto->stock_sin_ubicar = $stockSinUbicar;
            $producto->tiene_stock_sin_ubicar = $stockSinUbicar > 0;
            
            // Determinar estado de ubicación
            if ($stockEnUbicaciones == 0) {
                $producto->estado_ubicacion = 'sin_ubicar';
                $producto->texto_ubicacion = 'Sin ubicar';
            } elseif ($stockSinUbicar > 0) {
                $producto->estado_ubicacion = 'parcialmente_ubicado';
                $producto->texto_ubicacion = 'Parcialmente ubicado';
            } else {
                $producto->estado_ubicacion = 'completamente_ubicado';
                $producto->texto_ubicacion = 'Completamente ubicado';
            }
            
            // Crear detalle de ubicaciones agrupadas
            $producto->ubicaciones_detalle = $ubicacionesAgrupadas->map(function ($ubicacionesEnMismoLugar, $ubicacionCompleta) {
                $primeraUbicacion = $ubicacionesEnMismoLugar->first();
                $estante = $primeraUbicacion->ubicacion?->estante;
                $cantidadTotal = $ubicacionesEnMismoLugar->sum('cantidad');
                
                // Obtener información de lotes
                $lotes = $ubicacionesEnMismoLugar->map(function ($ub) {
                    return [
                        'lote' => $ub->lote,
                        'cantidad' => $ub->cantidad,
                        'fecha_vencimiento' => $ub->fecha_vencimiento
                    ];
                })->toArray();
                
                return [
                    'estante_nombre' => $estante?->nombre ?? 'Sin asignar',
                    'ubicacion_codigo' => $primeraUbicacion->ubicacion?->codigo ?? 'N/A',
                    'cantidad' => $cantidadTotal,
                    'fecha_vencimiento' => $primeraUbicacion->fecha_vencimiento,
                    'lote' => $primeraUbicacion->lote,
                    'ubicacion_completa' => $ubicacionCompleta,
                    'lotes_detalle' => $lotes, // Información detallada de todos los lotes
                    'tiene_multiples_lotes' => $ubicacionesEnMismoLugar->count() > 1
                ];
            })->values();
            
            // NO agregar stock sin ubicar como ubicación - se maneja por separado
            
            return $producto;
        });
        
        $categorias = Categoria::orderBy('nombre')->get();
        $presentaciones = \App\Models\Presentacion::orderBy('nombre')->get();
        $proveedores = \App\Models\Proveedor::where('estado', 'activo')->orderBy('razon_social')->get();
        $title = 'Gestión de Productos';
        $subTitle = 'Lista de Productos';
        
        // Si es una petición AJAX, devolver solo los datos
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $productos,
                'search' => $search,
                'estado' => $estado,
                'perPage' => $perPage
            ]);
        }
        
        // La vista legacy 'inventario.productos' ya no se utiliza; redirigir a Productos Botica
        // Soportar parámetro legacy 'filter' y mapear a 'estado'
        $filter = $request->get('filter');
        if ($filter) {
            $map = [
                'stock_bajo' => 'Bajo stock',
                'agotados' => 'Agotado',
                'por_vencer' => 'Por Vencer',
                'vencidos' => 'Vencido',
                'normal' => 'Normal'
            ];
            $estadoMapped = $map[$filter] ?? 'todos';
            $qs = $estadoMapped !== 'todos' ? ('?estado=' . urlencode($estadoMapped)) : '';
            return redirect('/inventario/productos-botica' . $qs);
        }
        return redirect()->route('inventario.productos.botica');
    }

    /**
     * Handle AJAX requests for products listing
     */
    public function ajaxIndex(Request $request)
    {
        try {
            // Obtener parámetros de paginación
            $perPage = (int)$request->get('per_page', 10);
            $search = $request->get('search', '');
            $estado = $request->get('estado', 'todos');
            
            // Validar que per_page sea un valor válido
            // Permitimos -1 para exportar todo (o un límite alto)
            if ($perPage == -1) {
                $perPage = 10000; // Límite alto para exportación
            } else {
                $validPerPage = [5, 10, 25, 50, 100];
                if (!in_array($perPage, $validPerPage)) {
                    $perPage = 10;
                }
            }
            
            // Usar el servicio de optimización para obtener productos con eager loading
            $query = $this->queryOptimizationService->getProductosOptimizados([
                'search' => $search,
                'estado' => $estado
            ]);
            
            // Aplicar paginación
            $productos = $query->paginate($perPage);

            // Rotación últimos 30 días por producto (para IDs de la página)
            $idsPagina = $productos->getCollection()->pluck('id');
            $rotacionMap = collect();
            // Detectar tabla y columnas dinámicamente
            $detTable = null;
            foreach (['venta_detalles', 'detalle_ventas', 'ventas_detalles'] as $t) {
                if (Schema::hasTable($t)) { $detTable = $t; break; }
            }
            if ($idsPagina->count() > 0 && $detTable && Schema::hasTable('ventas')) {
                $fechaCol = Schema::hasColumn('ventas', 'fecha_venta') ? 'COALESCE(ventas.fecha_venta, ventas.created_at)' : 'ventas.created_at';
                $desde = Carbon::now('America/Lima')->subDays(30);

                $prodIdCol = null; foreach (['producto_id','id_producto','producto','idProd'] as $c) { if (Schema::hasColumn($detTable, $c)) { $prodIdCol = $c; break; } }
                if (!$prodIdCol) { $prodIdCol = 'producto_id'; }

                $ventaIdCol = null; foreach (['venta_id','id_venta'] as $c) { if (Schema::hasColumn($detTable, $c)) { $ventaIdCol = $c; break; } }
                if (!$ventaIdCol) { $ventaIdCol = 'venta_id'; }

                $qtyCol = null; foreach (['cantidad','qty','unidades','cantidad_venta'] as $c) { if (Schema::hasColumn($detTable, $c)) { $qtyCol = $c; break; } }
                if (!$qtyCol) {
                    try {
                        $cols = Schema::getColumnListing($detTable);
                        foreach ($cols as $c) { if (preg_match('/cant|cantidad|unid|qty/i', $c)) { $qtyCol = $c; break; } }
                    } catch (\Throwable $e) {}
                }
                $sumExpr = $qtyCol ? ('SUM(' . $detTable . '.' . $qtyCol . ')') : 'COUNT(*)';

                $rows = DB::table($detTable)
                    ->join('ventas', $detTable . '.' . $ventaIdCol, '=', 'ventas.id')
                    ->whereIn($detTable . '.' . $prodIdCol, $idsPagina)
                    ->where(DB::raw($fechaCol), '>=', $desde)
                    ->when(Schema::hasColumn('ventas','estado'), function ($q) {
                        $q->whereIn('ventas.estado', ['completada','finalizada','pagada']);
                    })
                    ->select($detTable . '.' . $prodIdCol . ' as pid', DB::raw($sumExpr . ' as unidades'))
                    ->groupBy($detTable . '.' . $prodIdCol)
                    ->get();
                $rotacionMap = $rows->pluck('unidades', 'pid');
            }

            // Obtener información de lotes para los productos de la página
            $lotesInfo = collect();
            try {
                // Verificar si hay IDs y si la tabla existe para evitar errores 500
                if ($idsPagina->count() > 0 && Schema::hasTable('producto_ubicaciones')) {
                    $lotesInfo = ProductoUbicacion::with(['ubicacion.estante'])
                        ->whereIn('producto_id', $idsPagina)
                        ->where('cantidad', '>', 0)
                        ->select('id', 'producto_id', 'lote', 'fecha_vencimiento', 'cantidad', 'ubicacion_id')
                        ->orderBy('fecha_vencimiento', 'asc') // FEFO: First Expired, First Out
                        ->get()
                        ->groupBy('producto_id');
                }
            } catch (\Exception $e) {
                Log::error('Error cargando lotes en ajaxIndex: ' . $e->getMessage());
                // Continuar sin información de lotes para no romper el listado
            }

            // Devolver SOLO datos básicos y serializables (sin relaciones complejas)
            $productos->getCollection()->transform(function ($producto) use ($rotacionMap, $lotesInfo) {
                try {
                    $stockActual = (int)($producto->stock_actual ?? 0);
                    $presentacionNombre = $producto->presentacion ?? (optional($producto->presentacion_model)->nombre);
                    $categoriaNombre = $producto->categoria ?? (optional($producto->categoria_model)->nombre);
                    $imagenUrl = $producto->imagen_url ?? null;
                    $estadoFinal = $this->determinarEstado(
                        $stockActual,
                        (int)($producto->stock_minimo ?? 0),
                        $producto->fecha_vencimiento ?? null,
                        $producto->id
                    );

                    // FORCE OVERRIDE: Calcular estado real basado en fecha de vencimiento si hay stock
                    // Esto corrige el problema visual cuando el filtro "Por vencer" funciona pero la etiqueta dice "Normal"
                    if ($stockActual > 0 && $producto->fecha_vencimiento) {
                        $venc = \Carbon\Carbon::parse($producto->fecha_vencimiento);
                        $hoy = \Carbon\Carbon::now('America/Lima');
                        $dias = $hoy->diffInDays($venc, false);
                        
                        if ($dias < 0) {
                            $estadoFinal = 'Vencido';
                        } elseif ($dias <= 90) { // 90 días para coincidir con la alerta visual de lotes
                            $estadoFinal = 'Por vencer';
                        }
                    }

                    // Información de lotes
                    $lotesProducto = $lotesInfo[$producto->id] ?? collect();
                    $proximoVencimiento = $lotesProducto->first() ? $lotesProducto->first()->fecha_vencimiento : null;
                    
                    // Formatear lotes para el frontend
                    $lotesFormatted = $lotesProducto->map(function($l) {
                        $loc = 'Sin asignar';
                        if ($l->ubicacion) {
                            $estante = $l->ubicacion->estante->nombre ?? '';
                            $codigo = $l->ubicacion->codigo ?? '';
                            $loc = $estante . ($estante && $codigo ? ' - ' : '') . $codigo;
                        }

                        return [
                            'id' => $l->id,
                            'lote' => $l->lote, // Use key 'lote' to match JS expectation in tooltip/badge if needed, or maintain consistency
                            'codigo' => $l->lote, // Keep legacy key just in case
                            'vencimiento' => $l->fecha_vencimiento,
                            'cantidad' => $l->cantidad,
                            'ubicacion' => $loc, // Nueva propiedad de ubicación
                            'dias_para_vencer' => $l->fecha_vencimiento ? Carbon::now()->diffInDays(Carbon::parse($l->fecha_vencimiento), false) : null
                        ];
                    })->values();

                    // Cargar presentaciones del producto
                    $presentacionesProducto = $producto->presentaciones ?? collect();
                    $presentacionesFormatted = $presentacionesProducto->map(function($pres) {
                        return [
                            'id' => $pres->id,
                            'nombre_presentacion' => $pres->nombre_presentacion,
                            'unidades_por_presentacion' => $pres->unidades_por_presentacion,
                            'precio_venta_presentacion' => $pres->precio_venta_presentacion
                        ];
                    })->values();

                    return [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'concentracion' => $producto->concentracion ?? null,
                        'marca' => $producto->marca ?? null,
                        'categoria' => $categoriaNombre,
                        'presentacion' => $presentacionNombre,
                        'presentaciones' => $presentacionesFormatted, // ✅ Agregado para la lista
                        'precio_compra' => (float)($producto->precio_compra ?? 0),
                        'precio_venta' => (float)($producto->precio_venta ?? 0),
                        'stock_actual' => $stockActual,
                        'stock_minimo' => (int)($producto->stock_minimo ?? 0),
                        'fecha_vencimiento' => $producto->fecha_vencimiento ?? null,
                        'ubicacion' => $producto->ubicacion ?? ($producto->ubicacion_almacen ?? ''),
                        'ubicacion_almacen' => $producto->ubicacion_almacen ?? null,
                        'imagen_url' => $imagenUrl,
                        'estado' => $estadoFinal,
                        'proveedor_id' => $producto->proveedor_id ?? null,
                        'proveedor' => optional($producto->proveedor)->razon_social,
                        'rotacion_30d' => (int)($rotacionMap[$producto->id] ?? 0),
                        'lotes' => $lotesFormatted,
                        'total_lotes' => $lotesProducto->count(),
                        'proximo_vencimiento' => $proximoVencimiento,
                        // Defaults de ubicación para la UI (sin calcular relaciones)
                        'total_ubicaciones' => 0,
                        'stock_en_ubicaciones' => 0,
                        'stock_sin_ubicar' => 0,
                        'tiene_stock_sin_ubicar' => false,
                        'estado_ubicacion' => 'sin_ubicar',
                        'texto_ubicacion' => 'Sin ubicar',
                        'ubicaciones_detalle' => []
                    ];
                } catch (\Throwable $t) {
                    \Log::error('Error transformando producto para ajaxIndex (minimal)', [
                        'producto_id' => $producto->id ?? null,
                        'error' => $t->getMessage()
                    ]);
                    return [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'categoria' => $producto->categoria ?? null,
                        'precio_venta' => (float)($producto->precio_venta ?? 0),
                        'precio_compra' => (float)($producto->precio_compra ?? 0),
                        'stock_actual' => (int)($producto->stock_actual ?? 0),
                        'stock_minimo' => (int)($producto->stock_minimo ?? 0),
                        'estado' => $this->determinarEstado(
                            (int)($producto->stock_actual ?? 0),
                            (int)($producto->stock_minimo ?? 0),
                            $producto->fecha_vencimiento ?? now()
                        ),
                        'proveedor_id' => $producto->proveedor_id ?? null,
                        'proveedor' => optional($producto->proveedor)->razon_social,
                        'imagen_url' => $producto->imagen_url ?? null,
                        'total_ubicaciones' => 0,
                        'tiene_stock_sin_ubicar' => false,
                        'stock_sin_ubicar' => 0
                    ];
                }
            });

            // Devolver el paginador (con cada item como array serializable)
            return response()->json($productos);
            
        } catch (\Exception $e) {
            Log::error('Error en ajaxIndex: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar productos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $producto = Producto::with(['proveedor', 'presentaciones'])->findOrFail($id);
            
            // Obtener lotes FEFO activos
            $lotes = ProductoUbicacion::where('producto_id', $id)
                ->where('cantidad', '>', 0)
                ->select('id', 'lote', 'fecha_vencimiento', 'cantidad', 'precio_compra_lote', 'precio_venta_lote', 'proveedor_id')
                ->orderBy('fecha_vencimiento', 'asc')
                ->get();

            // Sincronizar stock_actual con la suma de lotes
            $stockTotal = $lotes->sum('cantidad');
            if ($producto->stock_actual != $stockTotal) {
                $producto->stock_actual = $stockTotal;
                $producto->save();
            }

            Log::info('InventarioController::show - Producto ID: ' . $id, [
                'lotes_count' => $lotes->count(),
                'stock_total' => $stockTotal,
                'lotes_data' => $lotes->toArray()
            ]);

            $lotesFormatted = $lotes->map(function($l) {
                // Obtener presentaciones específicas para este lote
                $presLote = DB::table('lote_presentaciones')
                    ->where('producto_ubicacion_id', $l->id)
                    ->get();

                return [
                    'id' => $l->id,
                    'lote' => $l->lote,
                    'fecha_vencimiento' => $l->fecha_vencimiento,
                    'cantidad' => $l->cantidad,
                    'precio_compra_lote' => $l->precio_compra_lote,
                    'precio_venta_lote' => $l->precio_venta_lote,
                    'proveedor_id' => $l->proveedor_id,
                    'dias_para_vencer' => $l->fecha_vencimiento ? Carbon::now()->diffInDays(Carbon::parse($l->fecha_vencimiento), false) : null,
                    'presentaciones_lote' => $presLote
                ];
            })->values();
            
            // Cargar presentaciones del producto
            $presentaciones = $producto->presentaciones->map(function($pres) {
                return [
                    'id' => $pres->id,
                    'nombre_presentacion' => $pres->nombre_presentacion,
                    'unidades_por_presentacion' => $pres->unidades_por_presentacion,
                    'precio_venta_presentacion' => $pres->precio_venta_presentacion
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'categoria' => $producto->categoria,
                    'marca' => $producto->marca,
                    'proveedor_id' => $producto->proveedor_id,
                    'proveedor' => $producto->proveedor ? $producto->proveedor->razon_social : null,
                    // REMOVIDO: 'presentacion' => $producto->presentacion,
                    'presentaciones' => $presentaciones, // ✅ Agregado
                    'concentracion' => $producto->concentracion,
                    'lote' => $producto->lote,
                    'codigo_barras' => $producto->codigo_barras,
                    'stock_actual' => $producto->stock_actual,
                    'stock_minimo' => $producto->stock_minimo,
                    'precio_compra' => $producto->precio_compra,
                    'precio_venta' => $producto->precio_venta,
                    // REMOVIDO: 'fecha_fabricacion' => $producto->fecha_fabricacion_solo_fecha,
                    'fecha_vencimiento' => $producto->fecha_vencimiento_solo_fecha,
                    'ubicacion' => $producto->ubicacion,
                    'ubicacion_almacen' => $producto->ubicacion_almacen,
                    'imagen' => $producto->imagen,
                    'imagen_url' => $producto->imagen_url,
                    'estado' => $producto->estado,
                    'lotes_detalle' => $lotesFormatted
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener producto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el producto: ' . $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Iniciando guardado de producto', [
                'datos_recibidos' => $request->all(),
                'headers' => $request->headers->all()
            ]);
            
            DB::beginTransaction();

            Log::info('Iniciando validación de datos');
            $hasProveedorColumn = Schema::hasColumn('productos', 'proveedor_id');
            $rules = [
                'nombre' => 'required|string|max:255',
                'codigo_barras' => 'required|string|max:255|unique:productos',
                'lote' => 'required|string|max:255',
                'categoria' => 'required|string|max:255',
                'marca' => 'required|string|max:255',
                // REMOVIDO: 'presentacion' => 'required|string|max:255', // Ya no se usa, ahora es producto_presentaciones
                'concentracion' => 'nullable|string|max:255',
                'stock_actual' => 'required|integer|min:0',
                'stock_minimo' => 'required|integer|min:0',
                // REMOVIDO: 'fecha_fabricacion' => 'required|date|before_or_equal:today', // Campo eliminado
                'fecha_vencimiento' => 'nullable|date', // Opcional
                'precio_compra' => 'required|numeric|gt:0',
                'precio_venta' => 'required|numeric|gt:0|gte:precio_compra',
                'imagen' => 'nullable|image|max:2048',
                'ubicacion' => 'nullable|string|max:255'
            ];
            if ($hasProveedorColumn) {
                $rules['proveedor_id'] = 'required|integer|exists:proveedores,id';
            }
            $messages = [
                'nombre.required' => 'El nombre del producto es obligatorio.',
                'codigo_barras.required' => 'El código de barras es obligatorio.',
                'codigo_barras.unique' => 'Este código de barras ya está registrado.',
                'lote.required' => 'El lote es obligatorio.',
                'categoria.required' => 'La categoría es obligatoria.',
                'marca.required' => 'La marca es obligatoria.',
                // REMOVIDO: 'presentacion.required' => 'La presentación es obligatoria.',
                'concentracion.regex' => 'La concentración debe ser número + unidad (ej: 500mg, 2.5ml, 10%).',
                'stock_actual.required' => 'El stock actual es obligatorio.',
                'stock_actual.min' => 'El stock actual no puede ser negativo.',
                'stock_minimo.required' => 'El stock mínimo es obligatorio.',
                'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',
                // REMOVIDO: 'fecha_fabricacion.required' => 'La fecha de fabricación es obligatoria.',
                // REMOVIDO: 'fecha_fabricacion.before_or_equal' => 'La fecha de fabricación no puede ser futura.',
                'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria.',
                'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
                'precio_compra.gt' => 'El precio de compra debe ser mayor a 0.',
                'precio_venta.gt' => 'El precio de venta debe ser mayor a 0.',
                'precio_venta.gte' => 'El precio de venta debe ser mayor o igual al precio de compra.'
            ];
            $validator = Validator::make($request->all(), $rules, $messages);
            $pattern = '/^\d+(\.\d+)?\s*(mg|g|ml|l|mcg|µg|UI|%|mEq|mmol)$/i';
            $validator->after(function($v) use ($request, $pattern) {
                if ($request->filled('concentracion') && !preg_match($pattern, $request->concentracion)) {
                    $v->errors()->add('concentracion', 'La concentración debe ser número + unidad (ej: 500mg, 2.5ml, 10%).');
                }
            });
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación.',
                    'errors' => $validator->errors()
                ], 422);
            }
            Log::info('Validación completada exitosamente');

            Log::info('Procesando imagen');
            $imagePath = null;
            if ($request->hasFile('imagen')) {
                $cloudinary = new CloudinaryService();
                if ($cloudinary->isEnabled()) {
                    $url = $cloudinary->uploadProductImage($request->file('imagen'), 'productos');
                    if ($url) {
                        $imagePath = $url;
                        Log::info('Imagen subida a Cloudinary: ' . $imagePath);
                    }
                }
                if (!$imagePath) {
                    $imageOptimizer = new ImageOptimizationService();
                    $imagePath = $imageOptimizer->optimizeProductImage($request->file('imagen'));
                    Log::info('Imagen optimizada y guardada localmente: ' . $imagePath);
                }
            }

            Log::info('Determinando estado del producto');
            $estado = $this->determinarEstado(
                $request->stock_actual, 
                $request->stock_minimo, 
                $request->fecha_vencimiento
            );
            Log::info('Estado determinado: ' . $estado);

            Log::info('Creando producto en base de datos');
            // Crear el producto usando el método create de Eloquent (auto-incremento)
            $datosProducto = [
                'nombre' => $request->nombre,
                'codigo_barras' => $request->codigo_barras,
                'lote' => $request->lote,
                'categoria' => $request->categoria,
                'marca' => $request->marca,
                // REMOVIDO: 'presentacion' => $request->presentacion, // Ya no se guarda aquí
                'concentracion' => $request->concentracion,
                'stock_actual' => $request->stock_actual,
                'stock_minimo' => $request->stock_minimo,
                'ubicacion' => $request->ubicacion ?? null,
                // REMOVIDO: 'fecha_fabricacion' => $request->fecha_fabricacion, // Campo eliminado
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'precio_compra' => $request->precio_compra,
                'precio_venta' => $request->precio_venta,
                'imagen' => $imagePath,
                'estado' => $estado
            ];
            if ($hasProveedorColumn && $request->filled('proveedor_id')) {
                $datosProducto['proveedor_id'] = $request->proveedor_id;
            }

            Log::info('Datos del producto a guardar', $datosProducto);

            $producto = Producto::create($datosProducto);
            Log::info('Producto creado exitosamente con ID: ' . $producto->id);

            // Guardar presentaciones múltiples
            if ($request->has('presentaciones') && is_array($request->presentaciones)) {
                Log::info('Guardando presentaciones múltiples para el producto', [
                    'producto_id' => $producto->id,
                    'presentaciones' => $request->presentaciones
                ]);
                
                foreach ($request->presentaciones as $presentacionData) {
                    // Asegúrate de que cada presentación tenga al menos un nombre y unidades
                    if (isset($presentacionData['nombre_presentacion']) && isset($presentacionData['unidades_por_presentacion'])) {
                        $producto->presentaciones()->create([
                            'nombre_presentacion' => $presentacionData['nombre_presentacion'],
                            'unidades_por_presentacion' => $presentacionData['unidades_por_presentacion'],
                            'precio_venta_presentacion' => $presentacionData['precio_venta_presentacion'] ?? null,
                        ]);
                        
                        Log::info('Presentación creada', [
                            'nombre' => $presentacionData['nombre_presentacion'],
                            'unidades' => $presentacionData['unidades_por_presentacion']
                        ]);
                    }
                }
            }

            // Crear el lote inicial automáticamente si hay stock inicial
            if ($request->stock_actual > 0) {
                Log::info('Creando lote inicial automático para el producto');
                try {
                    $this->loteService->crearLote([
                        'producto_id' => $producto->id,
                        'lote' => $request->lote,
                        'fecha_vencimiento' => $request->fecha_vencimiento,
                        'cantidad' => $request->stock_actual,
                        'observaciones' => 'Lote inicial creado al registrar el producto',
                        'precio_compra' => $request->precio_compra,
                        'precio_venta' => $request->precio_venta,
                        'proveedor_id' => $hasProveedorColumn ? $request->proveedor_id : null
                    ]);
                    Log::info('Lote inicial creado correctamente');
                } catch (\Exception $e) {
                    Log::error('Error al crear lote inicial: ' . $e->getMessage());
                    // No revertimos la transacción del producto, solo logueamos el error
                    // ya que el producto es lo principal.
                }
            }

            DB::commit();
            Log::info('Transacción confirmada');

            // Cargar las presentaciones del producto antes de devolverlo
            $producto->load('presentaciones');

            return response()->json([
                'success' => true,
                'message' => 'Producto guardado exitosamente',
                'data' => $producto
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar producto: ' . $e->getMessage());
            Log::error('Detalles del error: ', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el producto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $producto = Producto::findOrFail($id);
            
            Log::info("🗑️ Iniciando eliminación del producto ID: {$id} - {$producto->nombre}");
            
            // DESHABILITAR FOREIGN KEY CHECKS TEMPORALMENTE (si el driver lo soporta)
            try { DB::statement('SET FOREIGN_KEY_CHECKS=0;'); } catch (\Exception $fkOff) { Log::info('FK checks no soportados por el driver actual'); }
            
            // 1. Eliminar detalles de ventas (si existe la tabla)
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('venta_detalles')) {
                    $deletedVentaDetalles = DB::table('venta_detalles')->where('producto_id', $id)->delete();
                    if ($deletedVentaDetalles > 0) {
                        Log::info("✅ Eliminados {$deletedVentaDetalles} registros de venta_detalles");
                    }
                }
            } catch (\Exception $e) { Log::info('ℹ️ Tabla venta_detalles no existe o ya está limpia'); }
            
            // 2. Eliminar movimientos de stock (si existe)
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('movimientos_stock')) {
                    $deletedMovimientos = DB::table('movimientos_stock')->where('producto_id', $id)->delete();
                    if ($deletedMovimientos > 0) { Log::info("✅ Eliminados {$deletedMovimientos} movimientos de stock"); }
                }
            } catch (\Exception $e) { Log::info('ℹ️ Tabla movimientos_stock no existe o ya está limpia'); }
            
            // 3. Eliminar ubicaciones de productos en almacén (si existe)
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('producto_ubicaciones')) {
                    $deletedUbicaciones = DB::table('producto_ubicaciones')->where('producto_id', $id)->delete();
                    if ($deletedUbicaciones > 0) { Log::info("✅ Eliminadas {$deletedUbicaciones} ubicaciones de producto"); }
                }
            } catch (\Exception $e) { Log::info('ℹ️ Tabla producto_ubicaciones no existe o ya está limpia'); }
            
            // 4. Eliminar de otras tablas relacionadas que puedan existir
            $tablesWithProductoId = [
                'compra_detalles',
                'inventario_ajustes',
                'stock_minimo_alertas',
                'producto_proveedores',
                'promociones_productos',
                'alertas_productos'
            ];
            
            foreach ($tablesWithProductoId as $table) {
                try {
                    $deleted = DB::table($table)->where('producto_id', $id)->delete();
                    if ($deleted > 0) {
                        Log::info("✅ Eliminados {$deleted} registros de tabla {$table}");
                    }
                } catch (\Exception $e) {
                    Log::info("ℹ️ Tabla {$table} no existe o ya está limpia");
                }
            }
            
            // 6. Eliminar imagen si existe
            if ($producto->imagen && Storage::disk('public')->exists($producto->imagen)) {
                Storage::disk('public')->delete($producto->imagen);
                Log::info("✅ Imagen eliminada: {$producto->imagen}");
            }
            
            // 7. ELIMINAR EL PRODUCTO DIRECTAMENTE CON RAW SQL
            DB::statement('DELETE FROM productos WHERE id = ?', [$id]);
            Log::info("✅ Producto eliminado con SQL directo");
            
            // 8. REACTIVAR FOREIGN KEY CHECKS (si el driver lo soporta)
            try { DB::statement('SET FOREIGN_KEY_CHECKS=1;'); } catch (\Exception $fkOn) { Log::info('FK checks no soportados por el driver actual'); }
            
            DB::commit();
            Log::info("🎉 Eliminación completada exitosamente");

            return response()->json([
                'success' => true,
                'message' => "Producto '{$producto->nombre}' eliminado correctamente",
                'needsRefresh' => true
            ]);
            
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            
            // Asegurar que foreign keys estén activadas aunque falle
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Exception $fkError) {
                Log::error("Error reactivando foreign keys: " . $fkError->getMessage());
            }
            
            Log::error("❌ Error al eliminar producto ID {$id}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            
            // Mensaje de error más específico basado en el tipo de error
            $errorMessage = 'Error al eliminar el producto';
            if (str_contains($e->getMessage(), 'foreign key constraint')) {
                $errorMessage = 'No se puede eliminar el producto porque está siendo usado en ventas o compras';
            } elseif (str_contains($e->getMessage(), 'venta_detalles')) {
                $errorMessage = 'No se puede eliminar el producto porque tiene ventas asociadas';
            }
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function determinarEstado($stockActual, $stockMinimo, $fechaVencimiento, $productoId = null)
    {
        try {
            $hoy = Carbon::today();

            // Normalizar fecha de vencimiento (puede ser null)
            $fv = null;
            if ($fechaVencimiento instanceof Carbon) {
                $fv = $fechaVencimiento->copy();
            } elseif (is_string($fechaVencimiento) && trim($fechaVencimiento) !== '') {
                try { $fv = Carbon::createFromFormat('Y-m-d', $fechaVencimiento); } catch (\Throwable $e) { $fv = null; }
            } elseif ($fechaVencimiento) {
                try { $fv = Carbon::parse($fechaVencimiento); } catch (\Throwable $e) { $fv = null; }
            }

            // Buscar vencimiento más próximo en lotes con stock si no hay fecha en el producto o para robustez
            if ($productoId) {
                try {
                    $minLote = \App\Models\ProductoUbicacion::where('producto_id', $productoId)
                        ->where('cantidad', '>', 0)
                        ->whereNotNull('fecha_vencimiento')
                        ->orderBy('fecha_vencimiento', 'asc')
                        ->value('fecha_vencimiento');
                    if ($minLote) {
                        $minLoteCarbon = Carbon::parse($minLote)->startOfDay();
                        if (!$fv || $minLoteCarbon->lt($fv)) { $fv = $minLoteCarbon; }
                    }
                } catch (\Throwable $e) {
                    \Log::warning('No se pudo obtener vencimiento de lotes para producto '.$productoId.': '.$e->getMessage());
                }
            }

            // Si no hay fecha de vencimiento, usar una fecha muy futura para no marcar por vencer
            if (!$fv) { $fv = Carbon::today()->addYears(50); }

            $diasParaVencer = $hoy->diffInDays($fv, false);

            // 1. Verificar si está vencido
            if ($fv->lt($hoy)) {
                return 'Vencido';
            }
            
            // 2. Verificar si está agotado (stock 0)
            if ($stockActual <= 0) {
                return 'Agotado';
            }
            
            // 3. Verificar si está próximo a vencer (90 días)
            if ($diasParaVencer <= 90) {
                return 'Por vencer';
            }
            
            // 4. Verificar si tiene stock bajo (mayor a 0 pero menor o igual al mínimo)
            if ($stockActual <= $stockMinimo) {
                return 'Bajo stock';
            }
            
            // 5. Estado normal
            return 'Normal';
        } catch (\Exception $e) {
            Log::error('Error en determinarEstado: ' . $e->getMessage());
            return 'Normal'; // Estado por defecto en caso de error
        }
    }

    public function categorias()
    {
        $categorias = Categoria::orderBy('id')->get();
        return view('inventario.categorias', compact('categorias'));
    }

    public function categoriasApi()
    {
        try {
            $categorias = Categoria::orderBy('nombre')
                ->get(['id', 'nombre', 'descripcion', 'estado']);
            return response()->json([
                'success' => true,
                'data' => $categorias
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener categorías: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las categorías'
            ], 500);
        }
    }

    public function presentacion()
    {
        $presentaciones = \App\Models\Presentacion::orderBy('id')->get();
        return view('inventario.presentacion', compact('presentaciones'));
    }

    /**
     * Cambiar estado de categoría (activar/desactivar)
     */
    public function cambiarEstadoCategoria($id)
    {
        try {
            $categoria = Categoria::findOrFail($id);
            // Alternar columna booleana 'activo' si existe; compatibilidad con 'estado'
            if (array_key_exists('activo', $categoria->getAttributes())) {
                $categoria->activo = !$categoria->activo;
            } else {
                $categoria->estado = ($categoria->estado === 'activo') ? 'inactivo' : 'activo';
            }
            $categoria->save();

            $isActive = array_key_exists('activo', $categoria->getAttributes()) ? (bool)$categoria->activo : ($categoria->estado === 'activo');
            return response()->json(['success' => true, 'activo' => $isActive]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo cambiar el estado'], 500);
        }
    }

    /**
     * Cambiar estado de presentación (activar/desactivar)
     */
    public function cambiarEstadoPresentacion($id)
    {
        try {
            $presentacion = \App\Models\Presentacion::findOrFail($id);
            // Alternar columna booleana 'activo' si existe; compatibilidad con 'estado'
            if (array_key_exists('activo', $presentacion->getAttributes())) {
                $presentacion->activo = !$presentacion->activo;
            } else {
                $presentacion->estado = ($presentacion->estado === 'activo') ? 'inactivo' : 'activo';
            }
            $presentacion->save();

            $isActive = array_key_exists('activo', $presentacion->getAttributes()) ? (bool)$presentacion->activo : ($presentacion->estado === 'activo');
            return response()->json(['success' => true, 'activo' => $isActive]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo cambiar el estado'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $producto = Producto::find($id);
            if (!$producto) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Producto no encontrado.'
                ], 404);
            }

            $hasProveedorColumn = Schema::hasColumn('productos', 'proveedor_id');
            $rules = [
                'nombre' => 'required|string|max:255',
                'categoria' => 'required|string|max:255',
                'marca' => 'required|string|max:255',
                // REMOVIDO: 'presentacion' => 'required|string|max:255',
                'concentracion' => 'nullable|string|max:100',
                'lote' => 'required|string|max:100',
                'codigo_barras' => 'required|string|max:255|unique:productos,codigo_barras,' . $id,
                'stock_actual' => 'required|integer|min:0',
                'stock_minimo' => 'required|integer|min:0',
                'precio_compra' => 'required|numeric|gt:0',
                'precio_venta' => 'required|numeric|gt:0|gte:precio_compra',
                // REMOVIDO: 'fecha_fabricacion' => 'required|date|before_or_equal:today',
                'fecha_vencimiento' => 'nullable|date', // Ahora opcional en edición
                'ubicacion' => 'nullable|string|max:255',
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];
            if ($hasProveedorColumn) {
                $rules['proveedor_id'] = 'required|integer|exists:proveedores,id';
            }
            $messages = [
                'concentracion.regex' => 'La concentración debe ser número + unidad (ej: 500mg, 2.5ml, 10%).',
                'precio_compra.gt' => 'El precio de compra debe ser mayor a 0.',
                'precio_venta.gt' => 'El precio de venta debe ser mayor a 0.',
                'precio_venta.gte' => 'El precio de venta debe ser mayor o igual al precio de compra.',
                // REMOVIDO: 'fecha_fabricacion.before_or_equal' => 'La fecha de fabricación no puede ser futura.',
                'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria.',
                'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
                'codigo_barras.unique' => 'Este código de barras ya está registrado.',
            ];
            $validator = Validator::make($request->all(), $rules, $messages);
            $pattern = '/^\d+(\.\d+)?\s*(mg|g|ml|l|mcg|µg|UI|%|mEq|mmol)$/i';
            $validator->after(function($v) use ($request, $pattern) {
                if ($request->filled('concentracion') && !preg_match($pattern, $request->concentracion)) {
                    $v->errors()->add('concentracion', 'La concentración debe ser número + unidad (ej: 500mg, 2.5ml, 10%).');
                }
            });

            if ($validator->fails()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Error de validación.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Preparar datos para actualizar (excluir campos que no deben ser actualizados)
            $datos = $request->except(['_method', '_token', 'imagen', 'id', 'producto_id']);
            if (!$hasProveedorColumn) {
                unset($datos['proveedor_id']);
            }

            // Manejo de la imagen (Cloudinary si está habilitado, sino local)
            if ($request->hasFile('imagen')) {
                $cloudinary = new CloudinaryService();

                if ($cloudinary->isEnabled()) {
                    // Eliminar imagen local anterior si existe y no es URL absoluta
                    if ($producto->imagen 
                        && !preg_match('/^https?:\/\//i', (string) $producto->imagen)
                        && Storage::disk('public')->exists($producto->imagen)) {
                        (new ImageOptimizationService())->deleteProductImage($producto->imagen);
                    }

                    $url = $cloudinary->uploadProductImage($request->file('imagen'), 'productos');
                    if ($url) {
                        $datos['imagen'] = $url;
                    }
                }

                if (!isset($datos['imagen'])) {
                    $imageOptimizer = new ImageOptimizationService();
                    // Eliminar la imagen anterior si existe (solo local)
                    if ($producto->imagen && Storage::disk('public')->exists($producto->imagen)) {
                        $imageOptimizer->deleteProductImage($producto->imagen);
                    }
                    // Optimizar y guardar la nueva imagen localmente
                    $imagePath = $imageOptimizer->optimizeProductImage($request->file('imagen'));
                    $datos['imagen'] = $imagePath;
                }
            }

            // Actualizar el producto
            $producto->update($datos);
            
            // Actualizar presentaciones múltiples
            if ($request->has('presentaciones') && is_array($request->presentaciones)) {
                Log::info('Actualizando presentaciones múltiples para el producto', [
                    'producto_id' => $producto->id,
                    'presentaciones' => $request->presentaciones
                ]);
                
                $existingPresentationIds = $producto->presentaciones->pluck('id')->toArray();
                $updatedPresentationIds = [];
                $loteId = $request->input('lote_id');

                foreach ($request->presentaciones as $presId => $presentacionData) {
                    if (isset($presentacionData['nombre_presentacion']) && isset($presentacionData['unidades_por_presentacion'])) {
                        $currentPresId = null;
                        if (str_starts_with($presId, 'new_')) { // Nueva presentación
                            $nuevaPresentacion = $producto->presentaciones()->create([
                                'nombre_presentacion' => $presentacionData['nombre_presentacion'],
                                'unidades_por_presentacion' => $presentacionData['unidades_por_presentacion'],
                                'precio_venta_presentacion' => $presentacionData['precio_venta_presentacion'] ?? null,
                            ]);
                            $currentPresId = $nuevaPresentacion->id;
                            $updatedPresentationIds[] = $currentPresId;
                            
                            Log::info('Nueva presentación creada', [
                                'id' => $currentPresId,
                                'nombre' => $presentacionData['nombre_presentacion']
                            ]);
                        } else { // Presentación existente
                            $presentacion = \App\Models\ProductoPresentacion::find($presId);
                            if ($presentacion && $presentacion->producto_id == $producto->id) {
                                $presentacion->update([
                                    'nombre_presentacion' => $presentacionData['nombre_presentacion'],
                                    'unidades_por_presentacion' => $presentacionData['unidades_por_presentacion'],
                                    'precio_venta_presentacion' => $presentacionData['precio_venta_presentacion'] ?? null,
                                ]);
                                $currentPresId = $presentacion->id;
                                $updatedPresentationIds[] = $currentPresId;
                                
                                Log::info('Presentación actualizada', [
                                    'id' => $currentPresId,
                                    'nombre' => $presentacionData['nombre_presentacion']
                                ]);
                            }
                        }

                        // Si hay un lote_id, actualizar también en lote_presentaciones
                        if ($loteId && $currentPresId) {
                            DB::table('lote_presentaciones')->updateOrInsert(
                                [
                                    'producto_ubicacion_id' => $loteId,
                                    'producto_presentacion_id' => $currentPresId
                                ],
                                [
                                    'precio_venta' => $presentacionData['precio_venta_presentacion'] ?? 0,
                                    'unidades_por_presentacion' => $presentacionData['unidades_por_presentacion'] ?? 1,
                                    'updated_at' => now()
                                ]
                            );
                        }
                    }
                }
                
                // Eliminar presentaciones que ya no están en el request
                $presentationsToDelete = array_diff($existingPresentationIds, $updatedPresentationIds);
                if (!empty($presentationsToDelete)) {
                    \App\Models\ProductoPresentacion::whereIn('id', $presentationsToDelete)->delete();
                    // También eliminar de lote_presentaciones
                    DB::table('lote_presentaciones')->whereIn('producto_presentacion_id', $presentationsToDelete)->delete();
                    Log::info('Presentaciones eliminadas', ['ids' => $presentationsToDelete]);
                }
            }
            
            // Si se está editando un lote específico, actualizar también el lote en producto_ubicaciones
            if ($request->has('lote_id') && $request->lote_id) {
                $loteId = $request->lote_id;
                $lote = ProductoUbicacion::find($loteId);
                
                if ($lote && $lote->producto_id == $producto->id) {
                    $lote->update([
                        'lote' => $datos['lote'] ?? $lote->lote,
                        'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? $lote->fecha_vencimiento,
                        'cantidad' => $datos['stock_actual'] ?? $lote->cantidad, // ACTUALIZAR CANTIDAD DEL LOTE
                        'precio_compra_lote' => $datos['precio_compra'] ?? $lote->precio_compra_lote,
                        'precio_venta_lote' => $datos['precio_venta'] ?? $lote->precio_venta_lote,
                        'proveedor_id' => $datos['proveedor_id'] ?? $lote->proveedor_id,
                    ]);
                    
                    Log::info('Lote actualizado', [
                        'lote_id' => $loteId,
                        'cantidad' => $lote->cantidad,
                        'precio_compra' => $datos['precio_compra'],
                        'precio_venta' => $datos['precio_venta']
                    ]);
                }
            } else {
                // Si no viene lote_id, pero se cambió el stock_actual, intentar actualizar el primer lote encontrado
                // para mantener la consistencia en productos que solo tienen un lote.
                $primerLote = ProductoUbicacion::where('producto_id', $producto->id)->first();
                if ($primerLote) {
                    $primerLote->update([
                        'lote' => $datos['lote'] ?? $primerLote->lote,
                        'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? $primerLote->fecha_vencimiento,
                        'cantidad' => $datos['stock_actual'] ?? $primerLote->cantidad,
                        'precio_compra_lote' => $datos['precio_compra'] ?? $primerLote->precio_compra_lote,
                        'precio_venta_lote' => $datos['precio_venta'] ?? $primerLote->precio_venta_lote,
                        'proveedor_id' => $datos['proveedor_id'] ?? $primerLote->proveedor_id,
                    ]);
                    Log::info('Primer lote actualizado automáticamente por falta de lote_id');
                }
            }
            
            // SIEMPRE sincronizar el stock_actual del producto con la suma de sus lotes
            // Esto evita que si se editó un lote, el total quede mal si hay otros lotes.
            $nuevoStockTotal = ProductoUbicacion::where('producto_id', $producto->id)->sum('cantidad');
            $producto->update(['stock_actual' => $nuevoStockTotal]);
            
            Log::info('Stock total sincronizado para producto', [
                'producto_id' => $producto->id,
                'nuevo_stock_total' => $nuevoStockTotal
            ]);
            
            // Recalcular el estado del producto después de actualizar
            $producto->fresh()->recalcularEstado();
            
            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Producto actualizado correctamente.',
                'data' => $producto
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar producto: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API para obtener todas las presentaciones
     */
    public function presentacionesApi()
    {
        try {
            $presentaciones = \App\Models\Presentacion::where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre']);
            return response()->json([
                'success' => true,
                'data' => $presentaciones
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener presentaciones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las presentaciones'
            ], 500);
        }
    }

    /**
     * API para obtener un producto específico por ID
     */
    public function getProductoById($id)
    {
        try {
            $producto = Producto::with('proveedor')->findOrFail($id);
            
            // Obtener información detallada de lotes
            $lotesInfo = [];
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('producto_ubicaciones')) {
                    $lotesInfo = $this->loteService->obtenerInfoLotes($id);
                }
            } catch (\Exception $e) {
                Log::warning('Error obteniendo lotes para detalle de producto: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'categoria' => $producto->categoria,
                    'marca' => $producto->marca,
                    'proveedor_id' => $producto->proveedor_id,
                    'proveedor' => $producto->proveedor ? $producto->proveedor->razon_social : null,
                    'presentacion' => $producto->presentacion,
                    'concentracion' => $producto->concentracion,
                    'lote' => $producto->lote, // Mantenemos el legacy por compatibilidad
                    'lotes_detalle' => $lotesInfo['lotes'] ?? [], // Lista detallada de lotes
                    'resumen_lotes' => [
                        'total' => $lotesInfo['total_lotes'] ?? 0,
                        'stock_total' => $lotesInfo['stock_total'] ?? 0,
                        'proximo_vencer' => $lotesInfo['proximo_vencer'] ?? 0,
                        'vencidos' => $lotesInfo['vencidos'] ?? 0
                    ],
                    'codigo_barras' => $producto->codigo_barras,
                    'stock_actual' => $producto->stock_actual,
                    'stock_minimo' => $producto->stock_minimo,
                    'precio_compra' => $producto->precio_compra,
                    'precio_venta' => $producto->precio_venta,
                    'fecha_fabricacion' => $producto->fecha_fabricacion_solo_fecha,
                    'fecha_vencimiento' => $producto->fecha_vencimiento_solo_fecha,
                    'ubicacion' => $producto->ubicacion,
                    'ubicacion_almacen' => $producto->ubicacion_almacen,
                    'imagen' => $producto->imagen,
                    'imagen_url' => $producto->imagen_url,
                    'estado' => $producto->estado
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener producto por ID: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el producto: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Reordena los IDs de los productos para mantener secuencia consecutiva
     * VERSIÓN SEGURA - Solo se ejecuta manualmente
     */
    public function reordenarIds()
    {
        try {
            DB::beginTransaction();
            
            Log::info("🔄 Iniciando reordenamiento seguro de IDs de productos");
            
            // Obtener todos los productos ordenados por ID actual
            $productos = Producto::orderBy('id')->get();
            
            if ($productos->count() === 0) {
                Log::info("ℹ️ No hay productos para reordenar");
                return response()->json([
                    'success' => true,
                    'message' => 'No hay productos para reordenar'
                ]);
            }
            
            // Deshabilitar foreign key checks temporalmente
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // Mapear IDs antiguos a nuevos
            $idMapping = [];
            $counter = 1;
            
            foreach ($productos as $producto) {
                $idMapping[$producto->id] = $counter;
                $counter++;
            }
            
            // Actualizar referencias en tablas relacionadas PRIMERO
            foreach ($idMapping as $oldId => $newId) {
                if ($oldId != $newId) {
                    // Usar IDs temporales negativos para evitar conflictos
                    $tempId = -$newId;
                    
                    // Actualizar referencias en otras tablas
                    DB::statement('UPDATE venta_detalles SET producto_id = ? WHERE producto_id = ?', [$tempId, $oldId]);
                    DB::statement('UPDATE movimientos_stock SET producto_id = ? WHERE producto_id = ?', [$tempId, $oldId]);
                    DB::statement('UPDATE producto_ubicaciones SET producto_id = ? WHERE producto_id = ?', [$tempId, $oldId]);
                    
                    // Actualizar el producto
                    DB::statement('UPDATE productos SET id = ? WHERE id = ?', [$tempId, $oldId]);
                    
                    Log::info("✅ Paso 1: Producto ID {$oldId} → {$tempId} (temporal)");
                }
            }
            
            // Ahora convertir los IDs temporales a los finales
            foreach ($idMapping as $oldId => $newId) {
                if ($oldId != $newId) {
                    $tempId = -$newId;
                    
                    // Actualizar a IDs finales
                    DB::statement('UPDATE venta_detalles SET producto_id = ? WHERE producto_id = ?', [$newId, $tempId]);
                    DB::statement('UPDATE movimientos_stock SET producto_id = ? WHERE producto_id = ?', [$newId, $tempId]);
                    DB::statement('UPDATE producto_ubicaciones SET producto_id = ? WHERE producto_id = ?', [$newId, $tempId]);
                    DB::statement('UPDATE productos SET id = ? WHERE id = ?', [$newId, $tempId]);
                    
                    Log::info("✅ Paso 2: Producto ID {$tempId} → {$newId} (final)");
                }
            }
            
            // Reiniciar el AUTO_INCREMENT
            $nextId = $counter;
            DB::statement("ALTER TABLE productos AUTO_INCREMENT = {$nextId}");
            
            // Reactivar foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            DB::commit();
            
            Log::info("🎉 Reordenamiento completado. Próximo ID será: {$nextId}");
            
            return response()->json([
                'success' => true,
                'message' => "IDs reordenados correctamente. Próximo ID será: {$nextId}",
                'productos_procesados' => $productos->count()
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("❌ Error en reordenamiento de IDs: " . $e->getMessage());
            
            // Asegurar que foreign keys estén activadas
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Exception $fkError) {
                Log::error("Error reactivando foreign keys: " . $fkError->getMessage());
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Error al reordenar IDs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todas las categorías para filtros
     */
    public function obtenerCategorias()
    {
        try {
            $categorias = Categoria::select(['id', 'nombre'])
                ->where('estado', 'activo')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'categorias' => $categorias
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categorías'
            ], 500);
        }
    }

    /**
     * Exportar lista completa de productos a Excel profesional (Triple Fallback)
     */
    public function exportarExcel(Request $request)
    {
        try {
            $search = $request->get('search', '');
            $estado = $request->get('estado', 'todos');
            
            // Usar el servicio de optimización para obtener productos con eager loading
            $query = $this->queryOptimizationService->getProductosOptimizados([
                'search' => $search,
                'estado' => $estado
            ]);
            
            // Cargar relaciones necesarias para el reporte
            $query->with(['presentaciones', 'proveedor', 'categoria_model', 'ubicaciones']);
            
            // Obtener todos los productos (sin paginación para exportar)
            $productos = $query->get();

            // Preparar datos para la vista
            $datos = [
                'productos' => $productos,
                'total' => $productos->count(),
                'titulo' => 'LISTA DE PRODUCTOS - BOTICA SAN ANTONIO',
                'fecha' => now()->format('d/m/Y H:i'),
                'filtros' => [
                    'Búsqueda' => $search ?: 'Ninguna',
                    'Estado' => $estado
                ]
            ];

            // Generar HTML profesional
            $html = view('admin.reportes.productos-excel-template', compact('datos'))->render();
            
            // Añadir BOM para UTF-8 y forzar descarga como Excel
            $bom = chr(239) . chr(187) . chr(191);
            $nombreArchivo = 'Reporte_Productos_' . now()->format('dmY_His') . '.xls';
            
            return response($bom . $html)
                ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
                ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"');
            
        } catch (\Throwable $e) {
            \Log::error('Error al exportar productos a Excel: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al generar el reporte: ' . $e->getMessage());
        }
    }
}
