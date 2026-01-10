<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use App\Services\PdfService;

class ComprobantesPdfController extends Controller
{
    public function vistaPrevia(Request $request, PdfService $pdfService)
    {
        $width = (float)($request->get('w', 80));
        $height = (float)($request->get('h', 240));
        $orientation = $request->get('o', 'portrait');
        $view = 'punto-venta.ticket-termica';
        $venta = (object) [
            'numero_venta' => 'PV-' . date('YmdHis'),
            'created_at' => now(),
            'cliente' => (object) ['nombre' => 'Cliente General'],
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 20.0,
            'vuelto' => 0.0,
            'descuento' => 0.0,
            'igv' => 0.0,
            'subtotal' => 18.0,
            'total' => 18.0,
            'tipo_descuento' => null,
            'porcentaje_descuento' => null,
            'detalles' => collect([
                (object)[
                    'producto' => (object)['nombre' => 'Paracetamol 500mg'],
                    'cantidad' => 1,
                    'precio_unitario' => 6.0,
                    'subtotal' => 6.0,
                ],
                (object)[
                    'producto' => (object)['nombre' => 'Ibuprofeno 400mg'],
                    'cantidad' => 1,
                    'precio_unitario' => 6.0,
                    'subtotal' => 6.0,
                ],
                (object)[
                    'producto' => (object)['nombre' => 'Alcohol 70% 250ml'],
                    'cantidad' => 1,
                    'precio_unitario' => 6.0,
                    'subtotal' => 6.0,
                ],
            ]),
        ];
        $config = \App\Models\ConfiguracionSistema::obtenerConfiguracion();
        $data = ['venta' => $venta, 'modoPdf' => true, 'configuracion' => $config];
        $result = $pdfService->generateViewCustomSize($view, $data, $width, $height, $orientation, 'preview_ticket_' . (int)$width . 'mm');
        if (!($result['success'] ?? false)) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Error generando PDF'], 500);
        }
        return redirect($result['url']);
    }
}