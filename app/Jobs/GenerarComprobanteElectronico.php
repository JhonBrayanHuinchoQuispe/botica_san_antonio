<?php

namespace App\Jobs;

use App\Models\PuntoVenta\Venta;
use App\Services\Sunat\FacturacionElectronicaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerarComprobanteElectronico implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $afterCommit = true;

    protected int $ventaId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $ventaId)
    {
        $this->ventaId = $ventaId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $venta = Venta::find($this->ventaId);
        if (!$venta) {
            Log::warning('Venta no encontrada para comprobante electrónico', [
                'venta_id' => $this->ventaId,
            ]);
            return;
        }

        try {
            if (!in_array($venta->tipo_comprobante, ['boleta', 'factura'])) {
                Log::info('No se requiere comprobante electrónico', [
                    'tipo_comprobante' => $venta->tipo_comprobante,
                    'venta_id' => $venta->id,
                ]);
                return;
            }

            if (!extension_loaded('soap')) {
                Log::warning('Extensión SOAP no disponible, omitiendo facturación electrónica', [
                    'venta_id' => $venta->id,
                ]);
                return;
            }

            $facturacionService = new FacturacionElectronicaService();

            Log::info('Generando comprobante electrónico (Job)', [
                'venta_id' => $venta->id,
                'tipo_comprobante' => $venta->tipo_comprobante,
            ]);

            $resultado = $facturacionService->generarBoleta($venta);

            if (!($resultado['success'] ?? false)) {
                Log::error('Error al generar comprobante electrónico (Job)', [
                    'venta_id' => $venta->id,
                    'error' => $resultado['message'] ?? 'Error desconocido',
                ]);
                return;
            }

            $venta->update([
                'serie_electronica' => $resultado['serie'] ?? null,
                'numero_electronico' => $resultado['numero'] ?? null,
                'hash_cpe' => $resultado['hash'] ?? null,
                'xml_path' => $resultado['xml_path'] ?? null,
                'pdf_path' => $resultado['pdf_path'] ?? null,
            ]);

            Log::info('Comprobante electrónico generado exitosamente (Job)', [
                'venta_id' => $venta->id,
                'serie_numero' => ($resultado['serie'] ?? '') . '-' . ($resultado['numero'] ?? ''),
            ]);
        } catch (\Exception $e) {
            Log::error('Excepción al generar comprobante electrónico (Job)', [
                'venta_id' => $venta->id,
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
            ]);
        }
    }
}