<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Boleta;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Correlative;
use App\Models\PuntoVenta\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DocumentService
{
    protected $greenterService;
    protected $pdfService;

    public function __construct(GreenterService $greenterService, PdfService $pdfService)
    {
        $this->greenterService = $greenterService;
        $this->pdfService = $pdfService;
    }

    /**
     * Crear factura desde venta
     */
    public function crearFacturaDesdeVenta(Venta $venta, array $datosCliente, $companyId = 1, $branchId = 1)
    {
        try {
            DB::beginTransaction();

            // Obtener o crear cliente
            $client = $this->obtenerOCrearCliente($datosCliente, $companyId);

            // Obtener correlativo
            $correlative = $this->obtenerCorrelativo($companyId, $branchId, '01');

            // Crear factura
            $invoice = Invoice::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'client_id' => $client->id,
                'correlative_id' => $correlative->id,
                'serie' => $correlative->serie,
                'numero' => $correlative->getSiguienteNumero(),
                'fecha_emision' => $venta->fecha_venta->format('Y-m-d'),
                'hora_emision' => $venta->fecha_venta->format('H:i:s'),
                'tipo_operacion' => '0101',
                'tipo_documento' => '01',
                'tipo_moneda' => 'PEN',
                'cliente_tipo_documento' => $client->tipo_documento,
                'cliente_numero_documento' => $client->numero_documento,
                'cliente_razon_social' => $client->razon_social,
                'cliente_direccion' => $client->direccion_completa,
                'cliente_email' => $client->email,
                'cliente_telefono' => $client->telefono,
                'total_operaciones_gravadas' => $venta->monto_gravado,
                'total_operaciones_inafectas' => $venta->monto_inafecto,
                'total_operaciones_exoneradas' => $venta->monto_exonerado,
                'total_igv' => $venta->igv,
                'total_impuestos' => $venta->igv,
                'valor_total' => $venta->subtotal,
                'precio_total' => $venta->total,
                'total_valor_venta' => $venta->subtotal,
                'total_precio_venta' => $venta->total,
                'estado' => 'generado'
            ]);

            // Crear detalles de la factura
            $this->crearDetallesFactura($invoice, $venta);

            // Crear leyendas
            $this->crearLeyendasFactura($invoice);

            DB::commit();

            Log::info('Factura creada exitosamente', ['invoice_id' => $invoice->id]);

            return $invoice;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creando factura: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crear boleta desde venta
     */
    public function crearBoletaDesdeVenta(Venta $venta, array $datosCliente = null, $companyId = 1, $branchId = 1)
    {
        try {
            DB::beginTransaction();

            // Para boletas, el cliente puede ser opcional
            $client = null;
            if ($datosCliente && !empty($datosCliente['numero_documento'])) {
                $client = $this->obtenerOCrearCliente($datosCliente, $companyId);
            }

            // Obtener correlativo
            $correlative = $this->obtenerCorrelativo($companyId, $branchId, '03');

            // Crear boleta
            $boleta = Boleta::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'client_id' => $client ? $client->id : null,
                'correlative_id' => $correlative->id,
                'serie' => $correlative->serie,
                'numero' => $correlative->getSiguienteNumero(),
                'fecha_emision' => $venta->fecha_venta->format('Y-m-d'),
                'hora_emision' => $venta->fecha_venta->format('H:i:s'),
                'tipo_operacion' => '0101',
                'tipo_documento' => '03',
                'tipo_moneda' => 'PEN',
                'cliente_tipo_documento' => $client ? $client->tipo_documento : '1',
                'cliente_numero_documento' => $client ? $client->numero_documento : '00000000',
                'cliente_razon_social' => $client ? $client->razon_social : 'CLIENTE VARIOS',
                'cliente_direccion' => $client ? $client->direccion_completa : '-',
                'cliente_email' => $client ? $client->email : null,
                'cliente_telefono' => $client ? $client->telefono : null,
                'total_operaciones_gravadas' => $venta->monto_gravado,
                'total_operaciones_inafectas' => $venta->monto_inafecto,
                'total_operaciones_exoneradas' => $venta->monto_exonerado,
                'total_igv' => $venta->igv,
                'total_impuestos' => $venta->igv,
                'valor_total' => $venta->subtotal,
                'precio_total' => $venta->total,
                'total_valor_venta' => $venta->subtotal,
                'total_precio_venta' => $venta->total,
                'estado' => 'generado'
            ]);

            // Crear detalles de la boleta
            $this->crearDetallesBoleta($boleta, $venta);

            // Crear leyendas
            $this->crearLeyendasBoleta($boleta);

            DB::commit();

            Log::info('Boleta creada exitosamente', ['boleta_id' => $boleta->id]);

            return $boleta;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creando boleta: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener o crear cliente
     */
    private function obtenerOCrearCliente(array $datos, $companyId)
    {
        $client = Client::where('company_id', $companyId)
                       ->where('numero_documento', $datos['numero_documento'])
                       ->first();

        if (!$client) {
            $client = Client::create([
                'company_id' => $companyId,
                'tipo_documento' => $datos['tipo_documento'],
                'numero_documento' => $datos['numero_documento'],
                'razon_social' => $datos['razon_social'],
                'direccion' => $datos['direccion'] ?? '-',
                'email' => $datos['email'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
                'activo' => true
            ]);
        }

        return $client;
    }

    /**
     * Obtener correlativo activo
     */
    private function obtenerCorrelativo($companyId, $branchId, $tipoDocumento)
    {
        $correlative = Correlative::where('company_id', $companyId)
                                 ->where('branch_id', $branchId)
                                 ->where('tipo_documento', $tipoDocumento)
                                 ->where('activo', true)
                                 ->first();

        if (!$correlative) {
            throw new Exception("No se encontró correlativo activo para el tipo de documento {$tipoDocumento}");
        }

        return $correlative;
    }

    /**
     * Crear detalles de factura
     */
    private function crearDetallesFactura(Invoice $invoice, Venta $venta)
    {
        foreach ($venta->detalles as $detalle) {
            $invoice->details()->create([
                'codigo_interno' => $detalle->producto->codigo ?? 'PROD001',
                'codigo_producto_sunat' => '51121703', // Código genérico para medicamentos
                'codigo_unidad_medida' => 'NIU',
                'descripcion' => $detalle->producto->nombre,
                'cantidad' => $detalle->cantidad,
                'valor_unitario' => $detalle->precio_unitario / 1.18, // Sin IGV
                'precio_unitario' => $detalle->precio_unitario,
                'codigo_tipo_precio' => '01',
                'valor_total' => ($detalle->precio_unitario * $detalle->cantidad) / 1.18,
                'precio_total' => $detalle->precio_unitario * $detalle->cantidad,
                'codigo_tipo_afectacion_igv' => '10',
                'total_base_igv' => ($detalle->precio_unitario * $detalle->cantidad) / 1.18,
                'porcentaje_igv' => 18.00,
                'total_igv' => (($detalle->precio_unitario * $detalle->cantidad) / 1.18) * 0.18,
                'total_impuestos' => (($detalle->precio_unitario * $detalle->cantidad) / 1.18) * 0.18
            ]);
        }
    }

    /**
     * Crear detalles de boleta
     */
    private function crearDetallesBoleta(Boleta $boleta, Venta $venta)
    {
        foreach ($venta->detalles as $detalle) {
            $boleta->details()->create([
                'codigo_interno' => $detalle->producto->codigo ?? 'PROD001',
                'codigo_producto_sunat' => '51121703', // Código genérico para medicamentos
                'codigo_unidad_medida' => 'NIU',
                'descripcion' => $detalle->producto->nombre,
                'cantidad' => $detalle->cantidad,
                'valor_unitario' => $detalle->precio_unitario / 1.18, // Sin IGV
                'precio_unitario' => $detalle->precio_unitario,
                'codigo_tipo_precio' => '01',
                'valor_total' => ($detalle->precio_unitario * $detalle->cantidad) / 1.18,
                'precio_total' => $detalle->precio_unitario * $detalle->cantidad,
                'codigo_tipo_afectacion_igv' => '10',
                'total_base_igv' => ($detalle->precio_unitario * $detalle->cantidad) / 1.18,
                'porcentaje_igv' => 18.00,
                'total_igv' => (($detalle->precio_unitario * $detalle->cantidad) / 1.18) * 0.18,
                'total_impuestos' => (($detalle->precio_unitario * $detalle->cantidad) / 1.18) * 0.18
            ]);
        }
    }

    /**
     * Crear leyendas de factura
     */
    private function crearLeyendasFactura(Invoice $invoice)
    {
        $montoEnLetras = $this->convertirNumeroALetras($invoice->precio_total);
        
        $invoice->legends()->create([
            'codigo' => '1000',
            'descripcion' => $montoEnLetras
        ]);
    }

    /**
     * Crear leyendas de boleta
     */
    private function crearLeyendasBoleta(Boleta $boleta)
    {
        $montoEnLetras = $this->convertirNumeroALetras($boleta->precio_total);
        
        $boleta->legends()->create([
            'codigo' => '1000',
            'descripcion' => $montoEnLetras
        ]);
    }

    /**
     * Convertir número a letras (implementación básica)
     */
    private function convertirNumeroALetras($numero)
    {
        // Implementación básica - se puede mejorar con una librería especializada
        return 'SON ' . strtoupper(number_format($numero, 2)) . ' SOLES';
    }

    /**
     * Enviar documento a SUNAT
     */
    public function enviarDocumentoSunat($documento, $tipo = 'invoice')
    {
        try {
            if ($tipo === 'invoice') {
                return $this->greenterService->enviarFactura($documento);
            } else {
                return $this->greenterService->enviarBoleta($documento);
            }
        } catch (Exception $e) {
            Log::error("Error enviando {$tipo} a SUNAT: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar PDF del documento
     */
    public function generarPdf($documento, $tipo = 'invoice', $formato = 'a4')
    {
        try {
            if ($tipo === 'invoice') {
                return $this->pdfService->generateInvoicePdf($documento, $formato);
            } else {
                return $this->pdfService->generateBoletaPdf($documento, $formato);
            }
        } catch (Exception $e) {
            Log::error("Error generando PDF de {$tipo}: " . $e->getMessage());
            throw $e;
        }
    }
}