<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\ProductoApiController;
use App\Http\Controllers\Api\FacturacionController;
use App\Http\Controllers\Api\PdfController;
use App\Http\Controllers\Api\ConfiguracionController;
use App\Http\Controllers\IA\ChatController;
use App\Http\Controllers\IA\ChatLibreController;
use App\Http\Controllers\IA\RecomendacionController;
use App\Http\Controllers\Compra\CompraController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\PosOptimizadoController;
use App\Http\Controllers\ImageOptimizationController;
use App\Http\Controllers\NubeFactController;
use App\Models\Categoria;
use App\Models\Presentacion;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas públicas de autenticación móvil
Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileAuthController::class, 'login']);
    Route::post('/verify-token', [MobileAuthController::class, 'verifyToken']);
    Route::get('/proveedores', [\App\Http\Controllers\Compra\CompraController::class, 'buscarProveedoresApi']);
    Route::get('/categorias', function (Request $request) {
        try {
            // Construir COALESCE dinámico según columnas existentes
            if (Schema::hasTable('categorias')) {
                $catCandidates = ['nombre', 'nombre_categoria', 'categoria', 'name', 'descripcion'];
                $catCols = array_filter($catCandidates, function ($c) { return Schema::hasColumn('categorias', $c); });
                if (!empty($catCols)) {
                    $coalesce = implode(', ', $catCols);
                    $items = DB::table('categorias')
                        ->selectRaw("id, TRIM(COALESCE($coalesce)) as nombre")
                        ->whereRaw("COALESCE($coalesce) IS NOT NULL")
                        ->whereRaw("TRIM(COALESCE($coalesce)) <> ''")
                        ->orderBy('nombre')
                        ->take(500)
                        ->get();
                } else {
                    $items = collect();
                }
            } else {
                $items = collect();
            }
            // Preferir categorías usadas en productos para reflejar BD actual o si source=productos
            if (Schema::hasTable('productos')) {
                $prodCatCandidates = ['categoria', 'nombre_categoria', 'categoria_nombre', 'categoria_descripcion'];
                $prodCatCols = array_filter($prodCatCandidates, function ($c) { return Schema::hasColumn('productos', $c); });
                if (!empty($prodCatCols) || $request->query('source') === 'productos') {
                    $prodCoalesce = implode(', ', $prodCatCols);
                    $prodCats = DB::table('productos')
                        ->selectRaw("TRIM(COALESCE($prodCoalesce)) as nombre")
                        ->whereRaw("COALESCE($prodCoalesce) IS NOT NULL")
                        ->whereRaw("TRIM(COALESCE($prodCoalesce)) <> ''")
                        ->distinct()
                        ->orderBy('nombre')
                        ->take(500)
                        ->get();
                    $nombresProd = $prodCats->pluck('nombre')->map(function($n){ return trim($n); })
                        ->filter(function($n){ return $n !== ''; })
                        ->unique(function($n){ return strtolower($n); })
                        ->sort()->values();
                    if ($nombresProd->isNotEmpty()) {
                        // Si hay nombres usados en productos, devolvemos solo esos
                        $mapIds = $items->mapWithKeys(function($it){ return [strtolower($it->nombre) => $it->id]; });
                        $items = $nombresProd->map(function($n) use ($mapIds) {
                            $id = $mapIds[strtolower($n)] ?? 0;
                            return (object)['id' => $id, 'nombre' => $n];
                        });
                    }
                }
            }
            return response()->json(['success' => true, 'data' => $items]);
        } catch (\Throwable $e) {
            Log::error('mobile categorias error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });

    // Top ventas por período
    Route::get('/presentaciones', function (Request $request) {
        try {
            if (Schema::hasTable('presentaciones')) {
                $items = DB::table('presentaciones')
                    ->select('id', 'nombre')
                    ->where('estado', 'activo')
                    ->orWhere('activo', 1)
                    ->orderBy('nombre')
                    ->get();
                return response()->json(['success' => true, 'data' => $items]);
            }
            return response()->json(['success' => true, 'data' => []]);
        } catch (\Throwable $e) {
            Log::error('mobile presentaciones error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });

    // Top ventas por período
    Route::get('/ventas/top', function (Request $request) {
        try {
            $periodo = intval($request->query('periodo', 30));
            $order = strtolower($request->query('order', 'desc')) === 'asc' ? 'ASC' : 'DESC';
            $limit = intval($request->query('limit', 3));
            if ($limit <= 0 || $limit > 50) $limit = 3;

            $prodTables = ['productos','producto','articulos','items','producto_items','tblProducto'];
            $prodTable = null; foreach ($prodTables as $pt) { if (Schema::hasTable($pt)) { $prodTable = $pt; break; } }
            if (!$prodTable) return response()->json(['success' => true, 'data' => []]);

            $detailTables = ['ventas_detalle','detalle_ventas','venta_detalles','detalles_venta','ventas_items','items_venta','movimientos','kardex','kardex_detalle'];
            $items = [];
            foreach ($detailTables as $dt) {
                if (!Schema::hasTable($dt)) continue;
                $qtyCandidates = ['cantidad','cant','unidades','cantidad_vendida','qty','cantidad_salida','cantidad_venta','salida'];
                $dateCandidates = ['created_at','fecha','fecha_venta','fecha_detalle','fecha_mov','fecha_salida'];
                $prodCandidates = ['producto_id','product_id','id_producto','id_prod','producto'];
                $qtyCol = null; foreach ($qtyCandidates as $c) { if (Schema::hasColumn($dt, $c)) { $qtyCol = $c; break; } }
                $dateCol = null; foreach ($dateCandidates as $c) { if (Schema::hasColumn($dt, $c)) { $dateCol = $c; break; } }
                $prodCol = null; foreach ($prodCandidates as $c) { if (Schema::hasColumn($dt, $c)) { $prodCol = $c; break; } }
                if ($qtyCol && $dateCol && $prodCol) {
                    $sql = "SELECT p.id, TRIM(COALESCE(p.nombre, p.name)) AS nombre, TRIM(COALESCE(p.presentacion, p.concentracion)) AS presentacion,
                                COALESCE(SUM(d.$qtyCol), 0) AS unidades
                            FROM $prodTable p
                            LEFT JOIN $dt d ON d.$prodCol = p.id
                              AND COALESCE(d.$dateCol, NOW()) BETWEEN DATE_SUB(NOW(), INTERVAL ? DAY) AND NOW()
                            GROUP BY p.id, nombre, presentacion
                            ORDER BY unidades $order
                            LIMIT ?";
                // Detecta columnas id/nombre/presentación del producto para el SELECT
                $idCandidates = ['id','IDProducto','id_producto'];
                $nameCandidates = ['nombre','name','proNombre','descripcion','producto'];
                $presCandidates = ['presentacion','concentracion','nombre_presentacion','presentacion_nombre'];
                $idCol = 'id'; foreach ($idCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $idCol = $c; break; } }
                $nameCol = 'nombre'; foreach ($nameCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $nameCol = $c; break; } }
                $presCol = null; foreach ($presCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $presCol = $c; break; } }
                $sql = "SELECT p.$idCol as id, TRIM(COALESCE(p.$nameCol, '')) AS nombre, ".($presCol?"TRIM(COALESCE(p.$presCol, '')) AS presentacion, ":"").
                       "COALESCE(SUM(d.$qtyCol), 0) AS unidades FROM $prodTable p LEFT JOIN $dt d ON d.$prodCol = p.$idCol AND COALESCE(d.$dateCol, NOW()) BETWEEN DATE_SUB(NOW(), INTERVAL ? DAY) AND NOW() GROUP BY p.$idCol, nombre".($presCol?", presentacion":"")." ORDER BY unidades $order LIMIT ?";
                $items = DB::select($sql, [$periodo, $limit]);
                break;
                }
            }

            if (empty($items)) {
                $items = DB::select("SELECT id, TRIM(COALESCE(nombre, name)) AS nombre, TRIM(COALESCE(presentacion, concentracion)) AS presentacion, 0 AS unidades FROM $prodTable ORDER BY id ASC LIMIT $limit");
            }
            return response()->json(['success' => true, 'data' => $items]);
        } catch (\Throwable $e) {
            Log::error('mobile ventas top error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });
    Route::get('/ventas/resumen', function (Request $request) {
        try {
            $periodo = intval($request->query('periodo', 30));
            if ($periodo <= 0 || $periodo > 365) $periodo = 30;
            $ventasCandidates = ['ventas','venta','tblVenta'];
            $detCandidates = ['venta_detalles','detalle_ventas','tblDetalleVenta','detalles_venta'];
            $prodCandidates = ['productos','producto','items','tblProducto'];
            $ventas = null; foreach ($ventasCandidates as $t) { if (Schema::hasTable($t)) { $ventas = $t; break; } }
            $det = null; foreach ($detCandidates as $t) { if (Schema::hasTable($t)) { $det = $t; break; } }
            $prod = null; foreach ($prodCandidates as $t) { if (Schema::hasTable($t)) { $prod = $t; break; } }
            if (!$ventas || !$det || !$prod) return response()->json(['success' => true, 'data' => ['total_unidades' => 0, 'items' => []]]);
            $dateCols = ['fecha_venta','fecha','created_at'];
            $qtyCols = ['cantidad','qty','unidades'];
            $prodIdCols = ['producto_id','id_producto','producto','idProd'];
            $ventaIdCols = ['venta_id','id_venta'];
            $prodNameCols = ['nombre','name'];
            $dateCol = null; foreach ($dateCols as $c) { if (Schema::hasColumn($ventas, $c)) { $dateCol = $c; break; } }
            if (!$dateCol) $dateCol = 'created_at';
            $qtyCol = null; foreach ($qtyCols as $c) { if (Schema::hasColumn($det, $c)) { $qtyCol = $c; break; } }
            if (!$qtyCol) $qtyCol = 'cantidad';
            $prodIdCol = null; foreach ($prodIdCols as $c) { if (Schema::hasColumn($det, $c)) { $prodIdCol = $c; break; } }
            if (!$prodIdCol) $prodIdCol = 'producto_id';
            $ventaIdCol = null; foreach ($ventaIdCols as $c) { if (Schema::hasColumn($det, $c)) { $ventaIdCol = $c; break; } }
            if (!$ventaIdCol) $ventaIdCol = 'venta_id';
            $prodNameCol = null; foreach ($prodNameCols as $c) { if (Schema::hasColumn($prod, $c)) { $prodNameCol = $c; break; } }
            if (!$prodNameCol) $prodNameCol = 'nombre';
            $desde = Carbon\Carbon::now('America/Lima')->subDays($periodo);
            $prodPkCandidates = ['id','IDProducto','id_producto'];
            $prodPk = null; foreach ($prodPkCandidates as $c) { if (Schema::hasColumn($prod, $c)) { $prodPk = $c; break; } }
            if (!$prodPk) { $prodPk = 'id'; }
            $q = DB::table($det)
                ->join($ventas, "$det.$ventaIdCol", '=', "$ventas.id")
                ->join($prod, "$det.$prodIdCol", '=', "$prod.$prodPk")
                ->where(DB::raw("COALESCE($ventas.$dateCol, $ventas.created_at)"), '>=', $desde)
                ->select("$prod.$prodNameCol as nombre", DB::raw("SUM($det.$qtyCol) as unidades"));
            if (Schema::hasColumn($ventas,'estado')) { $q->where("$ventas.estado", 'completada'); }
            $items = $q->groupBy("$prod.$prodNameCol")->orderBy('unidades','desc')->limit(50)->get();
            $total = $items->sum('unidades');
            return response()->json(['success' => true, 'data' => ['total_unidades' => (int)$total, 'items' => $items]]);
        } catch (\Throwable $e) {
            Log::error('mobile ventas resumen error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => ['total_unidades' => 0, 'items' => []]]);
        }
    });
    Route::get('/ventas/categorias/top', function (Request $request) {
        try {
            $periodo = intval($request->query('periodo', 30));
            if ($periodo <= 0 || $periodo > 365) $periodo = 30;
            $limit = intval($request->query('limit', 20));
            if ($limit <= 0 || $limit > 100) $limit = 20;
            $ventasCandidates = ['ventas','venta','tblVenta'];
            $detCandidates = ['venta_detalles','detalle_ventas','tblDetalleVenta','detalles_venta'];
            $prodCandidates = ['productos','producto','items','tblProducto'];
            $catCandidates = ['categorias','categoria'];
            $ventas = null; foreach ($ventasCandidates as $t) { if (Schema::hasTable($t)) { $ventas = $t; break; } }
            $det = null; foreach ($detCandidates as $t) { if (Schema::hasTable($t)) { $det = $t; break; } }
            $prod = null; foreach ($prodCandidates as $t) { if (Schema::hasTable($t)) { $prod = $t; break; } }
            $cats = null; foreach ($catCandidates as $t) { if (Schema::hasTable($t)) { $cats = $t; break; } }
            if (!$ventas || !$det || !$prod) return response()->json(['success' => true, 'data' => []]);
            $desde = Carbon\Carbon::now('America/Lima')->subDays($periodo);
            $dateCols = ['fecha_venta','fecha','created_at'];
            $qtyCols = ['cantidad','qty','unidades'];
            $prodIdCols = ['producto_id','id_producto','producto','idProd'];
            $ventaIdCols = ['venta_id','id_venta'];
            $catNameCols = ['categoria','nombre','name'];
            $prodCatIdCols = ['categoria_id','id_categoria','category_id'];
            $prodCatTextCols = ['categoria','categoria_nombre','category','nombre_categoria'];
            $dateCol = null; foreach ($dateCols as $c) { if (Schema::hasColumn($ventas, $c)) { $dateCol = $c; break; } }
            if (!$dateCol) $dateCol = 'created_at';
            $qtyCol = null; foreach ($qtyCols as $c) { if (Schema::hasColumn($det, $c)) { $qtyCol = $c; break; } }
            if (!$qtyCol) $qtyCol = 'cantidad';
            $prodIdCol = null; foreach ($prodIdCols as $c) { if (Schema::hasColumn($det, $c)) { $prodIdCol = $c; break; } }
            if (!$prodIdCol) $prodIdCol = 'producto_id';
            $ventaIdCol = null; foreach ($ventaIdCols as $c) { if (Schema::hasColumn($det, $c)) { $ventaIdCol = $c; break; } }
            if (!$ventaIdCol) $ventaIdCol = 'venta_id';
            $prodCatTextCol = null; foreach ($prodCatTextCols as $c) { if (Schema::hasColumn($prod, $c)) { $prodCatTextCol = $c; break; } }
            $prodCatIdCol = null; foreach ($prodCatIdCols as $c) { if (Schema::hasColumn($prod, $c)) { $prodCatIdCol = $c; break; } }
            if ($prodCatTextCol) {
                $prodPkCandidates = ['id','IDProducto','id_producto'];
                $prodPk = null; foreach ($prodPkCandidates as $c) { if (Schema::hasColumn($prod, $c)) { $prodPk = $c; break; } }
                if (!$prodPk) { $prodPk = 'id'; }
                $items = DB::table($det)
                    ->join($ventas, "$det.$ventaIdCol", '=', "$ventas.id")
                    ->join($prod, "$det.$prodIdCol", '=', "$prod.$prodPk")
                    ->where(DB::raw("COALESCE($ventas.$dateCol, $ventas.created_at)"), '>=', $desde)
                    ->select(DB::raw("TRIM(COALESCE($prod.$prodCatTextCol, 'Sin categoría')) as nombre"), DB::raw("SUM($det.$qtyCol) as unidades"));
                if (Schema::hasColumn($ventas,'estado')) { $items->where("$ventas.estado", 'completada'); }
                $items = $items->groupBy(DB::raw("TRIM(COALESCE($prod.$prodCatTextCol, 'Sin categoría'))"))->orderBy('unidades','desc')->limit($limit)->get();
                return response()->json(['success' => true, 'data' => $items]);
            }
            if ($cats && $prodCatIdCol) {
                $catsNameCol = null; foreach ($catNameCols as $c) { if (Schema::hasColumn($cats, $c)) { $catsNameCol = $c; break; } }
                if (!$catsNameCol) $catsNameCol = 'nombre';
                $prodPkCandidates = ['id','IDProducto','id_producto'];
                $prodPk = null; foreach ($prodPkCandidates as $c) { if (Schema::hasColumn($prod, $c)) { $prodPk = $c; break; } }
                if (!$prodPk) { $prodPk = 'id'; }
                $items = DB::table($det)
                    ->join($ventas, "$det.$ventaIdCol", '=', "$ventas.id")
                    ->join($prod, "$det.$prodIdCol", '=', "$prod.$prodPk")
                    ->join($cats, "$prod.$prodCatIdCol", '=', "$cats.id")
                    ->where(DB::raw("COALESCE($ventas.$dateCol, $ventas.created_at)"), '>=', $desde)
                    ->select("$cats.$catsNameCol as nombre", DB::raw("SUM($det.$qtyCol) as unidades"));
                if (Schema::hasColumn($ventas,'estado')) { $items->where("$ventas.estado", 'completada'); }
                $items = $items->groupBy("$cats.$catsNameCol")->orderBy('unidades','desc')->limit($limit)->get();
                return response()->json(['success' => true, 'data' => $items]);
            }
            return response()->json(['success' => true, 'data' => []]);
        } catch (\Throwable $e) {
            Log::error('mobile ventas categorias top error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });
    Route::get('/ventas/categorias/resumen', function (Request $request) {
        try {
            $periodo = intval($request->query('periodo', 30));
            if ($periodo <= 0 || $periodo > 365) $periodo = 30;
            $ventasCandidates = ['ventas','venta','tblVenta'];
            $detCandidates = ['venta_detalles','detalle_ventas','tblDetalleVenta','detalles_venta'];
            $prodCandidates = ['productos','producto','items','tblProducto'];
            $catCandidates = ['categorias','categoria'];
            $ventas = null; foreach ($ventasCandidates as $t) { if (Schema::hasTable($t)) { $ventas = $t; break; } }
            $det = null; foreach ($detCandidates as $t) { if (Schema::hasTable($t)) { $det = $t; break; } }
            $prod = null; foreach ($prodCandidates as $t) { if (Schema::hasTable($t)) { $prod = $t; break; } }
            $cats = null; foreach ($catCandidates as $t) { if (Schema::hasTable($t)) { $cats = $t; break; } }
            if (!$ventas || !$det || !$prod) return response()->json(['success' => true, 'data' => ['total_unidades' => 0, 'items' => []]]);
            $desde = Carbon\Carbon::now('America/Lima')->subDays($periodo);
            $dateCols = ['fecha_venta','fecha','created_at'];
            $qtyCols = ['cantidad','qty','unidades'];
            $prodIdCols = ['producto_id','id_producto','producto','idProd'];
            $ventaIdCols = ['venta_id','id_venta'];
            $catNameCols = ['categoria','nombre','name'];
            $prodCatIdCols = ['categoria_id','id_categoria','category_id'];
            $prodCatTextCols = ['categoria','categoria_nombre','category','nombre_categoria'];
            $dateCol = null; foreach ($dateCols as $c) { if (Schema::hasColumn($ventas, $c)) { $dateCol = $c; break; } }
            if (!$dateCol) $dateCol = 'created_at';
            $qtyCol = null; foreach ($qtyCols as $c) { if (Schema::hasColumn($det, $c)) { $qtyCol = $c; break; } }
            if (!$qtyCol) $qtyCol = 'cantidad';
            $prodIdCol = null; foreach ($prodIdCols as $c) { if (Schema::hasColumn($det, $c)) { $prodIdCol = $c; break; } }
            if (!$prodIdCol) $prodIdCol = 'producto_id';
            $ventaIdCol = null; foreach ($ventaIdCols as $c) { if (Schema::hasColumn($det, $c)) { $ventaIdCol = $c; break; } }
            if (!$ventaIdCol) $ventaIdCol = 'venta_id';
            $prodCatTextCol = null; foreach ($prodCatTextCols as $c) { if (Schema::hasColumn($prod, $c)) { $prodCatTextCol = $c; break; } }
            $prodCatIdCol = null; foreach ($prodCatIdCols as $c) { if (Schema::hasColumn($prod, $c)) { $prodCatIdCol = $c; break; } }
            if ($prodCatTextCol) {
                $prodPkCandidates = ['id','IDProducto','id_producto'];
                $prodPk = null; foreach ($prodPkCandidates as $c) { if (Schema::hasColumn($prod, $c)) { $prodPk = $c; break; } }
                if (!$prodPk) { $prodPk = 'id'; }
                $items = DB::table($det)
                    ->join($ventas, "$det.$ventaIdCol", '=', "$ventas.id")
                    ->join($prod, "$det.$prodIdCol", '=', "$prod.$prodPk")
                    ->where(DB::raw("COALESCE($ventas.$dateCol, $ventas.created_at)"), '>=', $desde)
                    ->select(DB::raw("TRIM(COALESCE($prod.$prodCatTextCol, 'Sin categoría')) as nombre"), DB::raw("SUM($det.$qtyCol) as unidades"));
                if (Schema::hasColumn($ventas,'estado')) { $items->where("$ventas.estado", 'completada'); }
                $items = $items->groupBy(DB::raw("TRIM(COALESCE($prod.$prodCatTextCol, 'Sin categoría'))"))->orderBy('unidades','desc')->get();
                $total = $items->sum('unidades');
                return response()->json(['success' => true, 'data' => ['total_unidades' => (int)$total, 'items' => $items]]);
            }
            if ($cats && $prodCatIdCol) {
                $catsNameCol = null; foreach ($catNameCols as $c) { if (Schema::hasColumn($cats, $c)) { $catsNameCol = $c; break; } }
                if (!$catsNameCol) $catsNameCol = 'nombre';
                $prodPkCandidates = ['id','IDProducto','id_producto'];
                $prodPk = null; foreach ($prodPkCandidates as $c) { if (Schema::hasColumn($prod, $c)) { $prodPk = $c; break; } }
                if (!$prodPk) { $prodPk = 'id'; }
                $items = DB::table($det)
                    ->join($ventas, "$det.$ventaIdCol", '=', "$ventas.id")
                    ->join($prod, "$det.$prodIdCol", '=', "$prod.$prodPk")
                    ->join($cats, "$prod.$prodCatIdCol", '=', "$cats.id")
                    ->where(DB::raw("COALESCE($ventas.$dateCol, $ventas.created_at)"), '>=', $desde)
                    ->select("$cats.$catsNameCol as nombre", DB::raw("SUM($det.$qtyCol) as unidades"));
                if (Schema::hasColumn($ventas,'estado')) { $items->where("$ventas.estado", 'completada'); }
                $items = $items->groupBy("$cats.$catsNameCol")->orderBy('unidades','desc')->get();
                $total = $items->sum('unidades');
                return response()->json(['success' => true, 'data' => ['total_unidades' => (int)$total, 'items' => $items]]);
            }
            return response()->json(['success' => true, 'data' => ['total_unidades' => 0, 'items' => []]]);
        } catch (\Throwable $e) {
            Log::error('mobile ventas categorias resumen error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => ['total_unidades' => 0, 'items' => []]]);
        }
    });
    Route::get('/ventas/vendedores/top', function (Request $request) {
        try {
            $periodo = intval($request->query('periodo', 30));
            if ($periodo <= 0 || $periodo > 365) $periodo = 30;
            $limit = intval($request->query('limit', 20));
            if ($limit <= 0 || $limit > 100) $limit = 20;
            $ventasCandidates = ['ventas','venta','tblVenta'];
            $detCandidates = ['venta_detalles','detalle_ventas','tblDetalleVenta','detalles_venta'];
            $userCandidates = ['empleados','usuarios','vendedores'];
            $ventas = null; foreach ($ventasCandidates as $t) { if (Schema::hasTable($t)) { $ventas = $t; break; } }
            $det = null; foreach ($detCandidates as $t) { if (Schema::hasTable($t)) { $det = $t; break; } }
            if (!$ventas || !$det) return response()->json(['success' => true, 'data' => []]);
            $desde = Carbon\Carbon::now('America/Lima')->subDays($periodo);
            $dateCols = ['fecha_venta','fecha','created_at'];
            $qtyCols = ['cantidad','qty','unidades'];
            $ventaIdCols = ['venta_id','id_venta'];
            $userIdCols = ['empleado_id','usuario_id','vendedor_id'];
            $userNameCols = ['nombre','name','usuario','empleado'];
            $dateCol = null; foreach ($dateCols as $c) { if (Schema::hasColumn($ventas, $c)) { $dateCol = $c; break; } }
            if (!$dateCol) $dateCol = 'created_at';
            $qtyCol = null; foreach ($qtyCols as $c) { if (Schema::hasColumn($det, $c)) { $qtyCol = $c; break; } }
            if (!$qtyCol) $qtyCol = 'cantidad';
            $ventaIdCol = null; foreach ($ventaIdCols as $c) { if (Schema::hasColumn($det, $c)) { $ventaIdCol = $c; break; } }
            if (!$ventaIdCol) $ventaIdCol = 'venta_id';
            $userIdCol = null; foreach ($userIdCols as $c) { if (Schema::hasColumn($ventas, $c)) { $userIdCol = $c; break; } }
            $userTable = null; foreach ($userCandidates as $t) { if (Schema::hasTable($t)) { $userTable = $t; break; } }
            if ($userIdCol && $userTable) {
                $userNameCol = null; foreach ($userNameCols as $c) { if (Schema::hasColumn($userTable, $c)) { $userNameCol = $c; break; } }
                if (!$userNameCol) $userNameCol = 'nombre';
                $items = DB::table($det)
                    ->join($ventas, "$det.$ventaIdCol", '=', "$ventas.id")
                    ->join($userTable, "$ventas.$userIdCol", '=', "$userTable.id")
                    ->where(DB::raw("COALESCE($ventas.$dateCol, $ventas.created_at)"), '>=', $desde)
                    ->select("$userTable.$userNameCol as nombre", DB::raw("SUM($det.$qtyCol) as unidades"));
                if (Schema::hasColumn($ventas,'estado')) { $items->where("$ventas.estado", 'completada'); }
                $items = $items->groupBy("$userTable.$userNameCol")->orderBy('unidades','desc')->limit($limit)->get();
                return response()->json(['success' => true, 'data' => $items]);
            }
            $vendTextCols = ['vendedor','usuario','atendido_por'];
            $vendTextCol = null; foreach ($vendTextCols as $c) { if (Schema::hasColumn($ventas, $c)) { $vendTextCol = $c; break; } }
            if ($vendTextCol) {
                $items = DB::table($det)
                    ->join($ventas, "$det.$ventaIdCol", '=', "$ventas.id")
                    ->where(DB::raw("COALESCE($ventas.$dateCol, $ventas.created_at)"), '>=', $desde)
                    ->select(DB::raw("TRIM(COALESCE($ventas.$vendTextCol,'-')) as nombre"), DB::raw("SUM($det.$qtyCol) as unidades"))
                    ->groupBy(DB::raw("TRIM(COALESCE($ventas.$vendTextCol,'-'))"))
                    ->orderBy('unidades','desc')
                    ->limit($limit)
                    ->get();
                return response()->json(['success' => true, 'data' => $items]);
            }
            return response()->json(['success' => true, 'data' => []]);
        } catch (\Throwable $e) {
            Log::error('mobile ventas vendedores top error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });
    Route::get('/ventas/clientes/top', function (Request $request) {
        try {
            $periodo = intval($request->query('periodo', 30));
            if ($periodo <= 0 || $periodo > 365) $periodo = 30;
            $limit = intval($request->query('limit', 20));
            if ($limit <= 0 || $limit > 100) $limit = 20;
            $ventasCandidates = ['ventas','venta','tblVenta'];
            $detCandidates = ['venta_detalles','detalle_ventas','tblDetalleVenta','detalles_venta'];
            $cliCandidates = ['clientes','tblCliente','cliente'];
            $ventas = null; foreach ($ventasCandidates as $t) { if (Schema::hasTable($t)) { $ventas = $t; break; } }
            $det = null; foreach ($detCandidates as $t) { if (Schema::hasTable($t)) { $det = $t; break; } }
            if (!$ventas || !$det) return response()->json(['success' => true, 'data' => []]);
            $desde = Carbon\Carbon::now('America/Lima')->subDays($periodo);
            $dateCols = ['fecha_venta','fecha','created_at'];
            $qtyCols = ['cantidad','qty','unidades'];
            $ventaIdCols = ['venta_id','id_venta'];
            $cliIdCols = ['cliente_id','id_cliente'];
            $cliNameCols = ['nombre','name','razon_social'];
            $dateCol = null; foreach ($dateCols as $c) { if (Schema::hasColumn($ventas, $c)) { $dateCol = $c; break; } }
            if (!$dateCol) $dateCol = 'created_at';
            $qtyCol = null; foreach ($qtyCols as $c) { if (Schema::hasColumn($det, $c)) { $qtyCol = $c; break; } }
            if (!$qtyCol) $qtyCol = 'cantidad';
            $ventaIdCol = null; foreach ($ventaIdCols as $c) { if (Schema::hasColumn($det, $c)) { $ventaIdCol = $c; break; } }
            if (!$ventaIdCol) $ventaIdCol = 'venta_id';
            $cliIdCol = null; foreach ($cliIdCols as $c) { if (Schema::hasColumn($ventas, $c)) { $cliIdCol = $c; break; } }
            $cliTable = null; foreach ($cliCandidates as $t) { if (Schema::hasTable($t)) { $cliTable = $t; break; } }
            if ($cliIdCol && $cliTable) {
                $cliNameCol = null; foreach ($cliNameCols as $c) { if (Schema::hasColumn($cliTable, $c)) { $cliNameCol = $c; break; } }
                if (!$cliNameCol) $cliNameCol = 'nombre';
                $items = DB::table($det)
                    ->join($ventas, "$det.$ventaIdCol", '=', "$ventas.id")
                    ->join($cliTable, "$ventas.$cliIdCol", '=', "$cliTable.id")
                    ->where(DB::raw("COALESCE($ventas.$dateCol, $ventas.created_at)"), '>=', $desde)
                    ->select("$cliTable.$cliNameCol as nombre", DB::raw("SUM($det.$qtyCol) as unidades"));
                if (Schema::hasColumn($ventas,'estado')) { $items->where("$ventas.estado", 'completada'); }
                $items = $items->groupBy("$cliTable.$cliNameCol")->orderBy('unidades','desc')->limit($limit)->get();
                return response()->json(['success' => true, 'data' => $items]);
            }
            $cliTextCols = ['cliente','cliente_nombre'];
            $cliTextCol = null; foreach ($cliTextCols as $c) { if (Schema::hasColumn($ventas, $c)) { $cliTextCol = $c; break; } }
            if ($cliTextCol) {
                $items = DB::table($det)
                    ->join($ventas, "$det.$ventaIdCol", '=', "$ventas.id")
                    ->where(DB::raw("COALESCE($ventas.$dateCol, $ventas.created_at)"), '>=', $desde)
                    ->select(DB::raw("TRIM(COALESCE($ventas.$cliTextCol,'-')) as nombre"), DB::raw("SUM($det.$qtyCol) as unidades"))
                    ->groupBy(DB::raw("TRIM(COALESCE($ventas.$cliTextCol,'-'))"))
                    ->orderBy('unidades','desc')
                    ->limit($limit)
                    ->get();
                return response()->json(['success' => true, 'data' => $items]);
            }
            return response()->json(['success' => true, 'data' => []]);
        } catch (\Throwable $e) {
            Log::error('mobile ventas clientes top error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });
    Route::get('/stock/critico-categoria', function (Request $request) {
        try {
            $limit = intval($request->query('limit', 50));
            if ($limit <= 0 || $limit > 500) $limit = 50;
            $cat = trim($request->query('categoria', ''));
            $prodTables = ['productos','producto','articulos','items','producto_items'];
            $prodTable = null; foreach ($prodTables as $pt) { if (Schema::hasTable($pt)) { $prodTable = $pt; break; } }
            if (!$prodTable || !$cat) return response()->json(['success' => true, 'data' => []]);
            $stockCandidates = ['stock_actual','stock','existencia','cantidad_disponible','qty_disponible'];
            $minCandidates = ['stock_minimo','min_stock','minimo','stock_min','min'];
            $catCandidates = ['categoria','category','nombre_categoria','categoria_nombre'];
            $stockCol = null; foreach ($stockCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $stockCol = $c; break; } }
            $minCol = null; foreach ($minCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $minCol = $c; break; } }
            $catCol = null; foreach ($catCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $catCol = $c; break; } }
            if (!$catCol) return response()->json(['success' => true, 'data' => []]);
            if ($stockCol && $minCol) {
                $items = DB::table($prodTable)
                    ->selectRaw("id, TRIM(COALESCE(nombre, name)) AS nombre, TRIM(COALESCE(presentacion, concentracion)) AS presentacion, COALESCE($stockCol,0) AS stock, COALESCE($minCol,0) AS minimo")
                    ->where($catCol, 'like', '%'.$cat.'%')
                    ->whereRaw("COALESCE($stockCol,0) <= COALESCE($minCol,0)")
                    ->orderBy('stock','asc')
                    ->take($limit)
                    ->get();
                return response()->json(['success' => true, 'data' => $items]);
            }
            return response()->json(['success' => true, 'data' => []]);
        } catch (\Throwable $e) {
            Log::error('mobile stock critico categoria error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });

    // Productos agotados (stock <= 0)
    Route::get('/stock/agotados', function (Request $request) {
        try {
            $limit = intval($request->query('limit', 50));
            if ($limit <= 0 || $limit > 500) $limit = 50;
            $prodTables = ['productos','producto','articulos','items','producto_items','tblProducto'];
            $prodTable = null; foreach ($prodTables as $pt) { if (Schema::hasTable($pt)) { $prodTable = $pt; break; } }
            if (!$prodTable) return response()->json(['success' => true, 'data' => []]);
            $stockCandidates = ['stock_actual','stock','existencia','cantidad_disponible','qty_disponible'];
            $stockCol = null; foreach ($stockCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $stockCol = $c; break; } }
            $idCandidates = ['id','IDProducto','id_producto','codigo','codigo_producto'];
            $nameCandidates = ['nombre','name','proNombre','descripcion','producto'];
            $presCandidates = ['presentacion','concentracion','nombre_presentacion','presentacion_nombre'];
            $idCol = null; foreach ($idCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $idCol = $c; break; } }
            $nameCol = null; foreach ($nameCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $nameCol = $c; break; } }
            $presCol = null; foreach ($presCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $presCol = $c; break; } }
            // Búsqueda dinámica si no se encontró columna directa
            if (!$stockCol) {
                $rows = DB::select("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$prodTable]);
                foreach ($rows as $r) {
                    $lc = strtolower($r->COLUMN_NAME);
                    if (str_contains($lc,'stock') || str_contains($lc,'existencia') || str_contains($lc,'dispon')) { $stockCol = $r->COLUMN_NAME; break; }
                }
            }
            if (!$idCol || !$nameCol || !$presCol) {
                $rows = DB::select("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$prodTable]);
                foreach ($rows as $r) {
                    $lc = strtolower($r->COLUMN_NAME);
                    if (!$idCol && ($lc==='id' || str_contains($lc,'idproducto') || $lc==='id_producto' || str_contains($lc,'codigo'))) { $idCol = $r->COLUMN_NAME; }
                    if (!$nameCol && ($lc==='nombre' || $lc==='name' || str_contains($lc,'pronombre') || str_contains($lc,'descripcion') || str_contains($lc,'producto'))) { $nameCol = $r->COLUMN_NAME; }
                    if (!$presCol && (str_contains($lc,'presenta') || str_contains($lc,'concentr'))) { $presCol = $r->COLUMN_NAME; }
                }
            }
            if ($stockCol) {
                $items = DB::table($prodTable)
                    ->selectRaw("$idCol as id, TRIM(COALESCE($nameCol, '')) AS nombre, ".($presCol?"TRIM(COALESCE($presCol,'')) AS presentacion, ":"")."COALESCE($stockCol,0) AS stock")
                    ->whereRaw("COALESCE($stockCol,0) <= 0")
                    ->orderBy('nombre')
                    ->take($limit)
                    ->get();
                return response()->json(['success' => true, 'data' => $items]);
            }
            return response()->json(['success' => true, 'data' => []]);
        } catch (\Throwable $e) {
            Log::error('mobile stock agotados error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });

    // Productos con stock crítico (stock <= stock_minimo)
    Route::get('/stock/critico', function (Request $request) {
        try {
            $limit = intval($request->query('limit', 50));
            if ($limit <= 0 || $limit > 500) $limit = 50;
            $prodTables = ['productos','producto','articulos','items','producto_items','tblProducto'];
            $prodTable = null; foreach ($prodTables as $pt) { if (Schema::hasTable($pt)) { $prodTable = $pt; break; } }
            if (!$prodTable) return response()->json(['success' => true, 'data' => []]);
            $stockCandidates = ['stock_actual','stock','existencia','cantidad_disponible','qty_disponible'];
            $minCandidates = ['stock_minimo','min_stock','minimo','stock_min','min'];
            $stockCol = null; foreach ($stockCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $stockCol = $c; break; } }
            $minCol = null; foreach ($minCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $minCol = $c; break; } }
            $idCandidates = ['id','IDProducto','id_producto','codigo','codigo_producto'];
            $nameCandidates = ['nombre','name','proNombre','descripcion','producto'];
            $presCandidates = ['presentacion','concentracion','nombre_presentacion','presentacion_nombre'];
            $idCol = null; foreach ($idCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $idCol = $c; break; } }
            $nameCol = null; foreach ($nameCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $nameCol = $c; break; } }
            $presCol = null; foreach ($presCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $presCol = $c; break; } }
            if (!$stockCol || !$minCol) {
                $rows = DB::select("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$prodTable]);
                foreach ($rows as $r) {
                    $lc = strtolower($r->COLUMN_NAME);
                    if (!$stockCol && (str_contains($lc,'stock') || str_contains($lc,'existencia') || str_contains($lc,'dispon'))) { $stockCol = $r->COLUMN_NAME; }
                    if (!$minCol && (str_contains($lc,'min') || str_contains($lc,'minimo'))) { $minCol = $r->COLUMN_NAME; }
                    if (!$idCol && ($lc==='id' || str_contains($lc,'idproducto') || $lc==='id_producto' || str_contains($lc,'codigo'))) { $idCol = $r->COLUMN_NAME; }
                    if (!$nameCol && ($lc==='nombre' || $lc==='name' || str_contains($lc,'pronombre') || str_contains($lc,'descripcion') || str_contains($lc,'producto'))) { $nameCol = $r->COLUMN_NAME; }
                    if (!$presCol && (str_contains($lc,'presenta') || str_contains($lc,'concentr'))) { $presCol = $r->COLUMN_NAME; }
                }
            }
            if ($stockCol && $minCol) {
                $items = DB::table($prodTable)
                    ->selectRaw("$idCol as id, TRIM(COALESCE($nameCol,'')) AS nombre, ".($presCol?"TRIM(COALESCE($presCol,'')) AS presentacion, ":"")."COALESCE($stockCol,0) AS stock, COALESCE($minCol,0) AS minimo")
                    ->whereRaw("COALESCE($stockCol,0) <= COALESCE($minCol,0)")
                    ->orderBy('nombre')
                    ->take($limit)
                    ->get();
                return response()->json(['success' => true, 'data' => $items]);
            }
            return response()->json(['success' => true, 'data' => []]);
        } catch (\Throwable $e) {
            Log::error('mobile stock critico error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });
    Route::get('/productos/por-vencer', function (Request $request) {
        try {
            $dias = intval($request->query('dias', 30));
            if ($dias <= 0 || $dias > 365) $dias = 30;
            $limit = intval($request->query('limit', 50));
            if ($limit <= 0 || $limit > 500) $limit = 50;
            $prodTables = ['productos','producto','articulos','items','producto_items','tblProducto'];
            $prodTable = null; foreach ($prodTables as $pt) { if (Schema::hasTable($pt)) { $prodTable = $pt; break; } }
            if (!$prodTable) return response()->json(['success' => true, 'data' => []]);
            $fechaCandidates = ['fecha_vencimiento','vencimiento','vence','fecha_caducidad','caducidad','expira'];
            $stockCandidates = ['stock_actual','stock','existencia','cantidad_disponible','qty_disponible'];
            $fechaCol = null; foreach ($fechaCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $fechaCol = $c; break; } }
            $stockCol = null; foreach ($stockCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $stockCol = $c; break; } }
            if (!$fechaCol) {
                $rows = DB::select("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$prodTable]);
                foreach ($rows as $r) {
                    $lc = strtolower($r->COLUMN_NAME);
                    if (str_contains($lc,'venci') || str_contains($lc,'caduc') || str_contains($lc,'expir')) { $fechaCol = $r->COLUMN_NAME; break; }
                }
            }
            if (!$stockCol) {
                $rows = DB::select("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$prodTable]);
                foreach ($rows as $r) {
                    $lc = strtolower($r->COLUMN_NAME);
                    if (str_contains($lc,'stock') || str_contains($lc,'existencia') || str_contains($lc,'dispon')) { $stockCol = $r->COLUMN_NAME; break; }
                }
            }
            $idCandidates = ['id','IDProducto','id_producto','codigo','codigo_producto'];
            $nameCandidates = ['nombre','name','proNombre','descripcion','producto'];
            $presCandidates = ['presentacion','concentracion','nombre_presentacion','presentacion_nombre'];
            $idCol = null; foreach ($idCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $idCol = $c; break; } }
            $nameCol = null; foreach ($nameCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $nameCol = $c; break; } }
            $presCol = null; foreach ($presCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $presCol = $c; break; } }
            if ($fechaCol) {
                $hoy = Carbon\Carbon::today();
                $lim = Carbon\Carbon::today()->addDays($dias);
                $items = DB::table($prodTable)
                    ->selectRaw("$idCol as id, TRIM(COALESCE($nameCol,'')) AS nombre, ".($presCol?"TRIM(COALESCE($presCol,'')) AS presentacion, ":"").($stockCol?"COALESCE($stockCol,0) AS stock, ":" ")."DATE_FORMAT($fechaCol, '%Y-%m-%d') AS vence")
                    ->whereDate($fechaCol, '>', $hoy)
                    ->whereDate($fechaCol, '<=', $lim)
                    ->orderBy($fechaCol)
                    ->take($limit)
                    ->get();
                return response()->json(['success' => true, 'data' => $items]);
            }
            return response()->json(['success' => true, 'data' => []]);
        } catch (\Throwable $e) {
            Log::error('mobile productos por vencer error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });
    // Conteo total de productos
    Route::get('/productos/count', function () {
        try {
            $prodTables = ['productos','producto','articulos','items','producto_items'];
            $prodTable = null; foreach ($prodTables as $pt) { if (Schema::hasTable($pt)) { $prodTable = $pt; break; } }
            if (!$prodTable) return response()->json(['success' => true, 'data' => ['count' => 0]]);
            $row = DB::select("SELECT COUNT(*) AS c FROM $prodTable");
            $count = !empty($row) ? (int) $row[0]->c : 0;
            return response()->json(['success' => true, 'data' => ['count' => $count]]);
        } catch (\Throwable $e) {
            Log::error('mobile productos count error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => ['count' => 0]]);
        }
    });
    Route::get('/presentaciones', function (Request $request) {
        try {
            if (Schema::hasTable('presentaciones')) {
                // Consulta RAW directa para máxima compatibilidad
                $items = DB::select("SELECT id, TRIM(COALESCE(nombre, nombre_presentacion, presentacion, descripcion)) AS nombre
                                      FROM presentaciones
                                      WHERE COALESCE(nombre, nombre_presentacion, presentacion, descripcion) IS NOT NULL
                                        AND TRIM(COALESCE(nombre, nombre_presentacion, presentacion, descripcion)) <> ''
                                      ORDER BY nombre ASC LIMIT 500");
            } else {
                $items = [];
            }

            if ((empty($items) || $request->query('source') === 'productos') && Schema::hasTable('productos')) {
                // Fallback desde productos
                $items = DB::select("SELECT 0 AS id, nombre FROM (
                    SELECT DISTINCT TRIM(COALESCE(presentacion, nombre_presentacion, presentacion_nombre)) AS nombre
                    FROM productos
                    WHERE COALESCE(presentacion, nombre_presentacion, presentacion_nombre) IS NOT NULL
                      AND TRIM(COALESCE(presentacion, nombre_presentacion, presentacion_nombre)) <> ''
                ) t ORDER BY nombre ASC LIMIT 500");
            }

            return response()->json(['success' => true, 'data' => $items]);
        } catch (\Throwable $e) {
            Log::error('mobile presentaciones error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });

    // Presentaciones directamente desde productos (si hay problemas con la tabla presentaciones)
    Route::get('/presentaciones/from-products', function () {
        try {
            if (!Schema::hasTable('productos')) {
                return response()->json(['success' => true, 'data' => []]);
            }
            $textNames = DB::table('productos')
                ->selectRaw('TRIM(presentacion) AS nombre')
                ->whereNotNull('presentacion')
                ->whereRaw("TRIM(presentacion) <> ''")
                ->distinct()
                ->get();

            $joinNames = collect();
            if (Schema::hasColumn('productos', 'presentacion_id') && Schema::hasTable('presentaciones')) {
                $joinNames = DB::table('productos')
                    ->join('presentaciones', 'productos.presentacion_id', '=', 'presentaciones.id')
                    ->select('presentaciones.id as id', DB::raw('TRIM(presentaciones.nombre) as nombre'))
                    ->whereNotNull('presentaciones.nombre')
                    ->whereRaw("TRIM(presentaciones.nombre) <> ''")
                    ->distinct()
                    ->get();
            }

            $map = [];
            $combined = collect();
            foreach ($joinNames as $jn) {
                $key = strtolower($jn->nombre);
                if (!isset($map[$key])) {
                    $map[$key] = true;
                    $combined->push((object)['id' => $jn->id, 'nombre' => $jn->nombre]);
                }
            }
            foreach ($textNames as $tn) {
                $key = strtolower($tn->nombre);
                if (!isset($map[$key])) {
                    $map[$key] = true;
                    $combined->push((object)['id' => 0, 'nombre' => $tn->nombre]);
                }
            }

            $combined = $combined->sortBy('nombre')->values();
            return response()->json(['success' => true, 'data' => $combined]);
        } catch (\Throwable $e) {
            Log::error('mobile presentaciones from-products error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });

    // Categorías directamente desde productos
    Route::get('/categorias/from-products', function () {
        try {
            if (!Schema::hasTable('productos')) {
                return response()->json(['success' => true, 'data' => []]);
            }
            $textNames = DB::table('productos')
                ->selectRaw('TRIM(COALESCE(categoria, nombre_categoria, categoria_nombre)) AS nombre')
                ->whereRaw("COALESCE(categoria, nombre_categoria, categoria_nombre) IS NOT NULL")
                ->whereRaw("TRIM(COALESCE(categoria, nombre_categoria, categoria_nombre)) <> ''")
                ->distinct()
                ->get();

            $joinNames = collect();
            if (Schema::hasColumn('productos', 'categoria_id') && Schema::hasTable('categorias')) {
                $joinNames = DB::table('productos')
                    ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
                    ->select('categorias.id as id', DB::raw('TRIM(COALESCE(categorias.nombre, categorias.nombre_categoria, categorias.categoria)) as nombre'))
                    ->whereRaw("COALESCE(categorias.nombre, categorias.nombre_categoria, categorias.categoria) IS NOT NULL")
                    ->whereRaw("TRIM(COALESCE(categorias.nombre, categorias.nombre_categoria, categorias.categoria)) <> ''")
                    ->distinct()
                    ->get();
            }

            $map = [];
            $combined = collect();
            foreach ($joinNames as $jn) {
                $key = strtolower($jn->nombre);
                if (!isset($map[$key])) {
                    $map[$key] = true;
                    $combined->push((object)['id' => $jn->id, 'nombre' => $jn->nombre]);
                }
            }
            foreach ($textNames as $tn) {
                $key = strtolower($tn->nombre);
                if (!isset($map[$key])) {
                    $map[$key] = true;
                    $combined->push((object)['id' => 0, 'nombre' => $tn->nombre]);
                }
            }

            $combined = $combined->sortBy('nombre')->values();
            return response()->json(['success' => true, 'data' => $combined]);
        } catch (\Throwable $e) {
            Log::error('mobile categorias from-products error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });

    // Rotación mínima por período (en días)
    Route::get('/rotacion/minima', function (Request $request) {
        try {
            $periodo = intval($request->query('periodo', 30));
            $limit = intval($request->query('limit', 3));
            if ($periodo <= 0) $periodo = 30;
            if ($limit <= 0 || $limit > 50) $limit = 3;
            // Detectar tabla de productos
            $prodTables = ['productos','producto','articulos','items','producto_items'];
            $prodTable = null;
            foreach ($prodTables as $pt) { if (Schema::hasTable($pt)) { $prodTable = $pt; break; } }
            if (!$prodTable) { return response()->json(['success' => true, 'data' => []]); }

            // Intento 1: tablas conocidas
            $detailTables = ['ventas_detalle','detalle_ventas','venta_detalles','detalles_venta','ventas_items','items_venta','detalle_venta','det_venta','movimientos','kardex','kardex_detalle'];
            $items = [];
            foreach ($detailTables as $detailTableTry) {
                if (!Schema::hasTable($detailTableTry)) continue;
                $qtyCandidates = ['cantidad','cant','unidades','cantidad_vendida','qty','cantidad_salida','cantidad_venta','salida'];
                $dateCandidates = ['created_at','fecha','fecha_venta','fecha_detalle','fecha_mov','fecha_salida'];
                $prodCandidates = ['producto_id','product_id','id_producto','id_prod','producto'];
                $qtyCol = null; foreach ($qtyCandidates as $c) { if (Schema::hasColumn($detailTableTry, $c)) { $qtyCol = $c; break; } }
                $dateCol = null; foreach ($dateCandidates as $c) { if (Schema::hasColumn($detailTableTry, $c)) { $dateCol = $c; break; } }
                $prodCol = null; foreach ($prodCandidates as $c) { if (Schema::hasColumn($detailTableTry, $c)) { $prodCol = $c; break; } }
                if ($qtyCol && $dateCol && $prodCol) {
                    $sql = "SELECT p.id, TRIM(COALESCE(p.nombre, p.name)) AS nombre, TRIM(COALESCE(p.presentacion, p.concentracion)) AS presentacion,
                            COALESCE(SUM(d.$qtyCol), 0) AS unidades
                            FROM $prodTable p
                            LEFT JOIN $detailTableTry d ON d.$prodCol = p.id
                              AND COALESCE(d.$dateCol, NOW()) BETWEEN DATE_SUB(NOW(), INTERVAL ? DAY) AND NOW()
                            GROUP BY p.id, nombre, presentacion
                            ORDER BY unidades ASC
                            LIMIT ?";
                    $items = DB::select($sql, [$periodo, $limit]);
                    if (!empty($items)) break;
                }
            }

            // Intento 2: búsqueda dinámica en information_schema si aún vacío
            if (empty($items)) {
                $rows = DB::select("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()");
                $tables = [];
                foreach ($rows as $r) { $tables[$r->TABLE_NAME][] = $r->COLUMN_NAME; }
                foreach ($tables as $t => $cols) {
                    $hasQty = false; $qtyCol = '';
                    $hasDate = false; $dateCol = '';
                    $hasProd = false; $prodCol = '';
                    foreach ($cols as $c) {
                        $lc = strtolower($c);
                        if (!$hasQty && (str_contains($lc,'cantidad') || $lc==='qty' || str_contains($lc,'unidades') || str_contains($lc,'salida') || str_contains($lc,'venta'))) { $hasQty = true; $qtyCol = $c; }
                        if (!$hasDate && (str_contains($lc,'fecha') || $lc==='created_at')) { $hasDate = true; $dateCol = $c; }
                        if (!$hasProd && (str_contains($lc,'producto') || str_contains($lc,'product_id') || str_contains($lc,'id_prod'))) { $hasProd = true; $prodCol = $c; }
                    }
                    if ($hasQty && $hasDate && $hasProd) {
                        $sql = "SELECT p.id, TRIM(COALESCE(p.nombre, p.name)) AS nombre, TRIM(COALESCE(p.presentacion, p.concentracion)) AS presentacion,
                                COALESCE(SUM(d.$qtyCol), 0) AS unidades
                                FROM $prodTable p
                                LEFT JOIN $t d ON d.$prodCol = p.id
                                  AND COALESCE(d.$dateCol, NOW()) BETWEEN DATE_SUB(NOW(), INTERVAL ? DAY) AND NOW()
                                GROUP BY p.id, nombre, presentacion
                                ORDER BY unidades ASC
                                LIMIT ?";
                        $items = DB::select($sql, [$periodo, $limit]);
                        if (!empty($items)) break;
                    }
                }
            }

            if (empty($items)) {
                $items = DB::select("SELECT id, TRIM(COALESCE(nombre, name)) AS nombre, TRIM(COALESCE(presentacion, concentracion)) AS presentacion, 0 AS unidades FROM $prodTable ORDER BY id ASC LIMIT $limit");
            }

            return response()->json(['success' => true, 'data' => $items]);
        } catch (\Throwable $e) {
            Log::error('mobile rotacion minima error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });
    Route::get('/productos/sin-ventas', function (Request $request) {
        try {
            $dias = intval($request->query('dias', 30));
            if ($dias <= 0 || $dias > 365) $dias = 30;
            $limit = intval($request->query('limit', 50));
            if ($limit <= 0 || $limit > 500) $limit = 50;
            $ventasCandidates = ['ventas','venta','tblVenta'];
            $detCandidates = ['venta_detalles','detalle_ventas','tblDetalleVenta','detalles_venta'];
            $prodCandidates = ['productos','producto','items'];
            $ventas = null; foreach ($ventasCandidates as $t) { if (Schema::hasTable($t)) { $ventas = $t; break; } }
            $det = null; foreach ($detCandidates as $t) { if (Schema::hasTable($t)) { $det = $t; break; } }
            $prod = null; foreach ($prodCandidates as $t) { if (Schema::hasTable($t)) { $prod = $t; break; } }
            if (!$prod) return response()->json(['success' => true, 'data' => []]);
            $desde = Carbon\Carbon::now('America/Lima')->subDays($dias);
            if ($ventas && $det) {
                $dateCols = ['fecha_venta','fecha','created_at'];
                $qtyCols = ['cantidad','qty','unidades'];
                $prodIdCols = ['producto_id','id_producto','producto','idProd'];
                $ventaIdCols = ['venta_id','id_venta'];
                $dateCol = null; foreach ($dateCols as $c) { if (Schema::hasColumn($ventas, $c)) { $dateCol = $c; break; } }
                if (!$dateCol) $dateCol = 'created_at';
                $qtyCol = null; foreach ($qtyCols as $c) { if (Schema::hasColumn($det, $c)) { $qtyCol = $c; break; } }
                if (!$qtyCol) $qtyCol = 'cantidad';
                $prodIdCol = null; foreach ($prodIdCols as $c) { if (Schema::hasColumn($det, $c)) { $prodIdCol = $c; break; } }
                if (!$prodIdCol) $prodIdCol = 'producto_id';
                $ventaIdCol = null; foreach ($ventaIdCols as $c) { if (Schema::hasColumn($det, $c)) { $ventaIdCol = $c; break; } }
                if (!$ventaIdCol) $ventaIdCol = 'venta_id';
                $ventasAgg = DB::table($det)
                    ->join($ventas, "$det.$ventaIdCol", '=', "$ventas.id")
                    ->where(DB::raw("COALESCE($ventas.$dateCol, $ventas.created_at)"), '>=', $desde)
                    ->select("$det.$prodIdCol as pid", DB::raw("SUM($det.$qtyCol) as unidades"))
                    ->groupBy("$det.$prodIdCol");
                $items = DB::table($prod)
                    ->leftJoinSub($ventasAgg, 'v', function($join){ $join->on('v.pid','=',''.$prod.'.id'); })
                    ->selectRaw("$prod.id, TRIM(COALESCE($prod.nombre, $prod.name)) AS nombre, COALESCE($prod.stock_actual, $prod.stock) AS stock, COALESCE(v.unidades,0) AS unidades")
                    ->whereRaw('COALESCE(v.unidades,0) = 0')
                    ->orderBy('nombre')
                    ->limit($limit)
                    ->get();
                return response()->json(['success' => true, 'data' => $items]);
            }
            $items = DB::table($prod)
                ->selectRaw("$prod.id, TRIM(COALESCE($prod.nombre, $prod.name)) AS nombre, COALESCE($prod.stock_actual, $prod.stock) AS stock")
                ->orderBy('nombre')
                ->limit($limit)
                ->get();
            return response()->json(['success' => true, 'data' => $items]);
        } catch (\Throwable $e) {
            Log::error('mobile productos sin ventas error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });
    Route::get('/stock/sobre', function (Request $request) {
        try {
            $factor = floatval($request->query('factor', 3));
            if ($factor < 1) $factor = 3;
            $limit = intval($request->query('limit', 50));
            if ($limit <= 0 || $limit > 500) $limit = 50;
            $prodTables = ['productos','producto','articulos','items','producto_items'];
            $prodTable = null; foreach ($prodTables as $pt) { if (Schema::hasTable($pt)) { $prodTable = $pt; break; } }
            if (!$prodTable) return response()->json(['success' => true, 'data' => []]);
            $stockCandidates = ['stock_actual','stock','existencia','cantidad_disponible','qty_disponible'];
            $minCandidates = ['stock_minimo','min_stock','minimo','stock_min','min'];
            $stockCol = null; foreach ($stockCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $stockCol = $c; break; } }
            $minCol = null; foreach ($minCandidates as $c) { if (Schema::hasColumn($prodTable, $c)) { $minCol = $c; break; } }
            if (!$stockCol || !$minCol) {
                $rows = DB::select("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$prodTable]);
                foreach ($rows as $r) {
                    $lc = strtolower($r->COLUMN_NAME);
                    if (!$stockCol && (str_contains($lc,'stock') || str_contains($lc,'existencia') || str_contains($lc,'dispon'))) { $stockCol = $r->COLUMN_NAME; }
                    if (!$minCol && (str_contains($lc,'min') || str_contains($lc,'minimo'))) { $minCol = $r->COLUMN_NAME; }
                }
            }
            if ($stockCol && $minCol) {
                $items = DB::table($prodTable)
                    ->selectRaw("id, TRIM(COALESCE(nombre, name)) AS nombre, TRIM(COALESCE(presentacion, concentracion)) AS presentacion, COALESCE($stockCol,0) AS stock, COALESCE($minCol,0) AS minimo")
                    ->whereRaw("COALESCE($stockCol,0) >= GREATEST(1, COALESCE($minCol,0)) * ?", [$factor])
                    ->orderBy('stock','desc')
                    ->take($limit)
                    ->get();
                return response()->json(['success' => true, 'data' => $items]);
            }
            return response()->json(['success' => true, 'data' => []]);
        } catch (\Throwable $e) {
            Log::error('mobile stock sobre error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });

    // Predicción con scikit-learn: proxy a microservicio y fallback local
    Route::get('/predict-sklearn', function (Request $request) {
        try {
            $q = trim($request->query('q', ''));
            $productId = $request->query('product_id');
            $periodDays = intval($request->query('periodo', 180));
            if ($periodDays <= 0) $periodDays = 180;

            // Detectar tabla de productos
            $prodTables = ['productos','producto','articulos','items','producto_items'];
            $prodTable = null; foreach ($prodTables as $pt) { if (Schema::hasTable($pt)) { $prodTable = $pt; break; } }
            if (!$productId && !empty($q) && $prodTable) {
                $prod = DB::table($prodTable)
                    ->select('id', DB::raw('TRIM(COALESCE(nombre, name)) AS nombre'))
                    ->whereRaw('TRIM(COALESCE(nombre, name)) LIKE ?', ['%'.str_replace('%','',$q).'%'])
                    ->orderBy('id')->first();
                if ($prod) $productId = $prod->id;
            }

            $detailTables = ['ventas_detalle','detalle_ventas','venta_detalles','detalles_venta','ventas_items','items_venta','movimientos','kardex','kardex_detalle'];
            $detailTable = null;
            foreach ($detailTables as $t) { if (Schema::hasTable($t)) { $detailTable = $t; break; } }

            $series = [];
            if ($detailTable) {
                $qtyCandidates = ['cantidad','cant','unidades','cantidad_vendida','qty','cantidad_salida','cantidad_venta','salida'];
                $dateCandidates = ['created_at','fecha','fecha_venta','fecha_detalle','fecha_mov','fecha_salida'];
                $prodCandidates = ['producto_id','product_id','id_producto','id_prod','producto'];
                $qtyCol = null; foreach ($qtyCandidates as $c) { if (Schema::hasColumn($detailTable, $c)) { $qtyCol = $c; break; } }
                $dateCol = null; foreach ($dateCandidates as $c) { if (Schema::hasColumn($detailTable, $c)) { $dateCol = $c; break; } }
                $prodCol = null; foreach ($prodCandidates as $c) { if (Schema::hasColumn($detailTable, $c)) { $prodCol = $c; break; } }

                if ($qtyCol && $dateCol) {
                    $whereProd = ($productId && $prodCol) ? "AND d.$prodCol = ?" : '';
                    $params = [$periodDays];
                    if ($whereProd) $params[] = $productId;
                    $sql = "SELECT DATE(MIN(COALESCE(d.$dateCol, NOW()))) AS fecha,
                               COALESCE(SUM(d.$qtyCol), 0) AS unidades,
                               YEARWEEK(COALESCE(d.$dateCol, NOW())) AS yw
                             FROM $detailTable d
                             WHERE COALESCE(d.$dateCol, NOW()) BETWEEN DATE_SUB(NOW(), INTERVAL ? DAY) AND NOW()
                               $whereProd
                             GROUP BY yw
                             ORDER BY fecha ASC";
                    $rows = DB::select($sql, $params);
                    foreach ($rows as $r) { $series[] = ['date' => $r->fecha, 'units' => intval($r->unidades)]; }
                }
            }

            $sklearnUrl = env('SKLEARN_URL', 'http://127.0.0.1:8003');
            
            // Usar cURL nativo robusto para evitar problemas de conexión (error 7, timeout)
            try {
                $payload = ['series' => $series, 'product_id' => $productId, 'q' => $q];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, rtrim($sklearnUrl,'/').'/predict');
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: ' . env('IA_TOKEN', 'gemini')
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                curl_setopt($ch, CURLOPT_PROXY, ''); // Deshabilitar proxy
                curl_setopt($ch, CURLOPT_NOPROXY, '*');

                $responseBody = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                // Fallback a file_get_contents si cURL falla
                if ($curlError) {
                    Log::warning('sklearn curl failed, trying fallback: '.$curlError);
                    $opts = [
                        'http' => [
                            'method'  => 'POST',
                            'header'  => "Content-Type: application/json\r\n",
                            'content' => json_encode($payload),
                            'timeout' => 30,
                            'ignore_errors' => true
                        ],
                        'ssl' => ["verify_peer" => false, "verify_peer_name" => false]
                    ];
                    $context  = stream_context_create($opts);
                    $responseBody = @file_get_contents(rtrim($sklearnUrl,'/').'/predict', false, $context);
                    if ($responseBody !== false) $httpCode = 200;
                }

                if ($httpCode >= 200 && $httpCode < 300 && $responseBody) {
                    return response()->json(['success' => true, 'data' => json_decode($responseBody, true)]);
                }
            } catch (\Throwable $e) { Log::warning('sklearn microservice error: '.$e->getMessage()); }

            // Fallback: media móvil simple
            $n = count($series);
            $avg = 0;
            if ($n > 0) { $sum = 0; $count = 0; for ($i = max(0, $n-4); $i < $n; $i++) { $sum += intval($series[$i]['units']); $count++; } $avg = $count > 0 ? round($sum / $count) : 0; }
            $forecast = [$avg, $avg, $avg, $avg];
            $leadTimeWeeks = 1; $safetyFactor = 1.65; $reorderPoint = intval(round($avg * $leadTimeWeeks + $safetyFactor * ($avg * 0.2)));
            $text = 'Pronóstico (4 semanas): '.implode(', ', $forecast).' unidades. Punto de reorden aproximado: '.$reorderPoint.'.';
            return response()->json(['success' => true, 'data' => ['forecast' => $forecast, 'reorder_point' => $reorderPoint, 'text' => $text, 'confidence' => 0.5]]);
        } catch (\Throwable $e) {
            Log::error('predict-sklearn error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => ['forecast' => [], 'reorder_point' => 0, 'text' => 'No disponible', 'confidence' => 0.0]]);
        }
    });

    Route::get('/categorias/raw', function () {
        try {
            if (Schema::hasTable('categorias')) {
                $catCandidates = ['nombre', 'nombre_categoria', 'categoria', 'name', 'descripcion'];
                $catCols = array_filter($catCandidates, function ($c) { return Schema::hasColumn('categorias', $c); });
                if (!empty($catCols)) {
                    $coalesce = implode(', ', $catCols);
                    $items = DB::select("SELECT id, TRIM(COALESCE($coalesce)) AS nombre FROM categorias WHERE COALESCE($coalesce) IS NOT NULL AND TRIM(COALESCE($coalesce)) <> '' ORDER BY nombre ASC LIMIT 500");
                } else {
                    $items = [];
                }
            } else {
                $items = [];
            }
            if (empty($items) && Schema::hasTable('productos')) {
                $prodCatCandidates = ['categoria', 'nombre_categoria', 'categoria_nombre', 'categoria_descripcion'];
                $prodCatCols = array_filter($prodCatCandidates, function ($c) { return Schema::hasColumn('productos', $c); });
                if (!empty($prodCatCols)) {
                    $prodCoalesce = implode(', ', $prodCatCols);
                    $items = DB::select("SELECT 0 AS id, nombre FROM (
                        SELECT DISTINCT TRIM(COALESCE($prodCoalesce)) AS nombre
                        FROM productos
                        WHERE COALESCE($prodCoalesce) IS NOT NULL
                          AND TRIM(COALESCE($prodCoalesce)) <> ''
                    ) t ORDER BY nombre ASC LIMIT 500");
                } else {
                    $items = [];
                }
            }
            return response()->json(['success' => true, 'data' => $items]);
        } catch (\Throwable $e) {
            Log::error('mobile categorias raw error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });

    Route::get('/presentaciones/raw', function () {
        try {
            if (Schema::hasTable('presentaciones')) {
                $presCandidates = ['nombre', 'nombre_presentacion', 'presentacion', 'descripcion'];
                $presCols = array_filter($presCandidates, function ($c) { return Schema::hasColumn('presentaciones', $c); });
                
                // Detectar columna de estado/condición
                $statusCondition = "";
                if (Schema::hasColumn('presentaciones', 'condicion')) {
                    $statusCondition = "AND condicion = 1";
                } elseif (Schema::hasColumn('presentaciones', 'estado')) {
                    $statusCondition = "AND (estado = 'activo' OR estado = 1)";
                } elseif (Schema::hasColumn('presentaciones', 'active')) {
                    $statusCondition = "AND active = 1";
                }

                if (!empty($presCols)) {
                    $coalesce = implode(', ', $presCols);
                    $items = DB::select("SELECT id, TRIM(COALESCE($coalesce)) AS nombre FROM presentaciones WHERE COALESCE($coalesce) IS NOT NULL AND TRIM(COALESCE($coalesce)) <> '' $statusCondition ORDER BY nombre ASC LIMIT 500");
                } else {
                    $items = [];
                }
            } else {
                $items = [];
            }
            if (empty($items) && Schema::hasTable('productos')) {
                $prodPresCandidates = ['presentacion', 'nombre_presentacion', 'presentacion_nombre', 'presentacion_descripcion'];
                $prodPresCols = array_filter($prodPresCandidates, function ($c) { return Schema::hasColumn('productos', $c); });
                if (!empty($prodPresCols)) {
                    $prodCoalesce = implode(', ', $prodPresCols);
                    $items = DB::select("SELECT 0 AS id, nombre FROM (
                        SELECT DISTINCT TRIM(COALESCE($prodCoalesce)) AS nombre
                        FROM productos
                        WHERE COALESCE($prodCoalesce) IS NOT NULL
                          AND TRIM(COALESCE($prodCoalesce)) <> ''
                    ) t ORDER BY nombre ASC LIMIT 500");
                } else {
                    $items = [];
                }
            }
            return response()->json(['success' => true, 'data' => $items]);
        } catch (\Throwable $e) {
            Log::error('mobile presentaciones raw error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });
    
    // Rutas de recuperación de contraseña
    Route::post('/forgot-password', [MobileAuthController::class, 'forgotPassword']);

    // Proxy de imágenes robusto: acepta rutas absolutas/relativas y busca en public y storage
    Route::get('/image', function (Request $request) {
        $p = $request->query('p');
        if (!$p) {
            return response()->json(['error' => 'missing_path'], 400);
        }
        try {
            if (substr($p, 0, 4) === 'http') {
                $u = parse_url($p);
                if (!empty($u['path'])) {
                    $p = $u['path'];
                }
            }
            $relative = ltrim(urldecode($p), '/');
            // Si viene con prefijo 'storage/', intentar también sin el prefijo
            $relativeStripped = str_starts_with($relative, 'storage/') ? substr($relative, strlen('storage/')) : $relative;

            $publicPath = public_path($relative);
            if (file_exists($publicPath)) {
                $mime = @mime_content_type($publicPath) ?: 'application/octet-stream';
                return response()->file($publicPath, [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                    'Cache-Control' => 'public, max-age=86400',
                    'Content-Type' => $mime,
                ]);
            }

            // Probar ruta stripped en public
            $publicStrippedPath = public_path($relativeStripped);
            if (file_exists($publicStrippedPath)) {
                $mime = @mime_content_type($publicStrippedPath) ?: 'application/octet-stream';
                return response()->file($publicStrippedPath, [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                    'Cache-Control' => 'public, max-age=86400',
                    'Content-Type' => $mime,
                ]);
            }

            $storageRel = ltrim($relativeStripped, '/');
            $storagePath = storage_path('app/public/' . $storageRel);
            if (file_exists($storagePath)) {
                $mime = @mime_content_type($storagePath) ?: 'application/octet-stream';
                return response()->file($storagePath, [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                    'Cache-Control' => 'public, max-age=86400',
                    'Content-Type' => $mime,
                ]);
            }

            $fallback = public_path('assets/images/default-product.svg');
            if (file_exists($fallback)) {
                return response()->file($fallback, [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                    'Cache-Control' => 'public, max-age=86400',
                    'Content-Type' => 'image/svg+xml',
                ]);
            }
            return response()->json(['error' => 'not_found'], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'server_error',
                'message' => $e->getMessage(),
            ], 500);
        }
    });
});

// Rutas de búsqueda instantánea (con autenticación)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/buscar-productos', function (Request $request) {
        $termino = $request->get('q', '');
        $limit = min($request->get('limit', 20), 50); // Máximo 50 resultados
        
        $productos = \App\Services\CacheService::buscarProductos($termino, $limit);
        
        return response()->json([
            'success' => true,
            'data' => $productos,
            'count' => $productos->count()
        ]);
    });
    
    Route::post('/verify-reset-code', [MobileAuthController::class, 'verifyResetCode']);
    Route::post('/reset-password', [MobileAuthController::class, 'resetPasswordWithCode']);
    Route::post('/resend-reset-code', [MobileAuthController::class, 'resendResetCode']);
});

// Rutas protegidas de autenticación móvil
Route::middleware('auth:sanctum')->prefix('mobile')->group(function () {
    Route::post('/logout', [MobileAuthController::class, 'logout']);
    Route::get('/me', [MobileAuthController::class, 'me']);
    Route::post('/change-password', [MobileAuthController::class, 'changePassword']);
    Route::post('/update-profile', [MobileAuthController::class, 'updateProfile']);
    
    // Rutas de dashboard
    Route::get('/dashboard', [DashboardController::class, 'getDashboardData']);
    
    // Rutas de productos
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductoApiController::class, 'index']);
        Route::post('/', [ProductoApiController::class, 'store']); // Crear producto nuevo
        Route::get('/search', [ProductoApiController::class, 'searchByName']);
        Route::get('/critical', [ProductoApiController::class, 'getCriticalProducts']);
        Route::get('/low-stock', [ProductoApiController::class, 'lowStock']);
        Route::get('/expiring', [ProductoApiController::class, 'expiring']);
        Route::get('/expired', [ProductoApiController::class, 'expired']);
        Route::get('/alerts', [ProductoApiController::class, 'alerts']);
        // Endpoints de mutación para móvil
        Route::post('/{id}/add-stock', [ProductoApiController::class, 'addStock']);
        Route::post('/{id}/adjust-stock', [ProductoApiController::class, 'adjustStock']);
    });

    // Rutas de Lotes (Mobile)
    Route::post('/lotes/{id}/baja', [ProductoApiController::class, 'darBajaLote']);
    
    // Rutas de notificaciones
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::get('/type/{type}', [NotificationController::class, 'getByType']);
        // Nota: las rutas de productos no deben vivir bajo notifications.
    });
});

Route::prefix('compras')->group(function () {
    Route::get('/buscar-proveedores', [CompraController::class, 'buscarProveedoresApi']);
});

// Rutas públicas de productos (para interfaz web)
Route::get('/productos/{id}/detalles', [ProductoApiController::class, 'getDetallesConLotes']);
Route::get('/productos/{id}/lotes', [ProductoApiController::class, 'getLotes']);
Route::post('/productos/{id}/actualizar-stock', [ProductoApiController::class, 'actualizarStock']);

// Rutas públicas de notificaciones (para interfaz web)
Route::prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/unread', [NotificationController::class, 'unread']);
    Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead']);
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::get('/type/{type}', [NotificationController::class, 'getByType']);
});

// Rutas de punto de venta
Route::prefix('punto-venta')->group(function () {
    Route::get('/buscar-alternativas', [\App\Http\Controllers\PuntoVenta\PuntoVentaController::class, 'buscarAlternativas']);
    Route::post('/procesar-venta', [\App\Http\Controllers\PuntoVenta\PuntoVentaController::class, 'procesarVenta']);
    Route::get('/productos-mas-vendidos', [\App\Http\Controllers\PuntoVenta\PuntoVentaController::class, 'productosMasVendidos']);
});

// Rutas de facturación electrónica
Route::prefix('facturacion')->middleware('auth:sanctum')->group(function () {
    // Generar documentos electrónicos
    Route::post('/factura', [FacturacionController::class, 'generarFactura']);
    Route::post('/boleta', [FacturacionController::class, 'generarBoleta']);
    Route::post('/ticket', [FacturacionController::class, 'generarTicket']);
    
    // Consultar estado de documentos
    Route::post('/consultar-estado', [FacturacionController::class, 'consultarEstado']);
    
    // Listar documentos
    Route::get('/facturas', [FacturacionController::class, 'listarFacturas']);
    Route::get('/boletas', [FacturacionController::class, 'listarBoletas']);
    
    // Descargar PDF
    Route::post('/descargar-pdf', [FacturacionController::class, 'descargarPdf']);
});

// Integración directa con Nubefact para pruebas y validación
Route::prefix('nubefact')->group(function(){
    Route::post('/enviar', function(\Illuminate\Http\Request $request){
        $service = app(\App\Services\NubeFactService::class);
        $payload = $request->all();
        try {
            $resp = $service->enviar($payload);
            return response()->json($resp['data'] ?? $resp);
        } catch (\Throwable $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 400);
        }
    });
    Route::post('/boleta/desde-venta', function(\Illuminate\Http\Request $request){
        $ventaId = (int) $request->get('venta_id');
        $formato = $request->get('formato','A4');
        $venta = \App\Models\PuntoVenta\Venta::with(['detalles.producto','cliente'])->findOrFail($ventaId);
        $service = app(\App\Services\NubeFactService::class);
        $payload = $service->buildBoletaPayloadFromVenta($venta, $formato);
        try {
            $resp = $service->enviar($payload);
            return response()->json($resp['data'] ?? $resp);
        } catch (\Throwable $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage(),'payload'=>$payload], 400);
        }
    });

    // Diagnóstico de conexión y serie con payload mínimo
    Route::post('/diagnostico', function(\Illuminate\Http\Request $request){
        $service = app(\App\Services\NubeFactService::class);
        $serie = env('NUBEFACT_SERIE_BOLETA', '');
        $monto = (float) ($request->get('importe', 20));
        $igv = (float) env('NUBEFACT_IGV', 18);
        $gravado = $igv > 0;
        $valorUnit = $gravado ? round($monto / (1 + $igv/100), 2) : $monto;
        $payload = [
            'operacion' => 'generar_comprobante',
            'tipo_de_comprobante' => 2,
            'serie' => $serie,
            'numero' => '',
            'sunat_transaction' => 1,
            'cliente_tipo_de_documento' => 1,
            'cliente_numero_de_documento' => '99999999',
            'cliente_denominacion' => 'CLIENTE GENERAL',
            'cliente_direccion' => '-',
            'fecha_de_emision' => now()->format('Y-m-d'),
            'fecha_de_vencimiento' => now()->format('Y-m-d'),
            'moneda' => 1,
            'porcentaje_de_igv' => $igv,
            'total_descuento' => 0,
            'total_anticipo' => 0,
            'total_gravada' => $gravado ? $valorUnit : 0,
            'total_inafecta' => 0,
            'total_exonerada' => $gravado ? 0 : $monto,
            'total_igv' => $gravado ? round($valorUnit * ($igv/100), 2) : 0,
            'total_gratuita' => 0,
            'total_otros_cargos' => 0,
            'total' => $monto,
            'enviar_automaticamente_a_la_sunat' => true,
            'enviar_automaticamente_al_cliente' => false,
            'formato_de_pdf' => 'A4',
            'codigo_unico' => 'DIAG-' . now()->format('YmdHis'),
            'items' => [[
                'unidad_de_medida' => 'NIU',
                'codigo' => 'DIAG001',
                'descripcion' => 'Diagnóstico Nubefact',
                'cantidad' => 1,
                'valor_unitario' => $valorUnit,
                'precio_unitario' => $gravado ? $monto : $valorUnit,
                'descuento' => 0,
                'subtotal' => $valorUnit,
                'tipo_de_igv' => $gravado ? 1 : 20,
                'igv' => $gravado ? round($valorUnit * ($igv/100), 2) : 0,
                'total' => $monto,
                'anticipo_regularizacion' => false,
            ]]
        ];

        try {
            $resp = $service->enviar($payload);
            return response()->json([
                'success' => true,
                'data' => $resp['data'] ?? $resp,
                'payload' => $payload,
                'config' => [
                    'url' => env('NUBEFACT_API_URL'),
                    'serie' => $serie,
                    'modo_prueba' => env('NUBEFACT_MODO_PRUEBA')
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'payload' => $payload,
                'config' => [
                    'url' => env('NUBEFACT_API_URL'),
                    'serie' => $serie,
                    'modo_prueba' => env('NUBEFACT_MODO_PRUEBA')
                ]
            ], 400);
        }
    });
});

// Rutas de generación de PDFs
Route::prefix('pdf')->middleware('auth:sanctum')->group(function () {
    // Generar PDFs de facturas
    Route::get('/factura/{id}/a4', [PdfController::class, 'generarFacturaPdfA4']);
    Route::get('/factura/{id}/ticket', [PdfController::class, 'generarFacturaPdfTicket']);
    Route::get('/factura/{id}/descargar', [PdfController::class, 'descargarFacturaPdf']);
    Route::post('/factura/{id}/guardar', [PdfController::class, 'guardarFacturaPdf']);
    
    // Generar PDFs de boletas
    Route::get('/boleta/{id}/a4', [PdfController::class, 'generarBoletaPdfA4']);
    Route::get('/boleta/{id}/ticket', [PdfController::class, 'generarBoletaPdfTicket']);
    Route::get('/boleta/{id}/descargar', [PdfController::class, 'descargarBoletaPdf']);
    Route::post('/boleta/{id}/guardar', [PdfController::class, 'guardarBoletaPdf']);
    
    // Gestión de PDFs almacenados
    Route::get('/listar', [PdfController::class, 'listarPdfs']);
    Route::delete('/eliminar', [PdfController::class, 'eliminarPdf']);
});

// Rutas de configuración SUNAT
Route::prefix('configuracion')->middleware('auth:sanctum')->group(function () {
    // Configuración de empresa
    Route::get('/empresa', [ConfiguracionController::class, 'obtenerEmpresa']);
    Route::put('/empresa', [ConfiguracionController::class, 'actualizarEmpresa']);
    Route::post('/empresa/certificado', [ConfiguracionController::class, 'subirCertificado']);
    
    // Gestión de sucursales
    Route::get('/sucursales', [ConfiguracionController::class, 'listarSucursales']);
    Route::post('/sucursales', [ConfiguracionController::class, 'crearSucursal']);
    
    // Gestión de correlativos
    Route::get('/correlativos', [ConfiguracionController::class, 'listarCorrelativos']);
    Route::post('/correlativos', [ConfiguracionController::class, 'crearCorrelativo']);
    Route::put('/correlativos/{id}', [ConfiguracionController::class, 'actualizarCorrelativo']);
    
    // Prueba de conexión SUNAT
    Route::get('/probar-sunat', [ConfiguracionController::class, 'probarConexionSunat']);
});

// Rutas de WhatsApp para envío de boletas
// Rutas de WhatsApp (sin auth:sanctum para compatibilidad con POS web)
Route::prefix('whatsapp')->group(function () {
    Route::post('/enviar-boleta', [WhatsAppController::class, 'enviarBoleta']);
    Route::get('/venta/{ventaId}/info', [WhatsAppController::class, 'obtenerInfoVenta']);
});

// Rutas públicas de facturación (sin autenticación para consultas externas)
Route::prefix('facturacion-publica')->group(function () {
    Route::post('/consultar-documento', [FacturacionController::class, 'consultarEstado']);
});

// Rutas del POS Optimizado - Ultra rápidas
Route::prefix('pos-optimizado')->group(function () {
    Route::get('/buscar', [PosOptimizadoController::class, 'buscarProductos']);
    Route::get('/populares', [PosOptimizadoController::class, 'productosPopulares']);
    Route::get('/categoria', [PosOptimizadoController::class, 'productosPorCategoria']);
    Route::get('/scroll-infinito', [PosOptimizadoController::class, 'scrollInfinito']);
    Route::get('/estadisticas', [PosOptimizadoController::class, 'estadisticas']);
    Route::get('/codigo-barras', [PosOptimizadoController::class, 'obtenerPorCodigoBarras']);
    Route::get('/categorias', [PosOptimizadoController::class, 'obtenerCategorias']);
    Route::get('/marcas', [PosOptimizadoController::class, 'obtenerMarcas']);
    Route::post('/limpiar-cache', [PosOptimizadoController::class, 'limpiarCache']);
    Route::post('/precargar-cache', [PosOptimizadoController::class, 'precargarCache']);
});

Route::prefix('ia')->group(function () {
    Route::post('/chat', [ChatController::class, 'chat']);
    Route::get('/chat', [ChatController::class, 'chat']);
    Route::post('/chat-llm', [ChatLibreController::class, 'chat']);
    Route::get('/chat-llm', [ChatLibreController::class, 'chat']);
    Route::match(['get','post'], '/nl-sql', [ChatLibreController::class, 'nlSql']);
    Route::get('/predicciones/ventas', function (\Illuminate\Http\Request $r) {
        $srv = new \App\Services\IA\PrediccionServicio();
        $rango = $r->get('range', '7d');
        return response()->json($srv->ventas($rango));
    });
    // Tarjetas del Asistente IA
    Route::get('/cards', [RecomendacionController::class, 'index']);
    Route::post('/cards', [RecomendacionController::class, 'store']);
    Route::delete('/cards/{id}', [RecomendacionController::class, 'destroy']);
    Route::get('/cards/add', [RecomendacionController::class, 'add']);

    Route::get('/rag-chat', function (Request $r) {
        try {
            $q = $r->query('q', '');
            $topK = intval($r->query('top_k', 6));
            if ($topK <= 0 || $topK > 50) { $topK = 6; }
            $bases = [env('IA_RAG_URL'), env('SKLEARN_URL', 'http://127.0.0.1:8100')];
            foreach ($bases as $base) {
                if (empty($base)) continue;
                $url = rtrim($base, '/') . '/rag/chat';
                try {
                    $res = Http::timeout(10)->get($url, ['q' => $q, 'top_k' => $topK]);
                    if ($res->successful()) {
                        return response()->json(['success' => true, 'data' => $res->json()]);
                    }
                } catch (\Throwable $ex) {}
            }
            // Fallback al controlador PHP
            $ctrl = app(\App\Http\Controllers\IA\ChatLibreController::class);
            $fallback = $ctrl->chat($r);
            return $fallback;
        } catch (\Throwable $e) {
            Log::error('ia rag-chat proxy error: '.$e->getMessage());
            // Fallback al controlador PHP
            try {
                $ctrl = app(\App\Http\Controllers\IA\ChatLibreController::class);
                return $ctrl->chat($r);
            } catch (\Throwable $ex) {
                return response()->json(['success' => true, 'data' => ['text' => 'No disponible']], 200);
            }
        }   
    });

    Route::get('/rag-nl-sql', function (Request $r) {
        try {
            $q = $r->query('q', '');
            $bases = [env('IA_RAG_URL'), env('SKLEARN_URL', 'http://127.0.0.1:8100')];
            foreach ($bases as $base) {
                if (empty($base)) continue;
                $url = rtrim($base, '/') . '/rag/nl-sql';
                try {
                    $res = Http::timeout(12)->get($url, ['q' => $q]);
                    if ($res->successful()) {
                        return response()->json(['success' => true, 'data' => $res->json()]);
                    }
                } catch (\Throwable $ex) {}
            }
            // Fallback a nl-sql PHP
            $ctrl = app(\App\Http\Controllers\IA\ChatLibreController::class);
            return $ctrl->nlSql($r);
        } catch (\Throwable $e) {
            Log::error('ia rag-nl-sql proxy error: '.$e->getMessage());
            try {
                $ctrl = app(\App\Http\Controllers\IA\ChatLibreController::class);
                return $ctrl->nlSql($r);
            } catch (\Throwable $ex) {
                return response()->json(['success' => true, 'data' => ['text' => 'No disponible']], 200);
            }
        }
    });

    Route::get('/rag-health', function (Request $r) {
        try {
            $bases = [env('IA_RAG_URL'), env('SKLEARN_URL', 'http://127.0.0.1:8100')];
            foreach ($bases as $base) {
                if (empty($base)) continue;
                $url = rtrim($base, '/') . '/health';
                try {
                    $res = Http::timeout(6)->get($url);
                    if ($res->ok()) {
                        return response()->json([
                            'success' => true,
                            'data' => $res->json(),
                            'base' => $base,
                            'url' => $url
                        ]);
                    }
                } catch (\Throwable $ex) {}
            }
            return response()->json([
                'success' => false,
                'data' => ['text' => 'RAG no responde', 'code' => 404],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 200);
        }
    });

    // Analytics IA (DB via Python)
    Route::get('/analytics/agotados', function (Request $r) {
        try {
            $limit = intval($r->query('limit', 50));
            if ($limit <= 0 || $limit > 500) $limit = 50;
            $bases = [env('IA_RAG_URL'), env('SKLEARN_URL', 'http://127.0.0.1:8100')];
            foreach ($bases as $base) {
                if (empty($base)) continue;
                $url = rtrim($base, '/') . '/analytics/agotados';
                try {
                    $res = Http::timeout(8)->get($url, ['limit' => $limit]);
                    if ($res->ok()) {
                        $j = $res->json();
                        return response()->json(['success' => true, 'data' => ($j['data'] ?? [])]);
                    }
                } catch (\Throwable $ex) {}
            }
            return response()->json(['success' => true, 'data' => []]);
        } catch (\Throwable $e) {
            Log::warning('ia analytics agotados proxy error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });

    Route::get('/analytics/critico', function (Request $r) {
        try {
            $limit = intval($r->query('limit', 50));
            if ($limit <= 0 || $limit > 500) $limit = 50;
            $bases = [env('IA_RAG_URL'), env('SKLEARN_URL', 'http://127.0.0.1:8100')];
            foreach ($bases as $base) {
                if (empty($base)) continue;
                $url = rtrim($base, '/') . '/analytics/critico';
                try {
                    $res = Http::timeout(8)->get($url, ['limit' => $limit]);
                    if ($res->ok()) {
                        $j = $res->json();
                        return response()->json(['success' => true, 'data' => ($j['data'] ?? [])]);
                    }
                } catch (\Throwable $ex) {}
            }
            return response()->json(['success' => true, 'data' => []]);
        } catch (\Throwable $e) {
            Log::warning('ia analytics critico proxy error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });

    Route::get('/analytics/top-ventas', function (Request $r) {
        try {
            $periodo = intval($r->query('periodo', 30));
            if ($periodo <= 0 || $periodo > 365) $periodo = 30;
            $limit = intval($r->query('limit', 10));
            if ($limit <= 0 || $limit > 100) $limit = 10;
            $bases = [env('IA_RAG_URL'), env('SKLEARN_URL', 'http://127.0.0.1:8100')];
            foreach ($bases as $base) {
                if (empty($base)) continue;
                $url = rtrim($base, '/') . '/analytics/top-ventas';
                try {
                    $res = Http::timeout(10)->get($url, ['periodo' => $periodo, 'limit' => $limit]);
                    if ($res->ok()) {
                        $j = $res->json();
                        return response()->json(['success' => true, 'data' => ($j['data'] ?? [])]);
                    }
                } catch (\Throwable $ex) {}
            }
            return response()->json(['success' => true, 'data' => []]);
        } catch (\Throwable $e) {
            Log::warning('ia analytics top-ventas proxy error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });

    Route::get('/analytics/rotacion-minima', function (Request $r) {
        try {
            $periodo = intval($r->query('periodo', 30));
            if ($periodo <= 0 || $periodo > 365) $periodo = 30;
            $limit = intval($r->query('limit', 10));
            if ($limit <= 0 || $limit > 100) $limit = 10;
            $bases = [env('IA_RAG_URL'), env('SKLEARN_URL', 'http://127.0.0.1:8100')];
            foreach ($bases as $base) {
                if (empty($base)) continue;
                $url = rtrim($base, '/') . '/analytics/rotacion-minima';
                try {
                    $res = Http::timeout(10)->get($url, ['periodo' => $periodo, 'limit' => $limit]);
                    if ($res->ok()) {
                        $j = $res->json();
                        return response()->json(['success' => true, 'data' => ($j['data'] ?? [])]);
                    }
                } catch (\Throwable $ex) {}
            }
            return response()->json(['success' => true, 'data' => []]);
        } catch (\Throwable $e) {
            Log::warning('ia analytics rotacion-minima proxy error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => []]);
        }
    });

    Route::get('/analytics/ventas-ayer', function (Request $r) {
        try {
            $bases = [env('IA_RAG_URL'), env('SKLEARN_URL', 'http://127.0.0.1:8100')];
            foreach ($bases as $base) {
                if (empty($base)) continue;
                $url = rtrim($base, '/') . '/analytics/ventas-ayer';
                try {
                    $res = Http::timeout(8)->get($url);
                    if ($res->ok()) {
                        $j = $res->json();
                        return response()->json(['success' => true, 'data' => ($j['data'] ?? [])]);
                    }
                } catch (\Throwable $ex) {}
            }
            return response()->json(['success' => true, 'data' => ['total_unidades' => 0]]);
        } catch (\Throwable $e) {
            Log::warning('ia analytics ventas-ayer proxy error: '.$e->getMessage());
            return response()->json(['success' => true, 'data' => ['total_unidades' => 0]]);
        }
    });
});

// SQL read-only para IA (protegido con Sanctum).
Route::middleware('auth:sanctum')->prefix('ia')->group(function () {
    Route::post('/sql-read', [ChatLibreController::class, 'sqlRead']);
});

// Rutas de optimización de imágenes
Route::prefix('image-optimization')->group(function () {
    Route::post('/optimize-all', [ImageOptimizationController::class, 'optimizarTodasLasImagenes']);
    Route::get('/statistics', [ImageOptimizationController::class, 'obtenerEstadisticas']);
    Route::post('/clean-unused', [ImageOptimizationController::class, 'limpiarImagenesNoUtilizadas']);
    Route::post('/generate-placeholders', [ImageOptimizationController::class, 'generarPlaceholders']);
});

// Integración con Nubefact (modo prueba por configuración)
Route::prefix('nubefact')->group(function () {
    // Enviar payload crudo
    Route::post('/enviar', [NubeFactController::class, 'enviar']);
    // Enviar boleta de prueba
    Route::post('/boleta/prueba', [NubeFactController::class, 'boletaPrueba']);
});

// ============================================
// API MÓVIL - SIN AUTENTICACIÓN (para Flutter)
// ============================================
Route::prefix('mobile')->name('mobile.')->group(function () {
    // Chat de IA para móvil (sin autenticación)
    Route::post('/ai/chat', function(Request $request) {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            $aiService = app(\App\Services\AIService::class);
            $result = $aiService->chat($request->message);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'response' => 'Error procesando consulta: ' . $e->getMessage(),
                'error' => 'internal_error'
            ], 500);
        }
    })->name('ai.chat');
    
    // Predicciones para móvil
    Route::get('/ai/predict/sales', function(Request $request) {
        try {
            $aiEngineUrl = config('services.ai_engine.url', 'http://76.13.71.180:8001');
            $daysAhead = $request->get('days_ahead', 7);
            
            $response = Http::timeout(30)->get("{$aiEngineUrl}/predict/sales", [
                'days_ahead' => $daysAhead
            ]);
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Error al conectar con el servicio de predicciones'
            ], 500);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    })->name('ai.predict.sales');
    
    Route::get('/ai/predict/stock', function(Request $request) {
        try {
            $aiEngineUrl = config('services.ai_engine.url', 'http://76.13.71.180:8001');
            
            $response = Http::timeout(30)->get("{$aiEngineUrl}/predict/stock");
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Error al conectar con el servicio de análisis de stock'
            ], 500);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    })->name('ai.predict.stock');
    
    Route::get('/ai/analytics/trends', function(Request $request) {
        try {
            $aiEngineUrl = config('services.ai_engine.url', 'http://76.13.71.180:8001');
            
            $response = Http::timeout(30)->get("{$aiEngineUrl}/analytics/trends");
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Error al conectar con el servicio de análisis de tendencias'
            ], 500);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    })->name('ai.analytics.trends');
    
    // Health check para móvil
    Route::get('/ai/health', function() {
        try {
            $aiService = app(\App\Services\AIService::class);
            $result = $aiService->healthCheck();
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('ai.health');
    
    // Dar de baja lote (ajustar stock a 0)
    Route::post('/lotes/{loteId}/baja', function(Request $request, $loteId) {
        try {
            $motivo = $request->input('motivo', 'Vencimiento/Merma');
            
            // Buscar el lote en diferentes tablas posibles
            $lote = DB::table('lotes')->where('id', $loteId)->first();
            
            if (!$lote) {
                // Intentar buscar en tabla de productos_lotes si existe
                $lote = DB::table('productos_lotes')->where('id', $loteId)->first();
            }
            
            if (!$lote) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lote no encontrado con ID: ' . $loteId
                ], 404);
            }
            
            // Determinar qué campos usar según la estructura
            $cantidadActual = $lote->cantidad_actual ?? $lote->cantidad ?? 0;
            $tableName = DB::table('lotes')->where('id', $loteId)->exists() ? 'lotes' : 'productos_lotes';
            
            // Actualizar stock a 0
            $updateData = [
                'updated_at' => now()
            ];
            
            // Agregar campos según la estructura de la tabla
            if (DB::getSchemaBuilder()->hasColumn($tableName, 'cantidad_actual')) {
                $updateData['cantidad_actual'] = 0;
            }
            if (DB::getSchemaBuilder()->hasColumn($tableName, 'cantidad')) {
                $updateData['cantidad'] = 0;
            }
            if (DB::getSchemaBuilder()->hasColumn($tableName, 'estado')) {
                $updateData['estado'] = 'vencido';
            }
            
            DB::table($tableName)->where('id', $loteId)->update($updateData);
            
            // Intentar registrar movimiento si la tabla existe
            if (DB::getSchemaBuilder()->hasTable('lote_movimientos')) {
                $movimientoData = [
                    'lote_id' => $loteId,
                    'tipo_movimiento' => 'baja',
                    'cantidad' => $cantidadActual,
                    'motivo' => $motivo,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                
                // Agregar campos opcionales si existen
                if (DB::getSchemaBuilder()->hasColumn('lote_movimientos', 'cantidad_anterior')) {
                    $movimientoData['cantidad_anterior'] = $cantidadActual;
                }
                if (DB::getSchemaBuilder()->hasColumn('lote_movimientos', 'cantidad_nueva')) {
                    $movimientoData['cantidad_nueva'] = 0;
                }
                
                DB::table('lote_movimientos')->insert($movimientoData);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Lote dado de baja correctamente'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error al dar de baja lote: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al dar de baja el lote: ' . $e->getMessage()
            ], 500);
        }
    })->name('mobile.lotes.baja');
    
    // Endpoint para obtener presentaciones de un producto
    Route::get('/productos/{productId}/presentaciones', function ($productId) {
        try {
            \Log::info('Buscando presentaciones para producto: ' . $productId);
            
            // Buscar en diferentes tablas posibles
            $presentaciones = [];
            
            // Opción 1: Tabla producto_presentaciones
            if (DB::getSchemaBuilder()->hasTable('producto_presentaciones')) {
                $presentaciones = DB::table('producto_presentaciones')
                    ->where('producto_id', $productId)
                    ->get()
                    ->toArray();
                    
                if (!empty($presentaciones)) {
                    \Log::info('Presentaciones encontradas en producto_presentaciones: ' . count($presentaciones));
                    return response()->json([
                        'success' => true,
                        'data' => $presentaciones
                    ]);
                }
            }
            
            // Opción 2: Tabla presentaciones con relación
            if (DB::getSchemaBuilder()->hasTable('presentaciones')) {
                $presentaciones = DB::table('presentaciones')
                    ->where('producto_id', $productId)
                    ->orWhere('id', function($query) use ($productId) {
                        $query->select('presentacion_id')
                              ->from('productos')
                              ->where('id', $productId)
                              ->limit(1);
                    })
                    ->get()
                    ->toArray();
                    
                if (!empty($presentaciones)) {
                    \Log::info('Presentaciones encontradas en presentaciones: ' . count($presentaciones));
                    return response()->json([
                        'success' => true,
                        'data' => $presentaciones
                    ]);
                }
            }
            
            // Opción 3: Buscar en lotes con diferentes presentaciones
            if (DB::getSchemaBuilder()->hasTable('lotes')) {
                $lotes = DB::table('lotes')
                    ->where('producto_id', $productId)
                    ->select('presentacion', 'cantidad', 'precio_venta')
                    ->whereNotNull('presentacion')
                    ->where('presentacion', '!=', '')
                    ->get()
                    ->toArray();
                    
                if (!empty($lotes)) {
                    // Agrupar por presentación
                    $presentacionesAgrupadas = [];
                    foreach ($lotes as $lote) {
                        $key = $lote->presentacion ?? 'Sin especificar';
                        if (!isset($presentacionesAgrupadas[$key])) {
                            $presentacionesAgrupadas[$key] = [
                                'nombre' => $key,
                                'presentacion' => $key,
                                'cantidad' => 0,
                                'precio' => $lote->precio_venta ?? 0
                            ];
                        }
                        $presentacionesAgrupadas[$key]['cantidad'] += $lote->cantidad ?? 0;
                    }
                    
                    $presentaciones = array_values($presentacionesAgrupadas);
                    
                    \Log::info('Presentaciones encontradas en lotes: ' . count($presentaciones));
                    return response()->json([
                        'success' => true,
                        'data' => $presentaciones
                    ]);
                }
            }
            
            // Si no se encontró nada, devolver array vacío
            \Log::info('No se encontraron presentaciones para el producto: ' . $productId);
            return response()->json([
                'success' => true,
                'data' => []
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error al obtener presentaciones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener presentaciones: ' . $e->getMessage()
            ], 500);
        }
    })->name('mobile.productos.presentaciones');
});

// Ruta de prueba para verificar que la API funciona
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API funcionando correctamente',
        'timestamp' => now()
    ]);
});

// Rutas para notificaciones push (solo para pruebas)
Route::prefix('notifications')->group(function () {
    
    // Enviar notificación de prueba
    Route::post('/test', function () {
        try {
            $firebaseService = new \App\Services\FirebaseNotificationService();
            $result = $firebaseService->sendTestNotification();
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['response'] ?? null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    });
    
    // Simular notificación de stock bajo
    Route::post('/stock-bajo', function () {
        try {
            $firebaseService = new \App\Services\FirebaseNotificationService();
            $result = $firebaseService->notifyLowStock('Paracetamol 500mg', 5, 20);
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['response'] ?? null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    });
    
    // Simular notificación de producto agotado
    Route::post('/agotado', function () {
        try {
            $firebaseService = new \App\Services\FirebaseNotificationService();
            $result = $firebaseService->notifyOutOfStock('Ibuprofeno 400mg');
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['response'] ?? null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    });
    
    // Simular notificación de nueva venta
    Route::post('/nueva-venta', function () {
        try {
            $firebaseService = new \App\Services\FirebaseNotificationService();
            $result = $firebaseService->notifyNewSale(150.50, 3);
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['response'] ?? null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    });
});
