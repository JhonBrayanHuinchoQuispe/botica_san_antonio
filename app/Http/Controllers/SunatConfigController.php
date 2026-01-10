<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\FacturacionElectronicaService;
use App\Models\Venta;
use Exception;

class SunatConfigController extends Controller
{
    /**
     * Mostrar página de configuración SUNAT
     */
    public function index()
    {
        $config = [
            'ruc' => config('sistema.sunat.ruc', ''),
            'razon_social' => config('sistema.sunat.razon_social', ''),
            'nombre_comercial' => config('sistema.sunat.nombre_comercial', ''),
            'usuario_sol' => config('sistema.sunat.usuario_sol', ''),
            'produccion' => config('sistema.sunat.produccion', false),
            'certificado_existe' => Storage::exists('certificates/certificado.pfx'),
            'ubigeo' => config('sistema.empresa.ubigeo', '150101'),
            'departamento' => config('sistema.empresa.departamento', 'LIMA'),
            'provincia' => config('sistema.empresa.provincia', 'LIMA'),
            'distrito' => config('sistema.empresa.distrito', 'LIMA'),
            'direccion' => config('sistema.empresa.direccion', '')
        ];
        
        return view('admin.sunat.configuracion', compact('config'));
    }
    
    /**
     * Guardar configuración SUNAT
     */
    public function guardarConfiguracion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ruc' => 'required|string|size:11',
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'required|string|max:255',
            'usuario_sol' => 'required|string|max:50',
            'clave_sol' => 'required|string|max:50',
            'certificado_password' => 'nullable|string|max:100',
            'produccion' => 'boolean',
            'ubigeo' => 'required|string|size:6',
            'departamento' => 'required|string|max:100',
            'provincia' => 'required|string|max:100',
            'distrito' => 'required|string|max:100',
            'direccion' => 'required|string|max:255'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            // Actualizar archivo de configuración
            $this->updateConfigFile($request->all());
            
            Log::info('Configuración SUNAT actualizada por usuario: ' . auth()->user()->name);
            
            return response()->json([
                'success' => true,
                'message' => 'Configuración SUNAT guardada exitosamente'
            ]);
            
        } catch (Exception $e) {
            Log::error('Error guardando configuración SUNAT: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error guardando configuración: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Subir certificado digital
     */
    public function subirCertificado(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'certificado' => 'required|file|mimes:pfx|max:2048',
            'password' => 'required|string|max:100'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Archivo de certificado inválido',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            // Crear directorio si no existe
            if (!Storage::exists('certificates')) {
                Storage::makeDirectory('certificates');
            }
            
            // Guardar certificado
            $file = $request->file('certificado');
            $path = $file->storeAs('certificates', 'certificado.pfx');
            
            // Validar certificado
            $this->validarCertificado($request->password);
            
            // Actualizar password en configuración
            $this->updateConfigFile(['certificado_password' => $request->password]);
            
            Log::info('Certificado SUNAT subido por usuario: ' . auth()->user()->name);
            
            return response()->json([
                'success' => true,
                'message' => 'Certificado subido y validado exitosamente',
                'path' => $path
            ]);
            
        } catch (Exception $e) {
            // Eliminar certificado si hay error
            Storage::delete('certificates/certificado.pfx');
            
            Log::error('Error subiendo certificado: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error con el certificado: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Probar conexión con SUNAT
     */
    public function probarConexion()
    {
        try {
            $service = new FacturacionElectronicaService();
            
            // Crear venta de prueba
            $ventaPrueba = new Venta([
                'total' => 100.00,
                'fecha_venta' => now(),
                'moneda' => 'PEN'
            ]);
            
            // Intentar generar boleta de prueba
            $resultado = $service->generarBoleta($ventaPrueba);
            
            if ($resultado['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Conexión con SUNAT exitosa',
                    'modo' => config('sistema.sunat.produccion') ? 'PRODUCCIÓN' : 'BETA'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error en conexión: ' . $resultado['message']
                ]);
            }
            
        } catch (Exception $e) {
            Log::error('Error probando conexión SUNAT: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtener estado del sistema
     */
    public function estadoSistema()
    {
        $estado = [
            'configuracion_completa' => $this->isConfiguracionCompleta(),
            'certificado_valido' => $this->isCertificadoValido(),
            'conexion_sunat' => $this->isConexionSunatOk(),
            'modo' => config('sistema.sunat.produccion') ? 'PRODUCCIÓN' : 'BETA',
            'ultimas_boletas' => $this->getUltimasBoletasGeneradas()
        ];
        
        return response()->json($estado);
    }
    
    /**
     * Actualizar archivo de configuración
     */
    private function updateConfigFile($data)
    {
        $configPath = config_path('sistema.php');
        
        if (!file_exists($configPath)) {
            // Crear archivo de configuración si no existe
            $defaultConfig = [
                'sunat' => [],
                'empresa' => []
            ];
            file_put_contents($configPath, '<?php\n\nreturn ' . var_export($defaultConfig, true) . ';\n');
        }
        
        $config = include $configPath;
        
        // Actualizar configuración SUNAT
        if (isset($data['ruc'])) $config['sunat']['ruc'] = $data['ruc'];
        if (isset($data['razon_social'])) $config['sunat']['razon_social'] = $data['razon_social'];
        if (isset($data['nombre_comercial'])) $config['sunat']['nombre_comercial'] = $data['nombre_comercial'];
        if (isset($data['usuario_sol'])) $config['sunat']['usuario_sol'] = $data['usuario_sol'];
        if (isset($data['clave_sol'])) $config['sunat']['clave_sol'] = $data['clave_sol'];
        if (isset($data['certificado_password'])) $config['sunat']['certificado_password'] = $data['certificado_password'];
        if (isset($data['produccion'])) $config['sunat']['produccion'] = (bool)$data['produccion'];
        
        // Actualizar configuración empresa
        if (isset($data['ubigeo'])) $config['empresa']['ubigeo'] = $data['ubigeo'];
        if (isset($data['departamento'])) $config['empresa']['departamento'] = $data['departamento'];
        if (isset($data['provincia'])) $config['empresa']['provincia'] = $data['provincia'];
        if (isset($data['distrito'])) $config['empresa']['distrito'] = $data['distrito'];
        if (isset($data['direccion'])) $config['empresa']['direccion'] = $data['direccion'];
        
        // Guardar archivo
        file_put_contents($configPath, '<?php\n\nreturn ' . var_export($config, true) . ';\n');
        
        // Limpiar cache de configuración
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($configPath);
        }
    }
    
    /**
     * Validar certificado digital
     */
    private function validarCertificado($password)
    {
        try {
            $pfxContent = Storage::get('certificates/certificado.pfx');
            
            if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
                throw new Exception('Password del certificado incorrecto');
            }
            
            if (!isset($certs['cert']) || !isset($certs['pkey'])) {
                throw new Exception('Certificado digital inválido');
            }
            
            // Verificar que el certificado no haya expirado
            $certInfo = openssl_x509_parse($certs['cert']);
            if ($certInfo['validTo_time_t'] < time()) {
                throw new Exception('El certificado digital ha expirado');
            }
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception('Error validando certificado: ' . $e->getMessage());
        }
    }
    
    /**
     * Verificar si la configuración está completa
     */
    private function isConfiguracionCompleta()
    {
        $required = ['ruc', 'razon_social', 'usuario_sol', 'clave_sol'];
        
        foreach ($required as $key) {
            if (empty(config("sistema.sunat.{$key}"))) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Verificar si el certificado es válido
     */
    private function isCertificadoValido()
    {
        try {
            if (!Storage::exists('certificates/certificado.pfx')) {
                return false;
            }
            
            $password = config('sistema.sunat.certificado_password');
            if (empty($password)) {
                return false;
            }
            
            $this->validarCertificado($password);
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Verificar conexión con SUNAT
     */
    private function isConexionSunatOk()
    {
        // Esta verificación se puede hacer de forma asíncrona
        // Por ahora retornamos true si la configuración está completa
        return $this->isConfiguracionCompleta() && $this->isCertificadoValido();
    }
    
    /**
     * Obtener últimas boletas generadas
     */
    private function getUltimasBoletasGeneradas()
    {
        return Venta::whereNotNull('estado_sunat')
                   ->orderBy('fecha_envio_sunat', 'desc')
                   ->limit(5)
                   ->select('id', 'numero_electronico', 'serie_electronica', 'total', 'estado_sunat', 'fecha_envio_sunat')
                   ->get();
    }
}