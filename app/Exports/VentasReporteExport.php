<?php

namespace App\Exports;

use App\Models\PuntoVenta\Venta;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VentasReporteExport implements 
    FromArray,
    WithStyles, 
    WithTitle,
    WithColumnWidths,
    WithEvents
{
    protected $fechaInicio;
    protected $fechaFin;
    protected $usuarioId;
    protected $tipo;

    public function __construct($fechaInicio, $fechaFin, $usuarioId = null, $tipo = 'detallado')
    {
        $this->fechaInicio = Carbon::parse($fechaInicio);
        $this->fechaFin = Carbon::parse($fechaFin);
        $this->usuarioId = $usuarioId;
        $this->tipo = $tipo;
    }

    public function array(): array
    {
        try {
            // Obtener ventas
            $query = Venta::with(['cliente', 'usuario'])
                ->whereIn('estado', ['completada', 'parcialmente_devuelta'])
                ->whereBetween('fecha_venta', [$this->fechaInicio, $this->fechaFin]);
                
            if ($this->usuarioId) {
                $query->where('usuario_id', $this->usuarioId);
            }
            
            $ventas = $query->orderBy('fecha_venta', 'desc')->get();
            
            \Log::info('Ventas obtenidas para exportar', ['total' => $ventas->count()]);
            
            // Calcular estadísticas
            $totalVentas = $ventas->count();
            $totalIngresos = $ventas->sum('total');
            $ticketPromedio = $totalVentas > 0 ? ($totalIngresos / $totalVentas) : 0;
            
            $efectivo = $ventas->where('metodo_pago', 'efectivo')->sum('total');
            $tarjeta = $ventas->where('metodo_pago', 'tarjeta')->sum('total');
            $yape = $ventas->where('metodo_pago', 'yape')->sum('total');
            
            $totalMetodos = $efectivo + $tarjeta + $yape;
            $totalMetodos = $totalMetodos > 0 ? $totalMetodos : 1;
            
            // Construir el array de datos
            $data = [];
            
            // Encabezado
            $data[] = ['FARMACIA SAN ANTONIO', '', '', '', '', '', ''];
            $data[] = ['REPORTE DE VENTAS', '', '', '', '', '', ''];
            $data[] = ['Período: ' . $this->fechaInicio->format('d/m/Y') . ' - ' . $this->fechaFin->format('d/m/Y'), '', '', '', '', '', ''];
            $data[] = ['Generado: ' . now()->format('d/m/Y H:i:s'), '', '', '', '', '', ''];
            $data[] = ['', '', '', '', '', '', ''];
            
            // Resumen General
            $data[] = ['RESUMEN GENERAL', '', '', '', '', '', ''];
            $data[] = ['Total Ventas:', $totalVentas, '', '', '', '', ''];
            $data[] = ['Ingresos Totales:', 'S/ ' . number_format($totalIngresos, 2), '', '', '', '', ''];
            $data[] = ['Ticket Promedio:', 'S/ ' . number_format($ticketPromedio, 2), '', '', '', '', ''];
            $data[] = ['', '', '', '', '', '', ''];
            
            // Métodos de Pago
            $data[] = ['MÉTODOS DE PAGO', 'Cantidad', 'Porcentaje', 'Monto', '', '', ''];
            $data[] = ['Efectivo', 
                $ventas->where('metodo_pago', 'efectivo')->count(),
                number_format(($efectivo / $totalMetodos) * 100, 1) . '%',
                'S/ ' . number_format($efectivo, 2),
                '', '', ''
            ];
            $data[] = ['Tarjeta', 
                $ventas->where('metodo_pago', 'tarjeta')->count(),
                number_format(($tarjeta / $totalMetodos) * 100, 1) . '%',
                'S/ ' . number_format($tarjeta, 2),
                '', '', ''
            ];
            $data[] = ['Yape', 
                $ventas->where('metodo_pago', 'yape')->count(),
                number_format(($yape / $totalMetodos) * 100, 1) . '%',
                'S/ ' . number_format($yape, 2),
                '', '', ''
            ];
            $data[] = ['', '', '', '', '', '', ''];
            $data[] = ['', '', '', '', '', '', ''];
            
            // Encabezado de tabla
            if ($this->tipo === 'resumen') {
                $data[] = ['#', 'PRODUCTO', 'MARCA', 'CANTIDAD', 'TOTAL VENDIDO', 'PROMEDIO'];
                
                // Obtener detalle de productos vendidos
                $detalleProductos = \App\Models\PuntoVenta\VentaDetalle::select(
                        'venta_detalles.producto_id',
                        'productos.nombre',
                        'productos.marca',
                        \Illuminate\Support\Facades\DB::raw('SUM(venta_detalles.cantidad) as cantidad_total'),
                        \Illuminate\Support\Facades\DB::raw('SUM(venta_detalles.subtotal) as total_vendido'),
                        \Illuminate\Support\Facades\DB::raw('AVG(venta_detalles.precio_unitario) as precio_promedio')
                    )
                    ->join('productos', 'venta_detalles.producto_id', '=', 'productos.id')
                    ->whereHas('venta', function($q) {
                        $q->activas()->whereBetween('fecha_venta', [$this->fechaInicio, $this->fechaFin]);
                        if ($this->usuarioId) {
                            $q->where('usuario_id', $this->usuarioId);
                        }
                    })
                    ->groupBy('venta_detalles.producto_id', 'productos.nombre', 'productos.marca')
                    ->orderByDesc('cantidad_total')
                    ->get();

                $i = 1;
                foreach ($detalleProductos as $prod) {
                    $data[] = [
                        $i++,
                        $prod->nombre,
                        $prod->marca ?? '-',
                        $prod->cantidad_total,
                        'S/ ' . number_format($prod->total_vendido, 2),
                        'S/ ' . number_format($prod->precio_promedio, 2),
                    ];
                }
            } else {
                $data[] = ['#', 'FECHA', 'N° VENTA', 'CLIENTE', 'MÉTODO PAGO', 'TOTAL', 'ESTADO'];
                
                // Datos de ventas
                $i = 1;
                foreach ($ventas as $venta) {
                    // Obtener nombre del cliente de forma segura
                    $nombreCliente = 'Cliente General';
                    if (!empty($venta->cliente_nombre)) {
                        $nombreCliente = $venta->cliente_nombre;
                    } elseif (!empty($venta->cliente_razon_social)) {
                        $nombreCliente = $venta->cliente_razon_social;
                    } elseif ($venta->cliente) {
                        if (!empty($venta->cliente->razon_social)) {
                            $nombreCliente = $venta->cliente->razon_social;
                        } elseif (!empty($venta->cliente->nombre_completo)) {
                            $nombreCliente = $venta->cliente->nombre_completo;
                        } elseif (!empty($venta->cliente->nombre)) {
                            $nombreCliente = $venta->cliente->nombre;
                        }
                    }
                    
                    $data[] = [
                        $i++,
                        Carbon::parse($venta->fecha_venta)->format('d/m/Y H:i'),
                        $venta->numero_venta ?? 'N/A',
                        $nombreCliente,
                        strtoupper($venta->metodo_pago ?? 'N/A'),
                        'S/ ' . number_format($venta->total ?? 0, 2),
                        strtoupper($venta->estado ?? 'DESCONOCIDO'),
                    ];
                }
            }
            
            \Log::info('Array de datos construido', ['filas' => count($data)]);
            
            return $data;
            
        } catch (\Exception $e) {
            \Log::error('Error en array() de VentasReporteExport', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            
            // Retornar datos mínimos en caso de error
            return [
                ['ERROR AL GENERAR REPORTE'],
                ['Mensaje: ' . $e->getMessage()],
            ];
        }
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Título principal (Fila 1) - ROJO
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EF4444']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            
            // Subtítulo (Fila 2) - NEGRITA
            2 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            
            // RESUMEN GENERAL (Fila 6) - AZUL
            6 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '6366F1']],
            ],
            
            // MÉTODOS DE PAGO (Fila 11) - VERDE
            11 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '10B981']],
            ],
            
            // Encabezado de tabla (Fila 17) - AZUL
            17 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6366F1']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function title(): string
    {
        return 'Reporte de Ventas';
    }

    public function columnWidths(): array
    {
        if ($this->tipo === 'resumen') {
            return [
                'A' => 8,   // #
                'B' => 45,  // PRODUCTO
                'C' => 20,  // MARCA
                'D' => 15,  // CANTIDAD
                'E' => 18,  // TOTAL VENDIDO
                'F' => 18,  // PROMEDIO
            ];
        }
        
        return [
            'A' => 8,   // #
            'B' => 18,  // FECHA
            'C' => 18,  // N° VENTA
            'D' => 35,  // CLIENTE
            'E' => 18,  // MÉTODO PAGO
            'F' => 18,  // TOTAL
            'G' => 20,  // ESTADO
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $this->tipo === 'resumen' ? 'F' : 'G';
                
                // Merge cells para título
                $sheet->mergeCells('A1:' . $lastColumn . '1');
                $sheet->mergeCells('A2:' . $lastColumn . '2');
                $sheet->mergeCells('A3:' . $lastColumn . '3');
                $sheet->mergeCells('A4:' . $lastColumn . '4');
                
                // Calcular la última fila
                $lastRow = $sheet->getHighestRow();
                
                // Aplicar BORDES a toda la tabla de datos (desde fila 17)
                if ($lastRow > 17) {
                    $sheet->getStyle('A17:' . $lastColumn . $lastRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                    
                    // Filas alternadas (GRIS CLARO) - empezar desde fila 18
                    for ($i = 18; $i <= $lastRow; $i++) {
                        if ($i % 2 == 0) {
                            $sheet->getStyle('A' . $i . ':' . $lastColumn . $i)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F3F4F6'],
                                ],
                            ]);
                        }
                    }
                    
                    // Centrar números y alinear montos a la derecha
                    $sheet->getStyle('A18:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    
                    if ($this->tipo === 'resumen') {
                        $sheet->getStyle('D18:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('E18:F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    } else {
                        $sheet->getStyle('F18:F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle('G18:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                }
                
                // Bordes en resumen de métodos de pago (filas 11-14)
                $sheet->getStyle('A11:D14')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ]);
                
                // Bordes en resumen general (filas 7-9)
                $sheet->getStyle('A7:B9')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ]);
            },
        ];
    }
}
