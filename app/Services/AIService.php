<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de IA para Botica San Antonio
 * Conecta con el motor Python de IA
 */
class AIService
{
    protected string $baseUrl;
    protected int $timeout;
    protected int $cacheTtl;

    public function __construct()
    {
        $this->baseUrl = env('IA_API_URL', 'http://76.13.71.180/ai');
        $this->timeout = 30;
        $this->cacheTtl = env('IA_LLM_CACHE_TTL_MINUTES', 3) * 60;
    }

    /**
     * Envía un mensaje al motor de IA
     */
    public function chat(string $message, ?array $userContext = null): array
    {
        try {
            $user = auth()->user();
            
            $payload = [
                'message' => $message,
                'user_id' => $user->id ?? null,
                'user_name' => $user->name ?? 'Usuario',
                'user_role' => $user->role ?? 'vendedor',
            ];

            // Verificar cache
            $cacheKey = 'ai_chat_' . md5($message . ($user->id ?? ''));
            if (Cache::has($cacheKey)) {
                Log::info('AI Cache hit', ['key' => $cacheKey]);
                return Cache::get($cacheKey);
            }

            // Llamar al motor de IA
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/chat", $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                // Guardar en cache
                Cache::put($cacheKey, $data, $this->cacheTtl);
                
                return $data;
            }

            Log::error('AI Engine error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'response' => 'Lo siento, hubo un error al procesar tu consulta.',
                'error' => 'Error de conexión con el motor de IA'
            ];

        } catch (\Exception $e) {
            Log::error('AIService Exception', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'response' => 'El servicio de IA no está disponible en este momento.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene sugerencias de consultas
     */
    public function getSuggestions(): array
    {
        try {
            $cacheKey = 'ai_suggestions';
            
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/suggestions");

            if ($response->successful()) {
                $data = $response->json();
                Cache::put($cacheKey, $data, 3600); // Cache 1 hora
                return $data;
            }

            return ['suggestions' => $this->getDefaultSuggestions()];

        } catch (\Exception $e) {
            Log::error('AIService getSuggestions error', ['error' => $e->getMessage()]);
            return ['suggestions' => $this->getDefaultSuggestions()];
        }
    }

    /**
     * Verifica el estado del motor de IA
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            
            return $response->successful() 
                ? $response->json() 
                : ['status' => 'offline'];

        } catch (\Exception $e) {
            return ['status' => 'offline', 'error' => $e->getMessage()];
        }
    }

    /**
     * Sugerencias por defecto
     */
    protected function getDefaultSuggestions(): array
    {
        return [
            '💰 ¿Cuánto vendí hoy?',
            '📦 ¿Qué productos tienen stock bajo?',
            '⏰ ¿Qué lotes vencen pronto?',
            '🔥 ¿Cuáles son los más vendidos?',
            '📊 Compara ventas de hoy vs ayer',
            '📋 Lista productos por categoría',
        ];
    }
}
