<?php

namespace App\Http\Controllers\Ubicacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Estante;
use App\Models\Ubicacion;
use App\Models\Producto;
use App\Models\ProductoUbicacion;
use App\Models\MovimientoStock;
use App\Models\ConfiguracionAlmacen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class UbicacionController extends Controller
{
    /**
     * Mostrar el mapa del almacén con todos los estantes
     */
    public function mapa()
    {
        try {
            Log::info('🗺️ Cargando mapa del almacén...');
            
            // Obtener configuración del almacén
            $configuracion = ConfiguracionAlmacen::obtenerConfiguracion();
            
            // Obtener todos los estantes con información de ocupación
            $estantes = Estante::withCount('ubicaciones')
                ->with(['ubicaciones' => function($query) {
                    $query->withSum('productos', 'cantidad');
                }])
                ->where('activo', true)
                ->get()
                ->map(function ($estante) {
                    $totalSlots = $estante->ubicaciones_count;
                    $slotsOcupados = $estante->ubicaciones->filter(function($ubicacion) {
                        return $ubicacion->productos_sum_cantidad > 0;
                    })->count();
                    
                    $porcentajeOcupacion = $totalSlots > 0 ? ($slotsOcupados / $totalSlots) * 100 : 0;
                    
                    // Determinar estado visual
                    if ($porcentajeOcupacion >= 90) {
                        $estado = 'peligro';
                    } elseif ($porcentajeOcupacion >= 60) {
                        $estado = 'alerta';
            } else {
                        $estado = 'ok';
                    }
                    
                    return [
                        'id' => $estante->id,
                        'nombre' => $estante->nombre,
                        'productos_actuales' => $slotsOcupados,
                        'capacidad_total' => $totalSlots,
                        'ocupacion_porcentaje' => round($porcentajeOcupacion, 1),
                        'estado' => $estado,
                        'tipo' => $estante->tipo,
                        'ubicacion_fisica' => $estante->ubicacion_fisica
                    ];
                });

            // Obtener productos ubicados y sin ubicar
            $productosUbicados = $this->obtenerTodosLosProductosUbicados();
            $productosSinUbicar = $this->obtenerProductosSinUbicar();

            // Estadísticas para la vista
            $estadisticas = [
                'total_productos_ubicados' => $productosUbicados->count(),
                'total_productos_sin_ubicar' => $productosSinUbicar->count(),
                'productos_stock_critico' => $productosUbicados->where('estado', 'stock-critico')->count(),
                'productos_prioridad_alta' => $productosSinUbicar->where('prioridad', 'alta')->count(),
                'estantes_activos' => $estantes->count(),
                'ocupacion_promedio' => $estantes->avg('ocupacion_porcentaje')
            ];

            Log::info("📊 Estadísticas del almacén: " . json_encode($estadisticas));

            return view('ubicaciones.mapa', compact(
                'estantes', 
                'configuracion', 
                'productosUbicados', 
                'productosSinUbicar', 
                'estadisticas'
            ));
        } catch (\Exception $e) {
            Log::error('Error en mapa de almacén: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return view('ubicaciones.mapa', [
                'estantes' => collect(), 
                'configuracion' => null,
                'productosUbicados' => collect(),
                'productosSinUbicar' => collect(),
                'estadisticas' => []
            ]);
        }
    }

    /**
     * Mostrar detalle de un estante específico
     */
    public function detalleEstante($id)
    {
        try {
            Log::info("=== CARGANDO DETALLE ESTANTE ID: {$id} ===");
            
            $estante = Estante::findOrFail($id);
            Log::info("Estante encontrado: {$estante->nombre}, Niveles: {$estante->numero_niveles}, Posiciones: {$estante->numero_posiciones}");

                         // Obtener todas las ubicaciones del estante, incluyendo fusionadas
            $ubicaciones = Ubicacion::where('estante_id', $id)
                ->orderBy('nivel', 'asc')
                ->orderBy('posicion', 'asc')
                ->get();

            // Obtener ubicaciones fusionadas principales
            $ubicacionesFusionadas = Ubicacion::where('estante_id', $id)
                ->where('es_fusionado', true)
                ->get();

            Log::info("Ubicaciones encontradas en BD: " . $ubicaciones->count());

            // Obtener SOLO productos que REALMENTE están ubicados con cantidad > 0
            $productosUbicados = ProductoUbicacion::with(['producto', 'ubicacion'])
                ->whereHas('ubicacion', function($query) use ($id) {
                    $query->where('estante_id', $id);
                })
                ->where('cantidad', '>', 0)
                ->get();

            Log::info("Productos REALMENTE ubicados: " . $productosUbicados->count());

            // LIMPIEZA: Verificar y limpiar datos inconsistentes
            $productosInconsistentes = ProductoUbicacion::whereHas('ubicacion', function($query) use ($id) {
                $query->where('estante_id', $id);
            })
            ->where('cantidad', '<=', 0)
            ->get();

            if ($productosInconsistentes->count() > 0) {
                Log::warning("🧹 LIMPIANDO {$productosInconsistentes->count()} registros con cantidad <= 0");
                foreach ($productosInconsistentes as $inconsistente) {
                    Log::info("  🗑️ Eliminando: ProductoUbicacion ID {$inconsistente->id} (cantidad: {$inconsistente->cantidad})");
                }
                ProductoUbicacion::whereHas('ubicacion', function($query) use ($id) {
                    $query->where('estante_id', $id);
                })->where('cantidad', '<=', 0)->delete();
            }

            // VERIFICACIÓN: Actualizar ubicación de productos sin stock en ubicaciones
            $productosParaActualizar = Producto::whereIn('id', $productosUbicados->pluck('producto_id'))
                ->where('ubicacion_almacen', 'Sin ubicar')
                ->get();

            foreach ($productosParaActualizar as $producto) {
                $producto->actualizarUbicacionAlmacen();
                Log::info("📍 Actualizando ubicación del producto: {$producto->nombre}");
            }

            // Crear mapas para acceso rápido
            $ubicacionesMap = [];
            $productosMap = [];

            // Mapear ubicaciones por código
            foreach ($ubicaciones as $ubicacion) {
                $ubicacionesMap[$ubicacion->codigo] = $ubicacion;
            }

            // Mapear productos por ubicación
            foreach ($productosUbicados as $productoUbicacion) {
                $codigoUbicacion = $productoUbicacion->ubicacion->codigo;
                $productosMap[$codigoUbicacion] = $productoUbicacion;
                
                Log::info("✅ PRODUCTO CONFIRMADO: {$productoUbicacion->producto->nombre} en {$codigoUbicacion} ({$productoUbicacion->cantidad} unidades) - ID ProductoUbicacion: {$productoUbicacion->id}");
            }

            // Determinar rango REAL de productos (incluye productos fuera del rango configurado)
            $maxNivelReal = max($estante->numero_niveles, $productosUbicados->max('ubicacion.nivel') ?? 1);
            $maxPosicionReal = max($estante->numero_posiciones, $productosUbicados->max('ubicacion.posicion') ?? 1);
            
            Log::info("📐 RANGO CONFIGURADO: {$estante->numero_niveles}x{$estante->numero_posiciones}");
            Log::info("📐 RANGO REAL CON PRODUCTOS: {$maxNivelReal}x{$maxPosicionReal}");

            // Construir grilla COMPLETA asegurando todas las posiciones (incluye productos fuera de rango)
            $niveles = [];
            for ($nivel = 1; $nivel <= $maxNivelReal; $nivel++) {
                $productos = [];
                
                for ($posicion = 1; $posicion <= $maxPosicionReal; $posicion++) {
                    $codigoUbicacion = "{$nivel}-{$posicion}";
                    $ubicacion = $ubicacionesMap[$codigoUbicacion] ?? null;
                    $productoUbicacion = $productosMap[$codigoUbicacion] ?? null;
                    
                    // Determinar si esta posición está dentro del rango configurado  
                    $posicionDentroDelRango = ($nivel <= $estante->numero_niveles && $posicion <= $estante->numero_posiciones);

                    // VERIFICAR SI ESTE SLOT ES PARTE DE UNA FUSIÓN SECUNDARIA
                    $esFusionSecundaria = false;
                    if ($ubicacion && $ubicacion->fusion_principal_id && !$ubicacion->es_fusionado) {
                        // Este slot es parte de una fusión, pero no es el principal
                        $esFusionSecundaria = true;
                    }

                    // Si es fusión secundaria, saltar este slot (se renderiza dentro del principal)
                    if ($esFusionSecundaria) {
                        continue;
                    }

                    // Inicializar como slot vacío
                    $productoInfo = [
                        'id' => null,
                        'nombre' => null,
                        'marca' => '',
                        'concentracion' => '',
                        'precio_venta' => 0,
                        'unidades' => null,
                        'codigo_ubicacion' => $codigoUbicacion,
                        'lote' => null,
                        'fecha_vencimiento' => null,
                        'dias_vencimiento' => null,
                        'estado_vencimiento' => null,
                        'ubicacion_id' => $ubicacion ? $ubicacion->id : null,
                        'producto_ubicacion_id' => null,
                        'dentro_del_rango' => $posicionDentroDelRango,
                        'fuera_de_rango' => !$posicionDentroDelRango,
                        'es_fusionado' => $ubicacion && $ubicacion->es_fusionado,
                        'tipo_fusion' => $ubicacion && $ubicacion->es_fusionado ? $ubicacion->tipo_fusion : null,
                        'slots_ocupados' => $ubicacion && $ubicacion->es_fusionado ? $ubicacion->slots_ocupados : 1
                    ];

                    // SOLO si hay un producto CONFIRMADO en esta ubicación
                    if ($productoUbicacion) {
                        $producto = $productoUbicacion->producto;
                        
                        $productoInfo = [
                            'id' => $producto->id,
                            'nombre' => $producto->nombre,
                            'marca' => $producto->marca ?? '',
                            'concentracion' => $producto->concentracion ?? '',
                            'precio_venta' => $producto->precio_venta ?? 0,
                            'unidades' => $productoUbicacion->cantidad,
                            'codigo_ubicacion' => $codigoUbicacion,
                            'lote' => $productoUbicacion->lote,
                            'fecha_vencimiento' => $producto->fecha_vencimiento ? 
                                $producto->fecha_vencimiento->format('d/m/Y') : 
                                '31/12/2025', // Fecha de ejemplo si no existe
                            'dias_vencimiento' => $productoUbicacion->dias_para_vencer ?? null,
                            'estado_vencimiento' => $productoUbicacion->estado_vencimiento ?? 'normal',
                            'ubicacion_id' => $ubicacion->id,
                            'producto_ubicacion_id' => $productoUbicacion->id,
                            'dentro_del_rango' => $posicionDentroDelRango,
                            'fuera_de_rango' => !$posicionDentroDelRango,
                            'es_fusionado' => $ubicacion && $ubicacion->es_fusionado,
                            'tipo_fusion' => $ubicacion && $ubicacion->es_fusionado ? $ubicacion->tipo_fusion : null,
                            'slots_ocupados' => $ubicacion && $ubicacion->es_fusionado ? $ubicacion->slots_ocupados : 1
                        ];
                        
                        Log::info("✅ PRODUCTO MOSTRADO: {$producto->nombre} en {$codigoUbicacion} (Stock: {$productoUbicacion->cantidad})");
                    } else {
                        // Slot realmente vacío
                        if ($ubicacion) {
                            Log::info("📭 Slot {$codigoUbicacion} vacío (ubicación existe)");
                        } else {
                            Log::warning("⚠️ Slot {$codigoUbicacion} - ubicación no existe en BD");
                        }
                    }

                    $productos[] = $productoInfo;
                }
                
                // Determinar si este nivel está dentro del rango configurado
                $dentroDelRango = ($nivel <= $estante->numero_niveles);
                
                $niveles[] = [
                    'numero' => $nivel,
                    'nombre' => "Nivel {$nivel}" . ($dentroDelRango ? '' : ' (Fuera de rango)'),
                    'productos' => $productos,
                    'total_productos' => count(array_filter($productos, fn($p) => $p['nombre'] !== null)),
                    'dentro_del_rango' => $dentroDelRango,
                    'fuera_de_rango' => !$dentroDelRango
                ];
                
                Log::info("Nivel {$nivel} procesado: " . count($productos) . " posiciones");
            }

            $estanteInfo = [
                'id' => $estante->id,
                'nombre' => $estante->nombre,
                'ubicacion_fisica' => $estante->ubicacion_fisica ?? 'Ubicación no especificada',
                'foto_url' => asset('imagen/login/login-fondo.jpg'),
                'niveles' => $niveles,
                'capacidad_total' => $estante->capacidad_total,
                'numero_niveles' => $estante->numero_niveles,
                'numero_posiciones' => $estante->numero_posiciones,
                'numero_niveles_real' => $maxNivelReal,
                'numero_posiciones_real' => $maxPosicionReal,
                'tiene_productos_fuera_de_rango' => ($maxNivelReal > $estante->numero_niveles || $maxPosicionReal > $estante->numero_posiciones),
                'tipo' => $estante->tipo
            ];

            // Log de resumen DETALLADO
            $totalProductos = 0;
            $totalUbicaciones = 0;
            $productosDetectados = [];
            $productosFueraDeRango = [];
            
            foreach ($estanteInfo['niveles'] as $nivel) {
                $totalUbicaciones += count($nivel['productos']);
                foreach ($nivel['productos'] as $producto) {
                    if ($producto['nombre']) {
                        $totalProductos++;
                        $info = "{$producto['nombre']} en {$producto['codigo_ubicacion']}";
                        $productosDetectados[] = $info;
                        
                        if ($producto['fuera_de_rango']) {
                            $productosFueraDeRango[] = $info;
                }
            }
                }
            }
            
            // VERIFICACIÓN FINAL: Asegurar consistencia de productos sin ubicación
            $this->verificarConsistenciaProductos();

            Log::info("🎯 RESUMEN FINAL:");
            Log::info("  - Total productos mostrados: {$totalProductos}");
            Log::info("  - Total ubicaciones: {$totalUbicaciones}");
            Log::info("  - Rango configurado: {$estante->numero_niveles}x{$estante->numero_posiciones}");
            Log::info("  - Rango real mostrado: {$maxNivelReal}x{$maxPosicionReal}");
            Log::info("  - Productos dentro del rango: " . ($totalProductos - count($productosFueraDeRango)));
            Log::info("  - Productos fuera del rango: " . count($productosFueraDeRango));
            
            if (count($productosFueraDeRango) > 0) {
                Log::info("  - ⚠️ Productos fuera de rango: " . implode(', ', $productosFueraDeRango));
            }
            
            Log::info("  - Todos los productos: " . implode(', ', $productosDetectados));

            return view('ubicaciones.estante-detalle', ['estante' => (object)$estanteInfo]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error en detalle de estante: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()->route('ubicaciones.mapa')->with('error', 'Error al cargar el detalle del estante');
        }
    }

    /**
     * Verificar y corregir inconsistencias en la ubicación de productos
     */
    private function verificarConsistenciaProductos()
    {
        try {
            Log::info("🔍 VERIFICANDO CONSISTENCIA DE PRODUCTOS...");
            
            // Obtener todos los productos que tienen registros en producto_ubicaciones
            $productosConUbicacion = ProductoUbicacion::where('cantidad', '>', 0)
                ->pluck('producto_id')
                ->unique();
            
            // Obtener productos que no tienen ubicación pero están marcados como ubicados
            $productosInconsistentes = Producto::whereNotIn('id', $productosConUbicacion)
                ->whereNotNull('ubicacion_almacen')
                ->get();
            
            foreach ($productosInconsistentes as $producto) {
                Log::info("🔧 CORRIGIENDO: {$producto->nombre} (ID: {$producto->id}) - cambiando ubicación_almacen a NULL");
                $producto->ubicacion_almacen = null;
                $producto->save();
            }
            
            if ($productosInconsistentes->count() > 0) {
                Log::info("✅ CORREGIDOS {$productosInconsistentes->count()} productos inconsistentes");
            } else {
                Log::info("✅ Todos los productos están consistentes");
            }
            
        } catch (\Exception $e) {
            Log::error("❌ Error al verificar consistencia: " . $e->getMessage());
        }
    }

    /**
     * API: Obtener productos sin ubicar
     */
    public function productosSinUbicar()
    {
        try {
            Log::info('🔍 Obteniendo productos sin ubicar...');
            
            // Obtener productos que NO tienen ubicación asignada en el almacén
            $productos = Producto::where(function($query) {
                $query->whereNull('ubicacion_almacen');
            })
            // Removido el filtro de stock > 0 para mostrar todos los productos sin ubicar
            ->select('id', 'nombre', 'categoria', 'stock_actual', 'codigo_barras', 'marca', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($producto, $index) {
                // Determinar prioridad basada en múltiples factores para tener variedad
                $diasEsperando = now()->diffInDays($producto->created_at);
                $stock = $producto->stock_actual;
                
                // Determinar tiempo esperando (siempre basado en días)
                if ($diasEsperando >= 1) {
                    $tiempoEsperando = $diasEsperando . ' día' . ($diasEsperando > 1 ? 's' : '');
                } else {
                    $tiempoEsperando = 'Hoy';
                }
                
                // Lógica mejorada para prioridades más variadas
                if ($stock == 0) {
                    // Sin stock = Alta prioridad (necesita ubicación urgente)
                    $prioridad = 'alta';
                } elseif ($diasEsperando >= 7) {
                    // Más de una semana = Alta prioridad
                    $prioridad = 'alta';
                } elseif ($diasEsperando >= 3 || $stock <= 5) {
                    // 3+ días o stock bajo = Media prioridad
                    $prioridad = 'media';
                } elseif ($diasEsperando >= 1) {
                    // 1-2 días = Media prioridad
                    $prioridad = 'media';
                } else {
                    // Para que no todos sean "baja", usar el índice para variar
                    if (($index + $producto->id) % 3 == 0) {
                        $prioridad = 'alta';
                    } elseif (($index + $producto->id) % 2 == 0) {
                        $prioridad = 'media';
                    } else {
                        $prioridad = 'baja';
                    }
                }

                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'categoria' => $producto->categoria ?? 'Sin categoría',
                    'stock' => $producto->stock_actual,
                    'stock_actual' => $producto->stock_actual, // Para compatibilidad con JS
                    'codigo_barras' => $producto->codigo_barras,
                    'marca' => $producto->marca ?? '',
                    'prioridad' => $prioridad,
                    'tiempo_esperando' => $tiempoEsperando,
                    'dias_esperando' => $diasEsperando
                ];
            });

            Log::info("✅ Productos sin ubicar encontrados: " . $productos->count());

            return response()->json([
                'success' => true,
                'data' => $productos,
                'total' => $productos->count()
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener productos sin ubicar: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos sin ubicar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Obtener todos los productos disponibles (sin ubicar en almacén)
     */
    public function todosLosProductos()
    {
        try {
            Log::info('Iniciando consulta de productos sin ubicar en almacén...');
            
            // Solo productos que NO tienen ubicación en el almacén
            $productos = Producto::select('id', 'nombre', 'concentracion')
                ->sinUbicarEnAlmacen()
                ->orderBy('nombre')
                ->get();

            Log::info('Productos encontrados: ' . $productos->count());
            
            // Verificar que tenemos productos
            if ($productos->isEmpty()) {
                Log::warning('No se encontraron productos en la base de datos');
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No hay productos disponibles'
                ]);
            }

            // Convertir a array para asegurar compatibilidad
            $productosArray = $productos->map(function($producto) {
                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'concentracion' => $producto->concentracion
                ];
            })->toArray();

            Log::info('Productos procesados: ' . count($productosArray));

            return response()->json([
                'success' => true,
                'data' => $productosArray,
                'total' => count($productosArray)
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener productos: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos: ' . $e->getMessage(),
                'error_details' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * API: Obtener productos ubicados
     */
    public function productosUbicados()
    {
        try {
            $productos = ProductoUbicacion::with([
                'producto:id,nombre,codigo_barras,marca,categoria',
                'ubicacion.estante:id,nombre'
            ])
            ->where('cantidad', '>', 0)
            ->get()
            ->map(function ($pu) {
                return [
                    'id' => $pu->producto->id,
                    'nombre' => $pu->producto->nombre,
                    'codigo_barras' => $pu->producto->codigo_barras,
                    'cantidad' => $pu->cantidad,
                    'ubicacion' => $pu->ubicacion->estante->nombre . ' - ' . $pu->ubicacion->codigo,
                    'lote' => $pu->lote,
                    'fecha_vencimiento' => $pu->fecha_vencimiento?->format('d/m/Y'),
                    'estado_vencimiento' => $pu->estado_vencimiento
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $productos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos ubicados'
            ], 500);
        }
    }

    /**
     * API: Crear nuevo estante
     */
    public function crearEstante(Request $request)
    {
        // Log simplificado
        Log::info('=== CREAR ESTANTE - INICIO ===');
        Log::info('Datos recibidos: ' . json_encode($request->all()));

        try {
            // Validación básica
            $request->validate([
                'nombre' => 'required|string|max:50',
                'numero_niveles' => 'required|integer|min:1|max:10',
                'numero_posiciones' => 'required|integer|min:1|max:20',
                'tipo' => 'required|in:venta,almacen',
            ]);

            Log::info('Validación exitosa');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación: ' . json_encode($e->errors()));
            
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            Log::info('Iniciando creación del estante...');
            
            // Asegurar que las tablas existan
            $this->crearTablasBasicas();
            
            DB::beginTransaction();

            // Datos del estante
            $numeroNiveles = (int) $request->numero_niveles;
            $numeroPosiciones = (int) $request->numero_posiciones;
            $capacidadTotal = $numeroNiveles * $numeroPosiciones;

            // Usar DB raw para insertar directamente (sin descripcion que no existe)
            $estanteId = DB::table('estantes')->insertGetId([
                'nombre' => trim($request->nombre),
                'numero_niveles' => $numeroNiveles,
                'numero_posiciones' => $numeroPosiciones,
                'capacidad_total' => $capacidadTotal,
                'tipo' => $request->tipo,
                'ubicacion_fisica' => trim($request->ubicacion_fisica ?? ''),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info("Estante creado con ID: {$estanteId}");

            // Crear ubicaciones directamente con DB
            $ubicaciones = [];
            for ($nivel = 1; $nivel <= $numeroNiveles; $nivel++) {
                for ($posicion = 1; $posicion <= $numeroPosiciones; $posicion++) {
                    $ubicaciones[] = [
                        'estante_id' => $estanteId,
                        'nivel' => $nivel,
                        'posicion' => $posicion,
                        'codigo' => "{$nivel}-{$posicion}",
                        'capacidad_maxima' => 1,
                        'activo' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }

            DB::table('ubicaciones')->insert($ubicaciones);
            Log::info("Ubicaciones creadas: " . count($ubicaciones));

            DB::commit();
            Log::info('Transacción completada exitosamente');

            return response()->json([
                'success' => true,
                'message' => 'Estante creado exitosamente',
                'data' => [
                    'id' => $estanteId,
                    'nombre' => $request->nombre,
                    'numero_niveles' => $numeroNiveles,
                    'numero_posiciones' => $numeroPosiciones,
                    'capacidad_total' => $capacidadTotal,
                    'ubicaciones_creadas' => count($ubicaciones)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('=== CREAR ESTANTE - ERROR ===');
            Log::error('Error: ' . $e->getMessage());
            Log::error('Línea: ' . $e->getLine());
            Log::error('Archivo: ' . $e->getFile());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el estante: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asegurar que las tablas existan con SQL directo
     */
    private function crearTablasBasicas()
    {
        try {
            // Crear tabla estantes si no existe
            DB::statement("
                CREATE TABLE IF NOT EXISTS estantes (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    nombre VARCHAR(50) NOT NULL,
                    capacidad_total INT NOT NULL DEFAULT 20,
                    numero_niveles INT NOT NULL DEFAULT 4,
                    numero_posiciones INT NOT NULL DEFAULT 5,
                    ubicacion_fisica VARCHAR(255) NULL,
                    tipo ENUM('venta', 'almacen') NOT NULL DEFAULT 'venta',
                    activo TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            // Crear tabla ubicaciones si no existe
            DB::statement("
                CREATE TABLE IF NOT EXISTS ubicaciones (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    estante_id BIGINT UNSIGNED NOT NULL,
                    nivel INT NOT NULL,
                    posicion INT NOT NULL,
                    codigo VARCHAR(10) NOT NULL,
                    capacidad_maxima INT NOT NULL DEFAULT 1,
                    activo TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY unique_estante_codigo (estante_id, codigo),
                    UNIQUE KEY unique_estante_nivel_posicion (estante_id, nivel, posicion)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            Log::info('Tablas verificadas/creadas correctamente');

        } catch (\Exception $e) {
            Log::error('Error al crear tablas: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * API: Ubicar producto en una posición específica
     */
    public function ubicarProducto(Request $request)
    {
        Log::info('=== UBICAR PRODUCTO - INICIO ===');
        Log::info('📨 Método HTTP: ' . $request->method());
        Log::info('📨 URL: ' . $request->fullUrl());
        Log::info('📨 Headers: ' . json_encode($request->headers->all()));
        Log::info('📨 Datos recibidos: ' . json_encode($request->all()));
        Log::info('📨 Contenido raw: ' . $request->getContent());
        
        try {
        $request->validate([
                'producto_id' => 'required|integer',
                'ubicacion_id' => 'required|integer', 
                'cantidad' => 'required|integer|min:0',
            'lote' => 'nullable|string|max:100',
                'fecha_vencimiento' => 'nullable|date'
        ]);

            Log::info('✅ Validación básica exitosa');
            
            // Verificar que existan los registros después de la validación básica
            $producto = Producto::find($request->producto_id);
            if (!$producto) {
                Log::error("❌ Producto no encontrado: ID {$request->producto_id}");
                return response()->json([
                    'success' => false,
                    'message' => "Producto con ID {$request->producto_id} no encontrado"
                ], 404);
            }
            
            $ubicacion = Ubicacion::find($request->ubicacion_id);
            if (!$ubicacion) {
                Log::error("❌ Ubicación no encontrada: ID {$request->ubicacion_id}");
                    return response()->json([
                        'success' => false,
                    'message' => "Ubicación con ID {$request->ubicacion_id} no encontrada"
                ], 404);
                }
                
            Log::info('✅ Validación completa exitosa');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Error de validación: ' . json_encode($e->errors()));
            Log::error('❌ Datos recibidos para validación: ' . json_encode($request->all()));
                    return response()->json([
                        'success' => false,
                'message' => 'Error de validación: ' . json_encode($e->errors()),
                'errors' => $e->errors(),
                'received_data' => $request->all()
                    ], 422);
                }

        try {
            Log::info('🔄 Iniciando proceso ultra-simple...');
            
            // Verificar que los IDs existan
            $ubicacion = Ubicacion::with('estante')->find($request->ubicacion_id);
            $producto = Producto::find($request->producto_id);
            
            if (!$ubicacion) {
                return response()->json(['success' => false, 'message' => 'Ubicación no encontrada'], 404);
            }
            
            if (!$producto) {
                return response()->json(['success' => false, 'message' => 'Producto no encontrado'], 404);
            }
            
            Log::info("Ubicando: {$producto->nombre} en {$ubicacion->estante->nombre} - {$ubicacion->codigo}");
            
            // 1. Insertar en producto_ubicaciones
            $lote = 'LOTE_' . date('YmdHis');
            $productoUbicacionId = DB::table('producto_ubicaciones')->insertGetId([
                    'producto_id' => $request->producto_id,
                    'ubicacion_id' => $request->ubicacion_id,
                    'cantidad' => $request->cantidad,
                    'lote' => $lote,
                    'fecha_ingreso' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            Log::info("✅ Registro insertado en producto_ubicaciones con ID: {$productoUbicacionId}");
            
            // 2. Actualizar tabla productos directamente (solo ubicación, NO el stock)
            $ubicacionTexto = $ubicacion->estante->nombre . ' - ' . $ubicacion->codigo;
            
            DB::table('productos')
                ->where('id', $request->producto_id)
                ->update([
                    'ubicacion_almacen' => $ubicacionTexto,
                    'updated_at' => now()
                ]);
            
            Log::info("✅ Producto actualizado: ubicacion = '{$ubicacionTexto}', stock = {$request->cantidad}");

            // 3. Verificar que se guardó
            $verificacion = DB::table('productos')->where('id', $request->producto_id)->first();
            Log::info("🔍 Verificación - Ubicación actual en BD: '{$verificacion->ubicacion}'");
            
            $verificacionPU = DB::table('producto_ubicaciones')->where('id', $productoUbicacionId)->first();
            Log::info("🔍 Verificación - ProductoUbicacion en BD: cantidad = {$verificacionPU->cantidad}");
            
            Log::info('=== UBICACIÓN GUARDADA EXITOSAMENTE ===');

            return response()->json([
                'success' => true,
                'message' => 'Producto ubicado exitosamente',
                'data' => [
                    'producto_ubicacion_id' => $productoUbicacionId,
                    'producto_nombre' => $producto->nombre,
                    'ubicacion_codigo' => $ubicacion->codigo,
                    'ubicacion_texto' => $ubicacionTexto,
                    'cantidad_agregada' => $request->cantidad,
                    'verificacion' => [
                        'producto_ubicacion_bd' => $verificacion->ubicacion,
                        'stock_bd' => $verificacion->stock_actual
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al ubicar producto: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al ubicar el producto: ' . $e->getMessage(),
                'debug_info' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    /**
     * API: Distribuir producto en múltiples ubicaciones
     */
    public function distribuirProductoMultiple(Request $request)
    {
        Log::info('=== DISTRIBUCIÓN MÚLTIPLE - INICIO ===');
        Log::info('📨 Datos recibidos: ' . json_encode($request->all()));
        
        try {
            $request->validate([
                'producto_id' => 'required|integer',
                'distribuciones' => 'required|array|min:1',
                'distribuciones.*.ubicacion_id' => 'required|integer',
                'distribuciones.*.cantidad' => 'required|integer|min:1',
                'distribuciones.*.lote' => 'nullable|string|max:100',
                'distribuciones.*.fecha_vencimiento' => 'nullable|date'
            ]);

            DB::beginTransaction();

            $producto = Producto::findOrFail($request->producto_id);
            $distribuciones = $request->distribuciones;
            
            // Validar que las ubicaciones existan
            $ubicacionesIds = collect($distribuciones)->pluck('ubicacion_id');
            $ubicacionesValidas = Ubicacion::with('estante')->whereIn('id', $ubicacionesIds)->get();
            
            if ($ubicacionesValidas->count() !== $ubicacionesIds->count()) {
                throw new \Exception('Una o más ubicaciones no son válidas');
            }

            // Calcular el total a distribuir
            $totalCantidad = collect($distribuciones)->sum('cantidad');
            
            Log::info("Distribuyendo {$totalCantidad} unidades de '{$producto->nombre}' en " . count($distribuciones) . " ubicaciones");

            // Limpiar ubicaciones anteriores del producto
            ProductoUbicacion::where('producto_id', $producto->id)->delete();

            $ubicacionesCreadas = [];
            $ubicacionesTexto = [];

            // Crear nuevas ubicaciones
            foreach ($distribuciones as $distribucion) {
                $ubicacion = $ubicacionesValidas->where('id', $distribucion['ubicacion_id'])->first();
                
                $lote = $distribucion['lote'] ?? 'LOTE_' . date('YmdHis') . '_' . rand(100, 999);
                
                $productoUbicacion = ProductoUbicacion::create([
                    'producto_id' => $producto->id,
                    'ubicacion_id' => $distribucion['ubicacion_id'],
                    'cantidad' => $distribucion['cantidad'],
                    'lote' => $lote,
                    'fecha_ingreso' => now(),
                    'fecha_vencimiento' => $distribucion['fecha_vencimiento'] ?? null,
                    'observaciones' => "Distribuido en ubicación múltiple"
                ]);

                $ubicacionTexto = $ubicacion->estante->nombre . ' - ' . $ubicacion->codigo;
                $ubicacionesTexto[] = $ubicacionTexto;
                
                $ubicacionesCreadas[] = [
                    'id' => $productoUbicacion->id,
                    'ubicacion_texto' => $ubicacionTexto,
                    'cantidad' => $distribucion['cantidad'],
                    'lote' => $lote
                ];

                Log::info("✅ Ubicación creada: {$ubicacionTexto} con {$distribucion['cantidad']} unidades");
            }

            // Actualizar el producto con información de múltiples ubicaciones
            $ubicacionAlmacen = count($ubicacionesTexto) > 1 
                ? "Múltiples ubicaciones (" . count($ubicacionesTexto) . ")"
                : $ubicacionesTexto[0];

            $producto->update([
                'ubicacion_almacen' => $ubicacionAlmacen
            ]);

            // Registrar movimiento de stock
            MovimientoStock::create([
                'producto_id' => $producto->id,
                'tipo_movimiento' => 'entrada',
                'cantidad' => $totalCantidad,
                'motivo' => "Producto distribuido en " . count($distribuciones) . " ubicaciones",
                'usuario_id' => auth()->id()
            ]);

            DB::commit();

            Log::info('=== DISTRIBUCIÓN MÚLTIPLE COMPLETADA ===');

            return response()->json([
                'success' => true,
                'message' => 'Producto distribuido exitosamente en múltiples ubicaciones',
                'data' => [
                    'producto_id' => $producto->id,
                    'producto_nombre' => $producto->nombre,
                    'total_cantidad' => $totalCantidad,
                    'total_ubicaciones' => count($ubicacionesCreadas),
                    'ubicaciones' => $ubicacionesCreadas,
                    'ubicacion_almacen' => $ubicacionAlmacen
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('❌ Error en distribución múltiple: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al distribuir el producto: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * API: Obtener información de stock de un producto
     */
    public function obtenerInformacionStock($productoId)
    {
        try {
            $producto = Producto::with(['ubicaciones.ubicacion.estante'])->findOrFail($productoId);
            
            // Calcular información de stock
            $ubicacionesConStock = $producto->ubicaciones->where('cantidad', '>', 0);
            $stockEnUbicaciones = $ubicacionesConStock->sum('cantidad');
            $stockSinUbicar = max(0, $producto->stock_actual - $stockEnUbicaciones);
            $totalUbicaciones = $ubicacionesConStock->count();
            
            // Obtener nombres de ubicaciones existentes
            $ubicacionesExistentes = $ubicacionesConStock->map(function($ubicacion) {
                $estante = $ubicacion->ubicacion?->estante;
                return ($estante?->nombre ?? 'Sin asignar') . ' - ' . ($ubicacion->ubicacion?->codigo ?? 'N/A');
            })->toArray();
            
            return response()->json([
                'success' => true,
                'producto' => [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'stock_actual' => $producto->stock_actual,
                    'stock_en_ubicaciones' => $stockEnUbicaciones,
                    'stock_sin_ubicar' => $stockSinUbicar,
                    'total_ubicaciones' => $totalUbicaciones,
                    'ubicaciones_existentes' => $ubicacionesExistentes
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo información de stock: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del producto'
            ], 500);
        }
    }

    /**
     * API: Eliminar producto de ubicación
     */
    public function eliminarProductoDeUbicacion(Request $request)
    {
        Log::info('=== ELIMINAR PRODUCTO DE UBICACIÓN - INICIO ===');
        Log::info('📨 Datos recibidos: ' . json_encode($request->all()));
        
        try {
            $request->validate([
                'producto_ubicacion_id' => 'required|integer'
            ]);

            DB::beginTransaction();

            $productoUbicacion = ProductoUbicacion::with(['producto', 'ubicacion.estante'])
                                                 ->findOrFail($request->producto_ubicacion_id);
            
            $cantidadEliminada = $productoUbicacion->cantidad;
            $producto = $productoUbicacion->producto;
            
            // Eliminar la relación producto-ubicación
            $productoUbicacion->delete();
            
            // Actualizar stock total del producto
            $nuevoStockTotal = ProductoUbicacion::where('producto_id', $producto->id)->sum('cantidad');
            $producto->update(['stock_actual' => $nuevoStockTotal]);
            
            // Actualizar ubicacion_almacen
            $producto->actualizarUbicacionAlmacen();
            
            // Registrar movimiento
            MovimientoStock::create([
                'producto_id' => $producto->id,
                'tipo_movimiento' => 'salida',
                'cantidad' => $cantidadEliminada,
                'motivo' => 'Producto eliminado de ubicación',
                'usuario_id' => auth()->id()
            ]);

            DB::commit();

            Log::info('=== PRODUCTO ELIMINADO DE UBICACIÓN EXITOSAMENTE ===');

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado de la ubicación exitosamente',
                'data' => [
                    'cantidad_eliminada' => $cantidadEliminada,
                    'nuevo_stock_total' => $nuevoStockTotal
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('❌ Error eliminando producto de ubicación: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Mover producto entre ubicaciones
     */
    public function moverProducto(Request $request)
    {
        Log::info('=== MOVER PRODUCTO - INICIO ===');
        Log::info('Datos recibidos: ' . json_encode($request->all()));

        $request->validate([
            'estante_id' => 'required|exists:estantes,id',
            'ubicacion_origen_id' => 'required|exists:ubicaciones,id',
            'slot_origen' => 'required|string',
            'slot_destino' => 'required|string',
            'producto_id' => 'required|exists:productos,id',
            'motivo' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Buscar ubicación destino por slot
            $ubicacionDestino = Ubicacion::where('estante_id', $request->estante_id)
                ->where('codigo', $request->slot_destino)
                ->first();

            if (!$ubicacionDestino) {
                Log::error("Ubicación destino no encontrada para slot: {$request->slot_destino}");
                return response()->json([
                    'success' => false,
                    'message' => "No se encontró la ubicación para el slot {$request->slot_destino}"
                ], 422);
            }

            // Verificar ubicación origen
            $ubicacionOrigen = Ubicacion::findOrFail($request->ubicacion_origen_id);
            
            Log::info("Ubicación origen: {$ubicacionOrigen->codigo} (ID: {$ubicacionOrigen->id})");
            Log::info("Ubicación destino: {$ubicacionDestino->codigo} (ID: {$ubicacionDestino->id})");

            // Obtener el producto ubicado en origen
            $productoEnOrigen = ProductoUbicacion::where('producto_id', $request->producto_id)
                ->where('ubicacion_id', $request->ubicacion_origen_id)
                ->first();

            if (!$productoEnOrigen) {
                Log::error("Producto no encontrado en ubicación origen");
                return response()->json([
                    'success' => false,
                    'message' => 'El producto no se encuentra en la ubicación origen'
                ], 422);
            }

            // Verificar que la ubicación destino esté vacía
            $productosEnDestino = $ubicacionDestino->productos()->where('cantidad', '>', 0)->get();
            
            if ($productosEnDestino->count() > 0) {
                Log::error("Ubicación destino ocupada");
                return response()->json([
                    'success' => false,
                    'message' => 'La ubicación destino ya está ocupada'
                ], 422);
            }

            $cantidadAMover = $productoEnOrigen->cantidad;
            Log::info("Moviendo {$cantidadAMover} unidades del producto ID {$request->producto_id}");

            // Crear registro en ubicación destino
            ProductoUbicacion::create([
                'producto_id' => $request->producto_id,
                'ubicacion_id' => $ubicacionDestino->id,
                'cantidad' => $cantidadAMover,
                'lote' => $productoEnOrigen->lote,
                'fecha_ingreso' => $productoEnOrigen->fecha_ingreso,
                'fecha_vencimiento' => $productoEnOrigen->fecha_vencimiento
            ]);

            // Eliminar registro de ubicación origen
            $productoEnOrigen->delete();

            // Registrar movimiento
            try {
                MovimientoStock::registrarTransferencia(
                    $request->producto_id,
                    $request->ubicacion_origen_id,
                    $ubicacionDestino->id,
                    $cantidadAMover,
                    $request->motivo ?? 'Movimiento manual entre ubicaciones'
                );
                Log::info("Movimiento registrado exitosamente");
            } catch (\Exception $e) {
                Log::warning("Error al registrar movimiento: " . $e->getMessage());
                // Continuar sin fallar por esto
            }

            // Actualizar ubicación en almacén del producto
            try {
                $producto = Producto::findOrFail($request->producto_id);
                $producto->actualizarUbicacionAlmacen();
                Log::info("Ubicación del producto actualizada");
            } catch (\Exception $e) {
                Log::warning("Error al actualizar ubicación del producto: " . $e->getMessage());
                // Continuar sin fallar por esto
            }

            DB::commit();
            
            Log::info('=== MOVER PRODUCTO - ÉXITO ===');

            return response()->json([
                'success' => true,
                'message' => 'Producto movido exitosamente',
                'data' => [
                    'producto_id' => $request->producto_id,
                    'slot_origen' => $request->slot_origen,
                    'slot_destino' => $request->slot_destino,
                    'cantidad_movida' => $cantidadAMover,
                    'ubicacion_origen_id' => $request->ubicacion_origen_id,
                    'ubicacion_destino_id' => $ubicacionDestino->id
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('=== MOVER PRODUCTO - ERROR ===');
            Log::error('Error: ' . $e->getMessage());
            Log::error('Línea: ' . $e->getLine());
            Log::error('Archivo: ' . $e->getFile());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al mover el producto: ' . $e->getMessage(),
                'debug_info' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    /**
     * API: Intercambiar productos entre dos ubicaciones (para drag and drop)
     */
    public function intercambiarProductos(Request $request)
    {
        Log::info('=== INTERCAMBIAR PRODUCTOS - INICIO ===');
        Log::info('Datos recibidos: ' . json_encode($request->all()));

        $request->validate([
            'estante_id' => 'required|exists:estantes,id',
            'producto1' => 'required|array',
            'producto1.id' => 'required|exists:productos,id',
            'producto1.ubicacion_id' => 'required|exists:ubicaciones,id',
            'producto1.slot' => 'required|string',
            'producto2' => 'required|array',
            'producto2.id' => 'required|exists:productos,id',
            'producto2.ubicacion_id' => 'required|exists:ubicaciones,id',
            'producto2.slot' => 'required|string',
            'motivo' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Obtener las ubicaciones
            $ubicacion1 = Ubicacion::findOrFail($request->producto1['ubicacion_id']);
            $ubicacion2 = Ubicacion::findOrFail($request->producto2['ubicacion_id']);
            
            Log::info("Ubicación 1: {$ubicacion1->codigo} (ID: {$ubicacion1->id})");
            Log::info("Ubicación 2: {$ubicacion2->codigo} (ID: {$ubicacion2->id})");

            // Obtener los productos en sus ubicaciones actuales
            $productoUbicacion1 = ProductoUbicacion::where('producto_id', $request->producto1['id'])
                ->where('ubicacion_id', $request->producto1['ubicacion_id'])
                ->first();

            $productoUbicacion2 = ProductoUbicacion::where('producto_id', $request->producto2['id'])
                ->where('ubicacion_id', $request->producto2['ubicacion_id'])
                ->first();

            if (!$productoUbicacion1) {
                Log::error("Producto 1 no encontrado en ubicación 1");
                return response()->json([
                    'success' => false,
                    'message' => 'El producto 1 no se encuentra en su ubicación'
                ], 422);
            }

            if (!$productoUbicacion2) {
                Log::error("Producto 2 no encontrado en ubicación 2");
                return response()->json([
                    'success' => false,
                    'message' => 'El producto 2 no se encuentra en su ubicación'
                ], 422);
            }

            // Datos de los productos antes del intercambio
            $datosProducto1 = [
                'producto_id' => $productoUbicacion1->producto_id,
                'cantidad' => $productoUbicacion1->cantidad,
                'lote' => $productoUbicacion1->lote,
                'fecha_ingreso' => $productoUbicacion1->fecha_ingreso,
                'fecha_vencimiento' => $productoUbicacion1->fecha_vencimiento
            ];

            $datosProducto2 = [
                'producto_id' => $productoUbicacion2->producto_id,
                'cantidad' => $productoUbicacion2->cantidad,
                'lote' => $productoUbicacion2->lote,
                'fecha_ingreso' => $productoUbicacion2->fecha_ingreso,
                'fecha_vencimiento' => $productoUbicacion2->fecha_vencimiento
            ];

            Log::info("Intercambiando productos:");
            Log::info("Producto 1: {$datosProducto1['cantidad']} unidades");
            Log::info("Producto 2: {$datosProducto2['cantidad']} unidades");

            // Eliminar los registros actuales
            $productoUbicacion1->delete();
            $productoUbicacion2->delete();

            // Crear los nuevos registros intercambiados
            // Producto 1 va a la ubicación 2
            ProductoUbicacion::create([
                'producto_id' => $datosProducto1['producto_id'],
                'ubicacion_id' => $ubicacion2->id,
                'cantidad' => $datosProducto1['cantidad'],
                'lote' => $datosProducto1['lote'],
                'fecha_ingreso' => $datosProducto1['fecha_ingreso'],
                'fecha_vencimiento' => $datosProducto1['fecha_vencimiento']
            ]);

            // Producto 2 va a la ubicación 1
            ProductoUbicacion::create([
                'producto_id' => $datosProducto2['producto_id'],
                'ubicacion_id' => $ubicacion1->id,
                'cantidad' => $datosProducto2['cantidad'],
                'lote' => $datosProducto2['lote'],
                'fecha_ingreso' => $datosProducto2['fecha_ingreso'],
                'fecha_vencimiento' => $datosProducto2['fecha_vencimiento']
            ]);

            // Registrar movimientos para auditoría
            try {
                $motivo = $request->motivo ?? 'Intercambio por drag and drop';
                
                // Movimiento del producto 1: ubicación1 → ubicación2
                MovimientoStock::registrarTransferencia(
                    $datosProducto1['producto_id'],
                    $ubicacion1->id,
                    $ubicacion2->id,
                    $datosProducto1['cantidad'],
                    $motivo . " (Producto 1 de intercambio)"
                );

                // Movimiento del producto 2: ubicación2 → ubicación1
                MovimientoStock::registrarTransferencia(
                    $datosProducto2['producto_id'],
                    $ubicacion2->id,
                    $ubicacion1->id,
                    $datosProducto2['cantidad'],
                    $motivo . " (Producto 2 de intercambio)"
                );

                Log::info("Movimientos de intercambio registrados exitosamente");
            } catch (\Exception $e) {
                Log::warning("Error al registrar movimientos de intercambio: " . $e->getMessage());
                // Continuar sin fallar por esto
            }

            // Actualizar ubicaciones en almacén de ambos productos
            try {
                $producto1 = Producto::findOrFail($datosProducto1['producto_id']);
                $producto1->actualizarUbicacionAlmacen();

                $producto2 = Producto::findOrFail($datosProducto2['producto_id']);
                $producto2->actualizarUbicacionAlmacen();

                Log::info("Ubicaciones de productos actualizadas");
            } catch (\Exception $e) {
                Log::warning("Error al actualizar ubicaciones de productos: " . $e->getMessage());
                // Continuar sin fallar por esto
            }

            DB::commit();
            
            Log::info('=== INTERCAMBIAR PRODUCTOS - ÉXITO ===');

            return response()->json([
                'success' => true,
                'message' => 'Productos intercambiados exitosamente',
                'data' => [
                    'intercambio' => [
                        'producto1' => [
                            'id' => $datosProducto1['producto_id'],
                            'slot_anterior' => $request->producto1['slot'],
                            'slot_nuevo' => $request->producto2['slot'],
                            'cantidad' => $datosProducto1['cantidad']
                        ],
                        'producto2' => [
                            'id' => $datosProducto2['producto_id'],
                            'slot_anterior' => $request->producto2['slot'],
                            'slot_nuevo' => $request->producto1['slot'],
                            'cantidad' => $datosProducto2['cantidad']
                        ]
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('=== INTERCAMBIAR PRODUCTOS - ERROR ===');
            Log::error('Error: ' . $e->getMessage());
            Log::error('Línea: ' . $e->getLine());
            Log::error('Archivo: ' . $e->getFile());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al intercambiar productos: ' . $e->getMessage(),
                'debug_info' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    /**
     * API: Intercambio específico para drag and drop - MÉTODO GARANTIZADO
     */
    public function dragDropIntercambio(Request $request)
    {
        Log::info('=== DRAG DROP INTERCAMBIO PROFESIONAL - INICIO ===');
        Log::info('Datos recibidos: ' . json_encode($request->all()));
        Log::info('Headers recibidos: ' . json_encode($request->headers->all()));
        Log::info('Método HTTP: ' . $request->method());
        Log::info('IP del cliente: ' . $request->ip());

        try {
            // Validación simple y directa
            $request->validate([
                'slot1_codigo' => 'required|string',
                'slot1_producto_id' => 'required|integer|exists:productos,id',
                'slot1_ubicacion_id' => 'required|integer|exists:ubicaciones,id',
                'slot2_codigo' => 'required|string',
                'slot2_producto_id' => 'required|integer|exists:productos,id',
                'slot2_ubicacion_id' => 'required|integer|exists:ubicaciones,id',
                'estante_id' => 'required|integer|exists:estantes,id'
            ]);
            
            Log::info('✅ Validación exitosa');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Error de validación: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', array_flatten($e->errors())),
                'errors' => $e->errors(),
                'debug_info' => [
                    'datos_recibidos' => $request->all(),
                    'validacion_fallida' => true
                ]
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Obtener ubicaciones y productos
            $ubicacion1 = Ubicacion::findOrFail($request->slot1_ubicacion_id);
            $ubicacion2 = Ubicacion::findOrFail($request->slot2_ubicacion_id);
            $producto1 = Producto::findOrFail($request->slot1_producto_id);
            $producto2 = Producto::findOrFail($request->slot2_producto_id);

            Log::info("Intercambiando: {$producto1->nombre} ({$request->slot1_codigo}) ↔ {$producto2->nombre} ({$request->slot2_codigo})");

            // Obtener registros actuales en ProductoUbicacion
            $pu1 = ProductoUbicacion::where('producto_id', $request->slot1_producto_id)
                ->where('ubicacion_id', $request->slot1_ubicacion_id)
                ->first();

            $pu2 = ProductoUbicacion::where('producto_id', $request->slot2_producto_id)
                ->where('ubicacion_id', $request->slot2_ubicacion_id)
                ->first();

            if (!$pu1) {
                throw new \Exception("No se encontró el producto {$producto1->nombre} en la ubicación {$request->slot1_codigo}");
            }

            if (!$pu2) {
                throw new \Exception("No se encontró el producto {$producto2->nombre} en la ubicación {$request->slot2_codigo}");
            }

            // Guardar datos antes del intercambio
            $datos1 = [
                'cantidad' => $pu1->cantidad,
                'lote' => $pu1->lote,
                'fecha_ingreso' => $pu1->fecha_ingreso,
                'fecha_vencimiento' => $pu1->fecha_vencimiento
            ];

            $datos2 = [
                'cantidad' => $pu2->cantidad,
                'lote' => $pu2->lote,
                'fecha_ingreso' => $pu2->fecha_ingreso,
                'fecha_vencimiento' => $pu2->fecha_vencimiento
            ];

            // INTERCAMBIO DIRECTO EN BASE DE DATOS
            Log::info('Ejecutando intercambio directo en BD...');

            // Actualizar producto 1 a ubicación 2
            $pu1->update([
                'ubicacion_id' => $request->slot2_ubicacion_id
            ]);

            // Actualizar producto 2 a ubicación 1
            $pu2->update([
                'ubicacion_id' => $request->slot1_ubicacion_id
            ]);

            // Registrar movimientos para auditoría
            try {
                MovimientoStock::registrarTransferencia(
                    $request->slot1_producto_id,
                    $request->slot1_ubicacion_id,
                    $request->slot2_ubicacion_id,
                    $datos1['cantidad'],
                    'Intercambio por drag and drop - Producto 1'
                );

                MovimientoStock::registrarTransferencia(
                    $request->slot2_producto_id,
                    $request->slot2_ubicacion_id,
                    $request->slot1_ubicacion_id,
                    $datos2['cantidad'],
                    'Intercambio por drag and drop - Producto 2'
                );

                Log::info("Movimientos registrados para auditoría");
            } catch (\Exception $e) {
                Log::warning("Error registrando movimientos: " . $e->getMessage());
                // Continuar sin fallar
            }

            // Actualizar ubicaciones en productos
            try {
                $producto1->actualizarUbicacionAlmacen();
                $producto2->actualizarUbicacionAlmacen();
                Log::info("Ubicaciones de productos actualizadas");
            } catch (\Exception $e) {
                Log::warning("Error actualizando ubicaciones: " . $e->getMessage());
                // Continuar sin fallar
            }

            DB::commit();

            Log::info('=== DRAG DROP INTERCAMBIO - ÉXITO TOTAL ===');

            return response()->json([
                'success' => true,
                'message' => '¡Intercambio completado exitosamente!',
                'data' => [
                    'intercambio_realizado' => [
                        'producto1' => [
                            'nombre' => $producto1->nombre,
                            'ubicacion_anterior' => $request->slot1_codigo,
                            'ubicacion_nueva' => $request->slot2_codigo,
                            'cantidad' => $datos1['cantidad']
                        ],
                        'producto2' => [
                            'nombre' => $producto2->nombre,
                            'ubicacion_anterior' => $request->slot2_codigo,
                            'ubicacion_nueva' => $request->slot1_codigo,
                            'cantidad' => $datos2['cantidad']
                        ]
                    ],
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('=== DRAG DROP INTERCAMBIO - ERROR ===');
            Log::error('Error: ' . $e->getMessage());
            Log::error('Archivo: ' . $e->getFile());
            Log::error('Línea: ' . $e->getLine());

            return response()->json([
                'success' => false,
                'message' => 'Error en el intercambio: ' . $e->getMessage(),
                'debug_info' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'datos_recibidos' => $request->all()
                ] : null
            ], 500);
        }
    }

    /**
     * API: Editar producto ubicado
     */
    public function editarProductoUbicacion(Request $request)
    {
        Log::info('=== EDITAR PRODUCTO UBICACIÓN - INICIO ===');
        Log::info('Datos recibidos: ' . json_encode($request->all()));
        
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'ubicacion_id' => 'required|exists:ubicaciones,id',
            'nombre' => 'required|string|min:2|max:255',
            'marca' => 'nullable|string|max:100',
            'cantidad' => 'required|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            $producto = Producto::findOrFail($request->producto_id);
            $ubicacion = Ubicacion::findOrFail($request->ubicacion_id);
            
            Log::info("Editando producto: {$producto->nombre} en ubicación: {$ubicacion->codigo}");

            // Actualizar información del producto
            $producto->update([
                'nombre' => $request->nombre,
                'marca' => $request->marca ?? $producto->marca,
            ]);

            Log::info("Producto actualizado: nombre={$request->nombre}, marca={$request->marca}");

            // Buscar la relación producto-ubicación
            $productoUbicacion = ProductoUbicacion::where('producto_id', $request->producto_id)
                ->where('ubicacion_id', $request->ubicacion_id)
                ->first();

            if (!$productoUbicacion) {
                Log::warning("No se encontró relación producto-ubicación");
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el producto en esta ubicación'
                ], 404);
            }

            // Registrar movimiento si hay cambio de cantidad
            $cantidadAnterior = $productoUbicacion->cantidad;
            $cantidadNueva = $request->cantidad;
            
            if ($cantidadAnterior != $cantidadNueva) {
                $diferencia = $cantidadNueva - $cantidadAnterior;
                
                if ($diferencia > 0) {
                    // Entrada de stock
                    MovimientoStock::registrarEntrada(
                        $request->producto_id,
                        $request->ubicacion_id,
                        $diferencia,
                        'Ajuste por edición - Incremento',
                        [
                            'cantidad_anterior' => $cantidadAnterior,
                            'cantidad_nueva' => $cantidadNueva,
                            'motivo' => 'Edición manual'
                        ]
                    );
                } else {
                    // Salida de stock
                    MovimientoStock::registrarSalida(
                        $request->producto_id,
                        $request->ubicacion_id,
                        abs($diferencia),
                        'Ajuste por edición - Decremento',
                        [
                            'cantidad_anterior' => $cantidadAnterior,
                            'cantidad_nueva' => $cantidadNueva,
                            'motivo' => 'Edición manual'
                        ]
                    );
                }
                
                Log::info("Movimiento registrado: cantidad anterior={$cantidadAnterior}, nueva={$cantidadNueva}, diferencia={$diferencia}");
            }

            // Actualizar la cantidad en la ubicación
            $productoUbicacion->update([
                'cantidad' => $cantidadNueva
            ]);

            // Si la cantidad es 0, eliminar la relación
            if ($cantidadNueva == 0) {
                $productoUbicacion->delete();
                Log::info("Relación producto-ubicación eliminada por cantidad 0");
            }

            // Actualizar stock total del producto
            $producto->actualizarStockDesdeUbicaciones();
            $producto->actualizarEstadoDesdeUbicaciones();
            $producto->actualizarUbicacionAlmacen();

            DB::commit();
            
            Log::info('=== EDITAR PRODUCTO UBICACIÓN - ÉXITO ===');

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente',
                'data' => [
                    'producto_id' => $producto->id,
                    'producto_nombre' => $producto->nombre,
                    'producto_marca' => $producto->marca,
                    'ubicacion_codigo' => $ubicacion->codigo,
                    'cantidad_nueva' => $cantidadNueva,
                    'cantidad_anterior' => $cantidadAnterior
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('=== EDITAR PRODUCTO UBICACIÓN - ERROR ===');
            Log::error('Error: ' . $e->getMessage());
            Log::error('Línea: ' . $e->getLine());
            Log::error('Archivo: ' . $e->getFile());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el producto: ' . $e->getMessage(),
                'debug_info' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    /**
     * API: Obtener ubicaciones libres de un estante
     */
    public function ubicacionesLibres($estanteId)
    {
        try {
            $ubicaciones = Ubicacion::where('estante_id', $estanteId)
                ->libres()
                ->orderBy('nivel')
                ->orderBy('posicion')
                ->get()
                ->map(function ($ubicacion) {
                    return [
                        'id' => $ubicacion->id,
                        'codigo' => $ubicacion->codigo,
                        'nivel' => $ubicacion->nivel,
                        'posicion' => $ubicacion->posicion
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $ubicaciones
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ubicaciones libres'
            ], 500);
        }
    }

    /**
     * API: Obtener ubicacion_id de un slot específico
     */
    public function obtenerUbicacionIdPorSlot($estanteId, $slotCodigo)
    {
        try {
            Log::info("Buscando ubicacion_id para estante {$estanteId}, slot {$slotCodigo}");
            
            $ubicacion = Ubicacion::where('estante_id', $estanteId)
                ->where('codigo', $slotCodigo)
                ->first();

            if (!$ubicacion) {
                Log::warning("No se encontró ubicación para estante {$estanteId}, slot {$slotCodigo}");
                return response()->json([
                    'success' => false,
                    'message' => 'Ubicación no encontrada'
                ], 404);
            }

            Log::info("Ubicación encontrada: ID {$ubicacion->id} para slot {$slotCodigo}");

            return response()->json([
                'success' => true,
                'ubicacion_id' => $ubicacion->id,
                'data' => [
                    'id' => $ubicacion->id,
                    'codigo' => $ubicacion->codigo,
                    'nivel' => $ubicacion->nivel,
                    'posicion' => $ubicacion->posicion,
                    'estante_id' => $ubicacion->estante_id
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Error al obtener ubicacion_id para slot {$slotCodigo}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la ubicación del slot'
            ], 500);
        }
    }

    /**
     * API: Obtener todos los estantes
     */
    public function obtenerEstantes()
    {
        try {
            Log::info('🏗️ Iniciando obtención de estantes...');
            
            $estantes = Estante::withCount('ubicaciones')
                ->where('activo', true)
                ->get()
                ->map(function ($estante) {
                    try {
                        Log::info("Procesando estante: {$estante->nombre} (ID: {$estante->id})");
                        
                        $totalSlots = $estante->ubicaciones_count;
                        
                        // Calcular slots ocupados de forma más segura
                        $slotsOcupados = 0;
                        $totalProductos = 0;
                        
                        if ($totalSlots > 0) {
                            // Obtener ubicaciones del estante
                            $ubicacionesIds = Ubicacion::where('estante_id', $estante->id)
                                ->pluck('id');
                            
                            if ($ubicacionesIds->count() > 0) {
                                // Contar ubicaciones que tienen productos
                                $slotsOcupados = ProductoUbicacion::whereIn('ubicacion_id', $ubicacionesIds)
                                    ->where('cantidad', '>', 0)
                                    ->distinct('ubicacion_id')
                                    ->count('ubicacion_id');
                                
                                // Calcular total de productos
                                $totalProductos = ProductoUbicacion::whereIn('ubicacion_id', $ubicacionesIds)
                                    ->where('cantidad', '>', 0)
                                    ->sum('cantidad');
                            }
                        }
                        
                        $porcentajeOcupacion = $totalSlots > 0 ? ($slotsOcupados / $totalSlots) * 100 : 0;
                        
                        // Determinar estado visual
                        if ($porcentajeOcupacion >= 90) {
                            $estado = 'peligro';
                        } elseif ($porcentajeOcupacion >= 60) {
                            $estado = 'alerta';
                        } else {
                            $estado = 'ok';
                        }
                        
                        $resultado = [
                            'id' => $estante->id,
                            'nombre' => $estante->nombre,
                            'productos_actuales' => $totalProductos,
                            'slots_ocupados' => $slotsOcupados,
                            'capacidad_total' => $totalSlots,
                            'ocupacion_porcentaje' => round($porcentajeOcupacion, 1),
                            'estado' => $estado,
                            'tipo' => $estante->tipo ?? 'venta',
                            'ubicacion_fisica' => $estante->ubicacion_fisica ?? '',
                            'descripcion' => $estante->descripcion ?? '',
                            'numero_niveles' => $estante->numero_niveles ?? 4,
                            'numero_posiciones' => $estante->numero_posiciones ?? 5,
                            'activo' => $estante->activo ?? true
                        ];
                        
                        Log::info("Estante {$estante->nombre} procesado: {$slotsOcupados}/{$totalSlots} slots ocupados");
                        
                        return $resultado;
                        
                    } catch (\Exception $e) {
                        Log::error("Error procesando estante {$estante->nombre}: " . $e->getMessage());
                        
                        // Retornar datos básicos en caso de error
                        return [
                            'id' => $estante->id,
                            'nombre' => $estante->nombre,
                            'productos_actuales' => 0,
                            'slots_ocupados' => 0,
                            'capacidad_total' => $estante->ubicaciones_count ?? 0,
                            'ocupacion_porcentaje' => 0,
                            'estado' => 'ok',
                            'tipo' => $estante->tipo ?? 'venta',
                            'ubicacion_fisica' => $estante->ubicacion_fisica ?? '',
                            'descripcion' => $estante->descripcion ?? '',
                            'numero_niveles' => $estante->numero_niveles ?? 4,
                            'numero_posiciones' => $estante->numero_posiciones ?? 5,
                            'activo' => $estante->activo ?? true
                        ];
                    }
                });

            Log::info("✅ Estantes obtenidos exitosamente: " . $estantes->count());

            return response()->json([
                'success' => true,
                'data' => $estantes,
                'total' => $estantes->count(),
                'message' => 'Estantes obtenidos correctamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener estantes: ' . $e->getMessage());
            Log::error('📍 Línea: ' . $e->getLine());
            Log::error('📁 Archivo: ' . $e->getFile());
            Log::error('📚 Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los estantes: ' . $e->getMessage(),
                'debug_info' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    public function estantes()
    {
        return view('ubicaciones.estantes');
    }

    /**
     * API: Obtener configuración del almacén
     */
    public function obtenerConfiguracion()
    {
        try {
            $configuracion = ConfiguracionAlmacen::obtenerConfiguracion();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $configuracion->id ?? null,
                    'nombre' => $configuracion->nombre ?? 'Almacén Principal',
                    'descripcion' => $configuracion->descripcion ?? '',
                    'imagen_fondo_url' => $configuracion->imagen_fondo_url ?? asset('imagen/login/login-fondo.jpg'),
                    'configuraciones' => $configuracion->configuraciones ?? []
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener configuración: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la configuración del almacén'
            ], 500);
        }
    }

    /**
     * API: Actualizar imagen de fondo del almacén
     */
    public function actualizarImagenFondo(Request $request)
    {
        try {
            $request->validate([
                'imagen' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Máximo 2MB
            ]);

            $configuracion = ConfiguracionAlmacen::obtenerConfiguracion();
            
            if (!$configuracion) {
                // Crear configuración si no existe
                $configuracion = ConfiguracionAlmacen::create([
                    'nombre' => 'Almacén Principal',
                    'descripcion' => 'Configuración principal del sistema de almacén',
                    'activo' => true
                ]);
            }

            // Actualizar la imagen
            $configuracion->actualizarImagenFondo($request->file('imagen'));

            return response()->json([
                'success' => true,
                'message' => 'Imagen del almacén actualizada exitosamente',
                'data' => [
                    'imagen_url' => $configuracion->fresh()->imagen_fondo_url
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', array_flatten($e->errors())),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al actualizar imagen de fondo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la imagen del almacén: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Actualizar configuración del almacén
     */
    public function actualizarConfiguracion(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'sometimes|required|string|max:255',
                'descripcion' => 'sometimes|nullable|string',
                'configuraciones' => 'sometimes|nullable|array'
            ]);

            $configuracion = ConfiguracionAlmacen::obtenerConfiguracion();
            
            if (!$configuracion) {
                $configuracion = ConfiguracionAlmacen::create([
                    'nombre' => $request->nombre ?? 'Almacén Principal',
                    'descripcion' => $request->descripcion ?? '',
                    'activo' => true
                ]);
            } else {
                $configuracion->update($request->only(['nombre', 'descripcion', 'configuraciones']));
            }

            return response()->json([
                'success' => true,
                'message' => 'Configuración actualizada exitosamente',
                'data' => [
                    'id' => $configuracion->id,
                    'nombre' => $configuracion->nombre,
                    'descripcion' => $configuracion->descripcion,
                    'imagen_fondo_url' => $configuracion->imagen_fondo_url,
                    'configuraciones' => $configuracion->configuraciones
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', array_flatten($e->errors())),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al actualizar configuración: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Obtener datos de un estante específico
     */
    public function obtenerEstante($id)
    {
        try {
            Log::info("Iniciando obtención de datos para estante ID: {$id}");
            
            // Verificar que el ID sea válido
            if (!is_numeric($id) || $id <= 0) {
                Log::warning("ID de estante inválido: {$id}");
                return response()->json([
                    'success' => false,
                    'message' => 'ID de estante inválido'
                ], 400);
            }

            // Obtener el estante básico primero
            $estante = Estante::find($id);
            
            if (!$estante) {
                Log::warning("Estante no encontrado: ID {$id}");
                return response()->json([
                    'success' => false,
                    'message' => 'El estante especificado no existe'
                ], 404);
            }

            Log::info("Estante encontrado: {$estante->nombre}");

            // Obtener ubicaciones del estante
            $ubicaciones = Ubicacion::where('estante_id', $id)->get();
            Log::info("Ubicaciones encontradas: " . $ubicaciones->count());

            // Calcular productos actuales de forma más segura
            $productosActuales = 0;
            $ubicacionesOcupadas = 0;

            if ($ubicaciones->count() > 0) {
                try {
                    // Obtener productos ubicados en este estante
                    $productosUbicados = ProductoUbicacion::whereIn('ubicacion_id', $ubicaciones->pluck('id'))
                        ->where('cantidad', '>', 0)
                        ->get();

                    $productosActuales = $productosUbicados->sum('cantidad');
                    $ubicacionesOcupadas = $productosUbicados->count();
                    
                    Log::info("Productos ubicados calculados: {$productosActuales}");
                } catch (\Exception $e) {
                    Log::error("Error al calcular productos ubicados: " . $e->getMessage());
                    // Continuar con valores por defecto
                }
            }

            // Construir datos de respuesta con valores seguros
            $data = [
                'id' => (int) $estante->id,
                'nombre' => (string) $estante->nombre,
                'capacidad_total' => (int) ($estante->capacidad_total ?? 0),
                'numero_niveles' => (int) ($estante->numero_niveles ?? 1),
                'ubicacion_fisica' => (string) ($estante->ubicacion_fisica ?? ''),
                'tipo' => (string) ($estante->tipo ?? 'venta'),
                'activo' => (bool) ($estante->activo ?? true),
                'productos_actuales' => (int) $productosActuales,
                'ubicaciones_ocupadas' => (int) $ubicacionesOcupadas,
                'ubicaciones_disponibles' => (int) ($ubicaciones->count() - $ubicacionesOcupadas),
                'total_ubicaciones' => (int) $ubicaciones->count(),
                'created_at' => $estante->created_at,
                'updated_at' => $estante->updated_at
            ];

            Log::info("Datos del estante preparados correctamente");

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Datos del estante obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener datos del estante ID ' . $id);
            Log::error('Mensaje de error: ' . $e->getMessage());
            Log::error('Archivo: ' . $e->getFile() . ' - Línea: ' . $e->getLine());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
                    return response()->json([
            'success' => false,
            'message' => 'Error interno del servidor al obtener los datos del estante',
            'debug' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

/**
 * API: Fusionar slots para productos grandes
 */
public function fusionarSlots(Request $request)
{
    try {
        Log::info('=== FUSIONAR SLOTS - INICIO ===');
        Log::info('Datos recibidos: ' . json_encode($request->all()));

        // Validación de datos
        $validatedData = $request->validate([
            'estante_id' => 'required|integer|exists:estantes,id',
            'slot_origen' => 'required|string',
            'tipo_fusion' => 'required|in:horizontal-2,horizontal-3,vertical-2,cuadrado-2x2,horizontal-4,personalizada',
            'slots_seleccionados' => 'required|array|min:2',
            'slots_seleccionados.*' => 'string'
        ], [
            'estante_id.required' => 'El ID del estante es obligatorio',
            'estante_id.exists' => 'El estante especificado no existe',
            'slot_origen.required' => 'El slot de origen es obligatorio',
            'tipo_fusion.required' => 'El tipo de fusión es obligatorio',
            'tipo_fusion.in' => 'Tipo de fusión no válido',
            'slots_seleccionados.required' => 'Debes seleccionar al menos 2 slots para fusionar',
            'slots_seleccionados.min' => 'Debes seleccionar al menos 2 slots para fusionar'
        ]);

        $estanteId = $validatedData['estante_id'];
        $slotOrigen = $validatedData['slot_origen'];
        $tipoFusion = $validatedData['tipo_fusion'];
        $slotsSeleccionados = $validatedData['slots_seleccionados'];

        Log::info("Fusionando slots en estante {$estanteId} con tipo {$tipoFusion}. Slots: " . json_encode($slotsSeleccionados));

        DB::beginTransaction();

        // Obtener el estante
        $estante = Estante::findOrFail($estanteId);
        
        // Parsear slot origen
        [$nivelOrigen, $posicionOrigen] = explode('-', $slotOrigen);
        $nivelOrigen = (int) $nivelOrigen;
        $posicionOrigen = (int) $posicionOrigen;

        // Convertir slots seleccionados a formato de array
        $slotsAfectados = [];
        foreach ($slotsSeleccionados as $slotId) {
            [$nivel, $posicion] = explode('-', $slotId);
            $slotsAfectados[] = ['nivel' => (int)$nivel, 'posicion' => (int)$posicion];
        }
        
        Log::info("Slots afectados por la fusión: " . json_encode($slotsAfectados));

        // Verificar que todos los slots están vacíos
        foreach ($slotsAfectados as $slot) {
            $ubicacion = Ubicacion::where('estante_id', $estanteId)
                ->where('nivel', $slot['nivel'])
                ->where('posicion', $slot['posicion'])
                ->first();

            if ($ubicacion) {
                $tieneProducto = ProductoUbicacion::where('ubicacion_id', $ubicacion->id)
                    ->where('cantidad', '>', 0)
                    ->exists();
                
                if ($tieneProducto) {
                    throw new \Exception("El slot {$slot['nivel']}-{$slot['posicion']} no está vacío");
                }
            }
        }

        // Crear ubicación fusionada principal
        $ubicacionPrincipal = Ubicacion::updateOrCreate([
            'estante_id' => $estanteId,
            'nivel' => $nivelOrigen,
            'posicion' => $posicionOrigen
        ], [
            'codigo' => $slotOrigen,
            'capacidad_maxima' => $this->calcularCapacidadFusion($tipoFusion, count($slotsAfectados)),
            'es_fusionado' => true,
            'tipo_fusion' => $tipoFusion,
            'slots_ocupados' => count($slotsAfectados),
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Marcar slots secundarios como parte de la fusión
        foreach ($slotsAfectados as $slot) {
            if ($slot['nivel'] == $nivelOrigen && $slot['posicion'] == $posicionOrigen) {
                continue; // Saltar el slot principal
            }

            Ubicacion::updateOrCreate([
                'estante_id' => $estanteId,
                'nivel' => $slot['nivel'],
                'posicion' => $slot['posicion']
            ], [
                'codigo' => "{$slot['nivel']}-{$slot['posicion']}",
                'capacidad_maxima' => 0,
                'es_fusionado' => false,
                'fusion_principal_id' => $ubicacionPrincipal->id,
                'activo' => false, // Inactivo porque es parte de la fusión
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        DB::commit();
        
        Log::info("✅ Fusión completada exitosamente");

        return response()->json([
            'success' => true,
            'message' => 'Slots fusionados correctamente',
            'data' => [
                'ubicacion_principal_id' => $ubicacionPrincipal->id,
                'slots_afectados' => count($slotsAfectados),
                'tipo_fusion' => $tipoFusion,
                'capacidad_total' => $ubicacionPrincipal->capacidad_maxima
            ]
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Error de validación en fusionar slots: ' . json_encode($e->errors()));
        return response()->json([
            'success' => false,
            'message' => 'Error de validación',
            'errors' => $e->errors()
        ], 422);

            } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al fusionar slots: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al fusionar slots: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Separar slots fusionados
     */
    public function separarSlots(Request $request)
    {
        try {
            Log::info('=== SEPARAR SLOTS - INICIO ===');
            Log::info('Datos recibidos: ' . json_encode($request->all()));

            // Validación de datos
            $validatedData = $request->validate([
                'ubicacion_id' => 'required|integer|exists:ubicaciones,id'
            ], [
                'ubicacion_id.required' => 'El ID de la ubicación es obligatorio',
                'ubicacion_id.exists' => 'La ubicación especificada no existe'
            ]);

            $ubicacionId = $validatedData['ubicacion_id'];

            DB::beginTransaction();

            // Obtener la ubicación principal
            $ubicacionPrincipal = Ubicacion::findOrFail($ubicacionId);
            
            if (!$ubicacionPrincipal->es_fusionado) {
                throw new \Exception('Esta ubicación no está fusionada');
            }

            // Verificar que no tenga productos
            $tieneProductos = ProductoUbicacion::where('ubicacion_id', $ubicacionId)
                ->where('cantidad', '>', 0)
                ->exists();
            
            if ($tieneProductos) {
                throw new \Exception('No se puede separar una fusión que contiene productos. Primero mueve los productos a otra ubicación.');
            }

            Log::info("Separando fusión de ubicación {$ubicacionId} (tipo: {$ubicacionPrincipal->tipo_fusion})");

            // Obtener ubicaciones secundarias
            $ubicacionesSecundarias = Ubicacion::where('fusion_principal_id', $ubicacionId)->get();
            
            Log::info("Encontradas {$ubicacionesSecundarias->count()} ubicaciones secundarias");

            // Restaurar ubicaciones secundarias
            foreach ($ubicacionesSecundarias as $ubicacion) {
                $ubicacion->update([
                    'fusion_principal_id' => null,
                    'activo' => true,
                    'capacidad_maxima' => 1 // Capacidad normal de un slot
                ]);
                Log::info("Restaurada ubicación secundaria ID: {$ubicacion->id}");
            }

            // Restaurar ubicación principal
            $ubicacionPrincipal->update([
                'es_fusionado' => false,
                'tipo_fusion' => null,
                'slots_ocupados' => 1,
                'capacidad_maxima' => 1 // Capacidad normal de un slot
            ]);

            Log::info("Restaurada ubicación principal ID: {$ubicacionId}");

            DB::commit();
            
            Log::info("✅ Separación completada exitosamente");

            return response()->json([
                'success' => true,
                'message' => 'Slots separados correctamente',
                'data' => [
                    'ubicaciones_restauradas' => $ubicacionesSecundarias->count() + 1,
                    'ubicacion_principal_id' => $ubicacionId
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación en separar slots: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al separar slots: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al separar slots: ' . $e->getMessage()
            ], 500);
        }
    }

/**
 * Calcular slots afectados por la fusión
 */
private function calcularSlotsAfectados($nivelOrigen, $posicionOrigen, $tipoFusion)
{
    $slots = [];

    switch ($tipoFusion) {
        case 'horizontal-2':
            $slots = [
                ['nivel' => $nivelOrigen, 'posicion' => $posicionOrigen],
                ['nivel' => $nivelOrigen, 'posicion' => $posicionOrigen + 1]
            ];
            break;

        case 'horizontal-3':
            $slots = [
                ['nivel' => $nivelOrigen, 'posicion' => $posicionOrigen],
                ['nivel' => $nivelOrigen, 'posicion' => $posicionOrigen + 1],
                ['nivel' => $nivelOrigen, 'posicion' => $posicionOrigen + 2]
            ];
            break;

        case 'vertical-2':
            $slots = [
                ['nivel' => $nivelOrigen, 'posicion' => $posicionOrigen],
                ['nivel' => $nivelOrigen + 1, 'posicion' => $posicionOrigen]
            ];
            break;

        case 'cuadrado-2x2':
            $slots = [
                ['nivel' => $nivelOrigen, 'posicion' => $posicionOrigen],
                ['nivel' => $nivelOrigen, 'posicion' => $posicionOrigen + 1],
                ['nivel' => $nivelOrigen + 1, 'posicion' => $posicionOrigen],
                ['nivel' => $nivelOrigen + 1, 'posicion' => $posicionOrigen + 1]
            ];
            break;
    }

    return $slots;
}

/**
 * Calcular capacidad de la fusión
 */
private function calcularCapacidadFusion($tipoFusion, $numeroSlots = null)
{
    $capacidades = [
        'horizontal-2' => 2,
        'horizontal-3' => 3,
        'horizontal-4' => 4,
        'vertical-2' => 2,
        'cuadrado-2x2' => 4,
        'personalizada' => $numeroSlots ?? 1
    ];

    return $capacidades[$tipoFusion] ?? ($numeroSlots ?? 1);
}

    /**
     * API: Actualizar un estante
     */
    public function actualizarEstante(Request $request, $id)
    {
        try {
            $estante = Estante::findOrFail($id);

            // Validar datos
            $validatedData = $request->validate([
                'nombre' => 'required|string|min:2|max:100',
                'ubicacion_fisica' => 'nullable|string|max:200',
                'tipo' => 'required|in:venta,almacen',
                'activo' => 'boolean'
            ], [
                'nombre.required' => 'El nombre del estante es obligatorio',
                'nombre.min' => 'El nombre debe tener al menos 2 caracteres',
                'nombre.max' => 'El nombre no puede tener más de 100 caracteres',
                'tipo.required' => 'Debe seleccionar un tipo de estante',
                'tipo.in' => 'El tipo de estante debe ser "venta" o "almacen"',
                'ubicacion_fisica.max' => 'La ubicación física no puede tener más de 200 caracteres'
            ]);

            // Verificar si el nuevo nombre ya existe (excepto el actual)
            $existeNombre = Estante::where('nombre', $validatedData['nombre'])
                ->where('id', '!=', $id)
                ->exists();

            if ($existeNombre) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un estante con ese nombre',
                    'errors' => [
                        'nombre' => ['Ya existe un estante con ese nombre']
                    ]
                ], 422);
            }

            // Actualizar datos
            $estante->update([
                'nombre' => $validatedData['nombre'],
                'ubicacion_fisica' => $validatedData['ubicacion_fisica'],
                'tipo' => $validatedData['tipo'],
                'activo' => $request->has('activo') ? $validatedData['activo'] : true
            ]);

            // Recargar el estante actualizado
            $estante->refresh();

            // Obtener ubicaciones y calcular estadísticas de forma segura
            $ubicaciones = Ubicacion::where('estante_id', $estante->id)->get();
            
            $productosActuales = 0;
            $ubicacionesOcupadas = 0;

            if ($ubicaciones->count() > 0) {
                $productosUbicados = ProductoUbicacion::whereIn('ubicacion_id', $ubicaciones->pluck('id'))
                    ->where('cantidad', '>', 0)
                    ->get();

                $productosActuales = $productosUbicados->sum('cantidad');
                $ubicacionesOcupadas = $productosUbicados->count();
            }

            $data = [
                'id' => $estante->id,
                'nombre' => $estante->nombre,
                'capacidad_total' => $estante->capacidad_total ?? 0,
                'numero_niveles' => $estante->numero_niveles ?? 1,
                'ubicacion_fisica' => $estante->ubicacion_fisica ?? '',
                'tipo' => $estante->tipo,
                'activo' => $estante->activo,
                'productos_actuales' => $productosActuales,
                'ubicaciones_ocupadas' => $ubicacionesOcupadas,
                'ubicaciones_disponibles' => $ubicaciones->count() - $ubicacionesOcupadas,
                'total_ubicaciones' => $ubicaciones->count(),
                'updated_at' => $estante->updated_at
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Estante actualizado correctamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Estante no encontrado para actualización: ID {$id}");
            return response()->json([
                'success' => false,
                'message' => 'El estante especificado no existe'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::info("Error de validación al actualizar estante ID {$id}: " . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al actualizar estante ID ' . $id . ': ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al actualizar el estante'
            ], 500);
        }
    }

    /**
     * API: Eliminar estante
     */
    public function eliminarEstante($id)
    {
        try {
            Log::info("🗑️ === ELIMINANDO ESTANTE ID: {$id} ===");
            
            DB::beginTransaction();

            // Verificar que el estante existe
            $estante = Estante::find($id);
            if (!$estante) {
                Log::warning("❌ Estante no encontrado: ID {$id}");
                return response()->json([
                    'success' => false,
                    'message' => 'El estante especificado no existe'
                ], 404);
            }

            Log::info("📦 Estante encontrado: {$estante->nombre}");
            
            // Obtener todas las ubicaciones del estante
            $ubicacionesIds = Ubicacion::where('estante_id', $id)->pluck('id');
            $totalUbicaciones = $ubicacionesIds->count();
            
            Log::info("📍 Ubicaciones encontradas: {$totalUbicaciones}");

            // Verificar productos ubicados
            $productosUbicados = 0;
            $productosAfectados = [];
            
            if ($totalUbicaciones > 0) {
                $productosAfectados = ProductoUbicacion::whereIn('ubicacion_id', $ubicacionesIds)
                    ->where('cantidad', '>', 0)
                    ->with('producto')
                    ->get();
                
                $productosUbicados = $productosAfectados->count();
                Log::info("🏷️ Productos ubicados encontrados: {$productosUbicados}");
            }

            // Información del estante para respuesta
            $estanteInfo = [
                'id' => $estante->id,
                'nombre' => $estante->nombre,
                'tipo' => $estante->tipo ?? 'venta',
                'productos_ubicados' => $productosUbicados,
                'total_ubicaciones' => $totalUbicaciones
            ];

            // PASO 1: Manejar productos ubicados (forma simple y segura)
            if ($productosUbicados > 0) {
                Log::info("🔄 Procesando {$productosUbicados} productos ubicados...");
                
                foreach ($productosAfectados as $productoUbicacion) {
                    try {
                        // Limpiar ubicacion_almacen del producto
                        if ($productoUbicacion->producto) {
                            $producto = $productoUbicacion->producto;
                            
                            // Actualizar tanto ubicacion_almacen como ubicacion
                            $producto->update([
                                'ubicacion_almacen' => null,
                                'ubicacion' => 'Sin ubicar'
                            ]);
                            
                            Log::info("✅ Producto {$producto->nombre} sin ubicar (ubicacion_almacen limpiada)");
                        }
                        
                        // Eliminar registro de ProductoUbicacion
                        $productoUbicacion->delete();
                        
                    } catch (\Exception $e) {
                        Log::error("❌ Error procesando producto ID {$productoUbicacion->producto_id}: " . $e->getMessage());
                        // Continuar con el siguiente producto
                    }
                }
                
                // EXTRA: Limpiar cualquier producto que tenga referencia al estante eliminado
                // En caso de que haya inconsistencias en los datos
                try {
                    $productosConUbicacionEstante = Producto::where('ubicacion_almacen', 'LIKE', "%{$estante->nombre}%")
                        ->get();
                    
                    if ($productosConUbicacionEstante->count() > 0) {
                        Log::info("🧹 Limpiando {$productosConUbicacionEstante->count()} productos con referencia al estante eliminado");
                        
                        foreach ($productosConUbicacionEstante as $producto) {
                            $producto->update([
                                'ubicacion_almacen' => null,
                                'ubicacion' => 'Sin ubicar'
                            ]);
                            Log::info("✅ Producto {$producto->nombre} limpiado por referencia al estante");
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("❌ Error en limpieza extra: " . $e->getMessage());
                }
                
                Log::info("✅ {$productosUbicados} productos movidos a sin ubicar");
            }

            // PASO 2: Eliminar todas las ubicaciones del estante
            $ubicacionesEliminadas = 0;
            if ($totalUbicaciones > 0) {
                try {
                    $ubicacionesEliminadas = Ubicacion::where('estante_id', $id)->count();
                    Ubicacion::where('estante_id', $id)->delete();
                    Log::info("✅ {$ubicacionesEliminadas} ubicaciones eliminadas");
                } catch (\Exception $e) {
                    Log::error("❌ Error eliminando ubicaciones: " . $e->getMessage());
                    // Continuar con la eliminación del estante
                }
            }

            // PASO 3: Eliminar el estante
            try {
                $nombreEstante = $estante->nombre;
                $estante->delete();
                Log::info("✅ Estante '{$nombreEstante}' eliminado correctamente");
            } catch (\Exception $e) {
                Log::error("❌ Error eliminando estante: " . $e->getMessage());
                throw $e;
            }

            DB::commit();

            // Mensaje de éxito
            $mensaje = "Estante '{$estanteInfo['nombre']}' eliminado correctamente";
            if ($productosUbicados > 0) {
                $mensaje .= ". {$productosUbicados} productos fueron movidos a 'sin ubicar'";
            }

            Log::info("🎉 Eliminación completada exitosamente");

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => [
                    'estante_eliminado' => $estanteInfo,
                    'productos_reubicados' => $productosUbicados,
                    'ubicaciones_eliminadas' => $ubicacionesEliminadas,
                    'operacion_completada' => true
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("❌ === ERROR AL ELIMINAR ESTANTE ===");
            Log::error("ID del estante: {$id}");
            Log::error("Error: " . $e->getMessage());
            Log::error("Línea: " . $e->getLine());
            Log::error("Archivo: " . $e->getFile());
            Log::error("Stack trace: " . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el estante: ' . $e->getMessage(),
                'debug_info' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    /**
     * API: Actualizar estructura del estante (niveles y posiciones)
     */
    public function actualizarEstructuraEstante(Request $request, $id)
    {
        Log::info('=== ACTUALIZAR ESTRUCTURA ESTANTE - INICIO ===');
        Log::info("Estante ID: {$id}");
        Log::info('Datos recibidos: ' . json_encode($request->all()));

        try {
            // Validar datos
            $request->validate([
                'numero_niveles' => 'required|integer|min:1|max:10',
                'numero_posiciones' => 'required|integer|min:1|max:20'
            ], [
                'numero_niveles.required' => 'El número de niveles es obligatorio',
                'numero_niveles.min' => 'Debe tener al menos 1 nivel',
                'numero_niveles.max' => 'No puede tener más de 10 niveles',
                'numero_posiciones.required' => 'El número de posiciones es obligatorio',
                'numero_posiciones.min' => 'Debe tener al menos 1 posición por nivel',
                'numero_posiciones.max' => 'No puede tener más de 20 posiciones por nivel'
            ]);

            $nuevosNiveles = (int) $request->numero_niveles;
            $nuevasPosiciones = (int) $request->numero_posiciones;
            $nuevaCapacidad = $nuevosNiveles * $nuevasPosiciones;

            Log::info("Nueva estructura: {$nuevosNiveles} niveles x {$nuevasPosiciones} posiciones = {$nuevaCapacidad} slots");

            DB::beginTransaction();

            // Obtener estante actual
            $estante = Estante::findOrFail($id);
            Log::info("Estante encontrado: {$estante->nombre}");
            Log::info("Estructura actual: {$estante->numero_niveles} niveles x {$estante->numero_posiciones} posiciones");

            // Verificar si hay cambios
            if ($estante->numero_niveles == $nuevosNiveles && $estante->numero_posiciones == $nuevasPosiciones) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'La estructura especificada es igual a la actual'
                ], 422);
            }

            // Obtener ubicaciones actuales
            $ubicacionesActuales = Ubicacion::where('estante_id', $id)->get();
            Log::info("Ubicaciones actuales: {$ubicacionesActuales->count()}");

            // ========================================
            // PRESERVAR PRODUCTOS - LÓGICA INTELIGENTE
            // ========================================

            // 1. Identificar ubicaciones existentes y necesarias
            $ubicacionesExistentes = [];
            $productosEnUbicacionesFueraDeRango = [];

            foreach ($ubicacionesActuales as $ubicacion) {
                $ubicacionesExistentes[$ubicacion->codigo] = $ubicacion;
                
                // Si está fuera del nuevo rango, verificar si tiene productos
                if ($ubicacion->nivel > $nuevosNiveles || $ubicacion->posicion > $nuevasPosiciones) {
                    $productosEnEstaUbicacion = ProductoUbicacion::where('ubicacion_id', $ubicacion->id)
                        ->where('cantidad', '>', 0)
                        ->with('producto')
                        ->get();
                    
                    foreach ($productosEnEstaUbicacion as $pu) {
                        $productosEnUbicacionesFueraDeRango[] = [
                            'producto_nombre' => $pu->producto->nombre,
                            'cantidad' => $pu->cantidad,
                            'ubicacion_codigo' => $ubicacion->codigo,
                            'producto_id' => $pu->producto_id,
                            'ubicacion_id' => $ubicacion->id
                        ];
                    }
                }
            }

            Log::info("Ubicaciones existentes: " . count($ubicacionesExistentes));
            Log::info("Productos en ubicaciones fuera de rango: " . count($productosEnUbicacionesFueraDeRango));

            // 2. SOLO eliminar ubicaciones vacías que están fuera del rango
            $ubicacionesVaciasAEliminar = [];
            foreach ($ubicacionesActuales as $ubicacion) {
                if ($ubicacion->nivel > $nuevosNiveles || $ubicacion->posicion > $nuevasPosiciones) {
                    // Verificar si está vacía
                    $tieneProductos = ProductoUbicacion::where('ubicacion_id', $ubicacion->id)
                        ->where('cantidad', '>', 0)
                        ->exists();
                    
                    if (!$tieneProductos) {
                        $ubicacionesVaciasAEliminar[] = $ubicacion->id;
                        Log::info("Marcando ubicación vacía para eliminar: {$ubicacion->codigo}");
                    } else {
                        Log::warning("⚠️ NO eliminando ubicación {$ubicacion->codigo} porque tiene productos");
                    }
                }
            }

            // Eliminar solo ubicaciones vacías
            if (count($ubicacionesVaciasAEliminar) > 0) {
                Ubicacion::whereIn('id', $ubicacionesVaciasAEliminar)->delete();
                Log::info("✅ Eliminadas " . count($ubicacionesVaciasAEliminar) . " ubicaciones vacías");
            }

            // 3. Identificar qué ubicaciones nuevas necesitamos crear
            $codigosExistentes = array_keys($ubicacionesExistentes);
            $ubicacionesNecesarias = [];
            
            for ($nivel = 1; $nivel <= $nuevosNiveles; $nivel++) {
                for ($posicion = 1; $posicion <= $nuevasPosiciones; $posicion++) {
                    $codigo = "{$nivel}-{$posicion}";
                    if (!in_array($codigo, $codigosExistentes)) {
                        $ubicacionesNecesarias[] = [
                            'estante_id' => $id,
                            'nivel' => $nivel,
                            'posicion' => $posicion,
                            'codigo' => $codigo,
                            'capacidad_maxima' => 1,
                            'activo' => true,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }
            }

            // 4. Crear solo las ubicaciones nuevas que faltan
            if (count($ubicacionesNecesarias) > 0) {
                DB::table('ubicaciones')->insert($ubicacionesNecesarias);
                Log::info("✅ Creadas " . count($ubicacionesNecesarias) . " nuevas ubicaciones");
            } else {
                Log::info("✅ No se necesitan crear nuevas ubicaciones");
            }

            // Actualizar el estante
            $estante->update([
                'numero_niveles' => $nuevosNiveles,
                'numero_posiciones' => $nuevasPosiciones,
                'capacidad_total' => $nuevaCapacidad
            ]);

            Log::info("Estante actualizado correctamente");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estructura del estante actualizada correctamente',
                'data' => [
                    'estante_id' => $estante->id,
                    'nombre' => $estante->nombre,
                    'estructura_anterior' => [
                        'niveles' => $ubicacionesActuales->max('nivel') ?? 0,
                        'posiciones' => $ubicacionesActuales->max('posicion') ?? 0,
                        'total_slots' => $ubicacionesActuales->count()
                    ],
                    'estructura_nueva' => [
                        'niveles' => $nuevosNiveles,
                        'posiciones' => $nuevasPosiciones,
                        'total_slots' => $nuevaCapacidad
                    ],
                    'productos_en_ubicaciones_fuera_de_rango' => count($productosEnUbicacionesFueraDeRango),
                    'ubicaciones_vacias_eliminadas' => count($ubicacionesVaciasAEliminar),
                    'ubicaciones_nuevas_creadas' => count($ubicacionesNecesarias),
                    'productos_preservados' => count($productosEnUbicacionesFueraDeRango) > 0 ? 
                        'Los productos en ubicaciones fuera del nuevo rango se mantienen hasta que sean reubicados manualmente' : 
                        'Todos los productos mantienen sus ubicaciones'
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Error de validación: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error("Estante no encontrado: ID {$id}");
            return response()->json([
                'success' => false,
                'message' => 'El estante especificado no existe'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('=== ACTUALIZAR ESTRUCTURA ESTANTE - ERROR ===');
            Log::error('Error: ' . $e->getMessage());
            Log::error('Línea: ' . $e->getLine());
            Log::error('Archivo: ' . $e->getFile());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al actualizar la estructura del estante',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * API: Obtener información detallada de un estante para eliminación
     */
    public function informacionEstanteParaEliminacion($id)
    {
        try {
            $estante = Estante::with(['ubicaciones' => function($query) {
                $query->withSum('productos', 'cantidad');
            }])->findOrFail($id);

            $productosUbicados = ProductoUbicacion::whereHas('ubicacion', function($query) use ($id) {
                $query->where('estante_id', $id);
            })->where('cantidad', '>', 0)->with('producto')->get();

            $resumenProductos = $productosUbicados->map(function($pu) {
                return [
                    'id' => $pu->producto->id,
                    'nombre' => $pu->producto->nombre,
                    'cantidad' => $pu->cantidad,
                    'ubicacion_codigo' => $pu->ubicacion->codigo ?? 'Sin código'
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'estante' => [
                        'id' => $estante->id,
                        'nombre' => $estante->nombre,
                        'descripcion' => $estante->descripcion,
                        'capacidad_total' => $estante->capacidad_total,
                        'tipo' => $estante->tipo
                    ],
                    'estadisticas' => [
                        'total_ubicaciones' => $estante->ubicaciones->count(),
                        'productos_ubicados' => $productosUbicados->count(),
                        'cantidad_total_productos' => $productosUbicados->sum('cantidad')
                    ],
                    'productos' => $resumenProductos
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'El estante especificado no existe'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al obtener información del estante: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del estante'
            ], 500);
        }
    }

    /**
     * API: Actualizar información de un producto en una ubicación específica
     */
    public function actualizarProducto(Request $request)
    {
        Log::info('=== ACTUALIZAR PRODUCTO - INICIO ===');
        Log::info('Datos recibidos: ' . json_encode($request->all()));

        try {
            $request->validate([
                'producto_ubicacion_id' => 'required|integer',
                'nueva_cantidad' => 'required|integer|min:0',
                'nuevo_lote' => 'nullable|string|max:100',
                'nueva_fecha_vencimiento' => 'nullable|date'
            ]);

            DB::beginTransaction();

            $productoUbicacion = ProductoUbicacion::with(['producto', 'ubicacion.estante'])
                                                 ->findOrFail($request->producto_ubicacion_id);

            Log::info("📦 Producto encontrado: {$productoUbicacion->producto->nombre}");
            Log::info("📍 Ubicación: {$productoUbicacion->ubicacion->codigo}");

            // Obtener stock total actual del producto (sumando todas las ubicaciones)
            $stockTotalActual = ProductoUbicacion::where('producto_id', $productoUbicacion->producto_id)
                                                ->sum('cantidad');
            
            // Calcular diferencia
            $diferencia = $request->nueva_cantidad - $productoUbicacion->cantidad;
            $nuevoStockTotal = $stockTotalActual + $diferencia;
            
            Log::info("📊 Stock actual en ubicación: {$productoUbicacion->cantidad}");
            Log::info("📊 Nueva cantidad: {$request->nueva_cantidad}");
            Log::info("📊 Diferencia: {$diferencia}");
            Log::info("📊 Nuevo stock total: {$nuevoStockTotal}");
            
            // Validar que no sea negativo
            if ($nuevoStockTotal < 0) {
                throw new \Exception('La cantidad resultante no puede ser negativa');
            }

            // Actualizar ProductoUbicacion
            $productoUbicacion->update([
                'cantidad' => $request->nueva_cantidad,
                'lote' => $request->nuevo_lote ?? $productoUbicacion->lote,
                'fecha_vencimiento' => $request->nueva_fecha_vencimiento ?? $productoUbicacion->fecha_vencimiento
            ]);

            // Actualizar stock total del producto
            $productoUbicacion->producto->update([
                'stock_actual' => $nuevoStockTotal
            ]);
            
            // Recalcular el estado del producto después de actualizar el stock
            $productoUbicacion->producto->fresh()->recalcularEstado();

            // Si la cantidad es 0, actualizar ubicacion_almacen del producto
            if ($request->nueva_cantidad == 0) {
                $productoUbicacion->producto->actualizarUbicacionAlmacen();
            }

            // Registrar movimiento
            MovimientoStock::create([
                'producto_id' => $productoUbicacion->producto_id,
                'tipo_movimiento' => $diferencia >= 0 ? 'entrada' : 'salida',
                'cantidad' => abs($diferencia),
                'motivo' => 'Actualización desde estante: ' . $productoUbicacion->ubicacion->codigo,
                'usuario_id' => auth()->id() ?? 1
            ]);

            DB::commit();

            Log::info('=== PRODUCTO ACTUALIZADO EXITOSAMENTE ===');

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente',
                'data' => [
                    'nuevo_stock_total' => $nuevoStockTotal,
                    'cantidad_en_ubicacion' => $request->nueva_cantidad,
                    'diferencia' => $diferencia
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Error de validación: ' . json_encode($e->errors()));
            
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('❌ Error al actualizar producto: ' . $e->getMessage());
            Log::error('📍 Línea: ' . $e->getLine());
            Log::error('📁 Archivo: ' . $e->getFile());
            Log::error('📚 Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el producto: ' . $e->getMessage(),
                'debug_info' => [
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile()),
                    'datos_recibidos' => $request->all()
                ]
            ], 500);
        }
    }

    /**
     * Obtener todos los productos ubicados con información completa para la vista mapa
     */
    private function obtenerTodosLosProductosUbicados()
    {
        Log::info('📦 Obteniendo todos los productos ubicados...');
        
        $productosUbicados = ProductoUbicacion::with([
            'producto:id,nombre,codigo_barras,marca,categoria,precio_venta,concentracion',
            'ubicacion.estante:id,nombre,tipo'
        ])
        ->where('cantidad', '>', 0)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($pu) {
            // Determinar estado del stock
            $cantidad = $pu->cantidad;
            $estado = 'normal';
            if ($cantidad <= 3) {
                $estado = 'stock-critico';
            } elseif ($cantidad <= 10) {
                $estado = 'stock-bajo';
            }

            // Determinar tipo de ubicación para badge
            $tipoUbicacion = $pu->ubicacion->estante->tipo ?? 'venta';
            $iconoUbicacion = match($tipoUbicacion) {
                'venta' => 'solar:shop-bold-duotone',
                'almacen' => 'solar:box-minimalistic-bold-duotone',
                default => 'solar:home-bold-duotone'
            };

            return [
                'id' => $pu->producto->id,
                'nombre' => $pu->producto->nombre,
                'marca' => $pu->producto->marca ?? '',
                'categoria' => $pu->producto->categoria ?? '',
                'concentracion' => $pu->producto->concentracion ?? '',
                'codigo_barras' => $pu->producto->codigo_barras,
                'cantidad' => $cantidad,
                'precio_venta' => $pu->producto->precio_venta ?? 0,
                'lote' => $pu->lote,
                'fecha_vencimiento' => $pu->fecha_vencimiento?->format('d/m/Y'),
                'estado' => $estado,
                'estante_id' => $pu->ubicacion->estante->id,
                'estante_nombre' => $pu->ubicacion->estante->nombre,
                'estante_tipo' => $tipoUbicacion,
                'ubicacion_codigo' => $pu->ubicacion->codigo,
                'ubicacion_completa' => $pu->ubicacion->estante->nombre . ' - ' . $pu->ubicacion->codigo,
                'icono_ubicacion' => $iconoUbicacion,
                'producto_ubicacion_id' => $pu->id,
                'ubicacion_id' => $pu->ubicacion->id
            ];
        });

        Log::info("✅ Productos ubicados encontrados: " . $productosUbicados->count());
        return $productosUbicados;
    }

    /**
     * Obtener productos sin ubicar con información de prioridad
     */
    private function obtenerProductosSinUbicar()
    {
        Log::info('📋 Obteniendo productos sin ubicar...');
        
        $productosSinUbicar = Producto::where(function($query) {
            $query->where('ubicacion', 'Sin ubicar')
                  ->orWhereNull('ubicacion');
        })
        // Removido el filtro de stock > 0 para mostrar todos los productos sin ubicar
        ->select('id', 'nombre', 'categoria', 'stock_actual', 'concentracion', 'created_at')
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function($producto, $index) {
            // Determinar prioridad basada en múltiples factores para tener variedad
            $diasEsperando = now()->diffInDays($producto->created_at);
            $stock = $producto->stock_actual;
            
            // Determinar tiempo esperando (siempre basado en días)
            if ($diasEsperando >= 1) {
                $tiempoEsperando = $diasEsperando . ' día' . ($diasEsperando > 1 ? 's' : '');
            } else {
                $tiempoEsperando = 'Hoy';
            }
            
            // Lógica corregida para prioridades
            if ($stock == 0) {
                // Sin stock = ALTA prioridad (urgente, necesita reabastecimiento)
                $prioridad = 'alta';
            } elseif ($diasEsperando >= 7) {
                // Más de una semana esperando = ALTA prioridad  
                $prioridad = 'alta';
            } elseif ($diasEsperando >= 3 && $diasEsperando < 7) {
                // 3-6 días esperando = MEDIA prioridad
                $prioridad = 'media';
            } elseif ($stock > 0 && $stock <= 5) {
                // Stock bajo pero no vacío = MEDIA prioridad
                $prioridad = 'media';
            } elseif ($diasEsperando >= 0 && $diasEsperando <= 2) {
                // 0-2 días (Hoy, Ayer, Hace 2 días) = BAJA prioridad
                $prioridad = 'baja';
            } else {
                // Caso por defecto = BAJA prioridad
                $prioridad = 'baja';
            }

            return [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'categoria' => $producto->categoria ?? 'Sin categoría',
                'concentracion' => $producto->concentracion ?? '',
                'stock' => $producto->stock_actual,
                'prioridad' => $prioridad,
                'tiempo_esperando' => $tiempoEsperando,
                'dias_esperando' => $diasEsperando
            ];
        });

        Log::info("⚠️ Productos sin ubicar encontrados: " . $productosSinUbicar->count());
        return $productosSinUbicar;
    }

    /**
     * API: Obtener fecha de vencimiento de un producto específico
     */
    public function obtenerFechaVencimiento($productoId)
    {
        try {
            Log::info('🔍 Obteniendo fecha de vencimiento para producto ID: ' . $productoId);
            
            // Buscar el producto en la tabla productos
            $producto = \App\Models\Producto::find($productoId);
            
            if (!$producto) {
                Log::warning('⚠️ Producto no encontrado con ID: ' . $productoId);
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado',
                    'fecha_vencimiento' => null
                ], 404);
            }
            
            Log::info('📄 Producto encontrado: ' . $producto->nombre);
            Log::info('📅 Fecha de vencimiento en BD: ' . ($producto->fecha_vencimiento ?? 'NULL'));
            
            // Retornar la fecha de vencimiento
            return response()->json([
                'success' => true,
                'message' => 'Fecha de vencimiento obtenida correctamente',
                'producto_id' => $producto->id,
                'producto_nombre' => $producto->nombre,
                'fecha_vencimiento' => $producto->fecha_vencimiento,
                'fecha_vencimiento_formateada' => $producto->fecha_vencimiento ? 
                    \Carbon\Carbon::parse($producto->fecha_vencimiento)->format('d/m/Y') : null
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener fecha de vencimiento: ' . $e->getMessage());
            Log::error('🔍 Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage(),
                'fecha_vencimiento' => null
            ], 500);
        }
    }

    /**
     * Obtener ubicaciones para filtros del POS
     */
    public function obtenerUbicacionesParaFiltros()
    {
        try {
            $ubicaciones = Ubicacion::with('estante:id,nombre')
                ->select(['id', 'codigo', 'estante_id'])
                ->orderBy('codigo')
                ->get()
                ->map(function($ubicacion) {
                    return [
                        'id' => $ubicacion->id,
                        'codigo' => $ubicacion->codigo,
                        'nombre' => $ubicacion->estante ? 
                            $ubicacion->estante->nombre . ' - ' . $ubicacion->codigo : 
                            $ubicacion->codigo
                    ];
                });

            return response()->json([
                'success' => true,
                'ubicaciones' => $ubicaciones
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ubicaciones'
            ], 500);
        }
    }
}