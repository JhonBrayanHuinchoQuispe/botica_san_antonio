<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $key = 'global', int $maxAttempts = 60, int $decayMinutes = 1): HttpResponse
    {
        $rateLimitKey = $this->resolveRequestSignature($request, $key);
        
        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            return $this->buildTooManyAttemptsResponse($rateLimitKey, $maxAttempts);
        }
        
        RateLimiter::hit($rateLimitKey, $decayMinutes * 60);
        
        $response = $next($request);
        
        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($rateLimitKey, $maxAttempts)
        );
    }
    
    /**
     * Resolve the request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request, string $key): string
    {
        $userId = $request->user()?->id ?? 'guest';
        $ip = $request->ip();
        
        return match($key) {
            'login' => "login:{$ip}",
            'api' => "api:{$userId}:{$ip}",
            'search' => "search:{$userId}:{$ip}",
            'upload' => "upload:{$userId}:{$ip}",
            default => "global:{$ip}"
        };
    }
    
    /**
     * Create a 'too many attempts' response.
     */
    protected function buildTooManyAttemptsResponse(string $key, int $maxAttempts): HttpResponse
    {
        $retryAfter = RateLimiter::availableIn($key);
        
        $message = "Demasiados intentos. Intenta nuevamente en {$retryAfter} segundos.";
        
        return Response::json([
            'error' => 'Too Many Attempts',
            'message' => $message,
            'retry_after' => $retryAfter
        ], 429, [
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
        ]);
    }
    
    /**
     * Add rate limit headers to the response.
     */
    protected function addHeaders(HttpResponse $response, int $maxAttempts, int $remainingAttempts): HttpResponse
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $remainingAttempts),
        ]);
        
        return $response;
    }
    
    /**
     * Calculate the number of remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $maxAttempts - RateLimiter::attempts($key);
    }
}