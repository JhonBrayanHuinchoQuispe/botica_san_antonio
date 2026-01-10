<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ProductoApiController extends Controller
{
    /**
     * Obtener todos los productos con paginación
     */
    public function index(Request $request)
    {
        try {
            $query = Producto::query();
            
            // Búsqueda por nombre o código de barras
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('codigo_barras', 'like', "%{$search}%")
                      ->orWhere('marca', 'like', "%{$search}%");
                });
            }
            
            // Filtros adicionales
            if ($request->has('category')) {
                $query->where('categoria', $request->category);
            }
            
            // Ordenamiento
            $sortBy = $request->get('sort_by', 'nombre');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
            
            // Obtener productos sin paginación para calcular estados
            $allProducts = $query->get();
            
            // Agregar campos calculados a cada producto
            $productsWithStatus = $allProducts->map(function ($producto) {
                // Calcular días hasta vencimiento
                $diasParaVencer = null;
                if ($producto->fecha_vencimiento) {
                    $diasParaVencer = Carbon::now()->diffInDays(Carbon::parse($producto->fecha_vencimiento), false);
                }
                
                // Calcular estados
                $isLowStock = $producto->stock_actual <= $producto->stock_minimo;
                $isExpiringSoon = $diasParaVencer !== null && $diasParaVencer <= 30 && $diasParaVencer > 0;
                $isExpired = $diasParaVencer !== null && $diasParaVencer < 0;
                $isOutOfStock = $producto->stock_actual <= 0;
                
                // Determinar estado y color
                $status = 'in_stock';
                $statusText = 'En Stock';
                $statusColor = '#16a34a'; // Verde
                
                if ($isExpired) {
                    $status = 'expired';
                    $statusText = 'Vencido';
                    $statusColor = '#dc2626'; // Rojo
                } elseif ($isOutOfStock) {
                    $status = 'out_of_stock';
                    $statusText = 'Agotado';
                    $statusColor = '#6b7280'; // Gris
                } elseif ($isLowStock) {
                    $status = 'low_stock';
                    $statusText = 'Stock Bajo';
                    $statusColor = '#ea580c'; // Naranja
                } elseif ($isExpiringSoon) {
                    $status = 'expiring_soon';
                    $statusText = 'Por Vencer';
                    $statusColor = '#d97706'; // Amarillo/Naranja
                }
                
                // Agregar campos calculados al producto
                $producto->imagen_url = $producto->imagen_url ?? url('assets/images/default-product.svg');
                $producto->is_low_stock = $isLowStock;
                $producto->is_expiring_soon = $isExpiringSoon;
                $producto->is_expired = $isExpired;
                $producto->is_out_of_stock = $isOutOfStock;
                $producto->status = $status;
                $producto->status_text = $statusText;
                $producto->status_color = $statusColor;
                
                return $producto;
            });
            
            // Aplicar paginación manual
            $perPage = min($request->get('per_page', 20), 100);
            $currentPage = $request->get('page', 1);
            $total = $productsWithStatus->count();
            $offset = ($currentPage - 1) * $perPage;
            $paginatedProducts = $productsWithStatus->slice($offset, $perPage)->values();
            
            // Crear respuesta paginada
            $paginationData = [
                'current_page' => (int) $currentPage,
                'data' => $paginatedProducts,
                'first_page_url' => $request->url() . '?page=1',
                'from' => $offset + 1,
                'last_page' => ceil($total / $perPage),
                'last_page_url' => $request->url() . '?page=' . ceil($total / $perPage),
                'next_page_url' => $currentPage < ceil($total / $perPage) ? $request->url() . '?page=' . ($currentPage + 1) : null,
                'path' => $request->url(),
                'per_page' => $perPage,
                'prev_page_url' => $currentPage > 1 ? $request->url() . '?page=' . ($currentPage - 1) : null,
                'to' => min($offset + $perPage, $total),
                'total' => $total,
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'Productos obtenidos exitosamente',
                'data' => $paginationData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener un producto específico
     */
    public function show($id)
    {
        try {
            $product = Producto::find($id);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }
            
            // Calcular días hasta vencimiento
            $diasParaVencer = null;
            if ($product->fecha_vencimiento) {
                $diasParaVencer = Carbon::now()->diffInDays(Carbon::parse($product->fecha_vencimiento), false);
            }
            
            // Calcular estados
            $isLowStock = $product->stock_actual <= $product->stock_minimo;
            $isExpiringSoon = $diasParaVencer !== null && $diasParaVencer <= 30 && $diasParaVencer > 0;
            $isExpired = $diasParaVencer !== null && $diasParaVencer < 0;
            $isOutOfStock = $product->stock_actual <= 0;
            
            // Determinar estado y color
            $status = 'in_stock';
            $statusText = 'En Stock';
            $statusColor = '#16a34a'; // Verde
            
            if ($isExpired) {
                $status = 'expired';
                $statusText = 'Vencido';
                $statusColor = '#dc2626'; // Rojo
            } elseif ($isOutOfStock) {
                $status = 'out_of_stock';
                $statusText = 'Agotado';
                $statusColor = '#6b7280'; // Gris
            } elseif ($isLowStock) {
                $status = 'low_stock';
                $statusText = 'Stock Bajo';
                $statusColor = '#ea580c'; // Naranja
            } elseif ($isExpiringSoon) {
                $status = 'expiring_soon';
                $statusText = 'Por Vencer';
                $statusColor = '#d97706'; // Amarillo/Naranja
            }
            
            // Agregar campos calculados al producto
            $product->imagen_url = $product->imagen_url ?? url('assets/images/default-product.svg');
            $product->is_low_stock = $isLowStock;
            $product->is_expiring_soon = $isExpiringSoon;
            $product->is_expired = $isExpired;
            $product->is_out_of_stock = $isOutOfStock;
            $product->status = $status;
            $product->status_text = $statusText;
            $product->status_color = $statusColor;
            $product->dias_para_vencer = $diasParaVencer;
            
            return response()->json([
                'success' => true,
                'message' => 'Producto encontrado',
                'data' => $product
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Crear nuevo producto
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255',
                'marca' => 'required|string|max:255',
                'precio_venta' => 'required|numeric|min:0',
                'stock_actual' => 'required|integer|min:0',
                'stock_minimo' => 'required|integer|min:0',
                'fecha_vencimiento' => 'required|date|after:today',
                'codigo_barras' => 'nullable|string|unique:productos,codigo_barras',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $product = Producto::create($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'data' => $product
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Actualizar producto
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Producto::find($id);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }
            
            $validator = Validator::make($request->all(), [
                'nombre' => 'sometimes|required|string|max:255',
                'marca' => 'sometimes|required|string|max:255',
                'precio_venta' => 'sometimes|required|numeric|min:0',
                'stock_actual' => 'sometimes|required|integer|min:0',
                'stock_minimo' => 'sometimes|required|integer|min:0',
                'fecha_vencimiento' => 'sometimes|required|date',
                'codigo_barras' => 'sometimes|nullable|string|unique:productos,codigo_barras,' . $id,
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $product->update($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente',
                'data' => $product->fresh()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Eliminar producto
     */
    public function destroy($id)
    {
        try {
            $product = Producto::find($id);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }
            
            $product->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Buscar producto por código de barras
     */
    public function findByBarcode($barcode)
    {
        try {
            $product = Producto::where('codigo_barras', $barcode)->first();
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Producto encontrado',
                'data' => $product
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Buscar productos por nombre
     */
    public function searchByName(Request $request)
    {
        try {
            $query = $request->get('q', '');
            
            if (empty($query)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parámetro de búsqueda requerido'
                ], 400);
            }
            
            $products = Producto::where('nombre', 'like', "%{$query}%")
                             ->orWhere('marca', 'like', "%{$query}%")
                             ->limit(20)
                             ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Búsqueda completada',
                'data' => $products
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Productos con stock bajo
     */
    public function lowStock()
    {
        try {
            $products = Producto::whereRaw('stock_actual <= stock_minimo')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Productos con stock bajo obtenidos',
                'data' => $products
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos con stock bajo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Productos próximos a vencer
     */
    public function expiring()
    {
        try {
            $thirtyDaysFromNow = Carbon::now()->addDays(30);
            
            $products = Producto::where('fecha_vencimiento', '<=', $thirtyDaysFromNow)
                             ->where('fecha_vencimiento', '>', Carbon::now())
                             ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Productos próximos a vencer obtenidos',
                'data' => $products
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos próximos a vencer',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Productos vencidos
     */
    public function expired()
    {
        try {
            $products = Producto::where('fecha_vencimiento', '<', Carbon::now())->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Productos vencidos obtenidos',
                'data' => $products
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos vencidos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Agregar stock a un producto (entrada de mercadería desde móvil)
     * Campos soportados:
     * - quantity (requerido)
     * - proveedor_id (opcional)
     * - precio_compra (opcional)
     * - precio_venta (opcional)
     * - lote (opcional)
     * - fecha_vencimiento (opcional)
     * - observaciones (opcional)
     */
    public function addStock(Request $request, $id)
    {
        try {
            $product = Producto::find($id);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }
            
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:1',
                'proveedor_id' => 'nullable|exists:proveedores,id',
                'precio_compra' => 'nullable|numeric|min:0',
                'precio_venta' => 'nullable|numeric|min:0',
                'lote' => 'nullable|string|max:255',
                'fecha_vencimiento' => 'nullable|date|after:today',
                'observaciones' => 'nullable|string|max:255',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $validated = $validator->validated();
            
            // Valores anteriores para historial
            $stockAnterior = $product->stock_actual;
            $precioCompraAnterior = $product->precio_compra;
            $precioVentaAnterior = $product->precio_venta;
            
            // Actualizar stock
            $product->stock_actual = $stockAnterior + (int) $validated['quantity'];
            
            // Actualizar precios si se enviaron
            if (isset($validated['precio_compra'])) {
                $product->precio_compra = $validated['precio_compra'];
            }
            if (isset($validated['precio_venta'])) {
                $product->precio_venta = $validated['precio_venta'];
            }
            
            // Actualizar lote y fecha de vencimiento si se enviaron (opcionales)
            if (!empty($validated['lote'] ?? null)) {
                $product->lote = $validated['lote'];
            }
            if (!empty($validated['fecha_vencimiento'] ?? null)) {
                $product->fecha_vencimiento = $validated['fecha_vencimiento'];
            }
            
            $product->save();
            
            // Registrar historial de entrada de mercadería
            try {
                \App\Models\EntradaMercaderia::create([
                    'producto_id' => $product->id,
                    'usuario_id' => auth()->check() ? auth()->id() : 1,
                    'proveedor_id' => $validated['proveedor_id'] ?? null,
                    'cantidad' => (int) $validated['quantity'],
                    'precio_compra_anterior' => $precioCompraAnterior,
                    'precio_compra_nuevo' => $validated['precio_compra'] ?? $precioCompraAnterior,
                    'precio_venta_anterior' => $precioVentaAnterior,
                    'precio_venta_nuevo' => $validated['precio_venta'] ?? $precioVentaAnterior,
                    'lote' => $validated['lote'] ?? null,
                    'fecha_vencimiento' => $validated['fecha_vencimiento'] ?? null,
                    'observaciones' => $validated['observaciones'] ?? null,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $product->stock_actual,
                    'fecha_entrada' => \Carbon\Carbon::now(),
                ]);
            } catch (\Exception $e) {
                // No bloquear por error en historial
            }
            
            // Preparar respuesta con campos usados por móvil
            $product->refresh();
            $product->name = $product->nombre;
            $product->price = (float) $product->precio_venta;
            $product->stock = $product->stock_actual;
            $product->minStock = $product->stock_minimo;
            $product->barcode = $product->codigo_barras;
            
            return response()->json([
                'success' => true,
                'message' => 'Stock agregado exitosamente',
                'data' => $product,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Ajustar stock de un producto
     */
    public function adjustStock(Request $request, $id)
    {
        try {
            $product = Producto::find($id);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }
            
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:0',
                'reason' => 'required|string|max:255',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Ajustar stock
            $product->stock_actual = $request->quantity;
            $product->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Stock ajustado exitosamente',
                'data' => $product->fresh()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al ajustar stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener alertas de productos
     */
    public function alerts()
    {
        try {
            $alerts = [];
            
            // Productos con stock bajo
            $lowStock = Producto::whereRaw('stock_actual <= stock_minimo')->count();
            if ($lowStock > 0) {
                $alerts[] = [
                    'type' => 'low_stock',
                    'message' => "Tienes {$lowStock} productos con stock bajo",
                    'count' => $lowStock,
                    'priority' => 'medium'
                ];
            }
            
            // Productos próximos a vencer
            $expiring = Producto::where('fecha_vencimiento', '<=', Carbon::now()->addDays(30))
                             ->where('fecha_vencimiento', '>', Carbon::now())
                             ->count();
            if ($expiring > 0) {
                $alerts[] = [
                    'type' => 'expiring',
                    'message' => "Tienes {$expiring} productos próximos a vencer",
                    'count' => $expiring,
                    'priority' => 'high'
                ];
            }
            
            // Productos vencidos
            $expired = Producto::where('fecha_vencimiento', '<', Carbon::now())->count();
            if ($expired > 0) {
                $alerts[] = [
                    'type' => 'expired',
                    'message' => "Tienes {$expired} productos vencidos",
                    'count' => $expired,
                    'priority' => 'critical'
                ];
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Alertas obtenidas exitosamente',
                'data' => $alerts
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener alertas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener productos críticos (combinando todos los tipos de alertas)
     */
    public function getCriticalProducts(Request $request)
    {
        try {
            $criticalProducts = [];
            
            // Función auxiliar para agregar campos calculados
            $addCalculatedFields = function ($product) {
                // Calcular días hasta vencimiento
                $diasParaVencer = null;
                if ($product->fecha_vencimiento) {
                    $diasParaVencer = Carbon::now()->diffInDays(Carbon::parse($product->fecha_vencimiento), false);
                }
                
                // Calcular estados
                $isLowStock = $product->stock_actual <= $product->stock_minimo;
                $isExpiringSoon = $diasParaVencer !== null && $diasParaVencer <= 30 && $diasParaVencer > 0;
                $isExpired = $diasParaVencer !== null && $diasParaVencer < 0;
                $isOutOfStock = $product->stock_actual <= 0;
                
                // Determinar estado específico
                $estadoEspecifico = 'normal';
                if ($isOutOfStock) {
                    $estadoEspecifico = 'agotado';
                } elseif ($isExpired) {
                    $estadoEspecifico = 'vencido';
                } elseif ($isExpiringSoon) {
                    $estadoEspecifico = 'por_vencer';
                } elseif ($isLowStock) {
                    $estadoEspecifico = 'bajo_stock';
                }
                
                // Agregar todos los campos necesarios para la app móvil
                $product->id = $product->id;
                $product->name = $product->nombre;
                $product->brand = $product->marca ?? 'Sin marca';
                $product->category = $product->categoria ?? 'Sin categoría';
                $product->stock = $product->stock_actual;
                $product->minStock = $product->stock_minimo;
                $product->price = (float) $product->precio_venta;
                $product->expiryDate = $product->fecha_vencimiento ? $product->fecha_vencimiento->format('Y-m-d') : null;
                $product->diasParaVencer = $diasParaVencer;
                $product->estado = $estadoEspecifico;
                $product->estadoTexto = $product->estado;
                $product->barcode = $product->codigo_barras;
                $product->image = $product->imagen_url;
                $product->ubicacion = $product->ubicacion_almacen; // Campo de ubicación
                $product->presentacion = $product->presentacion;
                $product->concentracion = $product->concentracion;
                $product->lote = $product->lote;
                $product->isLowStock = $isLowStock;
                $product->isExpiringSoon = $isExpiringSoon;
                $product->isExpired = $isExpired;
                $product->isOutOfStock = $isOutOfStock;
                $product->is_low_stock = $isLowStock;
                $product->is_expiring_soon = $isExpiringSoon;
                $product->is_expired = $isExpired;
                $product->is_out_of_stock = $isOutOfStock;
                $product->dias_para_vencer = $diasParaVencer;
                
                return $product;
            };
            
            // Productos con stock bajo
            $lowStockProducts = Producto::whereRaw('stock_actual <= stock_minimo')
                ->get()
                ->map(function ($product) use ($addCalculatedFields) {
                    $product = $addCalculatedFields($product);
                    $product->alert_type = 'low_stock';
                    $product->alert_message = 'Stock bajo';
                    $product->priority = 'medium';
                    return $product;
                });
            
            // Productos próximos a vencer (30 días)
            $expiringProducts = Producto::where('fecha_vencimiento', '<=', Carbon::now()->addDays(30))
                ->where('fecha_vencimiento', '>', Carbon::now())
                ->get()
                ->map(function ($product) use ($addCalculatedFields) {
                    $product = $addCalculatedFields($product);
                    $product->alert_type = 'expiring';
                    $product->alert_message = 'Próximo a vencer';
                    $product->priority = 'high';
                    return $product;
                });
            
            // Productos vencidos
            $expiredProducts = Producto::where('fecha_vencimiento', '<', Carbon::now())
                ->get()
                ->map(function ($product) use ($addCalculatedFields) {
                    $product = $addCalculatedFields($product);
                    $product->alert_type = 'expired';
                    $product->alert_message = 'Vencido';
                    $product->priority = 'critical';
                    return $product;
                });
            
            // Productos agotados (stock = 0)
            $outOfStockProducts = Producto::where('stock_actual', 0)
                ->get()
                ->map(function ($product) use ($addCalculatedFields) {
                    $product = $addCalculatedFields($product);
                    $product->alert_type = 'out_of_stock';
                    $product->alert_message = 'Agotado';
                    $product->priority = 'high';
                    return $product;
                });
            
            // Combinar todos los productos críticos
            $criticalProducts = $lowStockProducts
                ->concat($expiringProducts)
                ->concat($expiredProducts)
                ->concat($outOfStockProducts)
                ->unique('id'); // Evitar duplicados
            
            // Filtros opcionales
            if ($request->has('type')) {
                $criticalProducts = $criticalProducts->where('alert_type', $request->type);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Productos críticos obtenidos exitosamente',
                'data' => $criticalProducts->values(),
                'total' => $criticalProducts->count()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos críticos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalles completos del producto con información de lotes
     */
    public function getDetallesConLotes($id)
    {
        try {
            // Buscar el producto principal con sus ubicaciones (lotes)
            $producto = Producto::with(['ubicaciones.ubicacion.estante', 'proveedor'])
                ->find($id);
            
            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            // Obtener todos los lotes del producto desde ProductoUbicacion
            $lotes = $producto->ubicaciones()
                ->with(['ubicacion.estante', 'proveedor'])
                ->where('cantidad', '>', 0) // Solo lotes con stock
                ->orderBy('fecha_vencimiento', 'asc') // Ordenar por fecha de vencimiento
                ->get()
                ->map(function ($ubicacion) {
                    // Calcular días hasta vencimiento
                    $diasParaVencer = null;
                    if ($ubicacion->fecha_vencimiento) {
                        $diasParaVencer = Carbon::now()->diffInDays(Carbon::parse($ubicacion->fecha_vencimiento), false);
                    }

                    // Determinar ubicación completa
                    $ubicacionTexto = 'Sin ubicar';
                    if ($ubicacion->ubicacion && $ubicacion->ubicacion->estante) {
                        $ubicacionTexto = $ubicacion->ubicacion->estante->nombre . ' - ' . $ubicacion->ubicacion->codigo;
                    }

                    return [
                        'id' => $ubicacion->id,
                        'lote' => $ubicacion->lote ?? 'Sin lote',
                        'stock_actual' => $ubicacion->cantidad,
                        'cantidad_inicial' => $ubicacion->cantidad_inicial,
                        'cantidad_vendida' => $ubicacion->cantidad_vendida ?? 0,
                        'fecha_ingreso' => $ubicacion->fecha_ingreso,
                        'fecha_vencimiento' => $ubicacion->fecha_vencimiento,
                        'ubicacion' => $ubicacionTexto,
                        'precio_compra' => $ubicacion->precio_compra_lote,
                        'precio_venta' => $ubicacion->precio_venta_lote,
                        'proveedor' => $ubicacion->proveedor ? $ubicacion->proveedor->nombre : null,
                        'estado_lote' => $ubicacion->estado_lote,
                        'observaciones' => $ubicacion->observaciones,
                        'dias_para_vencer' => $diasParaVencer,
                        'estado' => $this->determinarEstadoLote($ubicacion, $diasParaVencer)
                    ];
                });

            // Preparar datos del producto principal
            // Construir URL pública de imagen (soporta Cloudinary o almacenamiento local)
            $imagenUrl = $producto->imagen_url;

            $productoData = [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'concentracion' => $producto->concentracion,
                'categoria' => $producto->categoria,
                'marca' => $producto->marca,
                'proveedor' => $producto->proveedor ? $producto->proveedor->nombre : null,
                'presentacion' => $producto->presentacion,
                'codigo_barras' => $producto->codigo_barras,
                'lote' => $producto->lote,
                'stock_actual' => $producto->stock_actual,
                'stock_minimo' => $producto->stock_minimo,
                'precio_compra' => $producto->precio_compra,
                'precio_venta' => $producto->precio_venta,
                'fecha_fabricacion' => $producto->fecha_fabricacion,
                'fecha_vencimiento' => $producto->fecha_vencimiento,
                'ubicacion' => $producto->ubicacion_almacen,
                'imagen' => $producto->imagen,
                'imagen_url' => $imagenUrl,
                'estado' => $producto->estado ?? 'Normal'
            ];

            return response()->json([
                'success' => true,
                'message' => 'Detalles del producto obtenidos exitosamente',
                'producto' => $productoData,
                'lotes' => $lotes,
                'total_lotes' => $lotes->count(),
                'stock_total' => $lotes->sum('stock_actual'),
                'lotes_vencidos' => $lotes->where('estado', 'vencido')->count(),
                'lotes_por_vencer' => $lotes->where('estado', 'por_vencer')->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalles del producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determinar el estado de un lote específico
     */
    private function determinarEstadoLote($ubicacion, $diasParaVencer)
    {
        if ($diasParaVencer !== null && $diasParaVencer < 0) {
            return 'vencido';
        } elseif ($diasParaVencer !== null && $diasParaVencer <= 30) {
            return 'por_vencer';
        } elseif ($ubicacion->cantidad <= 5) { // Consideramos bajo stock si hay 5 o menos unidades
            return 'bajo_stock';
        } else {
            return 'normal';
        }
    }
}
