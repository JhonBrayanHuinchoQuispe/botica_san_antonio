<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class RucService
{
    /**
     * Consultar datos del RUC usando ApisPeru.
     * Devuelve arreglo normalizado con razon_social, nombre_comercial, direccion, estado, condicion, ruc.
     */
    public function consultarRuc(string $ruc): array
    {
        if (strlen($ruc) !== 11 || !ctype_digit($ruc)) {
            throw new Exception('El RUC debe tener 11 dígitos numéricos');
        }

        $token = env('APISPERU_TOKEN');
        if (empty($token)) {
            throw new Exception('Token de ApisPeru no configurado (APISPERU_TOKEN)');
        }

        $url = "https://dniruc.apisperu.com/api/v1/ruc/{$ruc}?token={$token}";

        try {
            Log::info("Consultando RUC en ApisPeru: {$ruc}");

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'User-Agent' => 'BoticaSistema/1.0'
            ])->get($url);

            $status = $response->status();
            $data = $response->json();

            Log::info("Respuesta ApisPeru RUC status {$status}", [
                'keys' => is_array($data) ? array_keys($data) : null
            ]);

            if (!$response->successful() || !is_array($data)) {
                throw new Exception('Error consultando RUC en ApisPeru');
            }

            $normalizado = $this->normalizarRespuesta($data);
            if (!$normalizado) {
                throw new Exception('Respuesta de ApisPeru no contiene datos válidos de RUC');
            }
            return $normalizado;

        } catch (Exception $e) {
            Log::error('Error consultando RUC: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Normaliza la respuesta de ApisPeru (soporta formatos directos y envueltos en data/success).
     */
    private function normalizarRespuesta(array $data): ?array
    {
        $payload = $data;
        if (isset($data['data']) && is_array($data['data'])) {
            $payload = $data['data'];
        }

        // Campos posibles según variantes de ApisPeru
        $razon = $payload['razonSocial']
            ?? $payload['razon_social']
            ?? $payload['nombre_o_razon_social']
            ?? null;
        $comercial = $payload['nombreComercial']
            ?? $payload['nombre_comercial']
            ?? null;
        $direccion = $payload['direccion'] ?? null;
        $estado = $payload['estado'] ?? null;
        $condicion = $payload['condicion'] ?? null;
        $ruc = $payload['ruc'] ?? null;

        if (!$razon && !$comercial && !$direccion) {
            return null;
        }

        return [
            'ruc' => $ruc,
            'razon_social' => $razon ?? $comercial ?? '',
            'nombre_comercial' => $comercial ?? $razon ?? '',
            'direccion' => $direccion ?? '',
            'estado' => $estado ?? '',
            'condicion' => $condicion ?? '',
            'fuente' => 'ApisPeru'
        ];
    }
}