<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\AIService;
use App\Models\AIChatHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Muestra la vista del chat de IA
     */
    public function index()
    {
        $suggestions = $this->aiService->getSuggestions();
        $health = $this->aiService->healthCheck();
        
        return view('ai.chat', [
            'suggestions' => $suggestions['suggestions'] ?? [],
            'aiStatus' => $health['status'] ?? 'unknown',
        ]);
    }

    /**
     * Procesa un mensaje del chat
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $user = auth()->user();
        $userId = $user->id ?? 0;

        // Rate limiting: 20 requests por minuto por usuario
        $key = 'ai_chat_' . $userId;
        
        if (RateLimiter::tooManyAttempts($key, 20)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'response' => "Has alcanzado el límite de consultas. Espera {$seconds} segundos.",
                'error' => 'rate_limit'
            ], 429);
        }

        RateLimiter::hit($key, 60);

        // Procesar con el servicio de IA
        $result = $this->aiService->chat($request->message);

        // Guardar en historial
        try {
            AIChatHistory::create([
                'user_id' => $userId,
                'message' => $request->message,
                'response' => $result['response'] ?? '',
                'sql_query' => $result['sql_query'] ?? null,
                'success' => $result['success'] ?? false,
            ]);
        } catch (\Exception $e) {
            // No fallar si no se puede guardar historial
        }

        return response()->json($result);
    }

    /**
     * Obtiene sugerencias de consultas
     */
    public function suggestions(): JsonResponse
    {
        $result = $this->aiService->getSuggestions();
        return response()->json($result);
    }

    /**
     * Obtiene el historial de chat del usuario
     */
    public function history(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $history = AIChatHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'history' => $history
        ]);
    }

    /**
     * Verifica el estado del motor de IA
     */
    public function health(): JsonResponse
    {
        $result = $this->aiService->healthCheck();
        return response()->json($result);
    }

    /**
     * Limpia el historial del usuario
     */
    public function clearHistory(): JsonResponse
    {
        $user = auth()->user();
        
        AIChatHistory::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Historial eliminado'
        ]);
    }

    /**
     * Proxy para predicciones de ventas
     */
    public function predictSales(Request $request): JsonResponse
    {
        try {
            $aiEngineUrl = config('services.ai_engine.url', 'http://76.13.71.180:8001');
            $daysAhead = $request->get('days_ahead', 7);
            
            $response = Http::timeout(30)->get("{$aiEngineUrl}/predict/sales", [
                'days_ahead' => $daysAhead
            ]);
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Error al conectar con el servicio de predicciones'
            ], 500);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proxy para análisis de stock
     */
    public function predictStock(Request $request): JsonResponse
    {
        try {
            $aiEngineUrl = config('services.ai_engine.url', 'http://76.13.71.180:8001');
            
            $response = Http::timeout(30)->get("{$aiEngineUrl}/predict/stock");
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Error al conectar con el servicio de análisis de stock'
            ], 500);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proxy para análisis de tendencias
     */
    public function analyticsTrends(Request $request): JsonResponse
    {
        try {
            $aiEngineUrl = config('services.ai_engine.url', 'http://76.13.71.180:8001');
            
            $response = Http::timeout(30)->get("{$aiEngineUrl}/analytics/trends");
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Error al conectar con el servicio de análisis de tendencias'
            ], 500);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }
}
