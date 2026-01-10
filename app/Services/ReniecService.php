<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ReniecService
{
    private $apis;
    private $lastAttempt;
    
    public function __construct()
    {
        // Token ApisPeru desde variables de entorno
        $apisperuToken = env('APISPERU_TOKEN');
        $codartToken = env('CODART_TOKEN');

        $this->apis = [];
        $this->lastAttempt = null;
        
        // Solo ApisPeru
        if (!empty($apisperuToken)) {
            $this->apis[] = [
                'name' => 'ApisPeru',
                'url' => "https://dniruc.apisperu.com/api/v1/dni/{dni}",
                // Autenticación por query param (?token=...)
                'headers' => [],
                'method' => 'GET',
                'format' => 'apisperu'
            ];
        }

        // Agregar Codart como fallback si hay token
        if (!empty($codartToken)) {
            $this->apis[] = [
                'name' => 'Codart',
                'url' => 'https://api.codart.cgrt.net/api/v1/consultas/reniec/dni/{dni}',
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'method' => 'GET',
                'format' => 'codart',
            ];
        }
        
        // Si no hay tokens configurados, se puede optar por un modo sin APIs externas
        // y solo consultar base local; la lógica de controlador ya maneja este caso.
    }
    
    /**
     * Consultar DNI en múltiples APIs de RENIEC
     */
    public function consultarDni($dni)
    {
        if (strlen($dni) !== 8 || !is_numeric($dni)) {
            throw new Exception('DNI debe tener 8 dígitos numéricos');
        }
        
        Log::info("🔍 Consultando DNI: {$dni} en APIs de RENIEC");
        
        foreach ($this->apis as $api) {
            try {
                Log::info("🌐 Intentando API: {$api['name']} - {$api['url']}");
                
                $response = $this->hacerConsulta($api, $dni);
                
                if ($response) {
                    $status = is_object($response) && method_exists($response, 'status') ? $response->status() : ($response['status'] ?? null);
                    $data = is_object($response) && method_exists($response, 'json') ? $response->json() : ($response['json'] ?? null);
                    $body = is_object($response) && method_exists($response, 'body') ? $response->body() : ($response['body'] ?? null);
                    Log::info("📄 Respuesta de {$api['name']} (status {$status}): " . json_encode($data));

                    // Registrar último intento para diagnóstico
                    $this->lastAttempt = [
                        'provider' => $api['name'],
                        'status' => $status,
                        'data_keys' => is_array($data) ? array_keys($data) : null,
                        'body_excerpt' => is_string($body) ? substr($body, 0, 500) : null,
                    ];

                    $datosPersona = $this->procesarRespuesta($api['format'], $data);
                    
                    if ($datosPersona) {
                        Log::info("✅ DNI encontrado en {$api['name']}: " . $datosPersona['nombre_completo']);
                        return $datosPersona;
                    }
                }
                
            } catch (Exception $e) {
                Log::warning("⚠️ Error en API {$api['name']}: " . $e->getMessage());
                $this->lastAttempt = [
                    'provider' => $api['name'],
                    'status' => null,
                    'error' => $e->getMessage(),
                ];
                continue; // Intentar con la siguiente API
            }
        }
        
        throw new Exception('No se pudo encontrar información del DNI en ninguna API disponible');
    }
    
    /**
     * Realizar consulta HTTP a la API
     */
    private function hacerConsulta($api, $dni)
    {
        $url = str_replace('{dni}', $dni, $api['url']);

        // ApisPeru usa token como parámetro de consulta (?token=...)
        // Para evitar exponer el token en logs, no lo registramos en el URL logueado.
        if (isset($api['format']) && $api['format'] === 'apisperu') {
            $token = env('APISPERU_TOKEN');
            if (!empty($token)) {
                $url .= (strpos($url, '?') !== false ? '&' : '?') . 'token=' . $token;
            }
        }
        // No se agregan otros proveedores: solo ApisPeru

        // Combinar headers propios con los de la API
        $headers = array_merge([
            'User-Agent' => 'BoticaSistema/1.0',
            // Algunas instalaciones de ApisPeru aceptan también Bearer Token en headers
            // además del query param. Lo enviamos por compatibilidad.
        ], $api['headers'] ?? []);

        if (isset($api['format']) && $api['format'] === 'apisperu') {
            $token = env('APISPERU_TOKEN');
            if (!empty($token)) {
                $headers['Authorization'] = 'Bearer ' . $token;
                $headers['Accept'] = 'application/json';
            }
        }

        if (isset($api['format']) && $api['format'] === 'codart') {
            $token = env('CODART_TOKEN');
            if (!empty($token)) {
                $headers['Authorization'] = 'Bearer ' . $token;
                $headers['Accept'] = 'application/json';
            }
        }

        // Configurar cliente HTTP con opción de desactivar verificación SSL en entorno local
        $request = Http::timeout(15)->retry(2, 250)->accept('application/json');
        if (env('HTTP_DISABLE_SSL_VERIFY', false)) {
            $request = $request->withoutVerifying();
        }
        $request = $request->withHeaders($headers);

        if ($api['method'] === 'POST') {
            $data = [$api['data_key'] => $dni];
            $response = $request->post($url, $data);
        } else {
            $response = $request->get($url);
        }

        // Si la respuesta no es exitosa, intentar fallback con cURL directo
        try {
            if (!$response || $response->failed()) {
                $curlResult = $this->hacerConsultaCurl($url, $headers);
                return $curlResult; // Respuesta tipo array
            }
        } catch (\Throwable $t) {
            Log::warning('Fallback cURL no disponible: ' . $t->getMessage());
        }

        return $response;
    }
    
    /**
     * Procesar respuesta según el formato de cada API
     */
    private function procesarRespuesta($format, $data)
    {
        switch ($format) {
            case 'apisperu':
                return $this->procesarApisPeru($data);
            case 'codart':
                return $this->procesarCodart($data);
            case 'perudevs_simple':
                return $this->procesarPeruDevsSimple($data);
                
            case 'perudevs':
                return $this->procesarPeruDevs($data);
                
            default:
                return null;
        }
    }
    
    /**
     * Procesar respuesta de ApisPeru
     */
    private function procesarApisPeru($data)
    {
        if (!is_array($data)) {
            return null;
        }

        // Caso 1: respuesta directa con campos esperados
        if (isset($data['nombres']) || isset($data['name'])) {
            $nombres = trim($data['nombres'] ?? $data['name'] ?? '');
            $apPat = trim($data['apellidoPaterno'] ?? $data['apellido_paterno'] ?? '');
            $apMat = trim($data['apellidoMaterno'] ?? $data['apellido_materno'] ?? '');
            if ($nombres !== '' && ($apPat !== '' || $apMat !== '')) {
                return [
                    'nombres' => $nombres,
                    'apellido_paterno' => $apPat,
                    'apellido_materno' => $apMat,
                    'nombre_completo' => trim($nombres . ' ' . $apPat . ' ' . $apMat),
                    'fuente' => 'ApisPeru'
                ];
            }
        }

        // Caso 2: respuesta envuelta: { success: true, data: { ... } }
        if (isset($data['data']) && is_array($data['data'])) {
            $persona = $data['data'];
            $nombres = trim($persona['nombres'] ?? $persona['name'] ?? '');
            $apPat = trim($persona['apellidoPaterno'] ?? $persona['apellido_paterno'] ?? '');
            $apMat = trim($persona['apellidoMaterno'] ?? $persona['apellido_materno'] ?? '');
            if ($nombres !== '' && ($apPat !== '' || $apMat !== '')) {
                return [
                    'nombres' => $nombres,
                    'apellido_paterno' => $apPat,
                    'apellido_materno' => $apMat,
                    'nombre_completo' => trim($nombres . ' ' . $apPat . ' ' . $apMat),
                    'fuente' => 'ApisPeru'
                ];
            }
        }

        // Si viene {success:false, message:"..."} devolvemos null
        return null;
    }
    
    
    /**
     * Procesar respuesta de PeruDevs
     */
    private function procesarPeruDevs($data)
    {
        // Formato completo: data.name o data con campos separados
        if (isset($data['data'])) {
            $persona = $data['data'];
            // Si vienen campos separados
            if (isset($persona['nombres']) || isset($persona['apellido_paterno']) || isset($persona['apellido_materno']) || isset($persona['apellidoPaterno']) || isset($persona['apellidoMaterno'])) {
                $nombres = trim($persona['nombres'] ?? $persona['nombre'] ?? $persona['first_name'] ?? '');
                $apPat = trim($persona['apellido_paterno'] ?? $persona['apellidoPaterno'] ?? $persona['last_name_father'] ?? '');
                $apMat = trim($persona['apellido_materno'] ?? $persona['apellidoMaterno'] ?? $persona['last_name_mother'] ?? '');
                $nombreCompleto = trim(($nombres . ' ' . $apPat . ' ' . $apMat));
                if (!empty($nombres) || !empty($apPat)) {
                    return [
                        'nombres' => $nombres,
                        'apellido_paterno' => $apPat,
                        'apellido_materno' => $apMat,
                        'nombre_completo' => $nombreCompleto,
                        'fuente' => 'PeruDevs'
                    ];
                }
            }
            // Si viene como 'name' único
            if (isset($persona['name'])) {
                $nombreCompleto = trim($persona['name']);
                $partesNombre = preg_split('/\s+/', $nombreCompleto);
                if (count($partesNombre) >= 3) {
                    return [
                        'nombres' => $partesNombre[0],
                        'apellido_paterno' => $partesNombre[1],
                        'apellido_materno' => implode(' ', array_slice($partesNombre, 2)),
                        'nombre_completo' => $nombreCompleto,
                        'fuente' => 'PeruDevs'
                    ];
                }
            }
        }
        return null;
    }

    /**
     * Procesar respuesta de PeruDevs Simple
     */
    private function procesarPeruDevsSimple($data)
    {
        // Puede venir como { success:true, data:{ ... } } o directamente con campos
        $persona = $data['data'] ?? $data;
        if (is_array($persona)) {
            if (isset($persona['nombres']) || isset($persona['apellido_paterno']) || isset($persona['apellido_materno']) || isset($persona['apellidoPaterno']) || isset($persona['apellidoMaterno'])) {
                $nombres = trim($persona['nombres'] ?? $persona['nombre'] ?? $persona['first_name'] ?? '');
                $apPat = trim($persona['apellido_paterno'] ?? $persona['apellidoPaterno'] ?? $persona['last_name_father'] ?? '');
                $apMat = trim($persona['apellido_materno'] ?? $persona['apellidoMaterno'] ?? $persona['last_name_mother'] ?? '');
                $nombreCompleto = trim(($nombres . ' ' . $apPat . ' ' . $apMat));
                if (!empty($nombres) || !empty($apPat)) {
                    return [
                        'nombres' => $nombres,
                        'apellido_paterno' => $apPat,
                        'apellido_materno' => $apMat,
                        'nombre_completo' => $nombreCompleto,
                        'fuente' => 'PeruDevs'
                    ];
                }
            }
            if (isset($persona['name'])) {
                $nombreCompleto = trim($persona['name']);
                $partesNombre = preg_split('/\s+/', $nombreCompleto);
                if (count($partesNombre) >= 3) {
                    return [
                        'nombres' => $partesNombre[0],
                        'apellido_paterno' => $partesNombre[1],
                        'apellido_materno' => implode(' ', array_slice($partesNombre, 2)),
                        'nombre_completo' => $nombreCompleto,
                        'fuente' => 'PeruDevs'
                    ];
                }
            }
        }
        return null;
    }
    
    /**
     * Validar si los datos son válidos
     */
    public function validarDatos($datos)
    {
        $nombres = trim($datos['nombres'] ?? '');
        // Aceptar distintas claves posibles para apellidos
        $apPat = trim($datos['apellido_paterno'] ?? ($datos['apellidoPaterno'] ?? ''));
        $apMat = trim($datos['apellido_materno'] ?? ($datos['apellidoMaterno'] ?? ''));
        // Validación más tolerante: nombres y al menos un apellido
        return $nombres !== '' && ($apPat !== '' || $apMat !== '');
    }

    /**
     * Diagnóstico del entorno y configuración para consultas de DNI
     * Devuelve información útil para detectar por qué puede fallar la integración.
     */
    public function diagnostico()
    {
        $apisConfiguradas = array_map(function ($api) {
            return [
                'name' => $api['name'],
                'method' => $api['method'],
                'url' => preg_replace('/token=[^&]*/', 'token=***', $api['url']),
            ];
        }, $this->apis);

        return [
            'php_extensions' => [
                'curl' => extension_loaded('curl'),
                'openssl' => extension_loaded('openssl'),
                'mbstring' => extension_loaded('mbstring'),
            ],
            'libraries' => [
                'guzzlehttp_guzzle' => class_exists(\GuzzleHttp\Client::class),
            ],
            'tokens_present' => [
                'APISPERU_TOKEN' => !empty(env('APISPERU_TOKEN')),
                'CODART_TOKEN' => !empty(env('CODART_TOKEN')),
            ],
            'http_disable_ssl_verify' => (bool) env('HTTP_DISABLE_SSL_VERIFY', false),
            'env' => [
                'APP_ENV' => env('APP_ENV'),
                'APP_DEBUG' => (bool) env('APP_DEBUG', false),
                'LOG_CHANNEL' => env('LOG_CHANNEL', 'stack'),
                'LOG_LEVEL' => env('LOG_LEVEL', 'debug'),
            ],
            'apis_configured' => $apisConfiguradas,
            'last_attempt' => $this->lastAttempt,
        ];
    }

    /**
     * Fallback: realizar consulta mediante cURL nativo
     */
    private function hacerConsultaCurl(string $url, array $headers = [])
    {
        if (!function_exists('curl_init')) {
            throw new Exception('Extensión cURL no está disponible');
        }

        $ch = curl_init();
        $curlHeaders = [];
        foreach ($headers as $k => $v) {
            $curlHeaders[] = $k . ': ' . $v;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $curlHeaders,
        ]);

        if (env('HTTP_DISABLE_SSL_VERIFY', false)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $json = null;
        if (is_string($body)) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $json = $decoded;
            }
        }

        if ($err) {
            Log::warning('Error cURL consultando ApisPeru: ' . $err);
        }

        return [
            'status' => $status,
            'json' => $json,
            'body' => $body,
        ];
    }
    /**
     * Procesar respuesta de Codart
     */
    private function procesarCodart($data)
    {
        if (!is_array($data)) {
            return null;
        }

        // Estructura esperada: { success: true, result: { ... } }
        if (isset($data['result']) && is_array($data['result'])) {
            $p = $data['result'];
            $nombres = trim($p['first_name'] ?? '');
            $apPat = trim($p['first_last_name'] ?? '');
            $apMat = trim($p['second_last_name'] ?? '');
            $full = trim($p['full_name'] ?? '');
            if ($full === '' && ($nombres !== '' || $apPat !== '' || $apMat !== '')) {
                $full = trim($apPat . ' ' . $apMat . ' ' . $nombres);
            }
            if ($nombres !== '' && ($apPat !== '' || $apMat !== '')) {
                return [
                    'nombres' => $nombres,
                    'apellido_paterno' => $apPat,
                    'apellido_materno' => $apMat,
                    'nombre_completo' => $full,
                    'fuente' => 'Codart',
                ];
            }
        }

        return null;
    }
}