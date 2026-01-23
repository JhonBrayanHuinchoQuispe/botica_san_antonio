<?php

namespace App\Http\Controllers\Compra;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Categoria;
// use App\Models\Presentacion; // REMOVIDO: La tabla presentaciones fue eliminada
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Services\LoteService;
use App\Services\RucService;
use Carbon\Carbon;

class CompraController extends Controller
{
    protected $loteService;

    /**
     * Constructor - Aplicar middleware de autenticación e inyectar dependencias
     */
    public function __construct(LoteService $loteService)
    {
        $this->loteService = $loteService;
        $this->middleware('auth')->except(['buscarProveedoresApi']);
    }

    /**
     * Obtener ID del usuario autenticado de forma segura
     */
    private function obtenerUsuarioId()
    {
        return auth()->check() ? auth()->id() : 1; 
    }

    /**
     * Consultar RUC: primero en BD, si no existe consultar fuente externa
     */
    public function consultarRuc(Request $request, $ruc)
    {
        try {
            $ruc = trim($ruc);
            if (strlen($ruc) !== 11 || !ctype_digit($ruc)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El RUC debe tener 11 dígitos numéricos'
                ], 422);
            }

            // Buscar primero en base de datos
            $proveedor = Proveedor::where('ruc', $ruc)->first();
            if ($proveedor) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'ruc' => $proveedor->ruc,
                        'razon_social' => $proveedor->razon_social ?? '',
                        'nombre_comercial' => $proveedor->nombre_comercial ?? '',
                        'direccion' => $proveedor->direccion ?? '',
                        'estado' => $proveedor->estado ?? '',
                        'condicion' => $proveedor->condicion ?? '',
                        'fuente' => 'bd'
                    ]
                ]);
            }

            // Si no existe en BD, consultar servicio externo
            $servicio = new RucService();
            $datos = $servicio->consultarRuc($ruc);

            return response()->json([
                'success' => true,
                'data' => [
                    'ruc' => $datos['ruc'] ?? $ruc,
                    'razon_social' => $datos['razon_social'] ?? '',
                    'nombre_comercial' => $datos['nombre_comercial'] ?? '',
                    'direccion' => $datos['direccion'] ?? '',
                    'estado' => $datos['estado'] ?? '',
                    'condicion' => $datos['condicion'] ?? '',
                    'fuente' => 'externo'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en consultarRuc: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar vista de nueva entrada de mercadería
     */
    public function nueva()
    {
        try {
            $productos = Producto::orderBy('nombre')->get();
            $categorias = Categoria::orderBy('nombre')->get();
            // $presentaciones = Presentacion::orderBy('nombre')->get(); // REMOVIDO: Ya no existe tabla presentaciones
            $proveedores = Proveedor::activos()->orderBy('razon_social')->get();
            
            return view('compras.nueva', compact('productos', 'categorias', 'proveedores'));
        } catch (\Exception $e) {
            Log::error('Error al cargar página de nueva entrada de mercadería: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->view('errors.500', [
                'message' => 'Error interno del servidor. Por favor, contacte al administrador.',
                'error_details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Mostrar historial de entradas
     */
    public function historial(Request $request)
    {
        $query = \App\Models\EntradaMercaderia::with(['producto', 'usuario', 'proveedor']);

        // Aplicar filtros
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('producto', function($qp) use ($search) {
                    $qp->where('nombre', 'like', "%{$search}%")
                       ->orWhere('codigo_barras', 'like', "%{$search}%");
                })->orWhere('lote', 'like', "%{$search}%");
            });
        }

        if ($request->has('fecha_desde') && !empty($request->fecha_desde)) {
            $query->whereDate('fecha_entrada', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta') && !empty($request->fecha_hasta)) {
            $query->whereDate('fecha_entrada', '<=', $request->fecha_hasta);
        }

        if ($request->has('usuario_id') && !empty($request->usuario_id)) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->has('proveedor_id') && !empty($request->proveedor_id)) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        $entradas = $query->orderBy('fecha_entrada', 'desc')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return view('compras.partials.tabla_historial', compact('entradas'))->render();
        }
            
        $estadisticas = \App\Models\EntradaMercaderia::obtenerEstadisticas();
        $proveedores = Proveedor::orderBy('razon_social')->get();
        $usuarios = \App\Models\User::orderBy('name')->get();
        
        return view('compras.historial', compact('entradas', 'estadisticas', 'proveedores', 'usuarios'));
    }

    /**
     * Gestión de proveedores
     */
    public function proveedores()
    {
        $proveedores = Proveedor::orderBy('razon_social')->get();
        return view('compras.proveedores', compact('proveedores'));
    }

    /**
     * Guardar nuevo proveedor
     */
    public function guardarProveedor(Request $request)
    {
        try {
            $request->validate([
                'razon_social' => 'required|string|max:255',
                'nombre_comercial' => 'nullable|string|max:255',
                'ruc' => 'nullable|string|size:11|unique:proveedores,ruc',
                'telefono' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:100',
                'direccion' => 'nullable|string',
                'ciudad' => 'nullable|string|max:100',
                'departamento' => 'nullable|string|max:100',
                'contacto_principal' => 'nullable|string|max:100',
                'telefono_contacto' => 'nullable|string|max:20',
                'email_contacto' => 'nullable|email|max:100',
                'observaciones' => 'nullable|string',
                'limite_credito' => 'nullable|numeric|min:0',
                'dias_credito' => 'nullable|integer|min:0',
                'categoria_proveedor' => 'nullable|string|max:50'
            ], [
                'razon_social.required' => 'La razón social del proveedor es obligatoria',
                'ruc.size' => 'El RUC debe tener exactamente 11 dígitos',
                'ruc.unique' => 'Ya existe un proveedor con este RUC',
                'email.email' => 'El formato del email no es válido',
                'email_contacto.email' => 'El formato del email de contacto no es válido'
            ]);

            // Generar código de proveedor automático
            $ultimoProveedor = Proveedor::orderBy('id', 'desc')->first();
            $numeroConsecutivo = $ultimoProveedor ? $ultimoProveedor->id + 1 : 1;
            $codigoProveedor = 'PROV-' . str_pad($numeroConsecutivo, 4, '0', STR_PAD_LEFT);

            $data = $request->all();
            $data['codigo_proveedor'] = $codigoProveedor;
            $data['estado'] = 'activo';

            $proveedor = Proveedor::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Proveedor creado exitosamente',
                'data' => $proveedor
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al crear proveedor: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Actualizar proveedor
     */
    public function actualizarProveedor(Request $request, $id)
    {
        try {
            $proveedor = Proveedor::findOrFail($id);

            $request->validate([
                'razon_social' => 'required|string|max:255',
                'nombre_comercial' => 'nullable|string|max:255',
                'ruc' => 'nullable|string|size:11|unique:proveedores,ruc,' . $id,
                'telefono' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:100',
                'direccion' => 'nullable|string',
                'ciudad' => 'nullable|string|max:100',
                'departamento' => 'nullable|string|max:100',
                'contacto_principal' => 'nullable|string|max:100',
                'telefono_contacto' => 'nullable|string|max:20',
                'email_contacto' => 'nullable|email|max:100',
                'observaciones' => 'nullable|string',
                'limite_credito' => 'nullable|numeric|min:0',
                'dias_credito' => 'nullable|integer|min:0',
                'categoria_proveedor' => 'nullable|string|max:50'
            ], [
                'razon_social.required' => 'La razón social del proveedor es obligatoria',
                'ruc.size' => 'El RUC debe tener exactamente 11 dígitos',
                'ruc.unique' => 'Ya existe un proveedor con este RUC',
                'email.email' => 'El formato del email no es válido',
                'email_contacto.email' => 'El formato del email de contacto no es válido'
            ]);

            $proveedor->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente',
                'data' => $proveedor
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al actualizar proveedor: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Cambiar estado del proveedor
     */
    public function cambiarEstadoProveedor($id)
    {
        try {
            $proveedor = Proveedor::findOrFail($id);
            $proveedor->estado = $proveedor->estado === 'activo' ? 'inactivo' : 'activo';
            $proveedor->save();

            $estado = $proveedor->estado === 'activo' ? 'activado' : 'desactivado';

            return response()->json([
                'success' => true,
                'message' => "Proveedor {$estado} exitosamente",
                'data' => $proveedor
            ]);

        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del proveedor: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Eliminar proveedor
     */
    public function eliminarProveedor($id)
    {
        try {
            $proveedor = Proveedor::findOrFail($id);
            
            // Verificar referencias en tablas relacionadas antes de eliminar
            $tieneEntradas = Schema::hasTable('entradas_mercaderia')
                && Schema::hasColumn('entradas_mercaderia', 'proveedor_id')
                && DB::table('entradas_mercaderia')->where('proveedor_id', $id)->exists();

            $tieneCompras = Schema::hasTable('compras')
                && Schema::hasColumn('compras', 'proveedor_id')
                && DB::table('compras')->where('proveedor_id', $id)->exists();

            $tieneProductos = Schema::hasTable('productos')
                && Schema::hasColumn('productos', 'proveedor_id')
                && DB::table('productos')->where('proveedor_id', $id)->exists();

            $tieneUbicaciones = Schema::hasTable('producto_ubicaciones')
                && Schema::hasColumn('producto_ubicaciones', 'proveedor_id')
                && DB::table('producto_ubicaciones')->where('proveedor_id', $id)->exists();

            if ($tieneEntradas || $tieneCompras || $tieneProductos || $tieneUbicaciones) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el proveedor porque tiene registros asociados (entradas, compras, productos o ubicaciones). Puede desactivarlo en su lugar.'
                ], 400);
            }
            
            $nombreProveedor = $proveedor->razon_social;
            $proveedor->delete();

            return response()->json([
                'success' => true,
                'message' => "Proveedor '{$nombreProveedor}' eliminado exitosamente"
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar proveedor: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Buscar proveedores
     */
    public function buscarProveedores(Request $request)
    {
        $termino = $request->get('q', '');
        
        $proveedores = Proveedor::buscar($termino)
            ->activos()
            ->limit(20)
            ->get(['id', 'razon_social as nombre', 'nombre_comercial', 'ruc', 'telefono', 'contacto_principal']);

        return response()->json([
            'success' => true,
            'data' => $proveedores
        ]);
    }

    public function buscarProveedoresApi(Request $request)
    {
        try {
            $termino = $request->get('q', '');
            $proveedores = Proveedor::buscar($termino)
                ->activos()
                ->limit(20)
                ->get(['id', 'razon_social as nombre', 'nombre_comercial', 'ruc', 'telefono', 'contacto_principal']);
            if ($proveedores->isEmpty()) {
                $proveedores = Proveedor::buscar($termino)
                    ->limit(20)
                    ->get(['id', 'razon_social as nombre', 'nombre_comercial', 'ruc', 'telefono', 'contacto_principal']);
            }
            return response()->json([
                'success' => true,
                'data' => $proveedores
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => true,
                'data' => [
                    [
                        'id' => 1,
                        'nombre' => 'Proveedor Demo A',
                        'nombre_comercial' => 'Demo A',
                        'ruc' => '00000000001',
                        'telefono' => '000-000',
                        'contacto_principal' => 'Contacto A',
                    ],
                    [
                        'id' => 2,
                        'nombre' => 'Proveedor Demo B',
                        'nombre_comercial' => 'Demo B',
                        'ruc' => '00000000002',
                        'telefono' => '000-001',
                        'contacto_principal' => 'Contacto B',
                    ],
                ],
                'fallback' => true,
                'message' => 'Modo demo por conexión a BD'
            ]);
        }
    }

    /**
     * Procesar nueva entrada de mercadería
     */
    public function procesarEntrada(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validaciones robustas del lado del servidor
            $validatedData = $request->validate([
                'producto_id' => 'required|integer|exists:productos,id',
                'proveedor_id' => 'required|integer|exists:proveedores,id', // Ahora es obligatorio
                'cantidad' => 'required|integer|min:1|max:999999',
                'precio_compra' => 'nullable|numeric|min:0|max:999999.99',
                'precio_venta' => 'nullable|numeric|min:0|max:999999.99',
                'lote' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9\- _\s]+$/',
                'existing_lote_id' => 'nullable|integer|exists:producto_ubicaciones,id',
                'fecha_vencimiento' => 'nullable|date|after_or_equal:today',
                'ubicacion_id' => 'nullable|integer|exists:ubicaciones,id',
                'observaciones' => 'nullable|string|max:500'
            ], [
                // Mensajes personalizados de validación
                'producto_id.required' => 'El producto es obligatorio.',
                'producto_id.exists' => 'El producto seleccionado no existe.',
                'proveedor_id.required' => 'El proveedor es obligatorio.',
                'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
                'cantidad.required' => 'La cantidad es obligatoria.',
                'cantidad.min' => 'La cantidad debe ser mayor a 0.',
                'cantidad.max' => 'La cantidad no puede exceder 999,999 unidades.',
                'precio_compra.min' => 'El precio de compra no puede ser negativo.',
                'precio_compra.max' => 'El precio de compra no puede exceder S/. 999,999.99.',
                'precio_venta.min' => 'El precio de venta no puede ser negativo.',
                'precio_venta.max' => 'El precio de venta no puede exceder S/. 999,999.99.',
                'lote.regex' => 'El lote solo puede contener letras, números, guiones y espacios.',
                'fecha_vencimiento.after_or_equal' => 'La fecha de vencimiento no puede ser anterior a hoy.',
                'ubicacion_id.exists' => 'La ubicación seleccionada no existe.'
            ]);

            // Sanitización de datos
            if (isset($validatedData['lote'])) {
                $validatedData['lote'] = trim(strip_tags($validatedData['lote']));
            }
            if (isset($validatedData['observaciones'])) {
                $validatedData['observaciones'] = trim(strip_tags($validatedData['observaciones']));
            }

            // Validaciones adicionales de negocio
            $producto = Producto::findOrFail($validatedData['producto_id']);
            
            // Verificar que el proveedor esté activo
            $proveedor = \App\Models\Proveedor::findOrFail($validatedData['proveedor_id']);
            if ($proveedor->estado !== 'activo') {
                return response()->json([
                    'success' => false,
                    'message' => 'El proveedor seleccionado no está activo.'
                ], 422);
            }

            // Validación de coherencia de precios
            if (isset($validatedData['precio_compra']) && isset($validatedData['precio_venta'])) {
                if ($validatedData['precio_venta'] <= $validatedData['precio_compra']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El precio de venta debe ser mayor al precio de compra.'
                    ], 422);
                }
            }

            // Validación de fecha de vencimiento más estricta
            if (isset($validatedData['fecha_vencimiento'])) {
                $fechaVencimiento = Carbon::parse($validatedData['fecha_vencimiento']);
                $hoy = Carbon::today();
                
                if ($fechaVencimiento->lt($hoy)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La fecha de vencimiento no puede ser anterior a la fecha actual.'
                    ], 422);
                }
                
                // Advertencia si el producto vence en menos de 30 días
                if ($fechaVencimiento->diffInDays($hoy) < 30) {
                    $loteLog = $validatedData['lote'] ?? 'Sin lote';
                    Log::warning("Producto con vencimiento próximo registrado: {$producto->nombre}, Lote: {$loteLog}, Vence: {$fechaVencimiento->format('Y-m-d')}");
                }
            }

            // Validación de cantidad máxima por transacción
            if ($validatedData['cantidad'] > 10000) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cantidad máxima por entrada es de 10,000 unidades. Para cantidades mayores, contacte al administrador.'
                ], 422);
            }

            // Guardar valores anteriores
            $precioCompraAnterior = $producto->precio_compra;
            $precioVentaAnterior = $producto->precio_venta;
            $stockAnterior = $producto->stock_actual;

            // Log para debugging
            Log::info('Entrada de mercadería - Valores iniciales', [
                'producto_id' => $producto->id,
                'producto_nombre' => $producto->nombre,
                'stock_anterior' => $stockAnterior,
                'cantidad_a_agregar' => $validatedData['cantidad']
            ]);

            // Validación de stock negativo (aunque en entrada siempre suma, es buena práctica)
            if ($stockAnterior < 0) {
                Log::warning("Producto con stock negativo detectado: {$producto->nombre}, Stock actual: {$stockAnterior}");
            }

            // Preparar actualizaciones del producto usando datos validados
            $updates = [];
            $precioCompraNuevo = $precioCompraAnterior;
            $precioVentaNuevo = $precioVentaAnterior;

            if (isset($validatedData['precio_compra']) && $validatedData['precio_compra'] > 0) {
                $updates['precio_compra'] = $validatedData['precio_compra'];
                $precioCompraNuevo = $validatedData['precio_compra'];
            }
            if (isset($validatedData['precio_venta']) && $validatedData['precio_venta'] > 0) {
                $updates['precio_venta'] = $validatedData['precio_venta'];
                $precioVentaNuevo = $validatedData['precio_venta'];
            }

            // Incrementar stock - SUMA, no reemplaza
            $stockNuevo = $stockAnterior + $validatedData['cantidad'];
            $updates['stock_actual'] = $stockNuevo;
            
            // Log para verificar cálculo
            Log::info('Entrada de mercadería - Cálculo de stock', [
                'stock_anterior' => $stockAnterior,
                'cantidad_agregada' => $validatedData['cantidad'],
                'stock_nuevo_calculado' => $stockNuevo
            ]);
            
            // Actualizar lote y fecha de vencimiento solo si se envían
            if (isset($validatedData['lote']) && $validatedData['lote'] !== '') {
                $updates['lote'] = $validatedData['lote'];
            }
            if (isset($validatedData['fecha_vencimiento'])) {
                $updates['fecha_vencimiento'] = $validatedData['fecha_vencimiento'];
            }

            // Actualizar producto
            $producto->update($updates);
            
            // Verificar que el stock se actualizó correctamente
            $producto->refresh();
            Log::info('Entrada de mercadería - Después de actualizar', [
                'stock_en_bd' => $producto->stock_actual,
                'stock_esperado' => $stockNuevo
            ]);
            
            // Recalcular el estado del producto después de actualizar el stock
            $producto->recalcularEstado();

            // Registrar la entrada en el historial usando datos validados
            \App\Models\EntradaMercaderia::create([
                'producto_id' => $producto->id,
                'usuario_id' => $this->obtenerUsuarioId(),
                'proveedor_id' => $validatedData['proveedor_id'],
                'cantidad' => $validatedData['cantidad'],
                'precio_compra_anterior' => $precioCompraAnterior,
                'precio_compra_nuevo' => $precioCompraNuevo,
                'precio_venta_anterior' => $precioVentaAnterior,
                'precio_venta_nuevo' => $precioVentaNuevo,
                'lote' => $validatedData['lote'] ?? 'SIN-LOTE',
                'fecha_vencimiento' => $validatedData['fecha_vencimiento'] ?? null,
                'observaciones' => $validatedData['observaciones'] ?? null,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo,
                'fecha_entrada' => now()
            ]);

            // Procesar lote (crear nuevo o incrementar existente)
            if (!empty($validatedData['existing_lote_id'])) {
                // Verificar que el lote pertenezca al producto
                $loteExistente = \App\Models\ProductoUbicacion::find($validatedData['existing_lote_id']);
                
                if (!$loteExistente || $loteExistente->producto_id != $producto->id) {
                    // Si el lote no coincide, fallback a crear uno nuevo (o lanzar error)
                    Log::warning("Intento de usar lote {$validatedData['existing_lote_id']} que no pertenece al producto {$producto->id}");
                    // En este caso, procedemos a crear uno nuevo para evitar errores bloqueantes,
                    // asumiendo que el usuario quería crear uno nuevo con ese nombre si fuera posible
                } else {
                    $lote = $this->loteService->incrementLoteStock(
                        $validatedData['existing_lote_id'],
                        $validatedData['cantidad'],
                        $precioCompraNuevo,
                        $precioVentaNuevo
                    );
                    
                    // Si se proporcionó una nueva fecha de vencimiento, actualizarla también
                    if (isset($validatedData['fecha_vencimiento'])) {
                        $lote->update(['fecha_vencimiento' => $validatedData['fecha_vencimiento']]);
                    }
                }
            }
            
            if (!isset($lote)) {
                $lote = $this->loteService->crearLote([
                    'producto_id' => $producto->id,
                    'ubicacion_id' => $validatedData['ubicacion_id'] ?? null,
                    'cantidad' => $validatedData['cantidad'],
                    'fecha_vencimiento' => $validatedData['fecha_vencimiento'] ?? null,
                    'lote' => $validatedData['lote'] ?? null,
                    'precio_compra' => $precioCompraNuevo,
                    'precio_venta' => $precioVentaNuevo,
                    'proveedor_id' => $validatedData['proveedor_id'],
                    'observaciones' => $validatedData['observaciones'] ?? null
                ]);
            }

            // Si se especifica una ubicación, actualizar ubicación del producto
            if (isset($validatedData['ubicacion_id'])) {
                $ubicacion = \App\Models\Ubicacion::with('estante')->find($validatedData['ubicacion_id']);
                if ($ubicacion) {
                    $ubicacionTexto = $ubicacion->estante->nombre . ' - ' . $ubicacion->codigo;
                    $producto->update(['ubicacion_almacen' => $ubicacionTexto]);
                }
            }

            // Guardar precios y cantidades de presentaciones para este lote específico
            if ($request->has('presentaciones')) {
                $unidadesModificadas = $request->input('presentaciones_unidades', []);
                $hasLotePresTable = Schema::hasTable('lote_presentaciones');
                
                foreach ($request->presentaciones as $presentacion_id => $precio_venta) {
                    if ($precio_venta > 0) {
                        // Si el ID es 0, es la presentación base (Unidad) que se actualiza en la tabla 'productos'
                        if ($presentacion_id == 0) {
                            $producto->update([
                                'precio_venta' => $precio_venta,
                                'precio_compra' => $validatedData['precio_compra'] ?? $producto->precio_compra
                            ]);
                            continue;
                        }

                        // Actualizar la tabla global de presentaciones del producto 
                        \DB::table('producto_presentaciones')
                            ->where('id', $presentacion_id)
                            ->update([
                                'precio_venta_presentacion' => $precio_venta,
                                'unidades_por_presentacion' => $unidadesModificadas[$presentacion_id] ?? 1,
                                'updated_at' => now()
                            ]);

                        // Guardar el registro específico para este lote si la tabla existe
                        if ($hasLotePresTable) {
                            $dataInsert = [
                                'producto_ubicacion_id' => $lote->id,
                                'producto_presentacion_id' => $presentacion_id,
                                'precio_venta' => $precio_venta,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            if (isset($unidadesModificadas[$presentacion_id])) {
                                $dataInsert['unidades_por_presentacion'] = $unidadesModificadas[$presentacion_id];
                            }

                            \DB::table('lote_presentaciones')->insert($dataInsert);
                        }
                    }
                }
            }

            DB::commit();

            // Log de auditoría para entrada exitosa
            Log::info('Entrada de mercadería registrada', [
                'usuario_id' => $this->obtenerUsuarioId(),
                'producto_id' => $producto->id,
                'producto_nombre' => $producto->nombre,
                'proveedor_id' => $validatedData['proveedor_id'],
                'cantidad' => $validatedData['cantidad'],
                'lote' => $validatedData['lote'] ?? 'SIN-LOTE',
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo,
                'precio_compra_nuevo' => $precioCompraNuevo,
                'precio_venta_nuevo' => $precioVentaNuevo
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Entrada de mercadería registrada correctamente',
                'data' => [
                    'producto' => $producto->nombre,
                    'cantidad' => $validatedData['cantidad'],
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $stockNuevo,
                    'lote' => $validatedData['lote'] ?? null,
                    'proveedor' => $proveedor->razon_social
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::warning('Validación fallida en entrada de mercadería', [
                'usuario_id' => $this->obtenerUsuarioId(),
                'errores' => $e->errors(),
                'datos_enviados' => $request->except(['_token'])
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en entrada de mercadería', [
                'usuario_id' => $this->obtenerUsuarioId(),
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'datos_enviados' => $request->except(['_token'])
            ]);
            
            // Respuesta temporal con detalle para diagnóstico
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint temporal para diagnosticar el problema de ubicaciones
     */
    public function testUbicaciones()
    {
        try {
            $ubicaciones = DB::table('ubicaciones')->get();
            $ubicacionesActivas = DB::table('ubicaciones')->where('activo', 1)->get();
            
            $loteService = new \App\Services\LoteService();
            
            // Intentar crear un lote de prueba
            $datos = [
                'producto_id' => 1,
                'proveedor_id' => 1,
                'cantidad' => 1,
                'precio_compra' => 10.00,
                'precio_venta' => 15.00,
                'lote' => 'TEST-WEB-' . date('Y-m-d-H-i-s'),
                'fecha_vencimiento' => '2025-12-31',
                'observaciones' => 'Prueba desde web'
            ];
            
            $lote = $loteService->crearLote($datos);
            
            return response()->json([
                'success' => true,
                'message' => 'Prueba exitosa',
                'data' => [
                    'total_ubicaciones' => $ubicaciones->count(),
                    'ubicaciones_activas' => $ubicacionesActivas->count(),
                    'lote_creado' => $lote->id,
                    'ubicacion_asignada' => $lote->ubicacion_id
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * API para obtener lotes activos de un producto con información detallada
     */
    public function obtenerLotesActivos($id)
    {
        try {
            $lotes = \App\Models\ProductoUbicacion::where('producto_id', $id)
                ->where('estado_lote', 'activo')
                ->select('id', 'lote', 'fecha_vencimiento', 'cantidad', 'proveedor_id', 'precio_compra_lote', 'precio_venta_lote')
                ->orderBy('fecha_vencimiento', 'asc')
                ->get();

            // Enriquecer cada lote con sus precios y unidades de presentaciones específicos
            $lotes->transform(function($lote) {
                $lote->presentaciones_precios = DB::table('lote_presentaciones')
                    ->where('producto_ubicacion_id', $lote->id)
                    ->get(['producto_presentacion_id', 'precio_venta', 'unidades_por_presentacion']);
                return $lote;
            });

            return response()->json([
                'success' => true,
                'lotes' => $lotes
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener lotes activos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener lotes'
            ], 500);
        }
    }

    /**
     * API para buscar productos con autocompletado inteligente
     */
    public function buscarProductos(Request $request)
    {
        // Validar y sanitizar el término de búsqueda
        $termino = trim(strip_tags($request->get('q', '')));
        
        if (strlen($termino) < 2) {
            return response()->json([
                'success' => true,
                'productos' => []
            ]);
        }

        try {
            // Evitar errores 500 si la tabla de entradas no existe en el entorno local
            $existeEntradas = Schema::hasTable('entradas_mercaderia');

            // Construir consulta base
            $query = Producto::select([
                    'productos.id', 'productos.nombre', 'productos.codigo_barras', 
                    'productos.stock_actual', 'productos.precio_compra', 'productos.precio_venta',
                    'productos.concentracion', 'productos.lote', 
                    'productos.fecha_vencimiento', 'productos.estado'
                ])
                ->where(function($q) use ($termino) {
                    $q->where('productos.nombre', 'like', "%{$termino}%")
                      ->orWhere('productos.codigo_barras', 'like', "%{$termino}%")
                      ->orWhere('productos.lote', 'like', "%{$termino}%");
                })
                ->whereIn('productos.estado', ['Normal', 'Bajo stock', 'Por vencer', 'Vencido']);

            if ($existeEntradas) {
                $query->leftJoin('entradas_mercaderia', 'productos.id', '=', 'entradas_mercaderia.producto_id')
                    ->addSelect(DB::raw('MAX(entradas_mercaderia.fecha_entrada) as ultima_entrada'))
                    ->addSelect(DB::raw('COUNT(entradas_mercaderia.id) as total_entradas'))
                    ->groupBy([
                        'productos.id', 'productos.nombre', 'productos.codigo_barras', 
                        'productos.stock_actual', 'productos.precio_compra', 
                        'productos.precio_venta', 
                        'productos.concentracion', 'productos.lote', 'productos.fecha_vencimiento',
                        'productos.estado'
                    ])
                    ->orderByRaw('ultima_entrada DESC, total_entradas DESC, productos.nombre ASC');
            } else {
                // Fallback seguro: sin joins ni agrupaciones
                $query->addSelect(DB::raw('NULL as ultima_entrada'))
                      ->addSelect(DB::raw('0 as total_entradas'))
                      ->orderBy('productos.nombre', 'ASC');
            }

            $productosConHistorial = $query->limit(20)->get();

            // Enriquecer datos con información adicional y de lotes
            $productos = $productosConHistorial->map(function($producto) {
                // Calcular días desde última entrada con texto amigable
                $diasUltimaEntrada = null;
                $textoUltimaEntrada = 'Sin entradas previas';
                if ($producto->ultima_entrada) {
                    $fechaEntrada = Carbon::parse($producto->ultima_entrada)->startOfDay();
                    $fechaActual = Carbon::now()->startOfDay();
                    $dias = $fechaEntrada->diffInDays($fechaActual);
                    
                    if ($dias == 0) {
                        $textoUltimaEntrada = 'hoy';
                    } elseif ($dias == 1) {
                        $textoUltimaEntrada = 'ayer';
                    } else {
                        $textoUltimaEntrada = "hace {$dias} días";
                    }
                    
                    $diasUltimaEntrada = $dias;
                }

                // Obtener información de lotes para este producto
                // Proteger contra entornos donde la tabla de lotes no exista aún
                $infoLotes = [
                    'stock_total' => 0,
                    'total_lotes' => 0,
                    'lotes_activos' => 0,
                    'lotes_vencidos' => 0,
                ];
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('producto_ubicaciones')) {
                        $infoLotes = $this->loteService->obtenerInfoLotes($producto->id);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Buscar productos (Compras): no se pudo obtener info de lotes', [
                        'producto_id' => $producto->id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Determinar todos los estados aplicables al producto
                $estadosAplicables = [];
                
                // Estado principal del producto
                $estadosAplicables[] = $producto->estado;
                
                // Verificar estado de stock
                if ($producto->stock_actual <= 0) {
                    $estadosAplicables[] = 'Agotado';
                } elseif ($producto->estado === 'Bajo stock' && !in_array('Bajo stock', $estadosAplicables)) {
                    // Ya está incluido en el estado principal
                }
                
                // Verificar si está próximo a vencer
                $proximoVencimiento = false;
                if ($producto->fecha_vencimiento) {
                    $fechaVencimiento = Carbon::parse($producto->fecha_vencimiento)->startOfDay();
                    $fechaActual = Carbon::now()->startOfDay();
                    $diasVencimiento = $fechaActual->diffInDays($fechaVencimiento);
                    if ($diasVencimiento <= 30 && $diasVencimiento > 0) {
                        $proximoVencimiento = true;
                        if (!in_array('Por vencer', $estadosAplicables)) {
                            $estadosAplicables[] = 'Por vencer';
                        }
                    }
                }
                
                // Verificar si hay lotes próximos a vencer
                $lotesProximosVencer = $infoLotes['lotes_proximos_vencer'] ?? 0;
                if ($lotesProximosVencer > 0 && !in_array('Por vencer', $estadosAplicables)) {
                    $estadosAplicables[] = 'Por vencer';
                    $proximoVencimiento = true;
                }
                
                // Remover duplicados y mantener orden de prioridad
                $estadosAplicables = array_unique($estadosAplicables);

                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'codigo_barras' => $producto->codigo_barras,
                    'stock_actual' => $producto->stock_actual,
                    'precio_compra' => $producto->precio_compra,
                    'precio_venta' => $producto->precio_venta,
                    'concentracion' => $producto->concentracion,
                    'lote' => $producto->lote,
                    'fecha_vencimiento' => $producto->fecha_vencimiento,
                    'estado' => $producto->estado, // Estado principal del producto
                    'estados_aplicables' => $estadosAplicables, // Todos los estados que aplican al producto
                    'proximo_vencimiento' => $proximoVencimiento,
                    'total_entradas' => $producto->total_entradas ?? 0,
                    'dias_ultima_entrada' => $diasUltimaEntrada,
                    'texto_ultima_entrada' => $textoUltimaEntrada, // Texto amigable para mostrar
                    'sugerido' => $producto->total_entradas > 0 && ($diasUltimaEntrada !== null && $diasUltimaEntrada <= 30),
                    // Información de lotes
                    'total_lotes' => $infoLotes['total_lotes'] ?? 0,
                    'lotes_activos' => $infoLotes['lotes_activos'] ?? 0,
                    'lotes_proximos_vencer' => $lotesProximosVencer,
                    'lotes_vencidos' => $infoLotes['lotes_vencidos'] ?? 0,
                    'stock_por_lotes' => $infoLotes['stock_total'] ?? 0
                ];
            });

            return response()->json([
                'success' => true,
                'productos' => $productos
            ]);

        } catch (\Exception $e) {
            Log::error('Error en búsqueda de productos', [
                'termino' => $termino,
                'usuario_id' => $this->obtenerUsuarioId(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda de productos'
            ], 500);
        }
    }



    /**
     * Obtener proveedor por ID
     */
    public function obtenerProveedor($id)
    {
        try {
            $proveedor = Proveedor::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $proveedor
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado'
            ], 404);
        }
    }

    /**
     * API para obtener todos los proveedores (para tabla)
     */
    public function apiProveedores()
    {
        try {
            $proveedores = Proveedor::orderBy('razon_social')->get();
            
            return response()->json([
                'success' => true,
                'data' => $proveedores
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener proveedores: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener proveedores'
            ], 500);
        }
    }
}
