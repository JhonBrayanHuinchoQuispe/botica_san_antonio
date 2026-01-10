<?php

namespace App\Helpers;

use App\Models\ConfiguracionSistema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConfiguracionHelper
{
    /**
     * Obtener una configuración específica del sistema
     */
    public static function obtener($clave, $default = null)
    {
        try {
            // Intentar obtener desde caché primero
            $cacheKey = "config_sistema_{$clave}";
            
            return Cache::remember($cacheKey, 3600, function () use ($clave, $default) {
                $config = ConfiguracionSistema::first();
                
                if (!$config) {
                    return $default;
                }
                
                return $config->{$clave} ?? $default;
            });
        } catch (\Exception $e) {
            Log::error("Error al obtener configuración {$clave}: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Establecer una configuración específica del sistema
     */
    public static function establecer($clave, $valor)
    {
        try {
            $config = ConfiguracionSistema::firstOrCreate([]);
            $config->{$clave} = $valor;
            $config->save();

            // Limpiar caché
            Cache::forget("config_sistema_{$clave}");
            Cache::forget('config_sistema_completa');

            return true;
        } catch (\Exception $e) {
            Log::error("Error al establecer configuración {$clave}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener toda la configuración del sistema
     */
    public static function obtenerToda()
    {
        try {
            return Cache::remember('config_sistema_completa', 3600, function () {
                return ConfiguracionSistema::first() ?? new ConfiguracionSistema();
            });
        } catch (\Exception $e) {
            Log::error("Error al obtener configuración completa: " . $e->getMessage());
            return new ConfiguracionSistema();
        }
    }

    /**
     * Actualizar múltiples configuraciones
     */
    public static function actualizarMultiples(array $configuraciones)
    {
        try {
            $config = ConfiguracionSistema::firstOrCreate([]);
            
            foreach ($configuraciones as $clave => $valor) {
                $config->{$clave} = $valor;
            }
            
            $config->save();

            // Limpiar caché
            self::limpiarCache();

            return true;
        } catch (\Exception $e) {
            Log::error("Error al actualizar configuraciones múltiples: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Limpiar caché de configuraciones
     */
    public static function limpiarCache()
    {
        try {
            $config = ConfiguracionSistema::first();
            
            if ($config) {
                $fillable = $config->getFillable();
                
                foreach ($fillable as $campo) {
                    Cache::forget("config_sistema_{$campo}");
                }
            }
            
            Cache::forget('config_sistema_completa');
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error al limpiar caché de configuraciones: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Exportar configuraciones a array
     */
    public static function exportar()
    {
        try {
            $config = ConfiguracionSistema::first();
            
            if (!$config) {
                return [];
            }
            
            return $config->toArray();
        } catch (\Exception $e) {
            Log::error("Error al exportar configuraciones: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Importar configuraciones desde array
     */
    public static function importar(array $configuraciones)
    {
        try {
            $config = ConfiguracionSistema::firstOrCreate([]);
            $fillable = $config->getFillable();
            
            foreach ($configuraciones as $clave => $valor) {
                if (in_array($clave, $fillable)) {
                    $config->{$clave} = $valor;
                }
            }
            
            $config->save();
            self::limpiarCache();
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error al importar configuraciones: " . $e->getMessage());
            return false;
        }
    }



    /**
     * Obtener estado del sistema
     */
    public static function obtenerEstadoSistema()
    {
        try {
            $estado = [
                'base_datos' => self::verificarBaseDatos(),
                'sunat' => self::verificarSunat(),
                'impresoras' => self::verificarImpresoras(),
                'cache' => self::verificarCache(),
                'permisos' => self::verificarPermisos()
            ];
            
            return $estado;
        } catch (\Exception $e) {
            Log::error("Error al obtener estado del sistema: " . $e->getMessage());
            return [
                'base_datos' => false,
                'sunat' => false,
                'impresoras' => false,
                'cache' => false,
                'permisos' => false
            ];
        }
    }

    /**
     * Verificar conexión a base de datos
     */
    private static function verificarBaseDatos()
    {
        try {
            \DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verificar configuración SUNAT
     */
    private static function verificarSunat()
    {
        $validacion = self::validarSunat();
        return $validacion['valido'];
    }

    /**
     * Verificar impresoras
     */
    private static function verificarImpresoras()
    {
        $config = self::obtenerToda();
        return !empty($config->impresora_principal);
    }

    /**
     * Verificar caché
     */
    private static function verificarCache()
    {
        try {
            Cache::put('test_cache', 'test', 1);
            $result = Cache::get('test_cache') === 'test';
            Cache::forget('test_cache');
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verificar permisos de archivos
     */
    private static function verificarPermisos()
    {
        try {
            $paths = [
                storage_path('app'),
                storage_path('logs'),
                public_path('storage')
            ];
            
            foreach ($paths as $path) {
                if (!is_writable($path)) {
                    return false;
                }
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}