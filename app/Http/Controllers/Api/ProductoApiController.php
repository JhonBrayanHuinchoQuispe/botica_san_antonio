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
            $query = Producto::with('ubicaciones'); // Eager load batches for accurate status
            
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
                // Determine effective expiration date based on batches (FEFO)
                // If there's an active batch expiring sooner than the product master date, use that.
                $minBatchDate = null;
                if ($producto->ubicaciones && $producto->ubicaciones->count() > 0) {
                    $activeBatches = $producto->ubicaciones->where('cantidad', '>', 0)->whereNotNull('fecha_vencimiento');
                    if ($activeBatches->count() > 0) {
                        $minBatchDate = $activeBatches->sortBy('fecha_vencimiento')->first()->fecha_vencimiento;
                    }
                }
                
                $effectiveDate = $producto->fecha_vencimiento;
                if ($minBatchDate) {
                    $batchDate = Carbon::parse($minBatchDate);
                    $masterDate = $producto->fecha_vencimiento ? Carbon::parse($producto->fecha_vencimiento) : null;
                    
                    // If batch date is earlier (or master is null), use batch date
                    if (!$masterDate || $batchDate->lt($masterDate)) {
                        $effectiveDate = $minBatchDate;
                    }
                }
                
                // Override the product's date for the JSON response so the app sees the worst case
                if ($effectiveDate) {
                    $producto->fecha_vencimiento = Carbon::parse($effectiveDate)->format('Y-m-d');
                }

                // Calcular días hasta vencimiento
                $diasParaVencer = null;
                if ($effectiveDate) {
                    $diasParaVencer = Carbon::now()->diffInDays(Carbon::parse($effectiveDate), false);
                }
                
                // Calcular estados
                $isLowStock = $producto->stock_actual <= $producto->stock_minimo;
                // Use 90 days to match Web System QueryOptimizationService logic
                $isExpiringSoon = $diasParaVencer !== null && $diasParaVencer <= 90 && $diasParaVencer >= 0;
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
                } elseif ($isExpiringSoon) {
                    $status = 'expiring_soon';
                    $statusText = 'Por Vencer';
                    $statusColor = '#d97706'; // Amarillo/Naranja
                } elseif ($isLowStock) {
                    $status = 'low_stock';
                    $statusText = 'Stock Bajo';
                    $statusColor = '#ea580c'; // Naranja
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
                $producto->dias_para_vencer = $diasParaVencer;
                
                // Count active batches and their statuses
                $producto->batches_count = 0;
                $producto->batches_expiring_count = 0;
                $producto->batches_expired_count = 0;

                if ($producto->relationLoaded('ubicaciones')) {
                    $activeBatches = $producto->ubicaciones->where('cantidad', '>', 0);
                    $producto->batches_count = $activeBatches->count();

                    foreach ($activeBatches as $batch) {
                        if ($batch->fecha_vencimiento) {
                            $days = Carbon::now()->diffInDays(Carbon::parse($batch->fecha_vencimiento), false);
                            if ($days < 0) {
                                $producto->batches_expired_count++;
                            } elseif ($days <= 90) {
                                $producto->batches_expiring_count++;
                            }
                        }
                    }
                }
                
                // Convertir a array y mezclar para asegurar que los campos dinámicos estén en el JSON
                $productoArray = $producto->toArray();
                $productoArray['batches_count'] = $producto->batches_count;
                $productoArray['batches_expiring_count'] = $producto->batches_expiring_count;
                $productoArray['batches_expired_count'] = $producto->batches_expired_count;
                
                // Asegurar que los campos calculados anteriores también estén
                $productoArray['status'] = $status;
                $productoArray['status_text'] = $statusText;
                $productoArray['status_color'] = $statusColor;
                $productoArray['is_low_stock'] = $isLowStock;
                $productoArray['is_expiring_soon'] = $isExpiringSoon;
                $productoArray['is_expired'] = $isExpired;
                $productoArray['is_out_of_stock'] = $isOutOfStock;
                $productoArray['dias_para_vencer'] = $diasParaVencer;
                $productoArray['imagen_url'] = $producto->imagen_url; // Append accessors

                return $productoArray;
            });
            
            // Aplicar paginación manual
            // Aumentamos el límite máximo a 2000 para permitir que la app móvil descargue todo el catálogo
            // y realice la búsqueda local correctamente.
            $perPage = min($request->get('per_page', 20), 2000);
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
            // Log para depuración
            \Log::info('Mobile: Creando producto', ['data' => $request->except(['imagen'])]);
            
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255',
                'marca' => 'nullable|string|max:255',
                'precio_venta' => 'required|numeric|min:0',
                'stock_actual' => 'nullable|integer|min:0',
                'stock_minimo' => 'nullable|integer|min:0',
                'fecha_vencimiento' => 'nullable|date',
                'codigo_barras' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                \Log::warning('Mobile: Validación fallida al crear producto', ['errors' => $validator->errors()->toArray()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Verificar si el código de barras ya existe (solo si se proporciona)
            if ($request->codigo_barras) {
                $existente = Producto::where('codigo_barras', $request->codigo_barras)->first();
                if ($existente) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El código de barras ya está registrado',
                        'errors' => ['codigo_barras' => ['El código de barras ya existe']]
                    ], 422);
                }
            }
            
            // Valores por defecto
            $request->merge([
                'stock_actual' => $request->stock_actual ?? 0,
                'stock_minimo' => $request->stock_minimo ?? 5,
                'marca' => $request->marca ?? 'Sin marca',
            ]);

            // Manejar subida de imagen
            $imagenUrl = null;
            if ($request->hasFile('imagen')) {
                $file = $request->file('imagen');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('public/productos', $filename);
                // Generar URL accesible
                $imagenUrl = url('storage/productos/' . $filename);
            }

            // Crear el producto con los datos básicos
            $data = $request->all();
            if ($imagenUrl) {
                $data['imagen_url'] = $imagenUrl;
                // Si tienes columna 'imagen' para el path relativo:
                $data['imagen'] = 'productos/' . $filename; 
            }

            $product = Producto::create($data);

            // --- MANEJO DE PRESENTACIONES ---
            if ($request->has('presentaciones')) {
                $presentaciones = $request->input('presentaciones');
                $presentacionesUnidades = $request->input('presentaciones_unidades', []);
                
                // Si viene como JSON string (desde multipart), decodificar
                if (is_string($presentaciones)) {
                    $presentaciones = json_decode($presentaciones, true);
                }
                if (is_string($presentacionesUnidades)) {
                    $presentacionesUnidades = json_decode($presentacionesUnidades, true);
                }

                if (is_array($presentaciones)) {
                    foreach ($presentaciones as $nombre => $precio_venta) {
                        $unidades = isset($presentacionesUnidades[$nombre]) ? (int)$presentacionesUnidades[$nombre] : 1;
                        
                        \App\Models\ProductoPresentacion::create([
                            'producto_id' => $product->id,
                            'nombre_presentacion' => $nombre,
                            'unidades_por_presentacion' => $unidades,
                            'precio_venta_presentacion' => $precio_venta,
                        ]);
                    }
                    $product->update(['tiene_presentaciones' => 1]);
                }
            }

            // --- CREACIÓN AUTOMÁTICA DE LOTE INICIAL ---
            // Si el producto se crea con stock > 0, debemos registrar el lote físico
            if ($product->stock_actual > 0) {
                // Obtener o crear ubicación por defecto
                $ubicacionDefault = \App\Models\Ubicacion::first();
                if (!$ubicacionDefault) {
                    // Crear estante por defecto si no existe (esto crea ubicaciones automáticamente)
                    $estanteDefault = \App\Models\Estante::firstOrCreate(
                        ['nombre' => 'Estante Principal'],
                        ['descripcion' => 'Estante creado automáticamente', 'numero_niveles' => 1, 'numero_posiciones' => 1, 'activo' => true]
                    );
                    // Obtener la ubicación creada automáticamente por el estante
                    $ubicacionDefault = \App\Models\Ubicacion::where('estante_id', $estanteDefault->id)->first();
                }
                $ubicacionId = $ubicacionDefault ? $ubicacionDefault->id : null;

                if ($ubicacionId) {
                    $loteCodigo = $request->lote ?? 'LOTE-INI-' . $product->id;
                    
                    \App\Models\ProductoUbicacion::create([
                        'producto_id' => $product->id,
                        'ubicacion_id' => $ubicacionId,
                        'cantidad' => $product->stock_actual,
                        'cantidad_inicial' => $product->stock_actual,
                        'lote' => $loteCodigo,
                        'fecha_vencimiento' => $product->fecha_vencimiento,
                        'fecha_ingreso' => now(),
                        'proveedor_id' => $request->proveedor_id ?? null,
                        'precio_compra_lote' => $request->precio_compra ?? $product->precio_compra,
                        'precio_venta_lote' => $request->precio_venta ?? $product->precio_venta,
                        'estado_lote' => 'activo',
                    ]);

                    // Registrar movimiento inicial
                    try {
                        \App\Models\MovimientoStock::create([
                            'producto_id' => $product->id,
                            'tipo_movimiento' => 'entrada',
                            'cantidad' => $product->stock_actual,
                            'cantidad_anterior' => 0,
                            'cantidad_nueva' => $product->stock_actual,
                            'motivo' => 'Inventario Inicial (App Móvil) - Lote: ' . $loteCodigo,
                            'usuario_id' => auth()->id() ?? 1,
                        ]);
                    } catch (\Exception $e) {
                        // Ignorar error de historial
                    }
                }
            }
            // ---------------------------------------------
            
            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'data' => $product
            ], 201);
            
        } catch (\Exception $e) {
            \Log::error('Mobile: Error al crear producto', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->except(['imagen'])
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear producto: ' . $e->getMessage(),
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
                'fecha_vencimiento' => 'nullable|date',
                'observaciones' => 'nullable|string|max:255',
                'presentaciones' => 'nullable|array',
                'presentaciones_unidades' => 'nullable|array',
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
            
            // --- CREACIÓN DE LOTE (ProductoUbicacion) ---
            // Obtener o crear ubicación por defecto
            $ubicacionDefault = \App\Models\Ubicacion::first();
            if (!$ubicacionDefault) {
                // Crear estante por defecto si no existe (esto crea ubicaciones automáticamente)
                $estanteDefault = \App\Models\Estante::firstOrCreate(
                    ['nombre' => 'Estante Principal'],
                    ['descripcion' => 'Estante creado automáticamente', 'numero_niveles' => 1, 'numero_posiciones' => 1, 'activo' => true]
                );
                // Obtener la ubicación creada automáticamente por el estante
                $ubicacionDefault = \App\Models\Ubicacion::where('estante_id', $estanteDefault->id)->first();
            }
            $ubicacionId = $ubicacionDefault ? $ubicacionDefault->id : null;
            
            $lote = null;
            // Si hay lote y ubicación, registrar en ProductoUbicacion
            if ($ubicacionId) {
                // Usar lote proporcionado o intentar obtener del producto
                $loteCodigo = $validated['lote'] ?? $product->lote ?? 'LOTE-GRAL';
                $fechaVenc = $validated['fecha_vencimiento'] ?? $product->fecha_vencimiento ?? null;
                
                if ($loteCodigo) {
                    $loteQuery = \App\Models\ProductoUbicacion::where('producto_id', $product->id)
                        ->where('lote', $loteCodigo)
                        ->where('ubicacion_id', $ubicacionId);
                        
                    if ($fechaVenc) {
                        $loteQuery->where('fecha_vencimiento', $fechaVenc);
                    }
                    
                    $lote = $loteQuery->first();

                    if ($lote) {
                        $lote->cantidad += (int) $validated['quantity'];
                        if (isset($validated['precio_compra'])) $lote->precio_compra_lote = $validated['precio_compra'];
                        if (isset($validated['precio_venta'])) $lote->precio_venta_lote = $validated['precio_venta'];
                        $lote->save();
                    } else {
                        $lote = \App\Models\ProductoUbicacion::create([
                            'producto_id' => $product->id,
                            'ubicacion_id' => $ubicacionId,
                            'cantidad' => (int) $validated['quantity'],
                            'cantidad_inicial' => (int) $validated['quantity'],
                            'lote' => $loteCodigo,
                            'fecha_vencimiento' => $fechaVenc,
                            'fecha_ingreso' => now(),
                            'proveedor_id' => $validated['proveedor_id'] ?? null,
                            'precio_compra_lote' => $validated['precio_compra'] ?? $product->precio_compra,
                            'precio_venta_lote' => $validated['precio_venta'] ?? $product->precio_venta,
                            'estado_lote' => 'activo',
                        ]);
                    }
                    
                    // Recalcular stock total real basado en lotes
                    $product->stock_actual = \App\Models\ProductoUbicacion::where('producto_id', $product->id)->sum('cantidad');
                }
            }
            // ---------------------------------------------
            
            $product->save();

            // --- MANEJO DE PRESENTACIONES ---
            if ($request->has('presentaciones') && $lote) {
                $unidadesModificadas = $request->input('presentaciones_unidades', []);
                $hasLotePresTable = \Illuminate\Support\Facades\Schema::hasTable('lote_presentaciones');
                
                // Asegurarnos de usar el precio de venta principal para la unidad
                $mainPrecioVenta = $validated['precio_venta'] ?? $product->precio_venta;
                
                foreach ($request->presentaciones as $presentacion_id => $precio_venta) {
                    // Obtener unidades para esta presentación
                    $unidades = isset($unidadesModificadas[$presentacion_id]) ? (int)$unidadesModificadas[$presentacion_id] : 0;
                    
                    // REGLA DE ORO: Si es la unidad (1 unidad o ID temporal 0), 
                    // forzamos el precio de venta principal para evitar inconsistencias
                    if ($unidades == 1 || $presentacion_id == "0") {
                        $precio_venta = $mainPrecioVenta;
                    }

                    if ($precio_venta > 0) {
                        // Si es la presentación base (unidades == 1) o ID es 0, sincronizar con el producto principal
                        if ($presentacion_id == "0" || $unidades == 1) {
                            $product->update([
                                'precio_venta' => $precio_venta,
                                'precio_compra' => $validated['precio_compra'] ?? $product->precio_compra
                            ]);
                            
                            $lote->update([
                                'precio_venta_lote' => $precio_venta,
                                'precio_compra_lote' => $validated['precio_compra'] ?? $lote->precio_compra_lote
                            ]);
                            
                            // Si es ID "0", no hay registro en producto_presentaciones que actualizar
                            if ($presentacion_id == "0") continue;
                        }

                        // Actualizar la tabla global de presentaciones del producto 
                        \DB::table('producto_presentaciones')
                            ->where('id', $presentacion_id)
                            ->update([
                                'precio_venta_presentacion' => $precio_venta,
                                'unidades_por_presentacion' => $unidades > 0 ? $unidades : 1,
                                'updated_at' => now()
                            ]);

                        // Guardar o actualizar el registro específico para este lote
                        if ($hasLotePresTable) {
                            \DB::table('lote_presentaciones')->updateOrInsert(
                                [
                                    'producto_ubicacion_id' => $lote->id,
                                    'producto_presentacion_id' => $presentacion_id,
                                ],
                                [
                                    'precio_venta' => $precio_venta,
                                    'unidades_por_presentacion' => $unidades > 0 ? $unidades : 1,
                                    'updated_at' => now(),
                                    'created_at' => now(),
                                ]
                            );
                        }
                    }
                }
            }
            // ---------------------------------------------
            
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
                'message' => 'Error al agregar stock: ' . $e->getMessage(),
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
            // Instantiate the service directly to ensure logic consistency with Web Dashboard
            $optimizationService = new \App\Services\QueryOptimizationService();
            
            // Helper to add calculated fields for mobile app compatibility
            $formatProduct = function ($product, $alertType, $priority) {
                // Determine effective expiration date based on batches (FEFO)
                $minBatchDate = null;
                if ($product->relationLoaded('ubicaciones') && $product->ubicaciones->count() > 0) {
                     $activeBatches = $product->ubicaciones->where('cantidad', '>', 0)->whereNotNull('fecha_vencimiento');
                     if ($activeBatches->count() > 0) {
                         $minBatchDate = $activeBatches->sortBy('fecha_vencimiento')->first()->fecha_vencimiento;
                     }
                }
                
                $effectiveDate = $product->fecha_vencimiento;
                if ($minBatchDate) {
                    $batchDate = Carbon::parse($minBatchDate);
                    $masterDate = $product->fecha_vencimiento ? Carbon::parse($product->fecha_vencimiento) : null;
                    
                    if (!$masterDate || $batchDate->lt($masterDate)) {
                        $effectiveDate = $minBatchDate;
                    }
                }
                
                // Override the product's date for the JSON response
                if ($effectiveDate) {
                    $product->fecha_vencimiento = Carbon::parse($effectiveDate)->format('Y-m-d');
                }

                // Calculate days until expiry
                $diasParaVencer = null;
                if ($effectiveDate) {
                    $diasParaVencer = Carbon::now()->diffInDays(Carbon::parse($effectiveDate), false);
                }
                
                // Add fields required by mobile app
                $product->id = $product->id;
                $product->name = $product->nombre;
                $product->brand = $product->marca ?? 'Sin marca';
                $product->category = $product->categoria ?? 'Sin categoría';
                $product->stock = $product->stock_actual;
                $product->minStock = $product->stock_minimo;
                $product->price = (float) $product->precio_venta;
                $product->expiryDate = $product->fecha_vencimiento;
                $product->diasParaVencer = $diasParaVencer;
                
                $product->alert_type = $alertType;
                $product->alert_message = match($alertType) {
                    'low_stock' => 'Stock bajo',
                    'expiring' => 'Próximo a vencer',
                    'expired' => 'Vencido',
                    'out_of_stock' => 'Agotado',
                    default => 'Alerta'
                };
                $product->priority = $priority;
                
                // Status flags
                $product->isLowStock = $alertType === 'low_stock';
                $product->isExpiringSoon = $alertType === 'expiring';
                $product->isExpired = $alertType === 'expired';
                $product->isOutOfStock = $alertType === 'out_of_stock';
                
                // Legacy fields compatibility
                $product->is_low_stock = $product->isLowStock;
                $product->is_expiring_soon = $product->isExpiringSoon;
                $product->is_expired = $product->isExpired;
                $product->is_out_of_stock = $product->isOutOfStock;
                
                // Additional fields
                $product->barcode = $product->codigo_barras;
                $product->image = $product->imagen_url;
                $product->ubicacion = $product->ubicacion_almacen;
                $product->presentacion = $product->presentacion;
                $product->concentracion = $product->concentracion;
                $product->lote = $product->lote;
                
                // Count active batches and their statuses for alerts
                $product->batches_count = 0;
                $product->batches_expiring_count = 0;
                $product->batches_expired_count = 0;

                if ($product->relationLoaded('ubicaciones')) {
                    $activeBatches = $product->ubicaciones->where('cantidad', '>', 0);
                    $product->batches_count = $activeBatches->count();

                    foreach ($activeBatches as $batch) {
                        if ($batch->fecha_vencimiento) {
                            $days = Carbon::now()->diffInDays(Carbon::parse($batch->fecha_vencimiento), false);
                            if ($days < 0) {
                                $product->batches_expired_count++;
                            } elseif ($days <= 90) {
                                $product->batches_expiring_count++;
                            }
                        }
                    }
                }

                // Convert to array to ensure dynamic properties are included
                $productArray = $product->toArray();
                $productArray['batches_count'] = $product->batches_count;
                $productArray['batches_expiring_count'] = $product->batches_expiring_count;
                $productArray['batches_expired_count'] = $product->batches_expired_count;
                
                // Ensure other dynamic fields are present
                $productArray['alert_type'] = $alertType;
                $productArray['alert_message'] = $product->alert_message;
                $productArray['priority'] = $priority;
                $productArray['diasParaVencer'] = $diasParaVencer;
                $productArray['expiryDate'] = $effectiveDate; // Send effective date
                $productArray['fecha_vencimiento'] = $effectiveDate; // Standard field
                
                // Add status flags
                $productArray['is_low_stock'] = $product->isLowStock;
                $productArray['is_expiring_soon'] = $product->isExpiringSoon;
                $productArray['is_expired'] = $product->isExpired;
                $productArray['is_out_of_stock'] = $product->isOutOfStock;
                
                return $productArray;
            };

            $criticalProducts = collect();

            // 1. Stock Bajo
            if (!$request->has('type') || $request->type === 'low_stock') {
                $lowStock = $optimizationService->getProductosOptimizados(['estado' => 'bajo_stock'])
                    ->with('ubicaciones')
                    ->get()
                    ->map(fn($p) => $formatProduct($p, 'low_stock', 'medium'));
                $criticalProducts = $criticalProducts->concat($lowStock);
            }

            // 2. Por Vencer (expiring)
            if (!$request->has('type') || $request->type === 'expiring') {
                $expiring = $optimizationService->getProductosOptimizados(['estado' => 'por_vencer'])
                    ->with('ubicaciones')
                    ->get()
                    ->map(fn($p) => $formatProduct($p, 'expiring', 'high'));
                $criticalProducts = $criticalProducts->concat($expiring);
            }

            // 3. Vencidos (expired)
            if (!$request->has('type') || $request->type === 'expired') {
                $expired = $optimizationService->getProductosOptimizados(['estado' => 'vencido'])
                    ->with('ubicaciones')
                    ->get()
                    ->map(fn($p) => $formatProduct($p, 'expired', 'critical'));
                $criticalProducts = $criticalProducts->concat($expired);
            }

            // 4. Agotados (out_of_stock)
            if (!$request->has('type') || $request->type === 'out_of_stock') {
                $outOfStock = $optimizationService->getProductosOptimizados(['estado' => 'agotado'])
                    ->with('ubicaciones')
                    ->get()
                    ->map(fn($p) => $formatProduct($p, 'out_of_stock', 'high'));
                $criticalProducts = $criticalProducts->concat($outOfStock);
            }

            // Remove duplicates ensuring highest priority status is kept
            // Order of severity: Vencido (expired) > Agotado (out_of_stock) > Por Vencer (expiring) > Stock Bajo (low_stock)
            // Since we concatenated in specific order, we can use unique('id') if we order them by severity first
            
            $criticalProducts = $criticalProducts->sortByDesc(function ($product) {
                return match($product->alert_type) {
                    'expired' => 4,
                    'out_of_stock' => 3,
                    'expiring' => 2,
                    'low_stock' => 1,
                    default => 0
                };
            })->unique('id')->values();
            
            return response()->json([
                'success' => true,
                'message' => 'Productos críticos obtenidos exitosamente',
                'data' => $criticalProducts,
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
     * Obtener lotes de un producto específico
     */
    public function getLotes($id)
    {
        try {
            $producto = Producto::findOrFail($id);
            
            // Obtener todos los lotes del producto desde producto_ubicaciones
            $lotes = \App\Models\ProductoUbicacion::where('producto_id', $id)
                ->where('cantidad', '>', 0)
                ->with(['ubicacion', 'proveedor'])
                ->get()
                ->map(function($ubicacion) {
                    $diasParaVencer = null;
                    if ($ubicacion->fecha_vencimiento) {
                        $diasParaVencer = Carbon::now()->diffInDays(Carbon::parse($ubicacion->fecha_vencimiento), false);
                    }
                    
                    return [
                        'id' => $ubicacion->id,
                        'numero_lote' => $ubicacion->lote ?? 'Sin lote',
                        'fecha_vencimiento' => $ubicacion->fecha_vencimiento ? $ubicacion->fecha_vencimiento->format('Y-m-d') : null,
                        'cantidad' => $ubicacion->cantidad,
                        'precio_compra' => $ubicacion->precio_compra_lote,
                        'precio_venta' => $ubicacion->precio_venta_lote,
                        'proveedor' => $ubicacion->proveedor ? $ubicacion->proveedor->nombre : null,
                        'ubicacion' => $ubicacion->ubicacion ? $ubicacion->ubicacion->nombre : null,
                        'dias_para_vencer' => $diasParaVencer,
                        'estado' => $this->determinarEstadoLote($ubicacion, $diasParaVencer),
                    ];
                });

            return response()->json([
                'success' => true,
                'lotes' => $lotes,
                'total_lotes' => $lotes->count(),
                'producto' => [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                        'concentracion' => $producto->concentracion,
                    'presentacion' => $producto->presentacion,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener lotes del producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar stock de un producto (crear entrada con lote)
     */
    public function actualizarStock(Request $request, $id)
    {
        try {
            $producto = Producto::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'cantidad' => 'required|integer|min:1',
                'lote' => 'required|string',
                'fecha_vencimiento' => 'required|date',
                'proveedor_id' => 'required|integer|exists:proveedores,id',
                'precio_compra' => 'nullable|numeric|min:0',
                'precio_venta' => 'nullable|numeric|min:0',
                'ubicacion_id' => 'nullable|integer|exists:ubicaciones,id',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Obtener ubicación por defecto si no se especifica
            $ubicacionId = $request->ubicacion_id;
            if (!$ubicacionId) {
                $ubicacionDefault = \App\Models\Ubicacion::first();
                
                // Si no hay ubicación por defecto, retornar error
                if (!$ubicacionDefault) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No hay ubicaciones registradas en el sistema. Por favor registre una ubicación en el sistema web antes de continuar.'
                    ], 404);
                }
                
                $ubicacionId = $ubicacionDefault->id;
            }

            // Verificar si ya existe un lote con el mismo código y fecha de vencimiento
            $loteExistente = \App\Models\ProductoUbicacion::where('producto_id', $id)
                ->where('lote', $request->lote)
                ->where('fecha_vencimiento', $request->fecha_vencimiento)
                ->where('ubicacion_id', $ubicacionId)
                ->first();

            if ($loteExistente) {
                // Actualizar lote existente
                $loteExistente->cantidad += $request->cantidad;
                if ($request->precio_compra) {
                    $loteExistente->precio_compra_lote = $request->precio_compra;
                }
                if ($request->precio_venta) {
                    $loteExistente->precio_venta_lote = $request->precio_venta;
                }
                $loteExistente->save();
            } else {
                // Crear nuevo lote
                \App\Models\ProductoUbicacion::create([
                    'producto_id' => $id,
                    'ubicacion_id' => $ubicacionId,
                    'cantidad' => $request->cantidad,
                    'cantidad_inicial' => $request->cantidad,
                    'lote' => $request->lote,
                    'fecha_vencimiento' => $request->fecha_vencimiento,
                    'fecha_ingreso' => now(),
                    'proveedor_id' => $request->proveedor_id,
                    'precio_compra_lote' => $request->precio_compra,
                    'precio_venta_lote' => $request->precio_venta,
                    'estado_lote' => 'activo',
                ]);
            }

            // Actualizar stock total del producto
            $stockTotal = \App\Models\ProductoUbicacion::where('producto_id', $id)->sum('cantidad');
            $producto->stock_actual = $stockTotal;
            
            // Actualizar precios si se proporcionaron
            if ($request->precio_compra) {
                $producto->precio_compra = $request->precio_compra;
            }
            if ($request->precio_venta) {
                $producto->precio_venta = $request->precio_venta;
            }
            
            $producto->save();

            // Registrar movimiento de stock
            try {
                \App\Models\MovimientoStock::create([
                    'producto_id' => $id,
                    'tipo_movimiento' => 'entrada',
                    'cantidad' => $request->cantidad,
                    'cantidad_anterior' => $stockTotal - $request->cantidad,
                    'cantidad_nueva' => $stockTotal,
                    'motivo' => 'Entrada desde app móvil - Lote: ' . $request->lote,
                    'usuario_id' => auth()->id() ?? 1,
                ]);
            } catch (\Exception $e) {
                // Si falla el registro del movimiento, no bloqueamos la respuesta exitosa
                // pero podríamos loguearlo internamente
                \Log::error('Error al registrar movimiento de stock: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Stock actualizado exitosamente',
                'data' => [
                    'producto' => $producto->fresh(),
                    'stock_total' => $stockTotal,
                    'cantidad_agregada' => $request->cantidad,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar stock',
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

    /**
     * Dar de baja un lote (Write-off)
     */
    public function darBajaLote(Request $request, $id)
    {
        try {
            $lote = \App\Models\ProductoUbicacion::find($id);
            
            if (!$lote) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lote no encontrado'
                ], 404);
            }

            $cantidadAnterior = $lote->cantidad;
            $motivo = $request->input('reason', 'Baja solicitada desde móvil');

            // 1. Actualizar el lote: stock a 0 y estado 'baja'
            $lote->cantidad = 0;
            $lote->estado_lote = 'baja';
            // Concatenar motivo a observaciones existentes si las hay
            $obs = $lote->observaciones ? $lote->observaciones . " | " : "";
            $lote->observaciones = $obs . "Baja: " . $motivo;
            $lote->save();

            // 2. Actualizar stock del producto principal
            $producto = Producto::find($lote->producto_id);
            if ($producto) {
                // Recalcular stock total sumando solo lotes activos (o todos si cantidad es la fuente de verdad)
                // Usamos sum('cantidad') porque el lote dado de baja ya tiene 0
                $stockTotal = \App\Models\ProductoUbicacion::where('producto_id', $producto->id)->sum('cantidad');
                
                $stockAnteriorProducto = $producto->stock_actual;
                $producto->stock_actual = $stockTotal;
                $producto->save();

                // 3. Registrar movimiento de stock (Salida)
                try {
                    \App\Models\MovimientoStock::create([
                        'producto_id' => $producto->id,
                        'tipo_movimiento' => 'salida', // Salida por baja
                        'cantidad' => $cantidadAnterior, // Cantidad que se redujo (lo que había en el lote)
                        'cantidad_anterior' => $stockAnteriorProducto,
                        'cantidad_nueva' => $stockTotal,
                        'motivo' => "Baja de Lote {$lote->lote} (Móvil): {$motivo}",
                        'usuario_id' => auth()->id() ?? 1,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error registrando movimiento de baja: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Lote dado de baja exitosamente',
                'data' => $lote
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al dar de baja el lote',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
