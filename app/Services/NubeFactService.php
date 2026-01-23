<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Exception;

class NubeFactService
{
    protected string $apiUrl;
    protected string $apiToken;
    protected bool $modoPrueba;

    public function __construct()
    {
        $this->apiUrl = config('nubefact.api_url');
        $this->apiToken = config('nubefact.api_token');
        $this->modoPrueba = (bool) config('nubefact.modo_prueba', true);
    }

    /**
     * Enviar un payload directamente a Nubefact.
     * El payload debe contener 'operacion', tipo, serie, numero y items.
     */
    public function enviar(array $payload): array
    {
        $this->validarConfiguracion();
        $payload = $this->aplicarModoPrueba($payload);
        
        $doRequest = function(array $data){
            return Http::withHeaders([
                'Authorization' => 'Token token="' . $this->apiToken . '"',
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl, $data);
        };

        $response = $doRequest($payload);
        // Backoff automático para errores temporales
        $attempt = 1;
        while ($response->failed() && $attempt <= 3) {
            $status = $response->status();
            $isTransient = ($status >= 500) || ($status === 429) || ($status === 408);
            if (!$isTransient) break;
            $sleep = 2 ** $attempt; // 2, 4, 8 segundos
            try { sleep($sleep); } catch (\Throwable $e) {}
            $response = $doRequest($payload);
            $attempt++;
        }
        
        if ($response->failed()) {
            $text = (string)($response->body() ?? '');
            $errors = $response->json('errors') ?? $response->json() ?? $text;
            $msg = is_string($errors) ? $errors : json_encode($errors);

            $shouldRetrySerie = (stripos($msg, 'Serie') !== false);
            if ($shouldRetrySerie) {
                $candidatesEnv = env('NUBEFACT_SERIES_FALLBACK', 'B001,B101,B201');
                $candidates = array_values(array_filter(array_map('trim', explode(',', $candidatesEnv))));
                foreach ($candidates as $serie) {
                    $payload['serie'] = $serie;
                    $retry = $doRequest($payload);
                    if ($retry->successful()) {
                        return ['success' => true, 'data' => $retry->json()];
                    }
                }
                if ($this->modoPrueba && env('NUBEFACT_SIMULAR_SI_FALLA_SERIE', true)) {
                    Log::warning('Nubefact: simulando respuesta por modo prueba (serie no habilitada)');
                    return ['success' => true, 'data' => $this->simularRespuesta($payload)];
                }
            }

            Log::error('Nubefact envío fallido: ' . $msg);
            throw new Exception($msg);
        }

        return ['success' => true, 'data' => $response->json()];
    }

    private function simularRespuesta(array $payload): array
    {
        $tipo = (int) ($payload['tipo_de_comprobante'] ?? 2);
        $serie = (string) ($payload['serie'] ?? 'B001');
        $numero = (int) (is_numeric($payload['numero'] ?? '') ? $payload['numero'] : 1);
        $hash = base64_encode(substr(sha1(($payload['codigo_unico'] ?? uniqid('SIM-'))), 0, 24));
        $qrCadena = sprintf('%s | %02d | %s | %06d | %s', config('sunat.empresa.ruc'), $tipo === 1 ? 1 : 03, $serie, $numero, now()->format('Y-m-d'));
        return [
            'tipo_de_comprobante' => $tipo,
            'serie' => $serie,
            'numero' => $numero,
            'enlace' => 'https://www.nubefact.com/cpe/simulacion/' . substr($hash, 0, 16),
            'enlace_del_pdf' => null,
            'enlace_del_xml' => null,
            'enlace_del_cdr' => null,
            'aceptada_por_sunat' => false,
            'sunat_description' => 'Simulación local en modo prueba: documento no enviado a SUNAT',
            'sunat_note' => null,
            'sunat_responsecode' => '0',
            'sunat_soap_error' => '',
            'cadena_para_codigo_qr' => $qrCadena,
            'codigo_hash' => $hash,
        ];
    }

    /**
     * Construye una boleta de ejemplo para pruebas rápidas.
     */
    public function construirBoletaEjemplo(float $importeGravado = 10.0): array
    {
        $igvPorcentaje = (float) config('nubefact.igv', 18);
        $igv = round($importeGravado * $igvPorcentaje / 100, 2);
        $total = round($importeGravado + $igv, 2);

        $serie = config('nubefact.serie_boleta', '');
        $payload = [
            'operacion' => 'generar_comprobante',
            'tipo_de_comprobante' => 2,
            'serie' => $serie,
            'numero' => '', // dejar vacío para que Nubefact asigne correlativo
            // Transacción a SUNAT: 1 = en tiempo real; en pruebas también se usa 1
            'sunat_transaction' => 1,
            'cliente_tipo_de_documento' => '1', // 1=DNI
            'cliente_numero_de_documento' => '99999999',
            'cliente_denominacion' => 'CLIENTE GENERAL',
            'cliente_direccion' => 'LIMA',
            'cliente_email' => '',
            'fecha_de_emision' => now()->format('Y-m-d'),
            'moneda' => '1', // 1=Soles
            'tipo_de_cambio' => '',
            'porcentaje_de_igv' => $igvPorcentaje,
            'total_gravada' => $importeGravado,
            'total_igv' => $igv,
            'total' => $total,
            'items' => [
                [
                    'unidad_de_medida' => 'NIU',
                    'codigo' => 'TEST-001',
                    'descripcion' => 'Prueba de integración Nubefact',
                    'cantidad' => 1,
                    'valor_unitario' => $importeGravado,
                    'precio_unitario' => $total,
                    'descuento' => 0,
                    'subtotal' => $importeGravado,
                    'tipo_de_igv' => '1', // 1=Gravado (IGV)
                    'igv' => $igv,
                    'total' => $total,
                ]
            ],
            'enviar_automaticamente_a_la_sunat' => true,
            'enviar_automaticamente_al_cliente' => false,
            'codigo_unico' => 'PRUEBA-' . now()->format('YmdHis'),
        ];
        if (!$serie) { unset($payload['serie']); }
        return $payload;
    }

    private function validarConfiguracion(): void
    {
        if (empty($this->apiUrl) || empty($this->apiToken)) {
            throw new Exception('Nubefact no configurado: faltan API_URL o API_TOKEN');
        }
    }

    private function aplicarModoPrueba(array $payload): array
    {
        // En Nubefact, el modo prueba depende de la empresa en el panel.
        // Forzamos flags seguros para no notificar al cliente en pruebas.
        if ($this->modoPrueba) {
            $payload['enviar_automaticamente_al_cliente'] = false;
        }
        return $payload;
    }

    /**
     * Construye el payload de boleta desde una Venta del POS.
     * $formatoPdf: 'A4' para boleta A4, 'ticket' para formato térmico Nubefact.
     */
    public function buildBoletaPayloadFromVenta(\App\Models\PuntoVenta\Venta $venta, string $formatoPdf = 'A4'): array
    {
        $empresa = [
            'ruc' => config('sunat.empresa.ruc'),
            'razon_social' => config('sunat.empresa.razon_social'),
            'direccion' => config('sunat.empresa.direccion'),
        ];

        $modoPrueba = (bool) config('nubefact.modo_prueba');
        $porcentajeIGV = (float) config('nubefact.igv', 18);
        $serieBoleta = config('nubefact.serie_boleta', '');

        $venta->loadMissing(['detalles.producto', 'cliente']);

        // Datos del cliente: por defecto cliente general si no hay DNI
        $clienteTipo = '1';
        $clienteNumero = '99999999';
        $clienteNombre = 'CLIENTE GENERAL';
        $clienteDireccion = '-';

        if ($venta->cliente) {
            $clienteTipo = '1'; // DNI en POS
            $clienteNumero = $venta->cliente->dni ?: '99999999';
            $clienteNombre = trim(($venta->cliente->nombres . ' ' . ($venta->cliente->apellido_paterno ?? '') . ' ' . ($venta->cliente->apellido_materno ?? ''))) ?: 'CLIENTE GENERAL';
            $clienteDireccion = $venta->cliente->direccion ?? '-';
        }

        // Construir items Nubefact
        $items = [];
        foreach ($venta->detalles as $detalle) {
            $producto = $detalle->producto;
            $cantidad = (int) $detalle->cantidad;
            $precioUnitario = (float) $detalle->precio_unitario; // se asume precio con IGV cuando IGV habilitado

            // Si el porcentaje IGV es mayor a 0, declaramos como gravado
            // y separamos base imponible.
            $esGravado = ($porcentajeIGV > 0);
            $valorUnitario = $esGravado ? round($precioUnitario / (1 + $porcentajeIGV / 100), 6) : $precioUnitario;
            $tipoIGV = $esGravado ? '1' : '20';

            // Construir descripción del producto
            $descParts = array_filter([
                $producto->nombre,
                $producto->concentracion ?? null,
            ]);
            
            // Si hay presentación vendida, agregarla
            if (!empty($detalle->presentacion_nombre)) {
                $descParts[] = $detalle->presentacion_nombre;
            } elseif (!empty($producto->presentacion)) {
                $descParts[] = $producto->presentacion;
            }
            
            $descripcion = trim(implode(' ', $descParts));

            $items[] = [
                'unidad_de_medida' => 'NIU',
                'codigo' => $producto->codigo_barras ?? (string) $producto->id,
                'descripcion' => $descripcion ?: ($producto->nombre ?? 'ITEM'),
                'cantidad' => $cantidad,
                'valor_unitario' => $valorUnitario,
                'precio_unitario' => $esGravado ? $precioUnitario : $valorUnitario,
                'descuento' => 0,
                'subtotal' => round($valorUnitario * $cantidad, 2),
                'tipo_de_igv' => $tipoIGV,
                'igv' => $esGravado ? round($valorUnitario * ($porcentajeIGV / 100) * $cantidad, 2) : 0,
                'total' => $esGravado ? round($precioUnitario * $cantidad, 2) : round($valorUnitario * $cantidad, 2),
                'anticipo_regularizacion' => false,
                'anticipo_documento_serie' => null,
                'anticipo_documento_numero' => null,
                'codigo_producto_sunat' => null
            ];
        }

        $esGravadoGlobal = ($porcentajeIGV > 0);
        $igvTotal = $esGravadoGlobal ? round(($porcentajeIGV / 100) * array_sum(array_map(function ($it) { return $it['subtotal']; }, $items)), 2) : 0;
        $totalValorVenta = $esGravadoGlobal ? round($venta->subtotal / (1 + $porcentajeIGV / 100), 2) : round($venta->subtotal, 2);

        $payload = [
            'operacion' => 'generar_comprobante',
            'tipo_de_comprobante' => 2,
            'serie' => $serieBoleta,
            'numero' => '', // dejar que Nubefact asigne correlativo
            'sunat_transaction' => 1,
            'cliente_tipo_de_documento' => $clienteTipo,
            'cliente_numero_de_documento' => $clienteNumero,
            'cliente_denominacion' => $clienteNombre,
            'cliente_direccion' => $clienteDireccion,
            'cliente_email' => null,
            'fecha_de_emision' => now()->format('Y-m-d'),
            'fecha_de_vencimiento' => now()->format('Y-m-d'),
            'moneda' => 1,
            'porcentaje_de_igv' => $porcentajeIGV,
            'total_descuento' => round($venta->descuento_monto ?? 0, 2),
            'total_anticipo' => 0,
            'total_gravada' => $esGravadoGlobal ? $totalValorVenta : 0,
            'total_inafecta' => 0,
            'total_exonerada' => $esGravadoGlobal ? 0 : round($venta->subtotal, 2),
            'total_igv' => $igvTotal,
            'total_gratuita' => 0,
            'total_otros_cargos' => 0,
            'total' => round($venta->total, 2),
            'items' => $items,
            'tipo_de_nota_de_credito' => null,
            'tipo_de_nota_de_debito' => null,
            'enviar_automaticamente_a_la_sunat' => true,
            'enviar_automaticamente_al_cliente' => false,
            'codigo_unico' => 'VENTA-' . $venta->id . '-' . now()->format('YmdHis'),
            'formato_de_pdf' => strtoupper($formatoPdf),
            'modo_prueba' => $modoPrueba,
            'datos_empresa' => $empresa,
        ];
        if (!$serieBoleta) { unset($payload['serie']); }
        return $payload;
    }

    /**
     * Descarga un archivo Nubefact y lo guarda localmente.
     * Retorna la ruta local guardada o null.
     */
    public function descargarYGuardar(string $url, string $nombreArchivo, string $subdir = 'comprobantes'): ?string
    {
        try {
            if (!$url) return null;
            $response = Http::timeout(30)->get($url);
            if (!$response->successful()) {
                Log::warning('No se pudo descargar archivo Nubefact', ['url' => $url, 'status' => $response->status()]);
                return null;
            }
            $dir = storage_path('app/' . $subdir);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $ruta = $dir . DIRECTORY_SEPARATOR . $nombreArchivo;
            file_put_contents($ruta, $response->body());
            return $ruta;
        } catch (\Throwable $e) {
            Log::error('Error guardando archivo Nubefact', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }
}
