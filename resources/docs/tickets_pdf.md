# Tickets y Boletas en PDF con tamaño personalizado

## Objetivo
Generar PDFs para tickets/boletas con el ancho exacto del papel térmico (58mm/80mm) y altura controlada, evitando el tamaño por defecto A4.

## Librería
Se usa `dompdf/dompdf` (ya instalado) con tamaños personalizados en puntos (pt). Conversión: `pt = (mm / 25.4) * 72`.

## Cómo usar

- Vista previa rápida: abrir `GET /admin/configuracion/comprobantes/vista-previa?w=80&h=200` para 80mm x 200mm.
- Parámetros:
  - `w`: ancho en mm (ej. 58 o 80)
  - `h`: alto en mm (ej. 200–400, según contenido)
  - `o`: orientación (`portrait` por defecto)

## Código clave

- Servicio: `App\Services\PdfService::generateViewCustomSize($view, $data, $widthMm, $heightMm, $orientation, $filename)`
- Controlador: `App\Http\Controllers\Admin\ComprobantesPdfController::vistaPrevia`
- Vista base de ticket: `resources/views/punto-venta/ticket-termica.blade.php` (ancho definido en CSS)

## Recomendaciones de impresión

- En Windows/macOS/Linux, crear tamaño de papel personalizado en el driver de la impresora (58mm/80mm) y usar márgenes pequeños (2–5mm).
- En el diálogo de impresión del visor PDF, seleccionar ese tamaño personalizado y desactivar escalado.

## Pruebas de impresión

- Probar en navegadores modernos (Chrome/Edge/Firefox) y verificar:
  - Proporción correcta (ancho exacto)
  - Contenido completo visible
  - Legibilidad (fuente monospace 10–12pt)
  - Coincidencia de largo con el contenido

## Manejo de errores

- Si falla la generación: el controlador responde JSON `500` con el mensaje.
- Si no existe la vista: el servicio lanza excepción controlada.

## Compatibilidad POS

- PDFs resultantes funcionan con impresoras térmicas comunes (Epson TM-T88, Elgin, etc.).
- Integrables en flujos de POS vía descarga o impresión directa desde iframe.