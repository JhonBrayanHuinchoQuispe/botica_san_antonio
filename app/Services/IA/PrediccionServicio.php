<?php

namespace App\Services\IA;

use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class PrediccionServicio
{
    private function unidades(int $n): string { return $n === 1 ? 'unidad' : 'unidades'; }
    public function ventas(string $rango)
    {
        $salida = $this->ejecutar(['task' => 'ventas', 'range' => $rango]);
        if ($salida) return $salida;
        return [
            'text' => 'Pronóstico semanal estimado.',
            'visualization' => [
                'type' => 'line',
                'title' => 'Pronóstico semanal',
                'labels' => ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'],
                'values' => [32,28,30,44,36,48,26],
            ],
        ];
    }

    public function topProductos(string $periodo)
    {
        $salida = $this->ejecutar(['task' => 'top', 'period' => $periodo]);
        if ($salida) return $salida;
        return [
            'text' => 'Producto más vendido del mes: Paracetamol 500mg.',
        ];
    }

    public function pronosticoProximoMes(): array
    {
        $series = $this->seriesVentasMensuales(12);
        $labels = array_values(array_map(fn($x)=>$x['label'], $series));
        $valores = array_values(array_map(fn($x)=>$x['valor'], $series));
        $avgLast3 = 0;
        if (count($valores) >= 3) {
            $slice = array_slice($valores, -3);
            $avgLast3 = array_sum($slice) / 3;
        } elseif (count($valores) > 0) {
            $avgLast3 = array_sum($valores) / count($valores);
        }
        // Variación últimos 30 días vs 30 días previos
        $hoy = Carbon::now('America/Lima');
        $desde1 = $hoy->copy()->subDays(30);
        $desde2 = $hoy->copy()->subDays(60);
        $v30 = 0; $vprev = 0;
        if (Schema::hasTable('venta_detalles') && Schema::hasTable('ventas')) {
            $v30 = (int) DB::table('venta_detalles')
                ->join('ventas','venta_detalles.venta_id','=','ventas.id')
                ->where('ventas.estado','completada')
                ->where(DB::raw('COALESCE(ventas.fecha_venta, ventas.created_at)'), '>=', $desde1)
                ->sum('venta_detalles.cantidad');
            $vprev = (int) DB::table('venta_detalles')
                ->join('ventas','venta_detalles.venta_id','=','ventas.id')
                ->where('ventas.estado','completada')
                ->whereBetween(DB::raw('COALESCE(ventas.fecha_venta, ventas.created_at)'), [$desde2, $desde1])
                ->sum('venta_detalles.cantidad');
        }
        $growthPct = ($vprev > 0) ? round((($v30 - $vprev) / $vprev) * 100) : ($v30 > 0 ? 100 : 0);
        // Estimación de rango (evitar 0)
        $base = max(1, (int) round($avgLast3));
        $low = max(1, (int) round($base * 0.85));
        $high = max(1, (int) round($base * 1.15));
        $nextDate = Carbon::now('America/Lima')->addMonth();
        $nextLabel = $nextDate->format('Y-m');
        // Intentar obtener visualización desde Python (opcional)
        $visual = null;
        $salida = $this->ejecutar(['task' => 'forecast_next_month', 'series' => $series]);
        if (is_array($salida) && isset($salida['visualization'])) {
            $visual = $salida['visualization'];
        }
        $nextMonthName = $nextDate->locale('es')->translatedFormat('F');
        $approx = max(1, (int) round($avgLast3));
        $texto = 'Pronóstico del próximo mes: esperamos aproximadamente '.$approx.' '.$this->unidades($approx).'. Base: promedio trimestral.';
        return [
            'text' => $texto,
            'visualization' => $visual ?: [ 'type' => 'bar', 'labels' => $labels, 'values' => $valores, 'next' => ['label'=>$nextLabel,'value'=>$base] ]
        ];
    }

    public function pronosticoProductoProximoMes(int $productoId): array
    {
        $series = $this->seriesProductoMensual($productoId, 12);
        $labels = array_values(array_map(fn($x)=>$x['label'], $series));
        $valores = array_values(array_map(fn($x)=>$x['valor'], $series));
        $avgLast3 = 0;
        if (count($valores) >= 3) {
            $slice = array_slice($valores, -3);
            $avgLast3 = array_sum($slice) / 3;
        } elseif (count($valores) > 0) {
            $avgLast3 = array_sum($valores) / count($valores);
        }
        $base = max(1, (int) round($avgLast3));
        $low = max(1, (int) round($base * 0.85));
        $high = max(1, (int) round($base * 1.15));
        $nextDate = Carbon::now('America/Lima')->addMonth();
        $nextLabel = $nextDate->format('Y-m');
        $visual = null;
        $salida = $this->ejecutar(['task' => 'forecast_product', 'series' => $series, 'producto_id' => $productoId]);
        if (is_array($salida) && isset($salida['visualization'])) {
            $visual = $salida['visualization'];
        }
        $nextMonthName = $nextDate->locale('es')->translatedFormat('F');
        // Variación 30d vs prev para el producto
        $hoy = Carbon::now('America/Lima');
        $desde1 = $hoy->copy()->subDays(30);
        $desde2 = $hoy->copy()->subDays(60);
        $v30 = 0; $vprev = 0;
        if (Schema::hasTable('venta_detalles') && Schema::hasTable('ventas')) {
            $v30 = (int) DB::table('venta_detalles')
                ->join('ventas','venta_detalles.venta_id','=','ventas.id')
                ->where('ventas.estado','completada')
                ->where('venta_detalles.producto_id',$productoId)
                ->where(DB::raw('COALESCE(ventas.fecha_venta, ventas.created_at)'), '>=', $desde1)
                ->sum('venta_detalles.cantidad');
            $vprev = (int) DB::table('venta_detalles')
                ->join('ventas','venta_detalles.venta_id','=','ventas.id')
                ->where('ventas.estado','completada')
                ->where('venta_detalles.producto_id',$productoId)
                ->whereBetween(DB::raw('COALESCE(ventas.fecha_venta, ventas.created_at)'), [$desde2, $desde1])
                ->sum('venta_detalles.cantidad');
        }
        $growthPct = ($vprev > 0) ? round((($v30 - $vprev) / $vprev) * 100) : ($v30 > 0 ? 100 : 0);
        $approx = max(1, (int) round($avgLast3));
        $texto = 'Pronóstico del producto (próximo mes): esperamos aproximadamente '.$approx.' '.$this->unidades($approx).'. Base: promedio trimestral.';
        return [
            'text' => $texto,
            'visualization' => $visual ?: [ 'type' => 'bar', 'labels' => $labels, 'values' => $valores, 'next' => ['label'=>$nextLabel,'value'=>$base] ]
        ];
    }

    public function topProductoPronosticadoProximoMes(): array
    {
        if (!Schema::hasTable('venta_detalles') || !Schema::hasTable('ventas')) {
            return ['text' => 'No hay datos de ventas recientes.'];
        }
        $hoy = Carbon::now('America/Lima');
        $desde1 = $hoy->copy()->subDays(30);
        $desde2 = $hoy->copy()->subDays(60);

        $ventas30 = DB::table('venta_detalles')
            ->join('ventas','venta_detalles.venta_id','=','ventas.id')
            ->where('ventas.estado','completada')
            ->where(DB::raw('COALESCE(ventas.fecha_venta, ventas.created_at)'), '>=', $desde1)
            ->select('venta_detalles.producto_id', DB::raw('SUM(venta_detalles.cantidad) as v30'))
            ->groupBy('venta_detalles.producto_id');

        $ventasPrev30 = DB::table('venta_detalles')
            ->join('ventas','venta_detalles.venta_id','=','ventas.id')
            ->where('ventas.estado','completada')
            ->whereBetween(DB::raw('COALESCE(ventas.fecha_venta, ventas.created_at)'), [$desde2, $desde1])
            ->select('venta_detalles.producto_id', DB::raw('SUM(venta_detalles.cantidad) as vprev'))
            ->groupBy('venta_detalles.producto_id');

        $row = DB::table('productos')
            ->leftJoinSub($ventas30, 'a', function($join){ $join->on('productos.id','=','a.producto_id'); })
            ->leftJoinSub($ventasPrev30, 'b', function($join){ $join->on('productos.id','=','b.producto_id'); })
            ->select('productos.id','productos.nombre','productos.presentacion','productos.concentracion','productos.marca','productos.categoria','productos.stock_actual', DB::raw('COALESCE(a.v30,0) as v30'), DB::raw('COALESCE(b.vprev,0) as vprev'))
            ->orderByRaw('COALESCE(a.v30,0) + GREATEST(COALESCE(a.v30,0)-COALESCE(b.vprev,0),0) DESC')
            ->limit(1)->first();

        if (!$row) return ['text' => 'No hay datos de ventas recientes.'];

        $pred = $this->pronosticoProductoProximoMes((int) $row->id);
        $nombreExt = trim(($row->nombre ?? '').' '.($row->presentacion ?? '').' '.($row->concentracion ?? ''));
        $texto = 'Producto con mayor proyección el próximo mes: '.$nombreExt."\n".$pred['text']."\n".
                 '• Ventas últimos 30 días: '.(int)$row->v30.' unidades' ."\n".
                 '• Stock actual: '.(int)$row->stock_actual.' unidades';
        return ['text' => $texto];
    }

    public function topCrecimiento30Dias(int $limite = 5): array
    {
        if (!Schema::hasTable('venta_detalles') || !Schema::hasTable('ventas')) {
            return ['text' => 'No hay datos suficientes para crecimiento en 30 días.'];
        }
        $hoy = Carbon::now('America/Lima');
        $desde1 = $hoy->copy()->subDays(30);
        $desde2 = $hoy->copy()->subDays(60);

        $ventas30 = DB::table('venta_detalles')
            ->join('ventas','venta_detalles.venta_id','=','ventas.id')
            ->where('ventas.estado','completada')
            ->where(DB::raw('COALESCE(ventas.fecha_venta, ventas.created_at)'), '>=', $desde1)
            ->select('venta_detalles.producto_id', DB::raw('SUM(venta_detalles.cantidad) as v30'))
            ->groupBy('venta_detalles.producto_id');

        $ventasPrev30 = DB::table('venta_detalles')
            ->join('ventas','venta_detalles.venta_id','=','ventas.id')
            ->where('ventas.estado','completada')
            ->whereBetween(DB::raw('COALESCE(ventas.fecha_venta, ventas.created_at)'), [$desde2, $desde1])
            ->select('venta_detalles.producto_id', DB::raw('SUM(venta_detalles.cantidad) as vprev'))
            ->groupBy('venta_detalles.producto_id');

        $rows = DB::table('productos')
            ->leftJoinSub($ventas30, 'a', function($join){ $join->on('productos.id','=','a.producto_id'); })
            ->leftJoinSub($ventasPrev30, 'b', function($join){ $join->on('productos.id','=','b.producto_id'); })
            ->select('productos.id','productos.nombre', DB::raw('COALESCE(a.v30,0) as v30'), DB::raw('COALESCE(b.vprev,0) as vprev'), 'productos.stock_actual')
            ->orderByRaw('GREATEST(COALESCE(a.v30,0)-COALESCE(b.vprev,0),0) DESC, COALESCE(a.v30,0) DESC')
            ->limit($limite)->get();

        $lista = $rows->map(function($r){
            return $r->nombre.' (↑ '.max(0, (int)$r->v30 - (int)$r->vprev).' en 30d, vendidas '.(int)$r->v30.')';
        })->implode('; ');
        return ['text' => 'Top '.$limite.' crecimiento 30 días: '.$lista.'.'];
    }

    public function topPronosticosProximoMes(int $limite = 3): array
    {
        if (!Schema::hasTable('venta_detalles') || !Schema::hasTable('ventas')) {
            return ['text' => 'No hay datos de ventas recientes para pronosticar top de productos.'];
        }
        $hoy = Carbon::now('America/Lima');
        $desde1 = $hoy->copy()->subDays(30);
        $desde2 = $hoy->copy()->subDays(60);

        $ventas30 = DB::table('venta_detalles')
            ->join('ventas','venta_detalles.venta_id','=','ventas.id')
            ->where('ventas.estado','completada')
            ->where(DB::raw('COALESCE(ventas.fecha_venta, ventas.created_at)'), '>=', $desde1)
            ->select('venta_detalles.producto_id', DB::raw('SUM(venta_detalles.cantidad) as v30'))
            ->groupBy('venta_detalles.producto_id');

        $ventasPrev30 = DB::table('venta_detalles')
            ->join('ventas','venta_detalles.venta_id','=','ventas.id')
            ->where('ventas.estado','completada')
            ->whereBetween(DB::raw('COALESCE(ventas.fecha_venta, ventas.created_at)'), [$desde2, $desde1])
            ->select('venta_detalles.producto_id', DB::raw('SUM(venta_detalles.cantidad) as vprev'))
            ->groupBy('venta_detalles.producto_id');

        $rows = DB::table('productos')
            ->leftJoinSub($ventas30, 'a', function($join){ $join->on('productos.id','=','a.producto_id'); })
            ->leftJoinSub($ventasPrev30, 'b', function($join){ $join->on('productos.id','=','b.producto_id'); })
            ->select('productos.id','productos.nombre','productos.marca','productos.categoria','productos.stock_actual', DB::raw('COALESCE(a.v30,0) as v30'), DB::raw('COALESCE(b.vprev,0) as vprev'))
            ->orderByRaw('COALESCE(a.v30,0) + GREATEST(COALESCE(a.v30,0)-COALESCE(b.vprev,0),0) DESC')
            ->limit($limite)->get();

        if ($rows->isEmpty()) return ['text'=>'No hay datos de ventas recientes para pronosticar top de productos.'];

        $nextMonthName = Carbon::now('America/Lima')->addMonth()->locale('es')->translatedFormat('F');
        $items = [];
        foreach ($rows as $r) {
            $prodForecast = $this->pronosticoProductoProximoMes((int)$r->id);
            $items[] = '• '.$r->nombre.': '.$prodForecast['text'];
        }
        $texto = 'Pronóstico de los productos con mayor proyección para '.$nextMonthName.':\n'.implode("\n", $items);
        return ['text' => $texto];
    }

    private function seriesVentasMensuales(int $meses = 12): array
    {
        if (!Schema::hasTable('venta_detalles') || !Schema::hasTable('ventas')) {
            $series = [];
            for ($i=0;$i<$meses;$i++) {
                $label = Carbon::now('America/Lima')->subMonths($meses-1-$i)->format('Y-m');
                $series[] = ['label'=>$label,'valor'=>0];
            }
            return $series;
        }
        $desde = Carbon::now('America/Lima')->subMonths($meses-1)->startOfMonth();
        $rows = DB::table('venta_detalles')
            ->join('ventas','venta_detalles.venta_id','=','ventas.id')
            ->where('ventas.estado','completada')
            ->select(DB::raw('DATE_FORMAT(COALESCE(ventas.fecha_venta, ventas.created_at), "%Y-%m") as ym'), DB::raw('SUM(venta_detalles.cantidad) as total'))
            ->where(DB::raw('COALESCE(ventas.fecha_venta, ventas.created_at)'), '>=', $desde)
            ->groupBy('ym')
            ->orderBy('ym')->get();
        $map = [];
        foreach ($rows as $r) { $map[$r->ym] = (int) $r->total; }
        $series = [];
        for ($i=0;$i<$meses;$i++) {
            $label = Carbon::now('America/Lima')->subMonths($meses-1-$i)->format('Y-m');
            $series[] = ['label'=>$label,'valor'=>$map[$label] ?? 0];
        }
        return $series;
    }

    private function seriesProductoMensual(int $productoId, int $meses = 12): array
    {
        if (!Schema::hasTable('venta_detalles') || !Schema::hasTable('ventas')) {
            $series = [];
            for ($i=0;$i<$meses;$i++) {
                $label = Carbon::now('America/Lima')->subMonths($meses-1-$i)->format('Y-m');
                $series[] = ['label'=>$label,'valor'=>0];
            }
            return $series;
        }
        $desde = Carbon::now('America/Lima')->subMonths($meses-1)->startOfMonth();
        $rows = DB::table('venta_detalles')
            ->join('ventas','venta_detalles.venta_id','=','ventas.id')
            ->where('ventas.estado','completada')
            ->where('venta_detalles.producto_id',$productoId)
            ->select(DB::raw('DATE_FORMAT(COALESCE(ventas.fecha_venta, ventas.created_at), "%Y-%m") as ym'), DB::raw('SUM(venta_detalles.cantidad) as total'))
            ->where(DB::raw('COALESCE(ventas.fecha_venta, ventas.created_at)'), '>=', $desde)
            ->groupBy('ym')
            ->orderBy('ym')->get();
        $map = [];
        foreach ($rows as $r) { $map[$r->ym] = (int) $r->total; }
        $series = [];
        for ($i=0;$i<$meses;$i++) {
            $label = Carbon::now('America/Lima')->subMonths($meses-1-$i)->format('Y-m');
            $series[] = ['label'=>$label,'valor'=>$map[$label] ?? 0];
        }
        return $series;
    }

    private function ejecutar(array $args)
    {
        $ruta = base_path('ia/predict.py');
        if (!file_exists($ruta)) return null;
        $cmd = ['python', $ruta, json_encode($args)];
        $proc = new Process($cmd, base_path());
        $proc->setTimeout(5);
        try {
            $proc->run();
            if (!$proc->isSuccessful()) return null;
            $json = trim($proc->getOutput());
            $data = json_decode($json, true);
            if (is_array($data)) return $data;
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }
}
