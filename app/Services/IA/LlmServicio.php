<?php

namespace App\Services\IA;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Services\IA\PrediccionServicio;

class LlmServicio
{
    public function responderLibre(string $texto, array $contexto = []): array
    {
        $proveedor = env('IA_LLM_PROVEEDOR', 'openai');
        if ($proveedor === 'openai') {
            return $this->openai($texto, $contexto);
        }
        if ($proveedor === 'gemini') {
            return $this->gemini($texto, $contexto);
        }
        if ($proveedor === 'azure' || $proveedor === 'azure_openai') {
            return $this->azureOpenAI($texto, $contexto);
        }
        // otros proveedores se pueden agregar luego
        return ['text' => 'Servicio LLM no configurado'];
    }

    private function openai(string $texto, array $contexto): array
    {
        $apiKey = env('IA_LLM_API_KEY');
        $base = rtrim(env('IA_LLM_BASE_URL', 'https://api.openai.com/v1'), '/');
        $modelo = env('IA_LLM_MODELO', 'gpt-4o-mini');
        if (!$apiKey) return ['text' => 'Falta configurar la clave de OpenAI'];

        $ttl = (int) env('IA_LLM_CACHE_TTL_MINUTES', 3);
        $cacheKey = 'ia:llm:'.md5($modelo.'|'.$texto.'|'.json_encode($contexto));
        if ($ttl > 0) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) return $cached;
        }

        $messages = [
            ['role' => 'system', 'content' => 'Eres Asistente IA de Botica San Antonio. Responde en español, claro, sólo texto, sin gráficos. Usa el contexto si está disponible.'],
        ];
        if (!empty($contexto)) {
            $messages[] = ['role' => 'system', 'content' => 'Contexto: '.json_encode($contexto, JSON_UNESCAPED_UNICODE)];
        }
        $messages[] = ['role' => 'user', 'content' => $texto];

        $payload = [
            'model' => $modelo,
            'messages' => $messages,
            'temperature' => 0.3,
            'max_tokens' => 400,
        ];

        $res = Http::withToken($apiKey)
            ->acceptJson()
            ->post($base.'/chat/completions', $payload);

        if (!$res->ok()) {
            $code = $res->status();
            if ($code === 429) {
                $fallback = $this->fallback($texto);
                return $fallback;
            }
            return ['text' => 'No disponible. Código '.$code];
        }

        $data = $res->json();
        $content = $data['choices'][0]['message']['content'] ?? 'Sin respuesta';
        $out = ['text' => $content];
        if ($ttl > 0) Cache::put($cacheKey, $out, now()->addMinutes($ttl));
        return $out;
    }

    private function fallback(string $texto): array
    {
        $t = mb_strtolower($texto);
        $pred = new PrediccionServicio();
        if (str_contains($t, 'predic') || str_contains($t, 'venta') || str_contains($t, 'vendido')) {
            $r = $pred->ventas('7d');
            return ['text' => ($r['text'] ?? 'Predicción estimada disponible próximamente.')];
        }
        return ['text' => 'Límite de uso alcanzado temporalmente. Intenta de nuevo en unos minutos.'];
    }

    private function gemini(string $texto, array $contexto): array
    {
        $apiKey = env('IA_LLM_API_KEY');
        $base = rtrim(env('IA_LLM_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $modelo = env('IA_LLM_MODELO', 'gemini-2.5-flash');
        if (!$apiKey) return ['text' => 'Falta configurar la clave de Gemini'];

        $ttl = (int) env('IA_LLM_CACHE_TTL_MINUTES', 3);
        $cacheKey = 'ia:llm:gemini:'.md5($modelo.'|'.$texto.'|'.json_encode($contexto));
        if ($ttl > 0) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) return $cached;
        }

        $ctxText = !empty($contexto) ? ('Contexto: '.json_encode($contexto, JSON_UNESCAPED_UNICODE)) : '';
        $tLower = mb_strtolower($texto);
        $breve = false;
      $systemText = 'Eres Asistente IA de Botica San Antonio. Responde en español, claro, sólo texto SIN formato Markdown (sin **, *, listas Markdown). Usa texto plano y listas con guiones (•) o numeradas, máximo 6 elementos. Cierra siempre con punto final. No digas que no tienes acceso a información interna; cuando falte contexto, entrega pautas generales útiles.' . ($ctxText ? (' ' . $ctxText) : '');

        $payload = [
            'systemInstruction' => ['role' => 'system', 'parts' => [['text' => $systemText]]],
            'contents' => [[ 'role' => 'user', 'parts' => [['text' => $texto]] ]],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 640,
                'candidateCount' => 1,
                'responseMimeType' => 'text/plain',
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
            ],
        ];
        // Intentos con diferentes bases/modelos para evitar 404 por versión o alias
        // Probar combinaciones más comunes primero y cubrir ambos modelos
        $bases = array_values(array_unique([$base, 'https://generativelanguage.googleapis.com/v1beta', 'https://generativelanguage.googleapis.com/v1']));
        $modelos = array_values(array_unique([$modelo, 'gemini-2.5-flash', 'gemini-2.5-flash-latest', 'gemini-1.5-flash', 'gemini-1.5-flash-latest']));
        $last = ['status' => null, 'endpoint' => null];
        foreach ($bases as $b) {
            foreach ($modelos as $m) {
                // Método 1: API key por query
                $url = rtrim($b, '/') . '/models/' . $m . ':generateContent?key=' . $apiKey;
                try {
                    $res = Http::acceptJson()->post($url, $payload);
                } catch (\Throwable $e) {
                    $last = ['status' => 0, 'endpoint' => $url, 'error' => $e->getMessage()];
                    $res = null;
                }
                if ($res && $res->ok()) {
                    $data = $res->json();
                    $text = null;
                    $parts = $data['candidates'][0]['content']['parts'] ?? [];
                    if (is_array($parts) && count($parts) > 0) {
                        $allTexts = [];
                        foreach ($parts as $p) {
                            if (is_array($p) && isset($p['text']) && $p['text'] !== '') { $allTexts[] = $p['text']; }
                        }
                        if (!empty($allTexts)) { $text = implode("\n", $allTexts); }
                    }
                    $finish = $data['candidates'][0]['finishReason'] ?? null;
                    if (!$text && ($finish === 'SAFETY' || $finish === 'BLOCKLIST')) { $text = 'La solicitud fue bloqueada por seguridad.'; }
                    if (!$text && isset($data['promptFeedback']['blockReason'])) { $text = 'La solicitud fue bloqueada por seguridad.'; }
                    if (!$text && $finish === 'MAX_TOKENS') {
                $shortPayload = [
                            'contents' => [[ 'parts' => [['text' => 'Responde en un párrafo claro (80–120 palabras).\n'. $texto]] ]],
                            'generationConfig' => [ 'temperature' => 0.3, 'maxOutputTokens' => 300, 'candidateCount' => 1, 'responseMimeType' => 'text/plain' ],
                            'safetySettings' => [
                                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
                            ],
                        ];
                        $re = Http::acceptJson()->post($url, $shortPayload);
                        if ($re->ok()) {
                            $rd = $re->json();
                            $rp = $rd['candidates'][0]['content']['parts'] ?? [];
                            if (is_array($rp)) {
                                foreach ($rp as $p) { if (isset($p['text']) && $p['text'] !== '') { $text = $p['text']; break; } }
                            }
                            $finish = $rd['candidates'][0]['finishReason'] ?? $finish;
                        }
                    }
                    if (!$text) {
                        $simplePayload = [
                            'contents' => [[ 'parts' => [['text' => $texto]] ]],
                            'generationConfig' => [ 'temperature' => 0.25, 'maxOutputTokens' => 640, 'candidateCount' => 1, 'responseMimeType' => 'text/plain' ],
                        ];
                        try {
                            $reSimple = Http::acceptJson()->post($url, $simplePayload);
                            if ($reSimple->ok()) {
                                $ds = $reSimple->json();
                                $ps = $ds['candidates'][0]['content']['parts'] ?? [];
                                foreach ($ps as $p) { if (isset($p['text']) && $p['text'] !== '') { $text = $p['text']; break; } }
                                $finish = $ds['candidates'][0]['finishReason'] ?? $finish;
                            }
                        } catch (\Throwable $e) {}
                    }
                    if (!$text) { $text = 'Sin respuesta'; }
                    $out = ['text' => $text];
                    if (env('APP_DEBUG', false)) { $out['debug'] = ['provider' => 'gemini', 'base' => $b, 'model' => $m, 'endpoint' => $url, 'finishReason' => $finish]; }
                    if ($ttl > 0 && $text !== 'Sin respuesta' && (!$finish || $finish === 'STOP')) Cache::put($cacheKey, $out, now()->addMinutes($ttl));
                    return $out;
                }
                if ($res) {
                    $last = ['status' => $res->status(), 'endpoint' => $url];
                    if ($res->status() === 429) return $this->fallback($texto);
                }
                // Intento alternativo: payload simple sin systemInstruction
                $simplePayload = [
                    'contents' => [[ 'parts' => [['text' => $texto]] ]],
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 640,
                        'candidateCount' => 1,
                        'responseMimeType' => 'text/plain',
                    ],
                    'safetySettings' => [
                        ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
                    ],
                ];
                if ($res && !$res->ok()) {
                    try {
                        $resAlt = Http::acceptJson()->post($url, $simplePayload);
                        if ($resAlt->ok()) {
                            $dataAlt = $resAlt->json();
                            $partsAlt = $dataAlt['candidates'][0]['content']['parts'] ?? [];
                            $textAlt = null;
                            if (is_array($partsAlt)) {
                                foreach ($partsAlt as $p) { if (isset($p['text']) && $p['text'] !== '') { $textAlt = $p['text']; break; } }
                            }
                            if ($textAlt) {
                                $finishAlt = $dataAlt['candidates'][0]['finishReason'] ?? null;
                                $out = ['text' => $textAlt];
                                if (env('APP_DEBUG', false)) { $out['debug'] = ['provider' => 'gemini', 'base' => $b, 'model' => $m, 'endpoint' => $url, 'finishReason' => $finishAlt]; }
                                if ($ttl > 0 && (!$finishAlt || $finishAlt === 'STOP')) Cache::put($cacheKey, $out, now()->addMinutes($ttl));
                                return $out;
                            }
                        }
                    } catch (\Throwable $e) {
                        $last = ['status' => 0, 'endpoint' => $url, 'error' => $e->getMessage()];
                    }
                }

                // Método 2: API key por header
                $url2 = rtrim($b, '/') . '/models/' . $m . ':generateContent';
                try {
                    $res2 = Http::acceptJson()->withHeaders(['x-goog-api-key' => $apiKey])->post($url2, $payload);
                } catch (\Throwable $e) {
                    $last = ['status' => 0, 'endpoint' => $url2, 'error' => $e->getMessage()];
                    $res2 = null;
                }
                if ($res2 && $res2->ok()) {
                    $data = $res2->json();
                    $text = null;
                    $parts = $data['candidates'][0]['content']['parts'] ?? [];
                    if (is_array($parts) && count($parts) > 0) {
                        $allTexts = [];
                        foreach ($parts as $p) {
                            if (is_array($p) && isset($p['text']) && $p['text'] !== '') { $allTexts[] = $p['text']; }
                        }
                        if (!empty($allTexts)) { $text = implode("\n", $allTexts); }
                    }
                    $finish = $data['candidates'][0]['finishReason'] ?? null;
                    if (!$text && ($finish === 'SAFETY' || $finish === 'BLOCKLIST')) { $text = 'La solicitud fue bloqueada por seguridad.'; }
                    if (!$text && isset($data['promptFeedback']['blockReason'])) { $text = 'La solicitud fue bloqueada por seguridad.'; }
                    if (!$text && $finish === 'MAX_TOKENS') {
                    $shortPayload = [
                            'contents' => [[ 'parts' => [['text' => 'Responde en un párrafo claro (80–120 palabras).\n'. $texto]] ]],
                            'generationConfig' => [ 'temperature' => 0.3, 'maxOutputTokens' => 300, 'candidateCount' => 1, 'responseMimeType' => 'text/plain' ],
                            'safetySettings' => [
                                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
                            ],
                        ];
                        $re2 = Http::acceptJson()->withHeaders(['x-goog-api-key' => $apiKey])->post($url2, $shortPayload);
                        if ($re2->ok()) {
                            $rd2 = $re2->json();
                            $rp2 = $rd2['candidates'][0]['content']['parts'] ?? [];
                            if (is_array($rp2)) {
                                foreach ($rp2 as $p) { if (isset($p['text']) && $p['text'] !== '') { $text = $p['text']; break; } }
                            }
                            $finish = $rd2['candidates'][0]['finishReason'] ?? $finish;
                        }
                    }
                    if (!$text) {
                $simplePayload2 = [
                    'contents' => [[ 'parts' => [['text' => $texto]] ]],
                    'generationConfig' => [ 'temperature' => 0.25, 'maxOutputTokens' => 640, 'candidateCount' => 1, 'responseMimeType' => 'text/plain' ],
                    'safetySettings' => [
                        ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
                    ],
                ];
                        try {
                            $re2Simple = Http::acceptJson()->withHeaders(['x-goog-api-key' => $apiKey])->post($url2, $simplePayload2);
                            if ($re2Simple->ok()) {
                                $ds2 = $re2Simple->json();
                                $ps2 = $ds2['candidates'][0]['content']['parts'] ?? [];
                                foreach ($ps2 as $p) { if (isset($p['text']) && $p['text'] !== '') { $text = $p['text']; break; } }
                                $finish = $ds2['candidates'][0]['finishReason'] ?? $finish;
                            }
                        } catch (\Throwable $e) {}
                    }
                    if (!$text) { $text = 'Sin respuesta'; }
                    $out = ['text' => $text];
                    if (env('APP_DEBUG', false)) { $out['debug'] = ['provider' => 'gemini', 'base' => $b, 'model' => $m, 'endpoint' => $url2, 'finishReason' => $finish]; }
                    if ($ttl > 0 && $text !== 'Sin respuesta' && (!$finish || $finish === 'STOP')) Cache::put($cacheKey, $out, now()->addMinutes($ttl));
                    return $out;
                }
                if ($res2) {
                    $last = ['status' => $res2->status(), 'endpoint' => $url2];
                    if ($res2->status() === 429) return $this->fallback($texto);
                }
                // Intento alternativo con payload simple
                try {
                    $res2Alt = Http::acceptJson()->withHeaders(['x-goog-api-key' => $apiKey])->post($url2, $simplePayload);
                    if ($res2Alt->ok()) {
                        $dataAlt = $res2Alt->json();
                        $partsAlt = $dataAlt['candidates'][0]['content']['parts'] ?? [];
                        $textAlt = null;
                        if (is_array($partsAlt)) {
                            foreach ($partsAlt as $p) { if (isset($p['text']) && $p['text'] !== '') { $textAlt = $p['text']; break; } }
                        }
                        if ($textAlt) {
                            $finishAlt = $dataAlt['candidates'][0]['finishReason'] ?? null;
                            $out = ['text' => $textAlt];
                            if (env('APP_DEBUG', false)) { $out['debug'] = ['provider' => 'gemini', 'base' => $b, 'model' => $m, 'endpoint' => $url2, 'finishReason' => $finishAlt]; }
                            if ($ttl > 0 && (!$finishAlt || $finishAlt === 'STOP')) Cache::put($cacheKey, $out, now()->addMinutes($ttl));
                            return $out;
                        }
                    }
                } catch (\Throwable $e) {
                    $last = ['status' => 0, 'endpoint' => $url2, 'error' => $e->getMessage()];
                }
            }
        }
        return ['text' => 'No disponible (Gemini). Código '.$last['status'], 'debug' => $last];
    }

    private function azureOpenAI(string $texto, array $contexto): array
    {
        $apiKey = env('IA_LLM_API_KEY');
        $base = rtrim(env('IA_LLM_BASE_URL', ''), '/'); // Ej: https://<recurso>.openai.azure.com
        $deployment = env('IA_LLM_DEPLOYMENT', env('IA_LLM_MODELO', 'gpt-4o-mini')); // nombre del deployment
        $apiVersion = env('IA_LLM_API_VERSION', '2024-08-01-preview');
        if (!$apiKey || !$base || !$deployment) return ['text' => 'Falta configurar Azure OpenAI: endpoint, clave o deployment.'];

        $ttl = (int) env('IA_LLM_CACHE_TTL_MINUTES', 3);
        $cacheKey = 'ia:llm:azure:'.md5($base.'|'.$deployment.'|'.$texto.'|'.json_encode($contexto));
        if ($ttl > 0) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) return $cached;
        }

        $messages = [
            ['role' => 'system', 'content' => 'Eres Asistente IA de Botica San Antonio. Responde en español, claro, sólo texto, sin gráficos.'],
        ];
        if (!empty($contexto)) {
            $messages[] = ['role' => 'system', 'content' => 'Contexto: '.json_encode($contexto, JSON_UNESCAPED_UNICODE)];
        }
        $messages[] = ['role' => 'user', 'content' => $texto];

        $payload = [
            'messages' => $messages,
            'temperature' => 0.3,
            'max_tokens' => 400,
        ];

        $url = $base.'/openai/deployments/'.$deployment.'/chat/completions?api-version='.$apiVersion;
        $res = Http::withHeaders(['api-key' => $apiKey])->acceptJson()->post($url, $payload);
        if (!$res->ok()) {
            $code = $res->status();
            if ($code === 429) {
                return $this->fallback($texto);
            }
            return ['text' => 'No disponible (Azure OpenAI). Código '.$code, 'debug' => ['endpoint' => $url]];
        }
        $data = $res->json();
        $content = $data['choices'][0]['message']['content'] ?? 'Sin respuesta';
        $out = ['text' => $content, 'debug' => ['provider' => 'azure', 'endpoint' => $url]];
        if ($ttl > 0) Cache::put($cacheKey, $out, now()->addMinutes($ttl));
        return $out;
    }
}
