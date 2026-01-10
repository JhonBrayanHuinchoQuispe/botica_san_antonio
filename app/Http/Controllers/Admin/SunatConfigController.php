<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Services\FacturacionElectronicaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SunatConfigController extends Controller
{
    /**
     * Mostrar configuración SUNAT
     */
    public function index()
    {
        return view('admin.sunat.configuracion');
    }
    
    /**
     * Guardar configuración SUNAT
     */
    public function guardarConfiguracion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ruc' => 'required|string|size:11',
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'direccion' => 'required|string|max:255',
            'ubigeo' => 'required|string|size:6',
            'usuario_sol' => 'required|string|max:50',
            'clave_sol' => 'required|string|max:50',
            'modo_prueba' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            // Actualizar configuración en archivo
            $config = config('sistema');
            
            $config['empresa']['ruc'] = $request->ruc;
            $config['empresa']['razon_social'] = $request->razon_social;
            $config['empresa']['nombre_comercial'] = $request->nombre_comercial;
            $config['empresa']['direccion'] = $request->direccion;
            $config['empresa']['ubigeo'] = $request->ubigeo;
            
            $config['sunat']['usuario_sol'] = $request->usuario_sol;
            $config['sunat']['clave_sol'] = $request->clave_sol;
            $config['sunat']['modo_prueba'] = $request->boolean('modo_prueba');
            
            // Guardar en archivo de configuración
            $configContent = "<?php\n\nreturn " . var_export($config, true) . ";\n";
            file_put_contents(config_path('sistema.php'), $configContent);
            
            // Limpiar cache de configuración
            \Artisan::call('config:clear');
            
            Log::info('Configuración SUNAT actualizada', [
                'ruc' => $request->ruc,
                'usuario' => auth()->user()->email
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Configuración guardada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al guardar configuración SUNAT', [
                'error' => $e->getMessage(),
                'usuario' => auth()->user()->email
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la configuración: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Subir certificado digital
     */
    public function subirCertificado(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'certificado' => 'required|file|mimes:p12,pfx|max:2048',
            'password_certificado' => 'required|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Archivo de certificado inválido',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $archivo = $request->file('certificado');
            $nombreArchivo = 'certificado_' . config('sistema.empresa.ruc') . '.p12';
            
            // Guardar certificado
            $rutaArchivo = $archivo->storeAs('certificados', $nombreArchivo, 'local');
            
            // Validar certificado
            $rutaCompleta = storage_path('app/' . $rutaArchivo);
            if (!openssl_pkcs12_read(file_get_contents($rutaCompleta), $certs, $request->password_certificado)) {
                Storage::delete($rutaArchivo);
                return response()->json([
                    'success' => false,
                    'message' => 'Certificado o contraseña inválidos'
                ]);
            }
            
            // Actualizar configuración
            $config = config('sistema');
            $config['sunat']['certificado_path'] = $rutaCompleta;
            $config['sunat']['certificado_password'] = $request->password_certificado;
            
            $configContent = "<?php\n\nreturn " . var_export($config, true) . ";\n";
            file_put_contents(config_path('sistema.php'), $configContent);
            
            \Artisan::call('config:clear');
            
            Log::info('Certificado SUNAT subido', [
                'archivo' => $nombreArchivo,
                'usuario' => auth()->user()->email
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Certificado subido y validado exitosamente',
                'archivo' => $nombreArchivo
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al subir certificado SUNAT', [
                'error' => $e->getMessage(),
                'usuario' => auth()->user()->email
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al subir el certificado: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Probar conexión con SUNAT
     */
    public function probarConexion(Request $request)
    {
        try {
            $facturacionService = new FacturacionElectronicaService();
            $resultado = $facturacionService->probarConexion();
            
            if ($resultado['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Conexión exitosa con SUNAT',
                    'datos' => $resultado['datos']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['message']
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error al probar conexión SUNAT', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Vista de monitoreo
     */
    public function monitoreo()
    {
        return view('admin.sunat.monitoreo');
    }
    
    /**
     * Datos para el monitoreo (DataTables)
     */
    public function monitoreoData(Request $request)
    {
        $query = Venta::with(['cliente', 'comprobante_electronico'])
            ->whereIn('tipo_comprobante', ['boleta', 'factura'])
            ->orderBy('created_at', 'desc');
            
        // Filtros
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }
        
        if ($request->filled('estado')) {
            $query->whereHas('comprobante_electronico', function($q) use ($request) {
                $q->where('estado_sunat', $request->estado);
            });
        }
        
        return datatables($query)
            ->addColumn('fecha', function($venta) {
                return $venta->created_at->format('d/m/Y H:i');
            })
            ->addColumn('cliente', function($venta) {
                return $venta->cliente ? $venta->cliente->nombre : 'Cliente General';
            })
            ->addColumn('serie_numero', function($venta) {
                return $venta->comprobante_electronico ? 
                    $venta->comprobante_electronico->serie_numero : 
                    '<span class="text-muted">No generado</span>';
            })
            ->addColumn('estado_sunat', function($venta) {
                if (!$venta->comprobante_electronico) {
                    return '<span class="badge badge-secondary">Sin generar</span>';
                }
                
                $estado = $venta->comprobante_electronico->estado_sunat;
                $class = match($estado) {
                    'aceptado' => 'badge-success',
                    'rechazado' => 'badge-danger',
                    'enviado' => 'badge-info',
                    'pendiente' => 'badge-warning',
                    default => 'badge-secondary'
                };
                
                return '<span class="badge ' . $class . '">' . ucfirst($estado) . '</span>';
            })
            ->addColumn('hash', function($venta) {
                if (!$venta->comprobante_electronico || !$venta->comprobante_electronico->hash) {
                    return '<span class="text-muted">-</span>';
                }
                
                $hash = $venta->comprobante_electronico->hash;
                return '<code title="' . $hash . '">' . substr($hash, 0, 8) . '...</code>';
            })
            ->addColumn('acciones', function($venta) {
                $botones = '<div class="btn-group btn-group-sm">';
                
                $botones .= '<button class="btn btn-info btn-detalles" data-venta-id="' . $venta->id . '" title="Ver detalles">';
                $botones .= '<i class="fas fa-eye"></i></button>';
                
                if ($venta->comprobante_electronico) {
                    $botones .= '<button class="btn btn-warning btn-verificar" data-venta-id="' . $venta->id . '" title="Verificar estado">';
                    $botones .= '<i class="fas fa-sync-alt"></i></button>';
                }
                
                $botones .= '<button class="btn btn-secondary btn-regenerar" data-venta-id="' . $venta->id . '" title="Regenerar">';
                $botones .= '<i class="fas fa-redo"></i></button>';
                
                $botones .= '</div>';
                
                return $botones;
            })
            ->rawColumns(['serie_numero', 'estado_sunat', 'hash', 'acciones'])
            ->make(true);
    }
    
    /**
     * Estadísticas para el dashboard
     */
    public function estadisticas(Request $request)
    {
        $fechaDesde = $request->input('fecha_desde', now()->subDays(7)->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));
        
        $query = Venta::whereIn('tipo_comprobante', ['boleta', 'factura'])
            ->whereDate('created_at', '>=', $fechaDesde)
            ->whereDate('created_at', '<=', $fechaHasta);
            
        $total = $query->count();
        
        $estadisticas = [
            'enviados' => $query->clone()->whereHas('comprobante_electronico')->count(),
            'aceptados' => $query->clone()->whereHas('comprobante_electronico', function($q) {
                $q->where('estado_sunat', 'aceptado');
            })->count(),
            'rechazados' => $query->clone()->whereHas('comprobante_electronico', function($q) {
                $q->where('estado_sunat', 'rechazado');
            })->count(),
            'pendientes' => $query->clone()->whereHas('comprobante_electronico', function($q) {
                $q->whereIn('estado_sunat', ['enviado', 'pendiente']);
            })->count()
        ];
        
        return response()->json($estadisticas);
    }
    
    /**
     * Actualizar estados masivamente
     */
    public function actualizarEstados(Request $request)
    {
        try {
            $ventas = Venta::with('comprobante_electronico')
                ->whereIn('tipo_comprobante', ['boleta', 'factura'])
                ->whereHas('comprobante_electronico', function($q) {
                    $q->whereIn('estado_sunat', ['enviado', 'pendiente']);
                })
                ->limit(50) // Limitar para evitar timeouts
                ->get();
                
            $facturacionService = new FacturacionElectronicaService();
            $actualizados = 0;
            
            foreach ($ventas as $venta) {
                try {
                    $resultado = $facturacionService->verificarEstadoSunat($venta->id);
                    if ($resultado) {
                        $actualizados++;
                    }
                } catch (\Exception $e) {
                    Log::warning('Error al actualizar estado de venta', [
                        'venta_id' => $venta->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Se actualizaron {$actualizados} comprobantes",
                'actualizados' => $actualizados
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al actualizar estados masivamente', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estados: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Ver detalles de un comprobante
     */
    public function detalles($ventaId)
    {
        $venta = Venta::with(['cliente', 'comprobante_electronico', 'detalles.producto'])
            ->findOrFail($ventaId);
            
        return view('admin.sunat.detalles', compact('venta'));
    }
    
    /**
     * Descargar archivo XML
     */
    public function descargarXML($ventaId)
    {
        $venta = Venta::with('comprobante_electronico')->findOrFail($ventaId);
        
        if (!$venta->comprobante_electronico || !$venta->comprobante_electronico->xml_path) {
            abort(404, 'Archivo XML no encontrado');
        }
        
        $rutaArchivo = $venta->comprobante_electronico->xml_path;
        
        if (!file_exists($rutaArchivo)) {
            abort(404, 'Archivo XML no existe en el servidor');
        }
        
        $nombreArchivo = $venta->comprobante_electronico->serie_numero . '.xml';
        
        return response()->download($rutaArchivo, $nombreArchivo, [
            'Content-Type' => 'application/xml'
        ]);
    }
    
    /**
     * Descargar archivo PDF
     */
    public function descargarPDF($ventaId)
    {
        $venta = Venta::with('comprobante_electronico')->findOrFail($ventaId);
        
        if (!$venta->comprobante_electronico) {
            abort(404, 'Comprobante electrónico no encontrado');
        }
        
        // Si no existe PDF, generarlo
        if (!$venta->comprobante_electronico->pdf_path || !file_exists($venta->comprobante_electronico->pdf_path)) {
            try {
                $facturacionService = new FacturacionElectronicaService();
                $resultado = $facturacionService->generarPDF($venta->id);
                
                if (!$resultado['success']) {
                    abort(500, 'Error al generar PDF: ' . $resultado['message']);
                }
                
                $rutaArchivo = $resultado['pdf_path'];
            } catch (\Exception $e) {
                abort(500, 'Error al generar PDF: ' . $e->getMessage());
            }
        } else {
            $rutaArchivo = $venta->comprobante_electronico->pdf_path;
        }
        
        if (!file_exists($rutaArchivo)) {
            abort(404, 'Archivo PDF no existe en el servidor');
        }
        
        $nombreArchivo = $venta->comprobante_electronico->serie_numero . '.pdf';
        
        return response()->download($rutaArchivo, $nombreArchivo, [
            'Content-Type' => 'application/pdf'
        ]);
    }
}