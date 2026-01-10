<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Boleta;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;

class PdfController extends Controller
{
    /**
     * Generar PDF de factura en formato A4
     */
    public function generarFacturaPdfA4($id): Response
    {
        try {
            $invoice = Invoice::with(['details', 'company', 'client'])->findOrFail($id);
            $company = $invoice->company ?? Company::first();

            $html = view('pdf.factura-a4', compact('invoice', 'company'))->render();
            
            return $this->generatePdf($html, "Factura-{$invoice->serie}-{$invoice->numero}.pdf");
            
        } catch (Exception $e) {
            Log::error('Error generando PDF A4 de factura: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar PDF'], 500);
        }
    }

    /**
     * Generar PDF de factura en formato ticket
     */
    public function generarFacturaPdfTicket($id): Response
    {
        try {
            $invoice = Invoice::with(['details', 'company', 'client'])->findOrFail($id);
            $company = $invoice->company ?? Company::first();

            $html = view('pdf.factura-ticket', compact('invoice', 'company'))->render();
            
            return $this->generatePdf($html, "Factura-Ticket-{$invoice->serie}-{$invoice->numero}.pdf", '80mm', '200mm');
            
        } catch (Exception $e) {
            Log::error('Error generando PDF ticket de factura: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar PDF'], 500);
        }
    }

    /**
     * Generar PDF de boleta en formato A4
     */
    public function generarBoletaPdfA4($id): Response
    {
        try {
            $boleta = Boleta::with(['details', 'company'])->findOrFail($id);
            $company = $boleta->company ?? Company::first();

            $html = view('pdf.boleta-a4', compact('boleta', 'company'))->render();
            
            return $this->generatePdf($html, "Boleta-{$boleta->serie}-{$boleta->numero}.pdf");
            
        } catch (Exception $e) {
            Log::error('Error generando PDF A4 de boleta: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar PDF'], 500);
        }
    }

    /**
     * Generar PDF de boleta en formato ticket
     */
    public function generarBoletaPdfTicket($id): Response
    {
        try {
            $boleta = Boleta::with(['details', 'company'])->findOrFail($id);
            $company = $boleta->company ?? Company::first();

            $html = view('pdf.boleta-ticket', compact('boleta', 'company'))->render();
            
            return $this->generatePdf($html, "Boleta-Ticket-{$boleta->serie}-{$boleta->numero}.pdf", '80mm', '200mm');
            
        } catch (Exception $e) {
            Log::error('Error generando PDF ticket de boleta: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar PDF'], 500);
        }
    }

    /**
     * Descargar PDF de factura
     */
    public function descargarFacturaPdf(Request $request, $id): Response
    {
        $formato = $request->get('formato', 'a4'); // a4 o ticket
        
        if ($formato === 'ticket') {
            return $this->generarFacturaPdfTicket($id);
        }
        
        return $this->generarFacturaPdfA4($id);
    }

    /**
     * Descargar PDF de boleta
     */
    public function descargarBoletaPdf(Request $request, $id): Response
    {
        $formato = $request->get('formato', 'a4'); // a4 o ticket
        
        if ($formato === 'ticket') {
            return $this->generarBoletaPdfTicket($id);
        }
        
        return $this->generarBoletaPdfA4($id);
    }

    /**
     * Generar PDF y guardarlo en storage
     */
    public function guardarFacturaPdf($id, $formato = 'a4'): JsonResponse
    {
        try {
            $invoice = Invoice::with(['details', 'company', 'client'])->findOrFail($id);
            $company = $invoice->company ?? Company::first();

            $template = $formato === 'ticket' ? 'pdf.factura-ticket' : 'pdf.factura-a4';
            $html = view($template, compact('invoice', 'company'))->render();
            
            $filename = "Factura-{$invoice->serie}-{$invoice->numero}-{$formato}.pdf";
            $pdfContent = $this->generatePdfContent($html, $formato === 'ticket' ? '80mm' : 'A4');
            
            // Guardar en storage
            $path = "pdfs/facturas/{$filename}";
            Storage::disk('local')->put($path, $pdfContent);
            
            return response()->json([
                'success' => true,
                'message' => 'PDF generado y guardado exitosamente',
                'path' => $path,
                'filename' => $filename
            ]);
            
        } catch (Exception $e) {
            Log::error('Error guardando PDF de factura: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar PDF'], 500);
        }
    }

    /**
     * Generar PDF y guardarlo en storage para boleta
     */
    public function guardarBoletaPdf($id, $formato = 'a4'): JsonResponse
    {
        try {
            $boleta = Boleta::with(['details', 'company'])->findOrFail($id);
            $company = $boleta->company ?? Company::first();

            $template = $formato === 'ticket' ? 'pdf.boleta-ticket' : 'pdf.boleta-a4';
            $html = view($template, compact('boleta', 'company'))->render();
            
            $filename = "Boleta-{$boleta->serie}-{$boleta->numero}-{$formato}.pdf";
            $pdfContent = $this->generatePdfContent($html, $formato === 'ticket' ? '80mm' : 'A4');
            
            // Guardar en storage
            $path = "pdfs/boletas/{$filename}";
            Storage::disk('local')->put($path, $pdfContent);
            
            return response()->json([
                'success' => true,
                'message' => 'PDF generado y guardado exitosamente',
                'path' => $path,
                'filename' => $filename
            ]);
            
        } catch (Exception $e) {
            Log::error('Error guardando PDF de boleta: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar PDF'], 500);
        }
    }

    /**
     * Listar PDFs generados
     */
    public function listarPdfs(Request $request): JsonResponse
    {
        try {
            $tipo = $request->get('tipo', 'all'); // facturas, boletas, all
            
            $pdfs = [];
            
            if ($tipo === 'facturas' || $tipo === 'all') {
                $facturaFiles = Storage::disk('local')->files('pdfs/facturas');
                foreach ($facturaFiles as $file) {
                    $pdfs[] = [
                        'tipo' => 'factura',
                        'path' => $file,
                        'filename' => basename($file),
                        'size' => Storage::disk('local')->size($file),
                        'created_at' => Storage::disk('local')->lastModified($file)
                    ];
                }
            }
            
            if ($tipo === 'boletas' || $tipo === 'all') {
                $boletaFiles = Storage::disk('local')->files('pdfs/boletas');
                foreach ($boletaFiles as $file) {
                    $pdfs[] = [
                        'tipo' => 'boleta',
                        'path' => $file,
                        'filename' => basename($file),
                        'size' => Storage::disk('local')->size($file),
                        'created_at' => Storage::disk('local')->lastModified($file)
                    ];
                }
            }
            
            // Ordenar por fecha de creación descendente
            usort($pdfs, function($a, $b) {
                return $b['created_at'] - $a['created_at'];
            });
            
            return response()->json([
                'success' => true,
                'pdfs' => $pdfs,
                'total' => count($pdfs)
            ]);
            
        } catch (Exception $e) {
            Log::error('Error listando PDFs: ' . $e->getMessage());
            return response()->json(['error' => 'Error al listar PDFs'], 500);
        }
    }

    /**
     * Eliminar PDF
     */
    public function eliminarPdf(Request $request): JsonResponse
    {
        try {
            $path = $request->get('path');
            
            if (!$path || !Storage::disk('local')->exists($path)) {
                return response()->json(['error' => 'Archivo no encontrado'], 404);
            }
            
            Storage::disk('local')->delete($path);
            
            return response()->json([
                'success' => true,
                'message' => 'PDF eliminado exitosamente'
            ]);
            
        } catch (Exception $e) {
            Log::error('Error eliminando PDF: ' . $e->getMessage());
            return response()->json(['error' => 'Error al eliminar PDF'], 500);
        }
    }

    /**
     * Método privado para generar PDF con DomPDF
     */
    private function generatePdf($html, $filename, $width = 'A4', $height = null): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        
        if ($width !== 'A4') {
            $dompdf->setPaper([$width, $height ?: '200mm']);
        } else {
            $dompdf->setPaper('A4', 'portrait');
        }
        
        $dompdf->render();
        
        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Método privado para generar contenido PDF
     */
    private function generatePdfContent($html, $paper = 'A4'): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, 'portrait');
        $dompdf->render();
        
        return $dompdf->output();
    }
}