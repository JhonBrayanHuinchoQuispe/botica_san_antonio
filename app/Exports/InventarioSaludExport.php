<?php

namespace App\Exports;

use App\Models\Producto;
use App\Models\ProductoUbicacion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class InventarioSaludExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithMapping
{
    public function title(): string
    {
        return 'Salud del Inventario';
    }

    public function collection()
    {
        return ProductoUbicacion::with('producto')
            ->where('cantidad', '>', 0)
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID Producto',
            'Producto',
            'Lote',
            'Ubicación',
            'Stock',
            'P. Compra',
            'P. Venta',
            'Valorización',
            'Vencimiento',
            'Estado Salud',
        ];
    }

    public function map($lote): array
    {
        $dias = $lote->dias_para_vencer;
        $estado = 'Saludable';
        if ($dias !== null && $dias < 0) $estado = 'VENCIDO';
        elseif ($dias !== null && $dias <= 90) $estado = 'RIESGO ALTO';
        elseif ($dias !== null && $dias <= 180) $estado = 'RIESGO MEDIO';

        return [
            $lote->producto_id,
            $lote->producto?->nombre ?? 'N/A',
            $lote->lote,
            $lote->ubicacion?->codigo ?? 'Sin Ubicar',
            (int)$lote->cantidad,
            (float)($lote->precio_compra_lote ?? 0),
            (float)($lote->precio_venta_lote ?? 0),
            (float)$lote->cantidad * (float)($lote->precio_compra_lote ?? 0),
            $lote->fecha_vencimiento ? $lote->fecha_vencimiento->format('d/m/Y') : 'N/A',
            $estado,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 45,
            'C' => 15,
            'D' => 15,
            'E' => 10,
            'F' => 12,
            'G' => 12,
            'H' => 15,
            'I' => 15,
            'J' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo para la cabecera
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E40AF'], // Azul oscuro (Navy)
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Altura de la cabecera
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Bordes para todo el contenido
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:J' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Formato de moneda para columnas de precio y valorización
        $sheet->getStyle('F2:H' . $lastRow)->getNumberFormat()->setFormatCode('"S/ " #,##0.00');

        // Formato condicional para la columna Estado Salud (J)
        for ($i = 2; $i <= $lastRow; $i++) {
            $cellValue = $sheet->getCell('J' . $i)->getValue();
            $color = 'FFFFFF';
            $bgColor = 'FFFFFF';

            if ($cellValue === 'VENCIDO') {
                $bgColor = 'FECACA'; // Rojo claro
                $color = '991B1B'; // Rojo oscuro
            } elseif ($cellValue === 'RIESGO ALTO') {
                $bgColor = 'FFEDD5'; // Naranja claro
                $color = '9A3412'; // Naranja oscuro
            } elseif ($cellValue === 'RIESGO MEDIO') {
                $bgColor = 'FEF9C3'; // Amarillo claro
                $color = '854D0E'; // Amarillo oscuro
            } elseif ($cellValue === 'Saludable') {
                $bgColor = 'DCFCE7'; // Verde claro
                $color = '166534'; // Verde oscuro
            }

            $sheet->getStyle('J' . $i)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $color]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
            ]);
        }

        return [
            1 => ['font' => ['size' => 12]],
        ];
    }
}
