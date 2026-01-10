<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductoRequest;
use App\Services\ProductoService;
use App\Repositories\ProductoRepository;
use App\Models\Producto;
use App\Events\ProductoActualizado;
use App\Jobs\ActualizarEstadosProductos;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\CloudinaryService;

class ProductoOptimizadoController extends Controller
{
    protected $productoService;
    protected $productoRepository;

    public function __construct(ProductoService $productoService, ProductoRepository $productoRepository)
    {
        $this->productoService = $productoService;
        $this->productoRepository = $productoRepository;
        
        // Aplicar middleware de autenticación
        $this->middleware('auth');
    }

    /**
     * Listar productos con filtros y cache
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filtros = $request->only(['categoria', 'estado', 'busqueda', 'marca']);
            $perPage = $request->get('per_page', 15);
            
            // Usar repository para consultas complejas
            $productos = $this->productoRepository->getPaginatedWithFilters($filtros, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => $productos,
                'filtros' => $filtros
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error listando productos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar productos'
            ], 500);
        }
    }

    /**
     * Buscar productos con autocompletado
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $termino = $request->get('q', '');
            $limite = $request->get('limit', 10);
            
            if (strlen($termino) < 2) {
                return response()->json([
                    'success' => true,
                    'productos' => []
                ]);
            }
            
            // Usar servicio con cache
            $productos = $this->productoService->buscarProductos($termino, $limite);
            
            return response()->json([
                'success' => true,
                'productos' => $productos
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error buscando productos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda'
            ], 500);
        }
    }

    /**
     * Crear nuevo producto
     */
    public function store(ProductoRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $data = $request->validated();
            
            // Manejar imagen si existe
            if ($request->hasFile('imagen')) {
                $data['imagen'] = $this->guardarImagen($request->file('imagen'));
            }
            
            $producto = $this->productoRepository->create($data);
            
            // Disparar evento para auditoría
            event(new ProductoActualizado(
                $producto,
                Auth::user(),
                ['nuevo' => $data],
                'creado'
            ));
            
            DB::commit();
            
            Log::info("Producto creado: {$producto->nombre}", [
                'producto_id' => $producto->id,
                'usuario_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'producto' => $producto
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando producto: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear producto'
            ], 500);
        }
    }

    /**
     * Mostrar producto específico
     */
    public function show(int $id): JsonResponse
    {
        try {
            $producto = $this->productoRepository->getAllWithRelations(['ubicaciones.ubicacion.estante'])
                                                 ->where('id', $id)
                                                 ->first();
            
            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'producto' => $producto
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error mostrando producto: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar producto'
            ], 500);
        }
    }

    /**
     * Actualizar producto
     */
    public function update(ProductoRequest $request, int $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $producto = $this->productoRepository->getAllWithRelations()->where('id', $id)->first();
            
            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }
            
            $datosAnteriores = $producto->toArray();
            $data = $request->validated();
            
            // Manejar imagen si existe
            if ($request->hasFile('imagen')) {
                // Eliminar imagen local anterior si no es URL absoluta
                if ($producto->imagen && !str_starts_with((string) $producto->imagen, 'http')) {
                    Storage::delete('public/productos/' . $producto->imagen);
                }
                $data['imagen'] = $this->guardarImagen($request->file('imagen'));
            }
            
            $productoActualizado = $this->productoRepository->update($id, $data);
            
            // Disparar evento para auditoría
            event(new ProductoActualizado(
                $productoActualizado,
                Auth::user(),
                [
                    'anterior' => $datosAnteriores,
                    'nuevo' => $data
                ],
                'actualizado'
            ));
            
            DB::commit();
            
            Log::info("Producto actualizado: {$productoActualizado->nombre}", [
                'producto_id' => $id,
                'usuario_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente',
                'producto' => $productoActualizado
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando producto: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar producto'
            ], 500);
        }
    }

    /**
     * Eliminar producto
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $producto = $this->productoRepository->getAllWithRelations()->where('id', $id)->first();
            
            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }
            
            $datosProducto = $producto->toArray();
            
            // Eliminar imagen local si existe y no es URL absoluta
            if ($producto->imagen && !str_starts_with((string) $producto->imagen, 'http')) {
                Storage::delete('public/productos/' . $producto->imagen);
            }
            
            $this->productoRepository->delete($id);
            
            // Disparar evento para auditoría
            event(new ProductoActualizado(
                $producto,
                Auth::user(),
                ['anterior' => $datosProducto],
                'eliminado'
            ));
            
            DB::commit();
            
            Log::info("Producto eliminado: {$producto->nombre}", [
                'producto_id' => $id,
                'usuario_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error eliminando producto: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar producto'
            ], 500);
        }
    }

    /**
     * Actualizar stock de producto
     */
    public function actualizarStock(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'cantidad' => 'required|integer|min:1',
            'tipo' => 'required|in:entrada,salida',
            'motivo' => 'nullable|string|max:255'
        ]);
        
        try {
            $producto = $this->productoService->actualizarStock(
                $id,
                $request->cantidad,
                $request->tipo,
                $request->motivo
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Stock actualizado exitosamente',
                'producto' => $producto
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error actualizando stock: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener estadísticas de productos
     */
    public function estadisticas(): JsonResponse
    {
        try {
            $estadisticas = $this->productoService->getEstadisticas();
            
            return response()->json([
                'success' => true,
                'estadisticas' => $estadisticas
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar estadísticas'
            ], 500);
        }
    }

    /**
     * Obtener productos con bajo stock
     */
    public function bajoStock(): JsonResponse
    {
        try {
            $productos = $this->productoService->getProductosBajoStock();
            
            return response()->json([
                'success' => true,
                'productos' => $productos
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo productos bajo stock: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar productos'
            ], 500);
        }
    }

    /**
     * Obtener productos próximos a vencer
     */
    public function proximosVencer(Request $request): JsonResponse
    {
        try {
            $dias = $request->get('dias', 30);
            $productos = $this->productoService->getProductosProximosVencer($dias);
            
            return response()->json([
                'success' => true,
                'productos' => $productos
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo productos próximos a vencer: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar productos'
            ], 500);
        }
    }

    /**
     * Obtener productos críticos para inventario móvil
     * Incluye: bajo stock, por vencer, vencido y agotado
     */
    public function productosCriticos(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 20);
            $estado = $request->get('estado', 'todos'); // todos, bajo_stock, por_vencer, vencido, agotado
            $search = $request->get('search', '');
            
            // Query base con relaciones necesarias
            $query = $this->productoRepository->getAllWithRelations(['ubicaciones.ubicacion.estante'])
                ->where(function($q) {
                    // Solo productos con estados críticos
                    $q->where('estado', 'Bajo stock')
                      ->orWhere('estado', 'Por vencer')
                      ->orWhere('estado', 'Vencido')
                      ->orWhere('stock_actual', '<=', 0); // Agotado
                });
            
            // Filtro por estado específico
            if ($estado !== 'todos') {
                switch ($estado) {
                    case 'bajo_stock':
                        $query->where('estado', 'Bajo stock');
                        break;
                    case 'por_vencer':
                        $query->where('estado', 'Por vencer');
                        break;
                    case 'vencido':
                        $query->where('estado', 'Vencido');
                        break;
                    case 'agotado':
                        $query->where('stock_actual', '<=', 0);
                        break;
                }
            }
            
            // Filtro de búsqueda
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'LIKE', "%{$search}%")
                      ->orWhere('categoria', 'LIKE', "%{$search}%")
                      ->orWhere('marca', 'LIKE', "%{$search}%")
                      ->orWhere('codigo_barras', 'LIKE', "%{$search}%");
                });
            }
            
            // Ordenar por prioridad (vencidos primero, luego por vencer, etc.)
            $query->orderByRaw("
                CASE 
                    WHEN estado = 'Vencido' THEN 1
                    WHEN stock_actual <= 0 THEN 2
                    WHEN estado = 'Por vencer' THEN 3
                    WHEN estado = 'Bajo stock' THEN 4
                    ELSE 5
                END
            ")->orderBy('fecha_vencimiento', 'asc')
              ->orderBy('stock_actual', 'asc');
            
            $productos = $query->paginate($perPage);
            
            // Transformar datos para la app móvil
            $productos->getCollection()->transform(function ($producto) {
                // Calcular días para vencer
                $diasParaVencer = null;
                if ($producto->fecha_vencimiento) {
                    $diasParaVencer = now()->diffInDays($producto->fecha_vencimiento, false);
                }
                
                // Determinar estado específico
                $estadoEspecifico = 'normal';
                if ($producto->stock_actual <= 0) {
                    $estadoEspecifico = 'agotado';
                } elseif ($producto->estado === 'Vencido') {
                    $estadoEspecifico = 'vencido';
                } elseif ($producto->estado === 'Por vencer') {
                    $estadoEspecifico = 'por_vencer';
                } elseif ($producto->estado === 'Bajo stock') {
                    $estadoEspecifico = 'bajo_stock';
                }
                
                return [
                    'id' => $producto->id,
                    'name' => $producto->nombre,
                    'brand' => $producto->marca ?? 'Sin marca',
                    'category' => $producto->categoria ?? 'Sin categoría',
                    'stock' => $producto->stock_actual,
                    'minStock' => $producto->stock_minimo,
                    'price' => (float) $producto->precio_venta,
                    'expiryDate' => $producto->fecha_vencimiento ? $producto->fecha_vencimiento->format('Y-m-d') : null,
                    'diasParaVencer' => $diasParaVencer,
                    'estado' => $estadoEspecifico,
                    'estadoTexto' => $producto->estado,
                    'barcode' => $producto->codigo_barras,
                    'image' => $producto->imagen_url,
                    'ubicacion' => $producto->ubicacion_almacen,
                    'presentacion' => $producto->presentacion,
                    'concentracion' => $producto->concentracion,
                    'lote' => $producto->lote,
                    'isLowStock' => $producto->stock_actual <= $producto->stock_minimo,
                    'isExpiringSoon' => $diasParaVencer !== null && $diasParaVencer <= 30 && $diasParaVencer > 0,
                    'isExpired' => $diasParaVencer !== null && $diasParaVencer < 0,
                    'isOutOfStock' => $producto->stock_actual <= 0,
                ];
            });
            
            // Estadísticas de productos críticos
            $estadisticas = [
                'total_criticos' => $productos->total(),
                'bajo_stock' => $this->productoRepository->getAllWithRelations()->where('estado', 'Bajo stock')->count(),
                'por_vencer' => $this->productoRepository->getAllWithRelations()->where('estado', 'Por vencer')->count(),
                'vencidos' => $this->productoRepository->getAllWithRelations()->where('estado', 'Vencido')->count(),
                'agotados' => $this->productoRepository->getAllWithRelations()->where('stock_actual', '<=', 0)->count(),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $productos,
                'estadisticas' => $estadisticas,
                'filtros' => [
                    'estado' => $estado,
                    'search' => $search,
                    'per_page' => $perPage
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo productos críticos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar productos críticos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar estados de productos (Job asíncrono)
     */
    public function actualizarEstados(): JsonResponse
    {
        try {
            // Despachar job asíncrono
            ActualizarEstadosProductos::dispatch();
            
            return response()->json([
                'success' => true,
                'message' => 'Actualización de estados iniciada en segundo plano'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error despachando job de actualización: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar actualización'
            ], 500);
        }
    }

    /**
     * Validar producto duplicado en tiempo real
     */
    public function validarDuplicado(Request $request): JsonResponse
    {
        try {
            $nombre = $request->get('nombre');
            $concentracion = $request->get('concentracion');
            $productoId = $request->get('producto_id');

            if (!$nombre || !$concentracion) {
                return response()->json([
                    'success' => true,
                    'es_duplicado' => false,
                    'message' => 'Datos incompletos para validar'
                ]);
            }

            $existe = Producto::where('nombre', 'LIKE', trim($nombre))
                ->where('concentracion', 'LIKE', trim($concentracion))
                ->when($productoId, function($query) use ($productoId) {
                    return $query->where('id', '!=', $productoId);
                })
                ->exists();

            return response()->json([
                'success' => true,
                'es_duplicado' => $existe,
                'message' => $existe 
                    ? "Ya existe un producto con el nombre '{$nombre}' y concentración '{$concentracion}'"
                    : 'Producto disponible'
            ]);

        } catch (\Exception $e) {
            Log::error('Error validando duplicado: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al validar producto'
            ], 500);
        }
    }

    /**
     * Validar código de barras único
     */
    public function validarCodigoBarras(Request $request): JsonResponse
    {
        try {
            $codigoBarras = $request->get('codigo_barras');
            $productoId = $request->get('producto_id');

            if (!$codigoBarras) {
                return response()->json([
                    'success' => true,
                    'es_unico' => true,
                    'message' => 'Código de barras vacío'
                ]);
            }

            $existe = Producto::where('codigo_barras', $codigoBarras)
                ->when($productoId, function($query) use ($productoId) {
                    return $query->where('id', '!=', $productoId);
                })
                ->exists();

            return response()->json([
                'success' => true,
                'es_unico' => !$existe,
                'message' => $existe 
                    ? 'Este código de barras ya está registrado'
                    : 'Código de barras disponible'
            ]);

        } catch (\Exception $e) {
            Log::error('Error validando código de barras: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al validar código de barras'
            ], 500);
        }
    }

    /**
     * Validar precios y calcular margen
     */
    public function validarPrecios(Request $request): JsonResponse
    {
        try {
            $precioCompra = (float) $request->get('precio_compra', 0);
            $precioVenta = (float) $request->get('precio_venta', 0);

            if ($precioCompra <= 0) {
                return response()->json([
                    'success' => true,
                    'es_valido' => false,
                    'message' => 'El precio de compra debe ser mayor a 0'
                ]);
            }

            if ($precioVenta <= 0) {
                return response()->json([
                    'success' => true,
                    'es_valido' => false,
                    'message' => 'El precio de venta debe ser mayor a 0'
                ]);
            }

            $margen = (($precioVenta - $precioCompra) / $precioCompra) * 100;
            $margenMinimo = 5; // 5%
            $margenMaximo = 500; // 500%

            $esValido = $margen >= $margenMinimo && $margen <= $margenMaximo;
            
            $message = '';
            if ($margen < $margenMinimo) {
                $precioMinimo = $precioCompra * 1.05;
                $message = "Margen muy bajo ({$margen}%). Precio mínimo sugerido: S/ " . number_format($precioMinimo, 2);
            } elseif ($margen > $margenMaximo) {
                $message = "Margen muy alto ({$margen}%). Verifica que sea correcto.";
            } else {
                $message = "Margen de ganancia: " . number_format($margen, 1) . "%";
            }

            return response()->json([
                'success' => true,
                'es_valido' => $esValido,
                'margen' => round($margen, 2),
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Error validando precios: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al validar precios'
            ], 500);
        }
    }

    /**
     * Obtener sugerencias de autocompletado
     */
    public function autocompletar(Request $request): JsonResponse
    {
        try {
            $campo = $request->get('campo'); // nombre, marca, categoria, etc.
            $termino = $request->get('termino');
            $limite = $request->get('limite', 10);

            if (!$campo || !$termino || strlen($termino) < 2) {
                return response()->json([
                    'success' => true,
                    'sugerencias' => []
                ]);
            }

            $camposPermitidos = ['nombre', 'marca', 'categoria', 'presentacion', 'ubicacion'];
            
            if (!in_array($campo, $camposPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campo no válido para autocompletado'
                ], 400);
            }

            $sugerencias = $this->productoRepository->getAllWithRelations()
                ->where($campo, 'LIKE', "%{$termino}%")
                ->distinct()
                ->limit($limite)
                ->pluck($campo)
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'sugerencias' => $sugerencias
            ]);

        } catch (\Exception $e) {
            Log::error('Error en autocompletado: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error en autocompletado'
            ], 500);
        }
    }

    /**
     * Guardar imagen de producto
     */
    private function guardarImagen($imagen): string
    {
        $cloudinary = new CloudinaryService();
        if ($cloudinary->isEnabled()) {
            $url = $cloudinary->uploadProductImage($imagen, 'productos');
            if ($url) {
                return $url;
            }
        }

        $nombreArchivo = time() . '_' . uniqid() . '.' . $imagen->getClientOriginalExtension();
        $imagen->storeAs('public/productos', $nombreArchivo);
        return $nombreArchivo;
    }
}
