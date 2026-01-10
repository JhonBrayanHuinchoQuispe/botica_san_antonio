<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ValidateInputMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Validar y limpiar datos de entrada
        $this->validateAndSanitizeInput($request);
        
        // Detectar posibles ataques
        $this->detectSuspiciousActivity($request);
        
        return $next($request);
    }

    /**
     * Validar y limpiar datos de entrada
     */
    private function validateAndSanitizeInput(Request $request): void
    {
        $input = $request->all();
        $sanitized = [];

        foreach ($input as $key => $value) {
            if (is_string($value)) {
                // Limpiar caracteres peligrosos
                $sanitized[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        // Reemplazar input con datos sanitizados
        $request->replace($sanitized);
    }

    /**
     * Limpiar string de caracteres peligrosos
     */
    private function sanitizeString(string $value): string
    {
        // Remover caracteres de control
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        // Limpiar HTML peligroso pero mantener básico
        $value = strip_tags($value, '<p><br><strong><em><u>');
        
        // Escapar caracteres especiales para SQL
        $value = addslashes($value);
        
        return trim($value);
    }

    /**
     * Limpiar array recursivamente
     */
    private function sanitizeArray(array $array): array
    {
        $sanitized = [];
        
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Detectar actividad sospechosa
     */
    private function detectSuspiciousActivity(Request $request): void
    {
        $input = $request->all();
        $suspicious = false;
        $reasons = [];

        // Detectar patrones de SQL injection
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                if ($this->containsSqlInjection($value)) {
                    $suspicious = true;
                    $reasons[] = "SQL injection attempt in field: {$key}";
                }
                
                if ($this->containsXss($value)) {
                    $suspicious = true;
                    $reasons[] = "XSS attempt in field: {$key}";
                }
                
                if ($this->containsPathTraversal($value)) {
                    $suspicious = true;
                    $reasons[] = "Path traversal attempt in field: {$key}";
                }
            }
        }

        // Verificar tamaño excesivo de datos
        if (strlen(json_encode($input)) > 1048576) { // 1MB
            $suspicious = true;
            $reasons[] = "Excessive data size";
        }

        if ($suspicious) {
            $this->logSuspiciousActivity($request, $reasons);
        }
    }

    /**
     * Detectar SQL injection
     */
    private function containsSqlInjection(string $value): bool
    {
        $patterns = [
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b)/i',
            '/(\bOR\b|\bAND\b)\s+\d+\s*=\s*\d+/i',
            '/[\'";]\s*(OR|AND)\s+[\'"]?\w+[\'"]?\s*=\s*[\'"]?\w+[\'"]?/i',
            '/\b(exec|execute|sp_|xp_)\b/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detectar XSS
     */
    private function containsXss(string $value): bool
    {
        $patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe\b/i',
            '/<object\b/i',
            '/<embed\b/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detectar path traversal
     */
    private function containsPathTraversal(string $value): bool
    {
        $patterns = [
            '/\.\.[\/\\\\]/',
            '/[\/\\\\]\.\.[\/\\\\]/',
            '/\.\.[\/\\\\]\.\.[\/\\\\]/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Registrar actividad sospechosa
     */
    private function logSuspiciousActivity(Request $request, array $reasons): void
    {
        Log::warning('Actividad sospechosa detectada', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'reasons' => $reasons,
            'input' => $request->all(),
            'user_id' => Auth::check() ? Auth::user()->id : null,
            'timestamp' => now()
        ]);

        // Opcional: Bloquear IP después de varios intentos
        $this->checkForBlocking($request->ip());
    }

    /**
     * Verificar si se debe bloquear IP
     */
    private function checkForBlocking(string $ip): void
    {
        $cacheKey = "suspicious_activity:{$ip}";
        $attempts = cache()->get($cacheKey, 0);
        
        $attempts++;
        cache()->put($cacheKey, $attempts, now()->addHour());
        
        if ($attempts >= 5) {
            Log::critical("IP bloqueada por actividad sospechosa: {$ip}");
            
            // Aquí podrías implementar bloqueo real
            // Por ejemplo, añadir a una lista negra en Redis
        }
    }
}