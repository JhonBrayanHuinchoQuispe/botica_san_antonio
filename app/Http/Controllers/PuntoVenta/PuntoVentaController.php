<?php

namespace App\Http\Controllers\PuntoVenta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PuntoVenta\{Cliente, Venta, VentaDetalle};
use App\Models\Producto;
use App\Models\ConfiguracionSistema;
use App\Services\ReniecService;
use App\Services\FacturacionElectronicaService;
use App\Services\FacturacionElectronicaServiceBeta;
use App\Services\QueryOptimizationService;
use App\Services\NubeFactService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PuntoVentaController extends Controller
{
    protected $queryOptimizationService;
    protected $loteService;

    public function __construct(QueryOptimizationService $queryOptimizationService, \App\Services\LoteService $loteService)
    {
        $this->queryOptimizationService = $queryOptimizationService;
        $this->loteService = $loteService;
    }

    public function index()
    {
        return view('punto-venta.index');
    }

    /**
     * Obtener productos más vendidos
     */
    public function productosMasVendidos()
    {
        try {
            // Usar el servicio optimizado para obtener productos más vendidos
            $productosConVentas = $this->queryOptimizationService->getProductosMasVendidosOptimizado(10);

            // Si no hay productos con stock, devolver lista vacía
            if ($productosConVentas->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'productos' => [],
                    'message' => 'No hay productos vendidos con stock disponible'
                ]);
            }

            // Formateo seguro (sin requerir relaciones de ubicaciones)
            $productosFormateados = $productosConVentas->map(function($producto) {
                // Asegurar campos presentes
                $stock = isset($producto->stock_actual) ? (int)$producto->stock_actual : 0;
                $fechaVenc = isset($producto->fecha_vencimiento) ? \Carbon\Carbon::parse($producto->fecha_vencimiento) : null;
                $diasParaVencer = $fechaVenc ? now()->diffInDays($fechaVenc, false) : null;
                $estadoVencimiento = $this->getEstadoVencimiento($diasParaVencer);

                // Construir URL pública de imagen con soporte Cloudinary
                $imagenUrl = asset('assets/images/default-product.svg');
                if (!empty($producto->imagen)) {
                    $raw = (string) $producto->imagen;
                    if (preg_match('/^https?:\/\//i', $raw)) {
                        $imagenUrl = $raw;
                    } else {
                        $pathStorage = storage_path('app/public/' . ltrim($raw, '/'));
                        if (file_exists($pathStorage)) {
                            $imagenUrl = asset('storage/' . ltrim($raw, '/'));
                        }
                    }
                }

                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'concentracion' => $producto->concentracion ?? null,
                    'presentacion' => $producto->presentacion ?? 'Presentación estándar',
                    'precio_venta' => isset($producto->precio_venta) ? (float)$producto->precio_venta : 0,
                    'stock_actual' => $stock,
                    'imagen_url' => $imagenUrl,
                    'ubicacion_almacen' => $stock <= 0 ? 'Sin ubicar' : ($producto->ubicacion_almacen ?? ''),
                    'fecha_vencimiento' => $fechaVenc ? $fechaVenc->format('Y-m-d') : null,
                    'dias_para_vencer' => $diasParaVencer,
                    'estado_vencimiento' => $estadoVencimiento,
                    'total_vendido' => $producto->total_vendido ?? 0,
                    'estado' => $stock > 0 ? 'disponible' : 'agotado'
                ];
            });

            return response()->json([
                'success' => true,
                'productos' => $productosFormateados
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos más vendidos',
                'productos' => []
            ], 500);
        }
    }

    /**
     * Buscar alternativas farmacológicas para un producto específico
     */
    public function buscarAlternativas(Request $request)
    {
        try {
            // 🔐 Verificar autenticación
            if (!auth()->check()) {
                Log::warning("❌ Usuario no autenticado intentando acceder a buscarAlternativas");
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }
            
            $termino = $request->get('q');
            
            // 🐛 DEBUG: Log del término recibido
            Log::info("🔍 Buscando alternativas para: " . $termino . " (Usuario: " . auth()->user()->name . ")");
            
            if (empty($termino)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Término de búsqueda requerido'
                ]);
            }

            // 🔍 BUSCAR EL PRODUCTO ORIGINAL DE MANERA MÁS PRECISA
            // Primero buscar coincidencia exacta
            $productoOriginal = Producto::whereRaw('LOWER(nombre) = ?', [strtolower($termino)])->first();
            
            // Si no encuentra exacto, buscar el que más se parezca
            if (!$productoOriginal) {
                $productoOriginal = Producto::where('nombre', 'like', "%{$termino}%")
                    ->orderByRaw('LENGTH(nombre) ASC') // Priorizar nombres más cortos (más específicos)
                    ->first();
            }
            
            // 🐛 DEBUG: Log del producto encontrado
            if ($productoOriginal) {
                Log::info("✅ Producto original encontrado: " . $productoOriginal->nombre . " (ID: " . $productoOriginal->id . ")");
            } else {
                Log::info("❌ No se encontró producto original para: " . $termino);
            }
            
            if (!$productoOriginal) {
                return response()->json([
                    'success' => true,
                    'alternativas' => [],
                    'mensaje' => 'Producto no encontrado para análisis farmacológico'
                ]);
            }

            $alternativas = collect();

            // 🐛 DEBUG: Log del producto original
            Log::info("🔍 Producto original: " . $productoOriginal->nombre . " (Categoría: " . $productoOriginal->categoria . ")");

            // 🧬 PASO 1: PRINCIPIO ACTIVO (Máxima prioridad farmacológica)
            $principioActivo = $this->extraerPrincipioActivo($productoOriginal->nombre);
            Log::info("🧬 Principio activo extraído: " . ($principioActivo ?: 'No encontrado'));
            
            if ($principioActivo) {
                $productosMismoPrincipio = Producto::where('stock_actual', '>', 0)
                    ->where('id', '!=', $productoOriginal->id)
                    ->where(function($query) use ($principioActivo) {
                        $query->where('nombre', 'like', "%{$principioActivo}%")
                              ->orWhere('concentracion', 'like', "%{$principioActivo}%");
                    })
                    ->orderBy('stock_actual', 'desc')
                    ->limit(4)
                    ->get();
                
                Log::info("🧬 Productos con mismo principio activo: " . $productosMismoPrincipio->count());
                $alternativas = $alternativas->merge($productosMismoPrincipio);
            }

            // 🎯 PASO 2: INDICACIÓN TERAPÉUTICA (Por categoría farmacológica)
            $indicacionTerapeutica = $this->obtenerIndicacionTerapeutica($productoOriginal->categoria);
            if ($indicacionTerapeutica) {
                $productosIndicacionSimilar = Producto::where('stock_actual', '>', 0)
                    ->where('id', '!=', $productoOriginal->id)
                    ->whereNotIn('id', $alternativas->pluck('id'))
                    ->whereIn('categoria', $indicacionTerapeutica)
                    ->orderBy('stock_actual', 'desc')
                    ->limit(3)
                    ->get();
                
                $alternativas = $alternativas->merge($productosIndicacionSimilar);
            }

            // 💊 PASO 3: CONCENTRACIÓN EQUIVALENTE
            if ($productoOriginal->concentracion) {
                $concentracionEquivalente = $this->buscarConcentracionEquivalente($productoOriginal->concentracion);
                if ($concentracionEquivalente) {
                    $productosConcentracionEquivalente = Producto::where('stock_actual', '>', 0)
                        ->where('id', '!=', $productoOriginal->id)
                        ->whereNotIn('id', $alternativas->pluck('id'))
                        ->whereIn('concentracion', $concentracionEquivalente)
                        ->orderBy('stock_actual', 'desc')
                        ->limit(3)
                        ->get();
                    
                    $alternativas = $alternativas->merge($productosConcentracionEquivalente);
                }
            }

            // 🏥 PASO 4: GRUPO FARMACOLÓGICO (Misma categoría)
            $productosGrupoFarmacologico = Producto::where('stock_actual', '>', 0)
                ->where('id', '!=', $productoOriginal->id)
                ->whereNotIn('id', $alternativas->pluck('id'))
                ->where('categoria', $productoOriginal->categoria)
                ->orderBy('stock_actual', 'desc')
                ->limit(3)
                ->get();
            
            $alternativas = $alternativas->merge($productosGrupoFarmacologico);

            // 🔄 PASO 5: FALLBACK - Productos relacionados por marca o presentación (solo si hay pocos resultados)
            if ($alternativas->count() < 5) {
                $productosRelacionados = Producto::where('stock_actual', '>', 0)
                    ->where('id', '!=', $productoOriginal->id)
                    ->whereNotIn('id', $alternativas->pluck('id'))
                    ->where(function($query) use ($productoOriginal) {
                        $query->where('marca', $productoOriginal->marca)
                              ->orWhere('presentacion', $productoOriginal->presentacion);
                    })
                    ->orderBy('stock_actual', 'desc')
                    ->limit(3)
                    ->get();
                
                $alternativas = $alternativas->merge($productosRelacionados);
            }

            // 📊 ORDENAR POR RELEVANCIA FARMACOLÓGICA
            $alternativasOrdenadas = $this->ordenarPorRelevanciaFarmacologica($alternativas, $productoOriginal);

            // Eliminar duplicados y limitar resultados
            $alternativasUnicas = $alternativasOrdenadas->unique('id')->take(8);

            // 🐛 DEBUG: Log del resultado final
            Log::info("📊 Total de alternativas únicas encontradas: " . $alternativasUnicas->count());
            foreach ($alternativasUnicas as $alt) {
                Log::info("  ✅ " . $alt->nombre . " (Stock: " . $alt->stock_actual . ")");
            }

            return response()->json([
                'success' => true,
                'producto_original' => $productoOriginal->nombre,
                'criterio_busqueda' => $this->generarCriterioBusqueda($productoOriginal),
                'alternativas' => $alternativasUnicas->map(function($producto) use ($productoOriginal) {
                    return [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'concentracion' => $producto->concentracion,
                        'presentacion' => $producto->presentacion ?? 'Presentación estándar',
                        'precio_venta' => (float) $producto->precio_venta,
                        'stock_actual' => $producto->stock_actual,
                        'imagen_url' => $producto->imagen_url,
                        'ubicacion_almacen' => $producto->ubicacion_almacen,
                        'categoria' => $producto->categoria ?? 'Sin categoría',
                        'marca' => $producto->marca,
                        'similitud' => $this->calcularSimilitudFarmacologica($producto, $productoOriginal),
                        'razon_similitud' => $this->explicarSimilitud($producto, $productoOriginal),
                        'estado' => 'disponible'
                    ];
                })->values()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar alternativas farmacológicas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🧬 Extraer principio activo del nombre del producto
     */
    private function extraerPrincipioActivo($nombre)
    {
        // Diccionario ampliado de principios activos comunes
        $principiosActivos = [
            // Analgésicos y antiinflamatorios
            'paracetamol' => ['paracetamol', 'acetaminofen', 'acetaminofén'],
            'ibuprofeno' => ['ibuprofeno'],
            'aspirina' => ['aspirina', 'ácido acetilsalicílico', 'acido acetilsalicilico'],
            'diclofenaco' => ['diclofenaco'],
            'naproxeno' => ['naproxeno'],
            'ketorolaco' => ['ketorolaco'],
            'meloxicam' => ['meloxicam'],
            
            // Antibióticos
            'amoxicilina' => ['amoxicilina'],
            'azitromicina' => ['azitromicina'],
            'ciprofloxacino' => ['ciprofloxacino'],
            'cefalexina' => ['cefalexina'],
            'clindamicina' => ['clindamicina'],
            'doxiciclina' => ['doxiciclina'],
            'eritromicina' => ['eritromicina'],
            
            // Antihistamínicos
            'loratadina' => ['loratadina'],
            'cetirizina' => ['cetirizina'],
            'difenhidramina' => ['difenhidramina'],
            'clorfenamina' => ['clorfenamina'],
            
            // Gastroprotectores
            'omeprazol' => ['omeprazol'],
            'ranitidina' => ['ranitidina'],
            'pantoprazol' => ['pantoprazol'],
            'esomeprazol' => ['esomeprazol'],
            
            // Cardiovasculares
            'enalapril' => ['enalapril'],
            'losartan' => ['losartan'],
            'amlodipino' => ['amlodipino'],
            'atenolol' => ['atenolol'],
            'simvastatina' => ['simvastatina'],
            'atorvastatina' => ['atorvastatina'],
            
            // Antidiabéticos
            'metformina' => ['metformina'],
            'glibenclamida' => ['glibenclamida'],
            'insulina' => ['insulina'],
            
            // Vitaminas y suplementos
            'vitamina' => ['vitamina', 'complejo', 'tiamina', 'riboflavina', 'niacina', 'piridoxina', 'cobalamina'],
            'acido folico' => ['acido folico', 'ácido fólico', 'folato'],
            'calcio' => ['calcio'],
            'hierro' => ['hierro', 'sulfato ferroso'],
            
            // Antiespasmódicos
            'butilhioscina' => ['butilhioscina', 'hioscina'],
            'trimebutina' => ['trimebutina'],
            
            // Antivirales
            'aciclovir' => ['aciclovir'],
            
            // Antifúngicos
            'fluconazol' => ['fluconazol'],
            'ketoconazol' => ['ketoconazol'],
            
            // Broncodilatadores
            'salbutamol' => ['salbutamol'],
            'teofilina' => ['teofilina'],
            
            // Corticoides
            'prednisolona' => ['prednisolona'],
            'dexametasona' => ['dexametasona'],
            'hidrocortisona' => ['hidrocortisona']
        ];

        $nombreLower = strtolower($nombre);
        
        foreach ($principiosActivos as $principio => $variantes) {
            foreach ($variantes as $variante) {
                if (strpos($nombreLower, $variante) !== false) {
                    return $principio;
                }
            }
        }

        // Si no encuentra un principio activo conocido, usar la primera palabra significativa
        $palabras = explode(' ', $nombreLower);
        $palabrasSignificativas = array_filter($palabras, function($palabra) {
            // Filtrar palabras comunes que no son principios activos
            $palabrasComunes = ['mg', 'ml', 'g', 'tabletas', 'capsulas', 'jarabe', 'suspension', 'gotas', 'crema', 'gel', 'pomada'];
            return !in_array($palabra, $palabrasComunes) && strlen($palabra) > 3;
        });
        
        return !empty($palabrasSignificativas) ? array_values($palabrasSignificativas)[0] : null;
    }

    /**
     * 🎯 Obtener indicaciones terapéuticas relacionadas
     */
    private function obtenerIndicacionTerapeutica($categoria)
    {
        $indicacionesTerapeuticas = [
            // Analgésicos y antiinflamatorios
            'Analgesico' => ['Analgesico', 'Analgésicos', 'Antiinflamatorios', 'Antipiréticos', 'AINES'],
            'Analgésicos' => ['Analgesico', 'Analgésicos', 'Antiinflamatorios', 'Antipiréticos', 'AINES'],
            'Antiinflamatorios' => ['Antiinflamatorios', 'Analgésicos', 'Analgesico', 'AINES'],
            'Antipiréticos' => ['Antipiréticos', 'Analgésicos', 'Analgesico', 'Antiinflamatorios'],
            'AINES' => ['AINES', 'Antiinflamatorios', 'Analgésicos', 'Analgesico'],
            
            // Antimicrobianos
            'Antibióticos' => ['Antibióticos', 'Antimicrobianos', 'Antiinfecciosos', 'Antibioticos'],
            'Antibioticos' => ['Antibióticos', 'Antimicrobianos', 'Antiinfecciosos', 'Antibioticos'],
            'Antimicrobianos' => ['Antimicrobianos', 'Antibióticos', 'Antiinfecciosos'],
            'Antiinfecciosos' => ['Antiinfecciosos', 'Antibióticos', 'Antimicrobianos'],
            
            // Antihistamínicos y antialérgicos
            'Antihistamínicos' => ['Antihistamínicos', 'Antialérgicos', 'Antihistaminicos', 'Antialergicos'],
            'Antihistaminicos' => ['Antihistamínicos', 'Antialérgicos', 'Antihistaminicos', 'Antialergicos'],
            'Antialérgicos' => ['Antialérgicos', 'Antihistamínicos', 'Antialergicos'],
            'Antialergicos' => ['Antialérgicos', 'Antihistamínicos', 'Antialergicos'],
            
            // Cardiovasculares
            'Anticoagulantes' => ['Anticoagulantes', 'Antitrombóticos', 'Antiplaquetarios'],
            'Antihipertensivos' => ['Antihipertensivos', 'Cardiovasculares', 'IECA', 'ARA II', 'Betabloqueadores'],
            'Cardiovasculares' => ['Cardiovasculares', 'Antihipertensivos', 'Cardiotónicos'],
            'Hipolipemiantes' => ['Hipolipemiantes', 'Estatinas', 'Cardiovasculares'],
            
            // Digestivos
            'Digestivos' => ['Digestivos', 'Gastroprotectores', 'Antiespasmódicos', 'Antidiarreicos'],
            'Gastroprotectores' => ['Gastroprotectores', 'Digestivos', 'Antiácidos', 'IBP'],
            'Antiespasmódicos' => ['Antiespasmódicos', 'Digestivos', 'Espasmolíticos'],
            'Antidiarreicos' => ['Antidiarreicos', 'Digestivos', 'Probióticos'],
            'Antiácidos' => ['Antiácidos', 'Gastroprotectores', 'Digestivos'],
            
            // Vitaminas y suplementos
            'Vitaminas' => ['Vitaminas', 'Suplementos', 'Nutricionales', 'Multivitamínicos', 'VITAMINAS'],
            'VITAMINAS' => ['Vitaminas', 'Suplementos', 'Nutricionales', 'Multivitamínicos', 'VITAMINAS'],
            'Suplementos' => ['Suplementos', 'Vitaminas', 'Nutricionales', 'Minerales'],
            'Nutricionales' => ['Nutricionales', 'Vitaminas', 'Suplementos'],
            'Multivitamínicos' => ['Multivitamínicos', 'Vitaminas', 'Suplementos'],
            
            // Respiratorios
            'Broncodilatadores' => ['Broncodilatadores', 'Respiratorios', 'Antiasmáticos'],
            'Antitusivos' => ['Antitusivos', 'Respiratorios', 'Expectorantes'],
            'Expectorantes' => ['Expectorantes', 'Antitusivos', 'Respiratorios'],
            'Respiratorios' => ['Respiratorios', 'Broncodilatadores', 'Antitusivos'],
            
            // Endocrinos
            'Antidiabéticos' => ['Antidiabéticos', 'Hipoglucemiantes', 'Endocrinos'],
            'Hipoglucemiantes' => ['Hipoglucemiantes', 'Antidiabéticos', 'Endocrinos'],
            'Hormonas' => ['Hormonas', 'Endocrinos', 'Tiroideos'],
            'Corticoides' => ['Corticoides', 'Antiinflamatorios', 'Hormonas'],
            
            // Dermatológicos
            'Dermatológicos' => ['Dermatológicos', 'Tópicos', 'Antifúngicos tópicos'],
            'Antifúngicos' => ['Antifúngicos', 'Antimicóticos', 'Dermatológicos'],
            'Antimicóticos' => ['Antimicóticos', 'Antifúngicos', 'Dermatológicos'],
            
            // Neurológicos y psiquiátricos
            'Anticonvulsivantes' => ['Anticonvulsivantes', 'Neurológicos', 'Antiepilépticos'],
            'Ansiolíticos' => ['Ansiolíticos', 'Psiquiátricos', 'Sedantes'],
            'Antidepresivos' => ['Antidepresivos', 'Psiquiátricos'],
            
            // Oftalmológicos y otológicos
            'Oftalmológicos' => ['Oftalmológicos', 'Oculares'],
            'Otológicos' => ['Otológicos', 'Óticos'],
            
            // Ginecológicos y urológicos
            'Ginecológicos' => ['Ginecológicos', 'Hormonales'],
            'Urológicos' => ['Urológicos', 'Genitourinarios']
        ];

        return $indicacionesTerapeuticas[$categoria] ?? [$categoria];
    }

    /**
     * 💊 Buscar concentraciones equivalentes farmacológicamente
     */
    private function buscarConcentracionEquivalente($concentracion)
    {
        if (!$concentracion) return [];

        // Extraer número y unidad con regex mejorada
        preg_match('/(\d+(?:\.\d+)?)\s*([a-zA-Z%]+)/i', $concentracion, $matches);
        
        if (count($matches) < 3) return [$concentracion];

        $numero = floatval($matches[1]);
        $unidad = strtolower($matches[2]);

        $equivalentes = [$concentracion];

        // Concentraciones equivalentes farmacológicamente relevantes
        switch ($unidad) {
            case 'mg':
            case 'miligramos':
                // Buscar concentraciones similares (±25%, ±50%)
                $variaciones = [0.5, 0.75, 1.25, 1.5, 2.0];
                foreach ($variaciones as $factor) {
                    $nueva = round($numero * $factor);
                    if ($nueva > 0 && $nueva != $numero) {
                        $equivalentes[] = $nueva . 'mg';
                    }
                }
                
                // Conversiones a otras unidades
                if ($numero >= 1000) {
                    $equivalentes[] = ($numero / 1000) . 'g';
                }
                $equivalentes[] = ($numero * 1000) . 'mcg';
                
                // Concentraciones estándar comunes
                $estandares = [5, 10, 25, 50, 100, 200, 250, 500, 1000];
                foreach ($estandares as $std) {
                    if (abs($numero - $std) <= $numero * 0.5) {
                        $equivalentes[] = $std . 'mg';
                    }
                }
                break;
                
            case 'ml':
            case 'mililitros':
                // Para líquidos, buscar presentaciones similares
                $variaciones = [0.5, 2.0, 1.5];
                foreach ($variaciones as $factor) {
                    $nueva = round($numero * $factor, 1);
                    if ($nueva > 0) {
                        $equivalentes[] = $nueva . 'ml';
                    }
                }
                break;
                
            case 'g':
            case 'gramos':
                // Conversiones a mg y variaciones
                $equivalentes[] = ($numero * 1000) . 'mg';
                $equivalentes[] = round($numero * 0.5, 2) . 'g';
                $equivalentes[] = round($numero * 2, 2) . 'g';
                break;
                
            case 'mcg':
            case 'μg':
            case 'microgramos':
                // Conversiones y variaciones
                if ($numero >= 1000) {
                    $equivalentes[] = ($numero / 1000) . 'mg';
                }
                $equivalentes[] = round($numero * 0.5) . 'mcg';
                $equivalentes[] = round($numero * 2) . 'mcg';
                break;
                
            case 'ui':
            case 'iu':
            case 'unidades':
                // Para unidades internacionales
                $equivalentes[] = round($numero * 0.5) . 'UI';
                $equivalentes[] = round($numero * 2) . 'UI';
                $equivalentes[] = $numero . 'IU';
                break;
                
            case '%':
                // Para concentraciones porcentuales (tópicos)
                $equivalentes[] = round($numero * 0.5, 1) . '%';
                $equivalentes[] = round($numero * 2, 1) . '%';
                $equivalentes[] = ($numero * 10) . 'mg/ml';
                break;
        }

        // Limpiar duplicados y valores inválidos
        $equivalentes = array_unique(array_filter($equivalentes, function($conc) {
            return !empty($conc) && preg_match('/\d/', $conc);
        }));

        return array_values($equivalentes);
    }

    /**
     * 📊 Ordenar por relevancia farmacológica (PRIORIDAD: FUNCIÓN TERAPÉUTICA)
     */
    private function ordenarPorRelevanciaFarmacologica($productos, $productoOriginal)
    {
        return $productos->sortByDesc(function($producto) use ($productoOriginal) {
            $score = 0;

            // 🎯 PRIORIDAD MÁXIMA: MISMA FUNCIÓN TERAPÉUTICA (+200 puntos)
            if ($producto->categoria === $productoOriginal->categoria) {
                $score += 200; // Duplicado porque es lo más importante
            } else {
                // Verificar categorías relacionadas terapéuticamente
                $indicacionesRelacionadas = $this->obtenerIndicacionTerapeutica($productoOriginal->categoria);
                if (in_array($producto->categoria, $indicacionesRelacionadas)) {
                    $score += 150; // Función terapéutica relacionada
                }
            }

            // 💊 SEGUNDO: Mismo principio activo (+100 puntos)
            $principioOriginal = $this->extraerPrincipioActivo($productoOriginal->nombre);
            $principioProducto = $this->extraerPrincipioActivo($producto->nombre);
            if ($principioOriginal && $principioProducto && $principioOriginal === $principioProducto) {
                $score += 100;
            }

            // 📦 TERCERO: Misma presentación (+50 puntos) - importante para la forma de uso
            if ($producto->presentacion === $productoOriginal->presentacion) {
                $score += 50;
            } else {
                // Presentaciones relacionadas (tabletas ≈ cápsulas, crema ≈ pomada)
                $presentacionesRelacionadas = [
                    'tabletas' => ['comprimidos', 'cápsulas', 'grageas'],
                    'comprimidos' => ['tabletas', 'cápsulas', 'grageas'],
                    'cápsulas' => ['tabletas', 'comprimidos', 'grageas'],
                    'jarabe' => ['suspensión', 'solución oral', 'gotas'],
                    'suspensión' => ['jarabe', 'solución oral', 'gotas'],
                    'crema' => ['pomada', 'gel', 'ungüento'],
                    'pomada' => ['crema', 'gel', 'ungüento']
                ];
                
                $presentOrig = strtolower($productoOriginal->presentacion ?? '');
                $presentProd = strtolower($producto->presentacion ?? '');
                
                if (isset($presentacionesRelacionadas[$presentOrig]) && 
                    in_array($presentProd, $presentacionesRelacionadas[$presentOrig])) {
                    $score += 30;
                }
            }

            // 📊 CUARTO: Stock disponible (+40 puntos máximo)
            if ($producto->stock_actual > 0) {
                $score += 25; // Bonificación base por tener stock
                $score += min(($producto->stock_actual / 10) * 3, 15); // Bonificación por cantidad
            } else {
                $score -= 50; // Penalización por no tener stock
            }

            // 🏷️ QUINTO: Misma marca (+20 puntos) - menos importante que la función
            if ($producto->marca === $productoOriginal->marca) {
                $score += 20;
            }

            // 💰 SEXTO: Concentración similar (+10 puntos) - menos importante
            if ($producto->concentracion === $productoOriginal->concentracion) {
                $score += 10;
            }

            // 📅 SÉPTIMO: Vigencia del producto
            if ($producto->fecha_vencimiento) {
                $diasParaVencer = now()->diffInDays($producto->fecha_vencimiento, false);
                if ($diasParaVencer > 90) {
                    $score += 5; // Bonificación por buena vigencia
                } elseif ($diasParaVencer <= 0) {
                    $score -= 100; // Penalización fuerte por vencido
                } elseif ($diasParaVencer <= 30) {
                    $score -= 25; // Penalización por próximo a vencer
                }
            }

            return $score;
        });
    }

    /**
     * 🔍 Calcular similitud farmacológica (PRIORIDAD: FUNCIÓN TERAPÉUTICA)
     */
    private function calcularSimilitudFarmacologica($producto, $productoOriginal)
    {
        $similitud = 0;

        // 🎯 FUNCIÓN TERAPÉUTICA (50% del peso total)
        if ($producto->categoria === $productoOriginal->categoria) {
            $similitud += 50; // Misma función terapéutica
        } else {
            $indicacionesRelacionadas = $this->obtenerIndicacionTerapeutica($productoOriginal->categoria);
            if (in_array($producto->categoria, $indicacionesRelacionadas)) {
                $similitud += 35; // Función terapéutica relacionada
            }
        }

        // 💊 PRINCIPIO ACTIVO (25% del peso total)
        $principioOriginal = $this->extraerPrincipioActivo($productoOriginal->nombre);
        $principioProducto = $this->extraerPrincipioActivo($producto->nombre);
        if ($principioOriginal && $principioProducto && $principioOriginal === $principioProducto) {
            $similitud += 25;
        }

        // 📦 PRESENTACIÓN (15% del peso total)
        if ($producto->presentacion === $productoOriginal->presentacion) {
            $similitud += 15;
        } else {
            // Presentaciones relacionadas
            $presentacionesRelacionadas = [
                'tabletas' => ['comprimidos', 'cápsulas', 'grageas'],
                'comprimidos' => ['tabletas', 'cápsulas', 'grageas'],
                'cápsulas' => ['tabletas', 'comprimidos', 'grageas'],
                'jarabe' => ['suspensión', 'solución oral', 'gotas'],
                'crema' => ['pomada', 'gel', 'ungüento']
            ];
            
            $presentOrig = strtolower($productoOriginal->presentacion ?? '');
            $presentProd = strtolower($producto->presentacion ?? '');
            
            if (isset($presentacionesRelacionadas[$presentOrig]) && 
                in_array($presentProd, $presentacionesRelacionadas[$presentOrig])) {
                $similitud += 10;
            }
        }

        // 🏷️ MARCA (5% del peso total)
        if ($producto->marca === $productoOriginal->marca) {
            $similitud += 5;
        }

        // 💰 CONCENTRACIÓN (5% del peso total) - ahora menos importante
        if ($producto->concentracion === $productoOriginal->concentracion) {
            $similitud += 5;
        }

        return min($similitud, 100) . '%';
    }

    /**
     * 💬 Explicar por qué es similar (PRIORIDAD: FUNCIÓN TERAPÉUTICA)
     */
    private function explicarSimilitud($producto, $productoOriginal)
    {
        $razones = [];

        // 🎯 PRIORIDAD 1: Función terapéutica (lo más importante)
        if ($producto->categoria === $productoOriginal->categoria) {
            $razones[] = "✅ Misma función terapéutica ({$producto->categoria})";
        } else {
            $indicacionesRelacionadas = $this->obtenerIndicacionTerapeutica($productoOriginal->categoria);
            if (in_array($producto->categoria, $indicacionesRelacionadas)) {
                $razones[] = "🔗 Función terapéutica relacionada ({$producto->categoria})";
            }
        }

        // 💊 PRIORIDAD 2: Principio activo
        $principioOriginal = $this->extraerPrincipioActivo($productoOriginal->nombre);
        $principioProducto = $this->extraerPrincipioActivo($producto->nombre);
        
        if ($principioOriginal && $principioProducto && $principioOriginal === $principioProducto) {
            $razones[] = "💊 Mismo principio activo ({$principioOriginal})";
        }

        // 📦 PRIORIDAD 3: Forma de administración
        if ($producto->presentacion === $productoOriginal->presentacion) {
            $razones[] = "📦 Misma presentación ({$producto->presentacion})";
        }

        // 🏷️ Marca (menos importante)
        if ($producto->marca === $productoOriginal->marca) {
            $razones[] = "🏷️ Misma marca ({$producto->marca})";
        }

        // 💰 Concentración (menos importante ahora)
        if ($producto->concentracion === $productoOriginal->concentracion) {
            $razones[] = "⚖️ Misma concentración ({$producto->concentracion})";
        }

        // 📊 Stock
        if ($producto->stock_actual > 0) {
            $razones[] = "✅ Disponible en stock ({$producto->stock_actual} unidades)";
        } else {
            $razones[] = "⚠️ Sin stock disponible";
        }

        return empty($razones) ? 'Producto farmacéuticamente relacionado' : implode(' • ', $razones);
    }

    /**
     * 📋 Generar criterio de búsqueda
     */
    private function generarCriterioBusqueda($producto)
    {
        $principio = $this->extraerPrincipioActivo($producto->nombre);
        return "Buscando alternativas para {$producto->nombre}: " . 
               "Principio activo: " . ($principio ?: 'No identificado') . 
               ", Categoría: {$producto->categoria}" . 
               ", Concentración: {$producto->concentracion}";
    }

    /**
     * Formatear producto para el POS
     */
    private function formatearProductoParaPOS($producto)
    {
        // Calcular estado del producto
        $estado = $this->calcularEstadoProducto($producto);
        
        // Calcular días para vencer
        $diasParaVencer = null;
        if ($producto->fecha_vencimiento) {
            $diasParaVencer = now()->diffInDays($producto->fecha_vencimiento, false);
        }
        
        return [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'codigo_barras' => $producto->codigo_barras,
            'marca' => $producto->marca ?? 'Sin marca',
            'concentracion' => $producto->concentracion ?? '',
            'categoria' => $producto->categoria ?? 'Sin categoría',
            'presentacion' => $producto->presentacion ?? 'Sin presentación',
            'precio_compra' => $producto->precio_compra,
            'precio_venta' => $producto->precio_venta,
            'stock_actual' => $producto->stock_actual,
            'stock_minimo' => $producto->stock_minimo,
            'ubicacion_almacen' => $producto->ubicacion_almacen ?? 'Sin ubicar',
            'fecha_vencimiento' => $producto->fecha_vencimiento ? $producto->fecha_vencimiento->format('d/m/Y') : null,
            'fecha_vencimiento_raw' => $producto->fecha_vencimiento,
            'dias_para_vencer' => $diasParaVencer,
            'estado' => $estado,
            'imagen_url' => $producto->imagen_url,
            'proveedor_id' => $producto->proveedor_id,
            'es_favorito' => false, // Puede implementarse más adelante
            'descuento' => 0, // Puede implementarse más adelante
            'disponible_online' => $producto->stock_actual > 0
        ];
    }

    public function buscarProductos(Request $request)
    {
        $termino = $request->get('q');

        try {
            if (empty($termino) || strlen($termino) < 2) {
                return response()->json(['success' => true, 'productos' => []]);
            }

            // Evitar errores si las tablas de ubicaciones aún no existen en el entorno local
            // Necesitamos las tres: producto_ubicaciones, ubicaciones y estantes para cargar relaciones anidadas
            $ubicacionesDisponibles = \Illuminate\Support\Facades\Schema::hasTable('producto_ubicaciones')
                && \Illuminate\Support\Facades\Schema::hasTable('ubicaciones')
                && \Illuminate\Support\Facades\Schema::hasTable('estantes');
            $withUbicaciones = $ubicacionesDisponibles ? ['ubicaciones.ubicacion.estante'] : [];

            // 🔥 BÚSQUEDA INTELIGENTE MEJORADA
            // 1. Búsqueda exacta por nombre completo (prioridad máxima)
            $productosExactos = Producto::select(['id', 'nombre', 'concentracion', 'presentacion', 'precio_venta', 'stock_actual', 'imagen', 'ubicacion_almacen', 'categoria', 'marca', 'fecha_vencimiento'])
                ->with($withUbicaciones)
                ->whereRaw('LOWER(nombre) = ?', [strtolower($termino)])
                ->orderBy('stock_actual', 'desc')
                ->limit(5)
                ->get();

            // 2. Búsqueda por nombres que empiecen con el término (prioridad alta)
            $productosInicio = Producto::select(['id', 'nombre', 'concentracion', 'presentacion', 'precio_venta', 'stock_actual', 'imagen', 'ubicacion_almacen', 'categoria', 'marca', 'fecha_vencimiento'])
                ->with($withUbicaciones)
                ->where('nombre', 'like', "{$termino}%")
                ->whereNotIn('id', $productosExactos->pluck('id'))
                ->orderBy('nombre', 'asc')
                ->limit(10)
                ->get();

            // 3. Búsqueda por nombres que contengan el término (prioridad media)
            $productosContienen = Producto::select(['id', 'nombre', 'concentracion', 'presentacion', 'precio_venta', 'stock_actual', 'imagen', 'ubicacion_almacen', 'categoria', 'marca', 'fecha_vencimiento'])
                ->with($withUbicaciones)
                ->where('nombre', 'like', "%{$termino}%")
                ->whereNotIn('id', $productosExactos->pluck('id')->merge($productosInicio->pluck('id')))
                ->orderBy('stock_actual', 'desc')
                ->limit(8)
                ->get();

            // 4. Búsqueda por principio activo (prioridad media-baja)
            $principioActivo = $this->extraerPrincipioActivo($termino);
            $productosPrincipio = collect();
            if ($principioActivo) {
                $productosPrincipio = Producto::select(['id', 'nombre', 'concentracion', 'presentacion', 'precio_venta', 'stock_actual', 'imagen', 'ubicacion_almacen', 'categoria', 'marca', 'fecha_vencimiento'])
                    ->with($withUbicaciones)
                    ->where('nombre', 'like', "%{$principioActivo}%")
                    ->whereNotIn('id', $productosExactos->pluck('id')->merge($productosInicio->pluck('id'))->merge($productosContienen->pluck('id')))
                    ->orderBy('stock_actual', 'desc')
                    ->limit(5)
                    ->get();
            }

            // 5. Búsqueda por marca (prioridad baja)
            $productosMarca = Producto::select(['id', 'nombre', 'concentracion', 'presentacion', 'precio_venta', 'stock_actual', 'imagen', 'ubicacion_almacen', 'categoria', 'marca', 'fecha_vencimiento'])
                ->with($withUbicaciones)
                ->where('marca', 'like', "%{$termino}%")
                ->whereNotIn('id', $productosExactos->pluck('id')->merge($productosInicio->pluck('id'))->merge($productosContienen->pluck('id'))->merge($productosPrincipio->pluck('id')))
                ->orderBy('stock_actual', 'desc')
                ->limit(3)
                ->get();

            // Combinar resultados manteniendo el orden de prioridad
            $productos = $productosExactos
                ->merge($productosInicio)
                ->merge($productosContienen)
                ->merge($productosPrincipio)
                ->merge($productosMarca)
                ->take(20); // Limitar a 20 resultados máximo

            // Formateo optimizado con información de vencimiento y ubicaciones
            // Corregido: capturar $ubicacionesDisponibles dentro del closure para evitar error 500
            $productosFormateados = $productos->map(function($producto) use ($ubicacionesDisponibles) {
                // Calcular días para vencer
                $diasParaVencer = null;
                $estadoVencimiento = 'sin_fecha';

                if ($producto->fecha_vencimiento) {
                    $diasParaVencer = now()->diffInDays($producto->fecha_vencimiento, false);
                    $estadoVencimiento = $this->getEstadoVencimiento($diasParaVencer);
                }

                // Procesar ubicaciones detalladas (solo si las tablas existen)
                $ubicacionesDetalle = [];
                $totalUbicaciones = 0;
                $stockEnUbicaciones = 0;
                if ($ubicacionesDisponibles && $producto->relationLoaded('ubicaciones') && $producto->ubicaciones->count() > 0) {
                    $ubicacionesConStock = $producto->ubicaciones->where('cantidad', '>', 0);
                    // Agrupar por estante + código para evitar duplicados del mismo lugar
                    $ubicacionesAgrupadas = $ubicacionesConStock->groupBy(function ($ubicacion) {
                        $estante = $ubicacion->ubicacion?->estante;
                        return ($estante?->nombre ?? 'Sin asignar') . ' - ' . ($ubicacion->ubicacion?->codigo ?? 'N/A');
                    });

                    $totalUbicaciones = $ubicacionesAgrupadas->count();
                    $stockEnUbicaciones = $ubicacionesConStock->sum('cantidad');

                    $ubicacionesDetalle = $ubicacionesAgrupadas->map(function ($ubicacionesEnMismoLugar, $ubicacionCompleta) {
                        $primera = $ubicacionesEnMismoLugar->first();
                        $estante = $primera->ubicacion?->estante;
                        $cantidadTotal = $ubicacionesEnMismoLugar->sum('cantidad');
                        return [
                            'ubicacion_completa' => $ubicacionCompleta,
                            'codigo' => $primera->ubicacion?->codigo ?? 'N/A',
                            'cantidad' => $cantidadTotal,
                            'lote' => $primera->lote,
                            'fecha_vencimiento' => $primera->fecha_vencimiento?->format('Y-m-d')
                        ];
                    })->values()->toArray();
                }

                $stockSinUbicar = max(0, $producto->stock_actual - $stockEnUbicaciones);
                $tieneStockSinUbicar = $stockSinUbicar > 0;

                // Obtener lista plana de lotes disponibles para selección FEFO
                $lotesDisponibles = [];
                if ($producto->relationLoaded('ubicaciones')) {
                    $lotesDisponibles = $producto->ubicaciones
                        ->where('cantidad', '>', 0)
                        ->where('estado_lote', 'activo')
                        ->sortBy('fecha_vencimiento')
                        ->map(function($lote) {
                            return [
                                'id' => $lote->id,
                                'lote' => $lote->lote,
                                'cantidad' => $lote->cantidad,
                                'fecha_vencimiento' => $lote->fecha_vencimiento ? $lote->fecha_vencimiento->format('Y-m-d') : null,
                                'dias_para_vencer' => $lote->fecha_vencimiento ? now()->diffInDays($lote->fecha_vencimiento, false) : null,
                                'precio_venta' => $lote->precio_venta_lote
                            ];
                        })->values();
                }

                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'concentracion' => $producto->concentracion,
                    'presentacion' => $producto->presentacion ?? 'Presentación estándar',
                    'precio_venta' => (float) $producto->precio_venta,
                    'stock_actual' => $producto->stock_actual,
                    'imagen_url' => $producto->imagen_url,
                    'ubicacion_almacen' => $producto->ubicacion_almacen,
                    'categoria' => $producto->categoria ?? 'Sin categoría',
                    'marca' => $producto->marca ?? 'Sin marca',
                    'fecha_vencimiento' => $producto->fecha_vencimiento ? $producto->fecha_vencimiento->format('Y-m-d') : null,
                    'dias_para_vencer' => $diasParaVencer,
                    'estado_vencimiento' => $estadoVencimiento,
                    'estado' => $producto->stock_actual > 0 ? 'disponible' : 'sin_stock',
                    'lotes_disponibles' => $lotesDisponibles, // Nueva lista para selección de lotes
                    // Información de ubicaciones detalladas
                    'ubicaciones_detalle' => $ubicacionesDetalle,
                    'total_ubicaciones' => $totalUbicaciones,
                    'stock_en_ubicaciones' => $stockEnUbicaciones,
                    'stock_sin_ubicar' => $stockSinUbicar,
                    'tiene_stock_sin_ubicar' => $tieneStockSinUbicar
                ];
            });

            return response()->json([
                'success' => true,
                'productos' => $productosFormateados
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al buscar productos en POS', [
                'termino' => $termino,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al buscar productos',
            ], 500);
        }
    }

    /**
     * Calcular el estado del producto basado en stock y vencimiento
     */
    private function calcularEstadoProducto($producto)
    {
        // Verificar si está vencido
        if ($producto->fecha_vencimiento && $producto->fecha_vencimiento->isPast()) {
            return [
                'codigo' => 'vencido',
                'texto' => 'Vencido',
                'color' => '#dc3545',
                'icono' => '⚫'
            ];
        }
        
        // Verificar si está próximo a vencer (30 días)
        if ($producto->fecha_vencimiento) {
            $diasParaVencer = now()->diffInDays($producto->fecha_vencimiento, false);
            if ($diasParaVencer <= 30) {
                return [
                    'codigo' => 'por_vencer',
                    'texto' => 'Por vencer',
                    'color' => '#f59e0b',
                    'icono' => '🟠'
                ];
            }
        }
        
        // Verificar stock bajo
        if ($producto->stock_actual <= $producto->stock_minimo) {
            return [
                'codigo' => 'stock_bajo',
                'texto' => 'Stock bajo',
                'color' => '#ef4444',
                'icono' => '🔴'
            ];
        }
        
        // Verificar stock crítico (menos del 50% del mínimo)
        if ($producto->stock_actual <= ($producto->stock_minimo * 0.5)) {
            return [
                'codigo' => 'stock_critico',
                'texto' => 'Stock crítico',
                'color' => '#b91c1c',
                'icono' => '🚨'
            ];
        }
        
        // Normal
        return [
            'codigo' => 'normal',
            'texto' => 'Normal',
            'color' => '#10b981',
            'icono' => '🟢'
        ];
    }
    
    /**
     * Obtener estado de vencimiento
     */
    private function getEstadoVencimiento($diasParaVencer)
    {
        if ($diasParaVencer === null) {
            return 'sin_fecha';
        }
        
        if ($diasParaVencer < 0) {
            return 'vencido';
        }
        
        if ($diasParaVencer <= 15) {
            return 'urgente';
        }
        
        if ($diasParaVencer <= 30) {
            return 'proximo';
        }
        
        if ($diasParaVencer <= 60) {
            return 'moderado';
        }
        
        return 'seguro';
    }

    public function consultarDni(Request $request)
    {
        $dni = $request->input('dni');

        if (strlen($dni) !== 8 || !is_numeric($dni)) {
            return response()->json([
                'success' => false,
                'message' => 'DNI debe tener 8 dígitos'
            ]);
        }

        // Primero buscar en la base de datos local
        $cliente = Cliente::buscarPorDni($dni)->first();
        
        if ($cliente) {
            return response()->json([
                'success' => true,
                'cliente' => [
                    'id' => $cliente->id,
                    'dni' => $cliente->dni,
                    'nombre_completo' => $cliente->nombre_completo,
                    'nombres' => $cliente->nombres,
                    'apellido_paterno' => $cliente->apellido_paterno,
                    'apellido_materno' => $cliente->apellido_materno
                ],
                'message' => 'Cliente encontrado en base de datos local'
            ]);
        }

        // Si no existe, consultar API de RENIEC
        try {
            $reniecService = new ReniecService();
            $datosPersona = $reniecService->consultarDni($dni);
            
            if ($datosPersona && $reniecService->validarDatos($datosPersona)) {
                // Crear cliente en la base de datos
                $nuevoCliente = Cliente::create([
                    'dni' => $dni,
                    'nombres' => $datosPersona['nombres'],
                    'apellido_paterno' => $datosPersona['apellido_paterno'],
                    'apellido_materno' => $datosPersona['apellido_materno'],
                    'activo' => true
                ]);

                Log::info("✅ Cliente creado exitosamente desde {$datosPersona['fuente']}: " . $nuevoCliente->nombre_completo);

                return response()->json([
                    'success' => true,
                    'cliente' => [
                        'id' => $nuevoCliente->id,
                        'dni' => $nuevoCliente->dni,
                        'nombre_completo' => $nuevoCliente->nombre_completo,
                        'nombres' => $nuevoCliente->nombres,
                        'apellido_paterno' => $nuevoCliente->apellido_paterno,
                        'apellido_materno' => $nuevoCliente->apellido_materno
                    ],
                    'message' => "Cliente encontrado y registrado desde RENIEC ({$datosPersona['fuente']})"
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('❌ Error consultando DNI: ' . $e->getMessage());
            $diagnostico = (new ReniecService())->diagnostico();
            return response()->json([
                'success' => false,
                'message' => 'No se pudo consultar el DNI. Verifique el número e intente nuevamente. Puede que el DNI no exista o los servicios de RENIEC estén temporalmente no disponibles.',
                'diagnostico' => $diagnostico,
            ], 500);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se pudo consultar el DNI. Verifique el número e intente nuevamente. Puede que el DNI no exista o los servicios de RENIEC estén temporalmente no disponibles.'
        ]);
    }

    public function procesarVenta(Request $request)
    {
        // Logging detallado de entrada
        Log::channel('daily')->info('Iniciando procesamiento de venta', [
            'datos_recibidos' => $request->all(),
            'productos_count' => count($request->productos ?? []),
            'metodo_pago' => $request->metodo_pago,
            'tipo_comprobante' => $request->tipo_comprobante,
            'headers' => $request->headers->all(),
            'ip' => $request->ip(),
            'usuario_id' => Auth::id()
        ]);

        try {
            // Validación de datos de entrada más estricta
            $validator = Validator::make($request->all(), [
                'productos' => 'required|array|min:1',
                'productos.*.id' => 'required|integer',
                'productos.*.cantidad' => 'required|integer|min:1',
                'productos.*.precio' => 'required|numeric|min:0',
                'metodo_pago' => 'required|string',
                'tipo_comprobante' => 'required|string',
                'total' => 'nullable|numeric|min:0',
                'subtotal' => 'nullable|numeric|min:0',
                'igv' => 'nullable|numeric|min:0',
                'descuento' => 'nullable|numeric|min:0',
                'efectivo_recibido' => 'nullable|numeric|min:0',
                'cliente_id' => 'nullable|integer|exists:clientes,id'
            ], [
                // Mensajes de error personalizados
                'productos.required' => 'Debe seleccionar al menos un producto.',
                'productos.*.id.exists' => 'Uno o más productos no existen en el sistema.',
                'productos.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
                'productos.*.cantidad.max' => 'La cantidad no puede superar 1000.',
                'productos.*.precio.min' => 'El precio no puede ser negativo.',
                'productos.*.precio.max' => 'El precio es demasiado alto.',
                'metodo_pago.in' => 'Método de pago inválido.',
                'tipo_comprobante.in' => 'Tipo de comprobante inválido.',
                'cliente_id.exists' => 'El cliente seleccionado no existe.'
            ]);

            if ($validator->fails()) {
                Log::channel('daily')->error('Error de validación al procesar venta', [
                    'errores' => $validator->errors()->toArray(),
                    'datos_recibidos' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de venta inválidos',
                    'errores' => $validator->errors()
                ], 400);
            }

            // Iniciar transacción de base de datos
            DB::beginTransaction();
            
            // Obtener configuración del sistema
            $configuracion = ConfiguracionSistema::obtenerConfiguracion();

            // Calcular totales
            $subtotal = 0;
            $productosIds = [];
            $productosDetalles = [];

            // Validar y preparar productos
            foreach ($request->productos as $producto) {
                $productosIds[] = $producto['id'];
                $productosDetalles[$producto['id']] = $producto;
                $subtotal += $producto['precio'] * $producto['cantidad'];
            }

            // Verificar stock de productos
            $productosDB = Producto::whereIn('id', $productosIds)
                                 ->lockForUpdate()
                                 ->get()
                                 ->keyBy('id');
            
            // Validar stock disponible
            $erroresStock = [];
            foreach ($productosIds as $productoId) {
                $productoModel = $productosDB[$productoId] ?? null;
                $productoDetalle = $productosDetalles[$productoId];

                if (!$productoModel) {
                    $erroresStock[] = "Producto ID {$productoId} no encontrado";
                    continue;
                }
                
                if ($productoModel->stock_actual < $productoDetalle['cantidad']) {
                    $erroresStock[] = "Stock insuficiente para '{$productoModel->nombre}'. Disponible: {$productoModel->stock_actual}, Solicitado: {$productoDetalle['cantidad']}";
                }
            }
            
            if (!empty($erroresStock)) {
                Log::channel('daily')->error('Errores de stock al procesar venta', [
                    'errores' => $erroresStock,
                    'productos' => $request->productos
                ]);
                throw new \Exception("Errores de stock: " . implode('; ', $erroresStock));
            }
            
            // Calcular descuento
            $descuentoMonto = 0;
            $descuentoPorcentaje = 0;
            
            if ($request->descuento_tipo && $request->descuento_valor > 0) {
                if ($request->descuento_tipo === 'porcentaje') {
                    $descuentoPorcentaje = min($request->descuento_valor, $configuracion->descuento_maximo_porcentaje);
                    $descuentoMonto = round($subtotal * ($descuentoPorcentaje / 100), 2);
                } else {
                    $descuentoMonto = min($request->descuento_valor, $subtotal);
                    $descuentoPorcentaje = round(($descuentoMonto / $subtotal) * 100, 2);
                }
            }
            
            // Subtotal con descuento
            $subtotalConDescuento = $subtotal - $descuentoMonto;
            
            // Calcular IGV según configuración
            $igv = 0;
            if ($configuracion->igv_habilitado) {
                $igv = round($subtotalConDescuento * ($configuracion->igv_porcentaje / 100), 2);
            }
            
            $total = round($subtotalConDescuento + $igv, 2);

            // Calcular vuelto solo para pagos en efectivo
            $vuelto = 0;
            $efectivoRecibido = 0;
            
            if ($request->metodo_pago === 'efectivo') {
                $efectivoRecibido = $request->efectivo_recibido ?? 0;
                if ($efectivoRecibido > 0) {
                    $vuelto = max(0, round($efectivoRecibido - $total, 2));
                }
            }

            // Generar número de comprobante electrónico
            $serieBoletaEnv = env('NUBEFACT_SERIE_BOLETA', 'B001');
            if ($request->tipo_comprobante === 'boleta' || $request->tipo_comprobante === 'ticket') {
                $serieComprobante = $serieBoletaEnv ?: 'B001';
            } else {
                $serieComprobante = env('SUNAT_SERIE_FACTURA', 'F001');
            }
            $numeroComprobante = $this->generarNumeroComprobante($serieComprobante);

            // Calcular montos para facturación electrónica
            $montoGravado = 0;
            $montoExonerado = 0;
            $montoInafecto = 0;
            
            if ($configuracion->igv_habilitado) {
                // Si el IGV está habilitado, el monto gravado es el subtotal sin IGV
                $montoGravado = round($subtotalConDescuento / 1.18, 2);
            } else {
                // Si no hay IGV, todo es exonerado
                $montoExonerado = $subtotalConDescuento;
            }

            // Crear venta
            $venta = Venta::create([
                'numero_venta' => $numeroComprobante,
                'cliente_id' => $request->cliente_id,
                'usuario_id' => Auth::id() ?? 1,
                'tipo_comprobante' => $request->tipo_comprobante,
                'subtotal' => $subtotal,
                'igv' => $igv,
                'total' => $total,
                'monto_gravado' => $montoGravado,
                'monto_exonerado' => $montoExonerado,
                'monto_inafecto' => $montoInafecto,
                'monto_gratuito' => 0.00,
                'descuento_porcentaje' => $descuentoPorcentaje,
                'descuento_monto' => $descuentoMonto,
                'descuento_tipo' => $request->descuento_tipo,
                'descuento_razon' => $request->descuento_tipo ? 'Descuento aplicado en venta' : null,
                'igv_incluido' => $configuracion->igv_habilitado,
                'metodo_pago' => $request->metodo_pago,
                'efectivo_recibido' => $efectivoRecibido,
                'vuelto' => $vuelto,
                'estado' => 'completada',
                'fecha_venta' => now()
            ]);

            // Insertar detalles de venta y actualizar stock usando LoteService
            $detallesVenta = [];
            
            foreach ($request->productos as $producto) {
                // Procesar la venta usando LoteService (FIFO o Lote Específico)
                // Si el frontend envía 'lote_id', lo usamos.
                $loteId = $producto['lote_id'] ?? null;
                
                $lotesUsados = $this->loteService->procesarVenta(
                    $producto['id'],
                    $producto['cantidad'],
                    ['venta_id' => $venta->id, 'numero_venta' => $venta->numero_venta],
                    $loteId
                );

                $detallesVenta[] = [
                    'venta_id' => $venta->id,
                    'producto_id' => $producto['id'],
                    'cantidad' => $producto['cantidad'],
                    'precio_unitario' => $producto['precio'],
                    'subtotal' => round($producto['precio'] * $producto['cantidad'], 2),
                    'lotes_info' => json_encode($lotesUsados),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            
            // Insertar detalles de venta
            VentaDetalle::insert($detallesVenta);
            
            // Nota: LoteService ya actualiza el stock total del producto en la tabla productos.
            // No es necesario hacerlo manualmente aquí.

            // Generación de comprobante electrónico temporalmente desactivada
            // Motivo: errores fatales en el Job impiden procesar la venta.
            // TODO: reactivar cuando el Job GenerarComprobanteElectronico esté corregido.

            DB::commit();

            // Enviar a Nubefact y guardar PDF/XML/CDR automáticamente (boleta/ticket)
            try {
                if (in_array($request->tipo_comprobante, ['boleta', 'ticket'])) {
                    $nube = app(NubeFactService::class);
                    // Usar formato PDF oficial de Nubefact según tipo: 'TICKET' para ticket, 'A4' para boleta
                    $formatoPdf = $request->tipo_comprobante === 'ticket' ? 'TICKET' : 'A4';
                    $payload = $nube->buildBoletaPayloadFromVenta($venta, $formatoPdf);
                    $resp = $nube->enviar($payload);
                    $data = $resp['data'] ?? [];
                    $enhanced = $data;
                    // Normalizar y conservar ambos posibles formatos
                    $enhanced['pdf_a4'] = strtoupper($formatoPdf) === 'A4' ? ($data['enlace_del_pdf'] ?? null) : ($data['pdf_a4'] ?? null);
                    $enhanced['pdf_ticket'] = strtoupper($formatoPdf) === 'TICKET' ? ($data['enlace_del_pdf'] ?? null) : ($data['pdf_ticket'] ?? null);
                    $enhanced['enlace_general'] = $data['enlace'] ?? ($enhanced['enlace_general'] ?? null);
                    $venta->nube_data = json_encode($enhanced);

                    // Actualizar datos SUNAT/Nubefact
                    $serie = $data['serie'] ?? ($payload['serie'] ?? null);
                    $numero = isset($data['numero']) ? str_pad((string)$data['numero'], 8, '0', STR_PAD_LEFT) : null;
                    $venta->serie_sunat = $serie ?? $venta->serie_sunat;
                    $venta->correlativo_sunat = $numero ?? $venta->correlativo_sunat;
                    $venta->numero_sunat = ($serie && $numero) ? ($serie . '-' . $numero) : $venta->numero_sunat;
                    $venta->codigo_hash = $data['codigo_hash'] ?? $venta->codigo_hash;
                    $venta->estado_sunat = ($data['aceptada_por_sunat'] ?? false) ? 'ACEPTADO' : 'ENVIADO';
                    $venta->observaciones_sunat = $data['sunat_description'] ?? ($data['sunat_error'] ?? $venta->observaciones_sunat);
                    $venta->fecha_envio_sunat = now();
                    if (($data['aceptada_por_sunat'] ?? false) === true) {
                        $venta->fecha_aceptacion_sunat = now();
                    }

                    // Descargar y guardar archivos
                    if (!empty($data['enlace_del_pdf'])) {
                        $nombrePdf = ($venta->numero_sunat ?: (($serie ?? 'B001') . '-' . ($numero ?? ''))) . '.pdf';
                        $pdfLocal = $nube->descargarYGuardar($data['enlace_del_pdf'], $nombrePdf);
                        // Setear pdf_path según tipo para garantizar impresión correcta
                        if (strtoupper($formatoPdf) === 'A4') {
                            $venta->pdf_path = $pdfLocal ?: $data['enlace_del_pdf'];
                        } else {
                            // Para ticket, si ya existe un A4 previo en nube_data, mantenerlo para boleta A4
                            $decoded = null;
                            try { $decoded = is_array($venta->nube_data) ? $venta->nube_data : json_decode($venta->nube_data, true); } catch (\Throwable $e) { $decoded = null; }
                            $a4Link = $decoded['pdf_a4'] ?? null;
                            $venta->pdf_path = $a4Link ?: ($pdfLocal ?: $data['enlace_del_pdf']);
                        }
                    }
                    if (!empty($data['enlace_del_xml'])) {
                        $nombreXml = ($venta->numero_sunat ?: (($serie ?? 'B001') . '-' . ($numero ?? ''))) . '.xml';
                        $xmlLocal = $nube->descargarYGuardar($data['enlace_del_xml'], $nombreXml);
                        $venta->xml_path = $xmlLocal ?: $data['enlace_del_xml'];
                    }
                    if (!empty($data['enlace_del_cdr'])) {
                        $nombreCdr = ($venta->numero_sunat ?: (($serie ?? 'B001') . '-' . ($numero ?? ''))) . '.zip';
                        $cdrLocal = $nube->descargarYGuardar($data['enlace_del_cdr'], $nombreCdr);
                        $venta->cdr_path = $cdrLocal ?: $data['enlace_del_cdr'];
                    }

                    $venta->save();

                    Log::channel('daily')->info('Venta enviada a Nubefact', [
                        'venta_id' => $venta->id,
                        'numero_sunat' => $venta->numero_sunat,
                        'estado_sunat' => $venta->estado_sunat,
                    ]);
                } else {
                    Log::channel('daily')->info('Tipo de comprobante no enviado a Nubefact', [
                        'venta_id' => $venta->id,
                        'tipo_comprobante' => $request->tipo_comprobante,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::channel('daily')->error('Error enviando venta a Nubefact', [
                    'venta_id' => $venta->id,
                    'error' => $e->getMessage(),
                ]);
                // Marcar como rechazado para seguimiento, mantener la venta
                $venta->estado_sunat = 'RECHAZADO';
                $venta->observaciones_sunat = 'Nubefact: ' . $e->getMessage();
                $venta->save();
            }

            // Invalidar caches relacionados para que el Top 10 y POS reflejen la venta reciente
            try {
                Cache::forget('productos_mas_vendidos_10');
                Cache::forget('productos_mas_vendidos_20');
                Cache::forget('productos_mas_vendidos_50');
                Cache::forget('pos_mas_vendidos_10');
                Cache::forget('pos_mas_vendidos_20');
                Cache::forget('pos_mas_vendidos_50');
                Cache::forget('dashboard_data');
            } catch (\Exception $e) {
                Log::warning('No se pudo invalidar cache tras venta', ['error' => $e->getMessage()]);
            }

            Log::channel('daily')->info('Venta procesada exitosamente', [
                'venta_id' => $venta->id,
                'numero_comprobante' => $venta->numero_venta,
                'total' => $total
            ]);

            // Emitir evento de notificación en tiempo real
            try {
                \App\Events\VentaProcesada::dispatch(
                    $venta->id,
                    (float) $total,
                    (int) (\Illuminate\Support\Facades\Auth::id() ?? 0),
                    [
                        'numero_venta' => $venta->numero_venta,
                        'tipo_comprobante' => $request->tipo_comprobante,
                        'metodo_pago' => $request->metodo_pago,
                    ]
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('No se pudo emitir evento VentaProcesada', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Venta procesada exitosamente',
                'venta' => [
                    'id' => $venta->id,
                    'numero_venta' => $venta->numero_venta,
                    'numero_comprobante' => $venta->numero_venta,
                    'numero_sunat' => $venta->numero_sunat,
                    'estado_sunat' => $venta->estado_sunat,
                    'total' => $total,
                    'subtotal' => $subtotal,
                    'igv' => $igv,
                    'descuento_monto' => $descuentoMonto,
                    'descuento_porcentaje' => $descuentoPorcentaje,
                    'metodo_pago' => $request->metodo_pago,
                    'tipo_comprobante' => $request->tipo_comprobante,
                    'efectivo_recibido' => $efectivoRecibido,
                    'vuelto' => $vuelto,
                    'estado' => 'completada',
                    'fecha_venta' => now()->format('Y-m-d H:i:s')
                ],
                'sunat' => [
                    'aceptada_por_sunat' => $venta->estado_sunat === 'ACEPTADO',
                    'numero' => $venta->numero_sunat,
                    'enlace_del_pdf' => isset($data) ? ($data['enlace_del_pdf'] ?? null) : null,
                    'enlace_del_xml' => isset($data) ? ($data['enlace_del_xml'] ?? null) : null,
                    'enlace_del_cdr' => isset($data) ? ($data['enlace_del_cdr'] ?? null) : null,
                    'cadena_para_codigo_qr' => isset($data) ? ($data['cadena_para_codigo_qr'] ?? null) : null,
                    'codigo_hash' => isset($data) ? ($data['codigo_hash'] ?? null) : null,
                    'descripcion' => $venta->observaciones_sunat,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::channel('daily')->error('Error al procesar venta', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'datos_recibidos' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la venta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar número de comprobante electrónico
     */
    private function generarNumeroComprobante($serie)
    {
        // Obtener el último número de comprobante para la serie
        $ultimoComprobante = Venta::where('numero_venta', 'like', $serie . '%')
            ->orderBy('numero_venta', 'desc')
            ->first();

        $numero = $ultimoComprobante 
            ? intval(substr($ultimoComprobante->numero_venta, -8)) + 1 
            : 1;

        return $serie . str_pad($numero, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Generar comprobante electrónico
     */
    private function generarComprobanteElectronico(Venta $venta)
    {
        try {
            // Verificar si el comprobante requiere generación electrónica
            if (!in_array($venta->tipo_comprobante, ['boleta', 'factura'])) {
                Log::info('No se requiere comprobante electrónico', [
                    'tipo_comprobante' => $venta->tipo_comprobante
                ]);
                return null;
            }

            // Verificar si la extensión SOAP está disponible
            if (!extension_loaded('soap')) {
                Log::warning('Extensión SOAP no disponible, omitiendo facturación electrónica', [
                    'venta_id' => $venta->id
                ]);
                return null;
            }

            // Usar servicio de facturación electrónica
            $facturacionService = new FacturacionElectronicaService();
            
            Log::info('Generando comprobante electrónico', [
                'venta_id' => $venta->id,
                'tipo_comprobante' => $venta->tipo_comprobante
            ]);

            // Generar boleta/factura
            $resultado = $facturacionService->generarBoleta($venta);

            if (!$resultado['success']) {
                Log::error('Error al generar comprobante electrónico', [
                    'venta_id' => $venta->id,
                    'error' => $resultado['message'] ?? 'Error desconocido'
                ]);
                throw new \Exception($resultado['message'] ?? 'Error al generar comprobante electrónico');
            }

            // Actualizar venta con datos del comprobante electrónico
            $venta->update([
                'serie_electronica' => $resultado['serie'],
                'numero_electronico' => $resultado['numero'],
                'hash_cpe' => $resultado['hash'] ?? null,
                'xml_path' => $resultado['xml_path'] ?? null,
                'pdf_path' => $resultado['pdf_path'] ?? null
            ]);

            Log::info('Comprobante electrónico generado exitosamente', [
                'venta_id' => $venta->id,
                'serie_numero' => $resultado['serie'] . '-' . $resultado['numero']
            ]);

            return $resultado;

        } catch (\Exception $e) {
            Log::error('Excepción al generar comprobante electrónico', [
                'venta_id' => $venta->id,
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]);

            // No lanzar excepción para no interrumpir el proceso de venta
            return null;
        }
    }

    public function vistaPrevia($ventaId)
    {
        $venta = Venta::with(['cliente', 'detalles.producto', 'usuario'])
                     ->findOrFail($ventaId);

        return view('punto-venta.vista-previa', compact('venta'));
    }
    
    /**
     * Obtener estadísticas del día
     */
    public function estadisticasHoy()
    {
        try {
            $hoy = now()->format('Y-m-d');
            
            $ventas = Venta::whereDate('fecha_venta', $hoy)
                          ->where('estado', 'completada')
                          ->count();
                          
            $total = Venta::whereDate('fecha_venta', $hoy)
                         ->where('estado', 'completada')
                         ->sum('total');
            
            return response()->json([
                'success' => true,
                'ventas' => $ventas,
                'total' => $total
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'ventas' => 0,
                'total' => 0
            ]);
        }
    }
    
    /**
     * Verificar estado de comprobante electrónico
     */
    public function verificarEstadoComprobante(Request $request)
    {
        try {
            $ventaId = $request->input('venta_id');
            $venta = Venta::findOrFail($ventaId);
            
            if (!in_array($venta->tipo_comprobante, ['boleta', 'factura'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta venta no requiere comprobante electrónico'
                ]);
            }
            
            $facturacionService = new FacturacionElectronicaService();
            $resultado = $facturacionService->verificarEstadoSunat($venta->id);
            
            return response()->json([
                'success' => true,
                'estado' => $resultado
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al verificar estado de comprobante', [
                'venta_id' => $request->input('venta_id'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar el estado: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Regenerar comprobante electrónico
     */
    public function regenerarComprobante(Request $request)
    {
        try {
            $ventaId = $request->input('venta_id');
            $venta = Venta::findOrFail($ventaId);
            
            if (!in_array($venta->tipo_comprobante, ['boleta', 'factura'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta venta no requiere comprobante electrónico'
                ]);
            }
            
            $facturacionService = new FacturacionElectronicaService();
            $resultado = $facturacionService->generarBoleta($venta->id, true); // true para regenerar
            
            if ($resultado['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Comprobante regenerado exitosamente',
                    'comprobante' => [
                        'serie_numero' => $resultado['serie_numero'],
                        'hash' => $resultado['hash'],
                        'qr_code' => $resultado['qr_code']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['message']
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error al regenerar comprobante', [
                'venta_id' => $request->input('venta_id'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al regenerar el comprobante: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener siguiente correlativo para SUNAT
     */
    private function obtenerSiguienteCorrelativo()
    {
        $serie = config('sistema.comprobantes.serie_boleta_default', 'B001');
        
        // Buscar el último correlativo en la base de datos
        $ultimaVenta = Venta::where('serie_sunat', $serie)
            ->whereNotNull('correlativo_sunat')
            ->orderBy('correlativo_sunat', 'desc')
            ->first();
            
        if ($ultimaVenta) {
            $ultimoCorrelativo = (int) $ultimaVenta->correlativo_sunat;
            return str_pad($ultimoCorrelativo + 1, 8, '0', STR_PAD_LEFT);
        }
        
        return '00000001';
    }

    /**
     * Generar PDF de la venta
     */
    public function generarPDF($ventaId)
    {
        try {
            // Cargar venta con relaciones disponibles
            $venta = Venta::with(['detalles.producto', 'cliente'])
                ->findOrFail($ventaId);
            
            // Si es boleta o factura, usar el servicio de facturación electrónica
            if (in_array($venta->tipo_comprobante, ['boleta', 'factura'])) {
                $facturacionService = new FacturacionElectronicaService();
                $resultado = $facturacionService->generarPDF($venta->id);
                
                if ($resultado['success']) {
                    $rutaArchivo = $resultado['pdf_path'];
                    $nombreArchivo = ($venta->comprobante_electronico->serie_numero ?? 'comprobante_' . $venta->id) . '.pdf';
                    
                    return response()->download($rutaArchivo, $nombreArchivo, [
                        'Content-Type' => 'application/pdf'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al generar PDF: ' . $resultado['message']
                    ], 500);
                }
            } else {
                // Para tickets, generar PDF simple
                return $this->generarTicketPDF($venta);
            }
            
        } catch (\Exception $e) {
            Log::error('Error al generar PDF', [
                'venta_id' => $ventaId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar boleta estándar para impresión
     */
    public function boleta($ventaId)
    {
        try {
            $venta = Venta::with(['detalles.producto', 'cliente'])
                ->findOrFail($ventaId);
            
            // Si hay PDF oficial de Nubefact, priorizar servirlo en origen
            if (!empty($venta->pdf_path)) {
                // Si es URL remota de Nubefact, intentar descargar y transmitir en origen
                if (is_string($venta->pdf_path) && preg_match('/^https?:\/\//i', $venta->pdf_path)) {
                    try {
                        $resp = Http::timeout(30)->get($venta->pdf_path);
                        if ($resp->successful()) {
                            $nombreArchivo = ($venta->numero_sunat ?? ('boleta_' . $venta->id)) . '.pdf';
                            return response($resp->body(), 200, [
                                'Content-Type' => 'application/pdf',
                                'Content-Disposition' => 'inline; filename="' . $nombreArchivo . '"'
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('No se pudo transmitir PDF Nubefact para boleta', ['venta_id' => $ventaId, 'error' => $e->getMessage()]);
                    }
                }
                // Si es ruta local y el archivo existe, servirlo
                if (is_string($venta->pdf_path) && file_exists($venta->pdf_path)) {
                    $nombreArchivo = ($venta->numero_sunat ?? ('boleta_' . $venta->id)) . '.pdf';
                    return response()->file($venta->pdf_path, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="' . $nombreArchivo . '"'
                    ]);
                }
            }

            // Fallback: vista HTML interna
            return view('punto-venta.boleta', compact('venta'));
            
        } catch (\Exception $e) {
            Log::error('Error al generar boleta', [
                'venta_id' => $ventaId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al generar boleta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar boleta térmica (80mm) para impresión
     */
    public function ticket($ventaId)
    {
        try {
            $venta = Venta::with(['detalles.producto', 'cliente'])
                ->findOrFail($ventaId);

            if (request()->get('formato') === 'pdf') {
                $config = ConfiguracionSistema::obtenerConfiguracion();
                $width = (float)(request()->get('w', $config->ticket_ancho_papel ?? ($config->papel_ticket_ancho ?? 80)));
                $items = $venta->detalles ? $venta->detalles->count() : 1;
                $baseHeight = 240.0;
                $perItem = 6.0;
                $calculatedHeight = $baseHeight + ($items * $perItem);
                $height = (float)(request()->get('h', max(200.0, $calculatedHeight)));
                $orientation = request()->get('o', 'portrait');
                $pdfService = app(\App\Services\PdfService::class);
                $result = $pdfService->generateViewCustomSize('punto-venta.ticket-termica', ['venta' => $venta, 'modoPdf' => true, 'configuracion' => $config], $width, $height, $orientation, 'ticket_' . $venta->id);
                if (($result['success'] ?? false) === true) {
                    return redirect($result['url']);
                }
                return response()->json(['success' => false, 'message' => $result['message'] ?? 'Error generando PDF'], 500);
            }

            // Bloquear oficial si RECHAZADO
            if (($venta->estado_sunat ?? null) === 'RECHAZADO') {
                $config = ConfiguracionSistema::obtenerConfiguracion();
                return view('punto-venta.ticket-termica', ['venta' => $venta, 'configuracion' => $config]);
            }

            if (!empty($venta->pdf_path)) {
                if (is_string($venta->pdf_path) && preg_match('/^https?:\/\//i', $venta->pdf_path)) {
                    try {
                        $resp = Http::timeout(30)->get($venta->pdf_path);
                        if ($resp->successful()) {
                            $nombreArchivo = ($venta->numero_sunat ?? ('ticket_' . $venta->id)) . '.pdf';
                            return response($resp->body(), 200, [
                                'Content-Type' => 'application/pdf',
                                'Content-Disposition' => 'inline; filename="' . $nombreArchivo . '"'
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('No se pudo transmitir PDF Nubefact para ticket', ['venta_id' => $ventaId, 'error' => $e->getMessage()]);
                    }
                }
                if (is_string($venta->pdf_path) && file_exists($venta->pdf_path)) {
                    $nombreArchivo = ($venta->numero_sunat ?? ('ticket_' . $venta->id)) . '.pdf';
                    return response()->file($venta->pdf_path, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="' . $nombreArchivo . '"'
                    ]);
                }
            }

            $config = ConfiguracionSistema::obtenerConfiguracion();
            return view('punto-venta.ticket-termica', ['venta' => $venta, 'configuracion' => $config]);
            
        } catch (\Exception $e) {
            Log::error('Error al generar boleta térmica', [
                'venta_id' => $ventaId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al generar boleta térmica: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar boleta A4 en formato PDF
     */
    public function pdf($ventaId)
    {
        try {
            // Cargar venta sin requerir relación 'comprobante_electronico'
            $venta = Venta::with(['detalles.producto', 'cliente'])
                ->findOrFail($ventaId);
            $disposition = request()->boolean('download') ? 'attachment' : 'inline';

            // Bloquear oficial si RECHAZADO
            if (($venta->estado_sunat ?? null) === 'RECHAZADO') {
                return $this->generarBoletaA4($venta);
            }
            
            // Priorizar PDF oficial de Nubefact si existe
            if (!empty($venta->pdf_path)) {
                if (is_string($venta->pdf_path) && preg_match('/^https?:\/\//i', $venta->pdf_path)) {
                    try {
                        $resp = Http::timeout(30)->get($venta->pdf_path);
                        if ($resp->successful()) {
                            $nombreArchivo = ($venta->numero_sunat ?? ('comprobante_' . $venta->id)) . '.pdf';
                            return response($resp->body(), 200, [
                                'Content-Type' => 'application/pdf',
                                'Content-Disposition' => $disposition . '; filename="' . $nombreArchivo . '"'
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('No se pudo transmitir PDF Nubefact para A4', ['venta_id' => $ventaId, 'error' => $e->getMessage()]);
                    }
                }
                if (is_string($venta->pdf_path) && file_exists($venta->pdf_path)) {
                    $nombreArchivo = ($venta->numero_sunat ?? ('comprobante_' . $venta->id)) . '.pdf';
                    return response()->file($venta->pdf_path, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => $disposition . '; filename="' . $nombreArchivo . '"'
                    ]);
                }
            }

            // Fallback: Boleta A4 HTML interna
            return $this->generarBoletaA4($venta);
            
        } catch (\Exception $e) {
            Log::error('Error al generar boleta A4', [
                'venta_id' => $ventaId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al generar boleta A4: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar boleta A4 en formato HTML
     */
    private function generarBoletaA4($venta)
    {
        // Generar vista de boleta A4 para descarga/impresión
        return view('punto-venta.boleta-a4', compact('venta'));
    }

}
