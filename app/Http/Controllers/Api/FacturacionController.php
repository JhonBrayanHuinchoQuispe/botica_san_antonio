<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PuntoVenta\Venta;
use App\Models\Invoice;
use App\Models\Boleta;
use App\Models\Company;
use App\Models\Client;
use App\Services\DocumentService;
use App\Services\PdfService;
use App\Services\GreenterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class FacturacionController extends Controller
{
    protected $documentService;
    protected $pdfService;
    protected $greenterService;

    public function __construct(
        DocumentService $documentService,
        PdfService $pdfService,
        GreenterService $greenterService
    ) {
        $this->documentService = $documentService;
        $this->pdfService = $pdfService;
        $this->greenterService = $greenterService;
    }

    /**
     * Generar factura electrónica desde una venta
     */
    public function generarFactura(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'venta_id' => 'required|exists:ventas,id',
            'cliente_tipo_documento' => 'required|string|in:1,6',
            'cliente_numero_documento' => 'required|string',
            'cliente_razon_social' => 'required|string',
            'cliente_direccion' => 'nullable|string',
            'tipo_operacion' => 'nullable|string|in:0101,0102,0103,0104',
            'observaciones' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $venta = Venta::with(['detalles.producto', 'cliente', 'usuario'])->findOrFail($request->venta_id);

            // Verificar si ya existe una factura para esta venta
            $facturaExistente = Invoice::where('venta_id', $venta->id)->first();
            if ($facturaExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una factura para esta venta',
                    'data' => [
                        'factura_id' => $facturaExistente->id,
                        'serie' => $facturaExistente->serie,
                        'numero' => $facturaExistente->numero
                    ]
                ], 409);
            }

            // Crear factura
            $factura = $this->documentService->crearFacturaDesdeVenta($venta, $request->all());

            // Enviar a SUNAT
            $resultadoSunat = $this->greenterService->enviarFactura($factura);

            // Generar PDF
            $pdfPath = $this->pdfService->generarPdfFactura($factura, 'A4');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Factura generada exitosamente',
                'data' => [
                    'factura_id' => $factura->id,
                    'serie' => $factura->serie,
                    'numero' => $factura->numero,
                    'estado_sunat' => $factura->estado_sunat,
                    'total' => $factura->precio_total,
                    'pdf_url' => $pdfPath,
                    'qr_url' => $factura->qr,
                    'hash' => $factura->hash,
                    'fecha_emision' => $factura->fecha_emision,
                    'cliente' => [
                        'tipo_documento' => $factura->cliente_tipo_documento,
                        'numero_documento' => $factura->cliente_numero_documento,
                        'razon_social' => $factura->cliente_razon_social
                    ]
                ]
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error generando factura: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al generar la factura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar boleta electrónica desde una venta
     */
    public function generarBoleta(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'venta_id' => 'required|exists:ventas,id',
            'cliente_tipo_documento' => 'nullable|string|in:1,4,7',
            'cliente_numero_documento' => 'nullable|string',
            'cliente_razon_social' => 'nullable|string',
            'observaciones' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $venta = Venta::with(['detalles.producto', 'cliente', 'usuario'])->findOrFail($request->venta_id);

            // Verificar si ya existe una boleta para esta venta
            $boletaExistente = Boleta::where('venta_id', $venta->id)->first();
            if ($boletaExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una boleta para esta venta',
                    'data' => [
                        'boleta_id' => $boletaExistente->id,
                        'serie' => $boletaExistente->serie,
                        'numero' => $boletaExistente->numero
                    ]
                ], 409);
            }

            // Crear boleta
            $boleta = $this->documentService->crearBoletaDesdeVenta($venta, $request->all());

            // Enviar a SUNAT
            $resultadoSunat = $this->greenterService->enviarBoleta($boleta);

            // Generar PDF
            $pdfPath = $this->pdfService->generarPdfBoleta($boleta, 'ticket');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Boleta generada exitosamente',
                'data' => [
                    'boleta_id' => $boleta->id,
                    'serie' => $boleta->serie,
                    'numero' => $boleta->numero,
                    'estado_sunat' => $boleta->estado_sunat,
                    'total' => $boleta->precio_total,
                    'pdf_url' => $pdfPath,
                    'qr_url' => $boleta->qr,
                    'hash' => $boleta->hash,
                    'fecha_emision' => $boleta->fecha_emision,
                    'cliente' => [
                        'tipo_documento' => $boleta->cliente_tipo_documento,
                        'numero_documento' => $boleta->cliente_numero_documento,
                        'razon_social' => $boleta->cliente_razon_social
                    ]
                ]
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error generando boleta: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al generar la boleta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar ticket de venta
     */
    public function generarTicket(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'venta_id' => 'required|exists:ventas,id',
            'formato' => 'nullable|string|in:80mm,50mm,ticket'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $venta = Venta::with(['detalles.producto', 'cliente', 'usuario'])->findOrFail($request->venta_id);
            $formato = $request->formato ?? 'ticket';

            // Generar PDF del ticket
            $pdfPath = $this->pdfService->generarTicketVenta($venta, $formato);

            return response()->json([
                'success' => true,
                'message' => 'Ticket generado exitosamente',
                'data' => [
                    'venta_id' => $venta->id,
                    'numero_venta' => $venta->numero_venta,
                    'total' => $venta->total,
                    'pdf_url' => $pdfPath,
                    'formato' => $formato,
                    'fecha_venta' => $venta->created_at
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Error generando ticket: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consultar estado de documento en SUNAT
     */
    public function consultarEstado(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tipo_documento' => 'required|string|in:01,03',
            'documento_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->tipo_documento === '01') {
                $documento = Invoice::findOrFail($request->documento_id);
            } else {
                $documento = Boleta::findOrFail($request->documento_id);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'documento_id' => $documento->id,
                    'serie' => $documento->serie,
                    'numero' => $documento->numero,
                    'estado_sunat' => $documento->estado_sunat,
                    'enviado_sunat' => $documento->enviado_sunat,
                    'fecha_envio_sunat' => $documento->fecha_envio_sunat,
                    'codigo_error' => $documento->codigo_error,
                    'mensaje_error' => $documento->mensaje_error,
                    'hash' => $documento->hash,
                    'qr_url' => $documento->qr
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar facturas
     */
    public function listarFacturas(Request $request): JsonResponse
    {
        try {
            $query = Invoice::with(['client', 'company', 'branch'])
                           ->orderBy('created_at', 'desc');

            // Filtros
            if ($request->has('fecha_inicio')) {
                $query->whereDate('fecha_emision', '>=', $request->fecha_inicio);
            }

            if ($request->has('fecha_fin')) {
                $query->whereDate('fecha_emision', '<=', $request->fecha_fin);
            }

            if ($request->has('estado_sunat')) {
                $query->where('estado_sunat', $request->estado_sunat);
            }

            if ($request->has('serie')) {
                $query->where('serie', $request->serie);
            }

            $facturas = $query->paginate($request->per_page ?? 15);

            return response()->json([
                'success' => true,
                'data' => $facturas
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar facturas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar boletas
     */
    public function listarBoletas(Request $request): JsonResponse
    {
        try {
            $query = Boleta::with(['client', 'company', 'branch'])
                          ->orderBy('created_at', 'desc');

            // Filtros
            if ($request->has('fecha_inicio')) {
                $query->whereDate('fecha_emision', '>=', $request->fecha_inicio);
            }

            if ($request->has('fecha_fin')) {
                $query->whereDate('fecha_emision', '<=', $request->fecha_fin);
            }

            if ($request->has('estado_sunat')) {
                $query->where('estado_sunat', $request->estado_sunat);
            }

            if ($request->has('serie')) {
                $query->where('serie', $request->serie);
            }

            $boletas = $query->paginate($request->per_page ?? 15);

            return response()->json([
                'success' => true,
                'data' => $boletas
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar boletas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar PDF de documento
     */
    public function descargarPdf(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tipo_documento' => 'required|string|in:01,03',
            'documento_id' => 'required|integer',
            'formato' => 'nullable|string|in:A4,A5,80mm,50mm,ticket'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $formato = $request->formato ?? 'A4';

            if ($request->tipo_documento === '01') {
                $documento = Invoice::findOrFail($request->documento_id);
                $pdfPath = $this->pdfService->generarPdfFactura($documento, $formato);
            } else {
                $documento = Boleta::findOrFail($request->documento_id);
                $pdfPath = $this->pdfService->generarPdfBoleta($documento, $formato);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'pdf_url' => $pdfPath,
                    'formato' => $formato
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}