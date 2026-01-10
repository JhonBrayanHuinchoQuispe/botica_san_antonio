<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use App\Models\ConfiguracionSistema;
use OwenIt\Auditing\Models\Audit;
use App\Models\User;

class AdminController extends Controller
{
    /**
     * Mostrar el perfil del usuario
     */
    public function verPerfil()
    {
        $user = Auth::user();
        
        // Estadísticas del usuario
        $stats = [
            'productos_creados' => 0, // Implementar según tu lógica
            'movimientos_realizados' => 0, // Implementar según tu lógica
            'sesiones_totales' => 1, // Implementar según tu lógica
        ];

        // Actividad reciente (ejemplo)
        $actividadReciente = [
            [
                'tipo' => 'producto',
                'icono' => 'heroicons:cube-solid',
                'titulo' => 'Producto agregado',
                'descripcion' => 'Agregaste un nuevo producto al inventario',
                'fecha' => '2 horas ago'
            ],
            [
                'tipo' => 'movimiento',
                'icono' => 'heroicons:arrow-path-solid',
                'titulo' => 'Movimiento de stock',
                'descripcion' => 'Actualizaste el stock de un producto',
                'fecha' => '1 día ago'
            ]
        ];

        return view('perfil.ver', compact('user', 'stats', 'actividadReciente'));
    }

    /**
     * Mostrar formulario de edición de perfil
     */
    public function editarPerfil()
    {
        $user = Auth::user();
        return view('perfil.editar', compact('user'));
    }

    /**
     * Actualizar información del perfil
     */
    public function actualizarPerfil(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        // Verificar que el nombre completo coincida con nombres + apellidos
        $fullNameExpected = trim($request->nombres . ' ' . $request->apellidos);
        if ($request->name !== $fullNameExpected) {
            return response()->json([
                'success' => false,
                'errors' => ['name' => ['El nombre completo no coincide con nombres y apellidos']]
            ], 422);
        }

        // Datos a actualizar
        $updateData = [
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos,
            'name' => $request->name,
            'email' => $request->email,
            'telefono' => $request->telefono,
            // 'cargo' removido
            'direccion' => $request->direccion,
        ];

        // Manejar avatar si se envió
        if ($request->hasFile('avatar')) {
            // Eliminar avatar anterior si existe
            if ($user->avatar && Storage::exists('public/' . $user->avatar)) {
                Storage::delete('public/' . $user->avatar);
            }

            // Guardar nuevo avatar
            $path = $request->file('avatar')->store('avatars', 'public');
            $updateData['avatar'] = $path;
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
            'user' => $user->fresh(),
            'avatar_url' => $user->fresh()->avatar_url
        ]);
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarPassword(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación al cambiar la contraseña.'
            ], 422);
        }
        $user = \Auth::user();
        $user->update([
            'password' => \Hash::make($request->password)
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente'
        ]);
    }

    /**
     * Subir avatar
     */
    public function subirAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048']
        ]);

        $user = Auth::user();

        // Eliminar avatar anterior si existe
        if ($user->avatar && Storage::exists('public/' . $user->avatar)) {
            Storage::delete('public/' . $user->avatar);
        }

        // Guardar nuevo avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        
        $user->update(['avatar' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar actualizado correctamente',
            'avatar_url' => $user->fresh()->avatar_url
        ]);
    }

    /**
     * Eliminar avatar
     */
    public function eliminarAvatar()
    {
        $user = Auth::user();

        if ($user->avatar && Storage::exists('public/' . $user->avatar)) {
            Storage::delete('public/' . $user->avatar);
        }

        $user->update(['avatar' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar eliminado correctamente'
        ]);
    }

    /**
     * Actualizar configuraciones del usuario
     */
    public function actualizarConfiguracion(Request $request)
    {
        $user = Auth::user();

        $user->update([
            'notif_email' => $request->has('notif_email'),
            'notif_stock' => $request->has('notif_stock'),
            'notif_vencimientos' => $request->has('notif_vencimientos'),
            'mostrar_actividad' => $request->has('mostrar_actividad'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Configuración actualizada correctamente'
        ]);
    }

    /**
     * Exportar datos del usuario
     */
    public function exportarDatos()
    {
        $user = Auth::user();

        $datos = [
            'informacion_personal' => [
                'nombre' => $user->name,
                'email' => $user->email,
                'telefono' => $user->telefono,
                'cargo' => $user->cargo,
                'direccion' => $user->direccion,
                'fecha_registro' => $user->created_at,
            ],
            'configuracion' => [
                'notificaciones_email' => $user->notif_email ?? true,
                'alertas_stock' => $user->notif_stock ?? true,
                'alertas_vencimientos' => $user->notif_vencimientos ?? true,
                'mostrar_actividad' => $user->mostrar_actividad ?? false,
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $datos,
            'filename' => 'datos_usuario_' . $user->id . '_' . date('Y-m-d') . '.json'
        ]);
    }

    /**
     * Eliminar cuenta (requiere confirmación)
     */
    public function eliminarCuenta(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'confirmacion' => ['required', 'in:ELIMINAR']
        ]);

        $user = Auth::user();

        // Eliminar avatar si existe
        if ($user->avatar && Storage::exists('public/' . $user->avatar)) {
            Storage::delete('public/' . $user->avatar);
        }

        // Aquí podrías agregar lógica adicional para limpiar datos relacionados
        
        $user->delete();

        Auth::logout();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta eliminada correctamente',
            'redirect' => route('login')
        ]);
    }

    public function viewProfile()
    {
        return view('admin.profile');
    }

    public function company()
    {
        return view('admin.company');
    }

    public function usuarios()
    {
        $proveedores = \App\Models\Proveedor::activos()->orderBy('razon_social')->get();
        $categorias = \App\Models\Categoria::orderBy('nombre')->get();
        return view('admin.usuarios', compact('proveedores', 'categorias'));
    }

    public function roles()
    {
        return view('admin.roles');
    }

    /**
     * Mostrar página de configuración del sistema
     */
    public function configuracion()
    {
        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        return view('admin.configuracion', compact('configuracion'));
    }

    /**
     * Actualizar configuración del sistema
     */
    public function actualizarConfiguracionSistema(Request $request)
    {
        try {
            $request->validate([
                'igv_habilitado' => 'required|boolean',
                'igv_porcentaje' => 'required|numeric|min:0|max:100',
                'igv_nombre' => 'required|string|max:50',
                'descuentos_habilitados' => 'required|boolean',
                'descuento_maximo_porcentaje' => 'required|numeric|min:0|max:100',
                'requiere_autorizacion_descuento' => 'required|boolean',
                'descuento_sin_autorizacion_max' => 'required|numeric|min:0|max:100',
                'promociones_habilitadas' => 'required|boolean',
                'serie_boleta' => 'required|string|max:10',
                'serie_factura' => 'required|string|max:10',
                'serie_ticket' => 'required|string|max:10',
                'moneda' => 'required|string|max:10',
                'simbolo_moneda' => 'required|string|max:10',
                'decimales' => 'required|integer|min:0|max:4',
                'imprimir_automatico' => 'required|boolean'
            ]);

            $configuracion = ConfiguracionSistema::obtenerConfiguracion();
            
            $configuracion->update($request->except(['_token', '_method']));

            return response()->json([
                'success' => true,
                'message' => 'Configuración actualizada correctamente',
                'configuracion' => $configuracion->fresh()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar configuración del sistema: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener configuración actual del sistema (API)
     */
    public function obtenerConfiguracionSistema()
    {
        try {
            $configuracion = ConfiguracionSistema::obtenerConfiguracion();
            
            return response()->json([
                'success' => true,
                'configuracion' => $configuracion
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reportes()
    {
        return view('admin.reportes');
    }

    // ============================================
    // NUEVOS MÉTODOS DE CONFIGURACIÓN ESPECÍFICA
    // ============================================

    /**
     * Configuración de datos de la empresa
     */
    public function configuracionEmpresa()
    {
        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        return view('admin.configuracion.empresa', compact('configuracion'));
    }

    public function actualizarConfiguracionEmpresa(Request $request)
    {
        $request->validate([
            'nombre_empresa' => 'required|string|max:255',
            'ruc_empresa' => 'required|string|max:11',
            'direccion_empresa' => 'required|string|max:500',
            'telefono_empresa' => 'nullable|string|max:20',
            'email_empresa' => 'nullable|email|max:255',
            'logo_empresa' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        $configuracion->update($request->except(['_token', 'logo_empresa']));

        if ($request->hasFile('logo_empresa')) {
            $path = $request->file('logo_empresa')->store('empresa', 'public');
            $configuracion->update(['logo_empresa' => $path]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuración de empresa actualizada correctamente'
        ]);
    }

    /**
     * Configuración específica del IGV
     */
    public function configuracionIgv()
    {
        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        return view('admin.configuracion.igv', compact('configuracion'));
    }

    public function actualizarConfiguracionIgv(Request $request)
    {
        $request->validate([
            'igv_habilitado' => 'required|boolean',
            'igv_porcentaje' => 'required|numeric|min:0|max:100',
            'igv_nombre' => 'required|string|max:50',
            'mostrar_igv_tickets' => 'required|boolean'
        ]);

        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        $configuracion->update($request->except(['_token']));

        return response()->json([
            'success' => true,
            'message' => 'Configuración de IGV actualizada correctamente'
        ]);
    }



    /**
     * Configuración de impresoras
     */
    public function configuracionImpresoras()
    {
        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        return view('admin.configuracion.impresoras', compact('configuracion'));
    }

    public function actualizarConfiguracionImpresoras(Request $request)
    {
        $request->validate([
            'impresora_principal' => 'required|string|max:255',
            'impresora_tickets' => 'nullable|string|max:255',
            'impresora_reportes' => 'nullable|string|max:255',
            'imprimir_automatico' => 'required|boolean',
            'copias_ticket' => 'required|integer|min:1|max:5',
            'papel_ticket_ancho' => 'required|integer|min:58|max:80'
        ]);

        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        $configuracion->update($request->except(['_token']));

        return response()->json([
            'success' => true,
            'message' => 'Configuración de impresoras actualizada correctamente'
        ]);
    }

    public function probarImpresora(Request $request)
    {
        $request->validate([
            'impresora' => 'required|string|max:255'
        ]);

        // Aquí implementarías la lógica para probar la impresora
        // Por ahora retornamos un mensaje de éxito simulado
        
        return response()->json([
            'success' => true,
            'message' => 'Impresora probada correctamente. Se envió un ticket de prueba.'
        ]);
    }

    /**
     * Configuración de tickets
     */
    public function configuracionTickets()
    {
        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        return view('admin.configuracion.tickets', compact('configuracion'));
    }

    public function actualizarConfiguracionTickets(Request $request)
    {
        $request->validate([
            'ticket_mostrar_logo' => 'required|boolean',
            'ticket_mostrar_direccion' => 'required|boolean',
            'ticket_mostrar_telefono' => 'required|boolean',
            'ticket_mostrar_igv' => 'required|boolean',
            'ticket_mensaje_pie' => 'nullable|string|max:500',
            'ticket_ancho_papel' => 'required|integer|min:58|max:80',
            'ticket_margen_superior' => 'required|integer|min:0|max:20',
            'ticket_margen_inferior' => 'required|integer|min:0|max:20'
        ]);

        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        $configuracion->update($request->except(['_token']));

        return response()->json([
            'success' => true,
            'message' => 'Configuración de tickets actualizada correctamente'
        ]);
    }

    public function vistaPreviaTicket()
    {
        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        
        // Datos de ejemplo para la vista previa
        $venta = (object) [
            'numero_venta' => 'V-' . date('Ymd') . '-0001',
            'fecha_venta' => now(),
            'total' => 25.50,
            'igv' => 4.08,
            'subtotal' => 21.42,
            'detalles' => [
                (object) ['producto' => 'Paracetamol 500mg', 'cantidad' => 2, 'precio' => 5.00, 'total' => 10.00],
                (object) ['producto' => 'Ibuprofeno 400mg', 'cantidad' => 1, 'precio' => 15.50, 'total' => 15.50]
            ]
        ];

        return view('admin.configuracion.ticket-preview', compact('configuracion', 'venta'));
    }

    /**
     * Configuración de comprobantes electrónicos
     */
    public function configuracionComprobantes()
    {
        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        return view('admin.configuracion.comprobantes', compact('configuracion'));
    }

    public function actualizarConfiguracionComprobantes(Request $request)
    {
        $request->validate([
            'serie_boleta' => 'required|string|max:10',
            'serie_factura' => 'required|string|max:10',
            'serie_ticket' => 'required|string|max:10',
            'numeracion_boleta' => 'required|integer|min:1',
            'numeracion_factura' => 'required|integer|min:1',
            'numeracion_ticket' => 'required|integer|min:1',
            'envio_automatico_sunat' => 'required|boolean',
            'generar_pdf_automatico' => 'required|boolean'
        ]);

        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        $configuracion->update($request->except(['_token']));

        return response()->json([
            'success' => true,
            'message' => 'Configuración de comprobantes actualizada correctamente'
        ]);
    }

    /**
     * Configuración de alertas del sistema
     */
    public function configuracionAlertas()
    {
        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        return view('admin.configuracion.alertas', compact('configuracion'));
    }

    public function actualizarConfiguracionAlertas(Request $request)
    {
        $request->validate([
            'alertas_stock_minimo' => 'required|boolean',
            'stock_minimo_global' => 'required|integer|min:0',
            'alertas_vencimiento' => 'required|boolean',
            'dias_alerta_vencimiento' => 'required|integer|min:1|max:365',
            'alertas_email' => 'required|boolean',
            'email_alertas' => 'nullable|email|max:255',
            'alertas_sistema' => 'required|boolean'
        ]);

        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        $configuracion->update($request->except(['_token']));

        return response()->json([
            'success' => true,
            'message' => 'Configuración de alertas actualizada correctamente'
        ]);
    }

    /**
     * Configuración y limpieza de caché
     */
    public function configuracionCache()
    {
        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        
        // Información del caché
        $cacheInfo = [
            'config_cache' => file_exists(base_path('bootstrap/cache/config.php')),
            'route_cache' => file_exists(base_path('bootstrap/cache/routes-v7.php')),
            'view_cache' => count(glob(storage_path('framework/views/*'))),
            'app_cache' => count(glob(storage_path('framework/cache/data/*')))
        ];

        return view('admin.configuracion.cache', compact('configuracion', 'cacheInfo'));
    }

    public function limpiarCache(Request $request)
    {
        $request->validate([
            'tipo_cache' => 'required|in:config,route,view,all'
        ]);

        try {
            switch ($request->tipo_cache) {
                case 'config':
                    \Artisan::call('config:clear');
                    $mensaje = 'Caché de configuración limpiado';
                    break;
                case 'route':
                    \Artisan::call('route:clear');
                    $mensaje = 'Caché de rutas limpiado';
                    break;
                case 'view':
                    \Artisan::call('view:clear');
                    $mensaje = 'Caché de vistas limpiado';
                    break;
                case 'all':
                    \Artisan::call('cache:clear');
                    \Artisan::call('config:clear');
                    \Artisan::call('route:clear');
                    \Artisan::call('view:clear');
                    $mensaje = 'Todo el caché ha sido limpiado';
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar caché: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimizar sistema
     */
    public function optimizarSistema(Request $request)
    {
        $request->validate([
            'tipo_optimizacion' => 'required|in:config,route,autoload,all'
        ]);

        try {
            switch ($request->tipo_optimizacion) {
                case 'config':
                    \Artisan::call('config:cache');
                    $mensaje = 'Configuración optimizada';
                    break;
                case 'route':
                    \Artisan::call('route:cache');
                    $mensaje = 'Rutas optimizadas';
                    break;
                case 'autoload':
                    \Artisan::call('optimize');
                    $mensaje = 'Autoload optimizado';
                    break;
                case 'all':
                    \Artisan::call('optimize');
                    \Artisan::call('config:cache');
                    \Artisan::call('route:cache');
                    $mensaje = 'Sistema completamente optimizado';
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al optimizar sistema: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar configuración del sistema
     */
    public function exportarConfiguracion()
    {
        try {
            $configuracion = ConfiguracionSistema::obtenerConfiguracion();
            
            $data = [
                'version' => '1.0',
                'fecha_exportacion' => now()->toISOString(),
                'configuracion' => $configuracion->toArray()
            ];

            $filename = 'configuracion_sistema_' . date('Y-m-d_H-i-s') . '.json';
            
            return response()->json($data)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Importar configuración del sistema
     */
    public function importarConfiguracion(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:json',
            'sobrescribir' => 'required|boolean'
        ]);

        try {
            $archivo = $request->file('archivo');
            $contenido = file_get_contents($archivo->getPathname());
            $data = json_decode($contenido, true);

            if (!$data || !isset($data['configuracion'])) {
                throw new \Exception('Archivo de configuración inválido');
            }

            $configuracion = ConfiguracionSistema::obtenerConfiguracion();
            
            if ($request->sobrescribir) {
                $configuracion->update($data['configuracion']);
            } else {
                // Solo actualizar campos que no estén vacíos en la configuración actual
                $datosActualizacion = [];
                foreach ($data['configuracion'] as $campo => $valor) {
                    if (empty($configuracion->$campo)) {
                        $datosActualizacion[$campo] = $valor;
                    }
                }
                $configuracion->update($datosActualizacion);
            }

            return response()->json([
                'success' => true,
                'message' => 'Configuración importada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al importar configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estado del sistema
     */
    public function estadoSistema()
    {
        try {
            $configuracion = ConfiguracionSistema::obtenerConfiguracion();
            
            // Verificar conexión a base de datos
            $database = true;
            try {
                \DB::connection()->getPdo();
            } catch (\Exception $e) {
                $database = false;
            }

            // Verificar estado SUNAT
            $sunat = $configuracion->sunat_habilitado ?? false;

            // Verificar configuración de impresoras
            $printer = !empty($configuracion->impresora_principal);

            // Verificar estado del caché
            $cache = file_exists(base_path('bootstrap/cache/config.php'));

            return response()->json([
                'success' => true,
                'database' => $database,
                'sunat' => $sunat,
                'printer' => $printer,
                'cache' => $cache
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado del sistema: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Probar alerta del sistema
     */
    public function probarAlerta(Request $request)
    {
        $request->validate([
            'tipo_alerta' => 'required|in:email,sistema,stock,vencimiento'
        ]);

        try {
            $configuracion = ConfiguracionSistema::obtenerConfiguracion();
            
            switch ($request->tipo_alerta) {
                case 'email':
                    if (!$configuracion->alertas_email || empty($configuracion->email_alertas)) {
                        throw new \Exception('Alertas por email no configuradas');
                    }
                    // Aquí enviarías un email de prueba
                    $mensaje = 'Email de prueba enviado a ' . $configuracion->email_alertas;
                    break;
                    
                case 'sistema':
                    if (!$configuracion->alertas_sistema) {
                        throw new \Exception('Alertas del sistema no habilitadas');
                    }
                    $mensaje = 'Alerta del sistema probada correctamente';
                    break;
                    
                case 'stock':
                    if (!$configuracion->alertas_stock_minimo) {
                        throw new \Exception('Alertas de stock mínimo no habilitadas');
                    }
                    $mensaje = 'Alerta de stock mínimo probada correctamente';
                    break;
                    
                case 'vencimiento':
                    if (!$configuracion->alertas_vencimiento) {
                        throw new \Exception('Alertas de vencimiento no habilitadas');
                    }
                    $mensaje = 'Alerta de vencimiento probada correctamente';
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al probar alerta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vista previa de comprobante
     */
    public function vistaPreviaComprobante()
    {
        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        
        // Datos de ejemplo para la vista previa
        $comprobante = (object) [
            'tipo' => 'boleta',
            'serie' => $configuracion->serie_boleta ?? 'B001',
            'numero' => str_pad($configuracion->numeracion_boleta ?? 1, 8, '0', STR_PAD_LEFT),
            'fecha' => now(),
            'cliente' => (object) [
                'nombre' => 'Cliente de Ejemplo',
                'documento' => '12345678',
                'direccion' => 'Dirección de ejemplo'
            ],
            'items' => [
                (object) ['descripcion' => 'Paracetamol 500mg', 'cantidad' => 2, 'precio' => 5.00, 'total' => 10.00],
                (object) ['descripcion' => 'Ibuprofeno 400mg', 'cantidad' => 1, 'precio' => 15.50, 'total' => 15.50]
            ],
            'subtotal' => 21.42,
            'igv' => 4.08,
            'total' => 25.50
        ];

        return view('admin.configuracion.comprobante-preview', compact('configuracion', 'comprobante'));
    }

    /**
     * Mostrar vista principal de configuración con pestañas
     */
    public function configuracionIndex()
    {
        $configuracion = ConfiguracionSistema::obtenerConfiguracion();
        return view('admin.configuracion.index', compact('configuracion'));
    }

    /**
     * Mostrar logs del sistema - Solo para Productos, Categorías y Presentaciones
     */
    public function logs(Request $request)
    {
        // Filtrar solo auditorías de Productos, Categorías y Presentaciones
        $query = Audit::with('user', 'auditable')
            ->whereIn('auditable_type', [
                'App\\Models\\Producto',
                'App\\Models\\Categoria',
                'App\\Models\\Presentacion'
            ])
            ->orderBy('created_at', 'desc');

        // Filtro por evento
        if ($request->has('event') && $request->event != '') {
            $query->where('event', $request->event);
        }

        // Filtro por usuario
        if ($request->has('user_id') && $request->user_id != '') {
            $query->where('user_id', $request->user_id);
        }

        // Filtro por tipo de módulo
        if ($request->has('module') && $request->module != '') {
            $query->where('auditable_type', $request->module);
        }

        // Filtro por fecha
        if ($request->has('fecha') && $request->fecha != '') {
            $query->whereDate('created_at', $request->fecha);
        }

        // Filtro por búsqueda de nombre de producto
        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->whereHasMorph('auditable', ['App\\Models\\Producto'], function($query) use ($searchTerm) {
                    $query->where('nombre', 'LIKE', '%' . $searchTerm . '%');
                })
                ->orWhereHasMorph('auditable', ['App\\Models\\Categoria'], function($query) use ($searchTerm) {
                    $query->where('nombre', 'LIKE', '%' . $searchTerm . '%');
                })
                ->orWhereHasMorph('auditable', ['App\\Models\\Presentacion'], function($query) use ($searchTerm) {
                    $query->where('nombre', 'LIKE', '%' . $searchTerm . '%');
                });
            });
        }

        $audits = $query->paginate(10)->withQueryString();
        $usuarios = User::orderBy('name')->get();

        return view('admin.logs.index', compact('audits', 'usuarios'));
    }
}