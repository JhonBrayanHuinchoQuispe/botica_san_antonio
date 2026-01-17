<?php

namespace App\Http\Controllers\IA;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Producto;
use App\Models\PuntoVenta\VentaDetalle;
    use Illuminate\Support\Facades\DB;
    use Carbon\Carbon;

    class ChatLibreController extends Controller
    {
        // Usamos la IP de red local para asegurar conectividad desde Apache/XAMPP y evitar bloqueos de localhost/loopback
        private $pythonApiUrl = 'http://127.0.0.1:8003';
        private $apiToken = 'gemini'; // Token definido en ia/.env

        public function chat(Request $request)
        {
            $texto = trim($request->input('query', $request->input('q', '')));
            if ($texto === '') {
                return response()->json(['success' => true, 'data' => ['text' => 'Escribe tu consulta en español.']]);
            }
            
            $bajo = mb_strtolower($texto);

            // Detectar intención de predicción
            if (str_contains($bajo, 'predic') || str_contains($bajo, 'pronostic') || str_contains($bajo, 'ventas futuras') || str_contains($bajo, 'proximo mes') || str_contains($bajo, 'grafico') || str_contains($bajo, 'gráfico') || str_contains($bajo, 'graico')) {
                return $this->handlePrediction($texto);
            }

            // Por defecto: Consulta General (NL-SQL a Python)
            return $this->handleGeneralQuery($texto);
        }

        private function handleGeneralQuery($query)
        {
            try {
                // Usar cURL nativo para consultas generales también
                $ch = curl_init();
                $url = "{$this->pythonApiUrl}/rag/nl-sql?" . http_build_query(['q' => $query]);
                
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: ' . $this->apiToken
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

                $responseBody = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError) {
                    Log::error('[IA] Raw CURL Error (General)', ['msg' => $curlError]);
                    return response()->json(['success' => true, 'data' => ['text' => "Error de conexión: $curlError"]]);
                }

                if ($httpCode >= 200 && $httpCode < 300) {
                    $data = json_decode($responseBody, true);
                    return response()->json(['success' => true, 'data' => $data]);
                }

                Log::error('[IA] Error response from Python API', ['status' => $httpCode, 'body' => $responseBody]);
                return response()->json(['success' => true, 'data' => ['text' => 'Lo siento, tuve un problema conectando con el cerebro de la IA.']]);

            } catch (\Exception $e) {
                Log::error('[IA] Exception calling Python API', ['msg' => $e->getMessage()]);
                return response()->json(['success' => true, 'data' => ['text' => 'Error de conexión con el servicio de IA. Asegúrate de que el servidor Python esté corriendo.']]);
            }
        }

        private function handlePrediction($query)
        {
            try {
                // 1. Identificar producto (búsqueda simple en Laravel para obtener ID)
                // Extraer palabras clave ignorando las comunes de predicción
                $clean = str_replace(['prediccion', 'predicción', 'pronostico', 'pronóstico', 'ventas', 'de', 'para', 'el', 'la', 'los', 'las', 'un', 'una', 'futuras', 'proximo', 'mes'], '', mb_strtolower($query));
                $words = array_filter(explode(' ', $clean), fn($w) => strlen($w) > 3);
                
                $producto = null;
                if (!empty($words)) {
                    $search = implode('%', $words);
                    $producto = Producto::where('nombre', 'LIKE', "%{$search}%")
                                ->orWhere('codigo_barras', 'LIKE', "%{$search}%")
                                ->first();
                }

                // Si no se encuentra producto específico, usar el más vendido del último mes como ejemplo
                if (!$producto) {
                    // Intentar encontrar el más vendido si no se especificó nada
                    $top = VentaDetalle::select('producto_id', DB::raw('SUM(cantidad) as total'))
                            ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
                            ->where('ventas.fecha_venta', '>=', Carbon::now()->subDays(30))
                            ->groupBy('producto_id')
                            ->orderByDesc('total')
                            ->first();
                    if ($top) {
                        $producto = Producto::find($top->producto_id);
                    }
                }

                if (!$producto) {
                    return response()->json(['success' => true, 'data' => ['text' => 'No encontré datos de productos para realizar una predicción.']]);
                }

                // 2. Obtener historial de ventas diario (últimos 90 días) para enviar a Python
                // Aunque Python tiene acceso a la BD, le enviamos la serie temporal procesada para facilitar el trabajo de scikit-learn
                $historial = VentaDetalle::select(
                                DB::raw('DATE(ventas.fecha_venta) as fecha'),
                                DB::raw('SUM(venta_detalles.cantidad) as unidades')
                            )
                            ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
                            ->where('venta_detalles.producto_id', $producto->id)
                            ->where('ventas.estado', 'completada')
                            ->where('ventas.fecha_venta', '>=', Carbon::now()->subDays(90))
                            ->groupBy('fecha')
                            ->orderBy('fecha', 'asc')
                            ->get();

                $series = $historial->map(function($item) {
                    return [
                        'date' => $item->fecha,
                        'units' => (int) $item->unidades
                    ];
                })->values()->toArray();

                if (count($series) < 5) {
                    return response()->json(['success' => true, 'data' => ['text' => "No hay suficientes datos históricos (mínimo 5 días de ventas) para predecir ventas de **{$producto->nombre}**."]]);
                }

                // Definir payload antes de usarlo
                $payload = [
                    'series' => $series,
                    'product_id' => $producto->id,
                    'q' => $producto->nombre,
                    'horizon' => 7 // 7 días o periodos
                ];

                // 3. Llamar a Python endpoint /predict con cURL nativo
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "{$this->pythonApiUrl}/predict");
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: ' . $this->apiToken
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                // Deshabilitar proxy explícitamente por si Apache tiene uno configurado
                curl_setopt($ch, CURLOPT_PROXY, '');
                curl_setopt($ch, CURLOPT_NOPROXY, '*');

                $responseBody = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                // FALLBACK: Si cURL falla, intentar con file_get_contents (streams)
                if ($curlError) {
                    Log::warning('[IA] cURL failed, trying file_get_contents fallback', ['msg' => $curlError]);
                    
                    try {
                        $opts = [
                            'http' => [
                                'method'  => 'POST',
                                'header'  => "Content-Type: application/json\r\n" .
                                            "Authorization: {$this->apiToken}\r\n",
                                'content' => json_encode($payload),
                                'timeout' => 30,
                                'ignore_errors' => true
                            ],
                            'ssl' => [
                                "verify_peer" => false,
                                "verify_peer_name" => false,
                            ]
                        ];
                        
                        $context  = stream_context_create($opts);
                        $responseBody = file_get_contents("{$this->pythonApiUrl}/predict", false, $context);
                        
                        if ($responseBody !== false) {
                            // Si funciona el fallback, limpiamos el error y simulamos éxito 200
                            $curlError = null; 
                            $httpCode = 200;
                            // Verificar headers para status real si es necesario, pero file_get_contents devuelve el cuerpo
                        } else {
                            // Obtener error del último error de PHP
                            $e = error_get_last();
                            $curlError .= " | Fallback Error: " . ($e['message'] ?? 'Unknown');
                        }
                    } catch (\Exception $ex) {
                        $curlError .= " | Fallback Exception: " . $ex->getMessage();
                    }
                }

                if ($curlError) {
                    Log::error('[IA] All connection methods failed (Prediction)', ['msg' => $curlError]);
                    return response()->json(['success' => true, 'data' => ['text' => "Error de conexión persistente: $curlError. Verifica que el servidor Python esté corriendo en el puerto 8001."]]);
                }

                if ($httpCode >= 200 && $httpCode < 300) {
                    $pred = json_decode($responseBody, true);
                    $text = "Análisis para **{$producto->nombre}**:\n" . ($pred['text'] ?? '');
                    
                    return response()->json([
                        'success' => true, 
                        'data' => [
                            'text' => $text,
                            'plot_png_base64' => $pred['plot_png_base64'] ?? null 
                        ]
                    ]);
                }

                Log::error('[IA] Error response from Python API (Prediction)', ['status' => $httpCode, 'body' => $responseBody]);
                return response()->json(['success' => true, 'data' => ['text' => 'No se pudo generar la predicción desde el servicio IA.']]);

            } catch (\Exception $e) {
                Log::error('[IA] Prediction Error', ['msg' => $e->getMessage()]);
                return response()->json(['success' => true, 'data' => ['text' => 'Ocurrió un error al procesar la predicción: ' . $e->getMessage()]]);
            }
        }
    }
