<?php

namespace App\Http\Controllers\IA;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Services\IA\PrediccionServicio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        $texto = trim($request->input('query', $request->input('q', '')));
        $plain = filter_var($request->input('plain', false), FILTER_VALIDATE_BOOLEAN);
        if ($texto === '') {
            return response()->json([
                'text' => 'Escribe una consulta para analizar ventas, stock y predicciones.',
            ]);
        }

        $bajo = mb_strtolower($texto);
        $servicio = new PrediccionServicio();

        // Acceso genérico: conteo y listado para entidades comunes
        if (str_contains($bajo,'proveedor')) {
            if (str_contains($bajo,'cuantos') || str_contains($bajo,'cuántos') || str_contains($bajo,'total')) {
                $provTables = ['proveedores','tblProveedor','proveedor','suppliers'];
                foreach ($provTables as $t) {
                    if (Schema::hasTable($t)) {
                        $row = DB::select("SELECT COUNT(*) AS c FROM $t");
                        $count = !empty($row) ? (int) $row[0]->c : 0;
                        return response()->json(['success'=>true,'data'=>['text'=>'Total de proveedores registrados: '.$count.'.']]);
                    }
                }
                return response()->json(['success'=>true,'data'=>['text'=>'No se encontró la tabla de proveedores.']]);
            }
            if (str_contains($bajo,'lista') || str_contains($bajo,'listar')) {
                $provTables = ['proveedores','tblProveedor','proveedor','suppliers'];
                foreach ($provTables as $t) {
                    if (Schema::hasTable($t)) {
                        $cols = DB::getSchemaBuilder()->getColumnListing($t);
                        $name = null; foreach (['nombre','razon_social','proveedor','name'] as $c){ if (in_array($c,$cols)){ $name=$c; break; } }
                        $tel = null; foreach (['telefono','tel','phone'] as $c){ if (in_array($c,$cols)){ $tel=$c; break; } }
                        $doc = null; foreach (['ruc','documento','nro_documento'] as $c){ if (in_array($c,$cols)){ $doc=$c; break; } }
                        $q = DB::table($t)->select(($name?:$cols[0]).($tel?','.$tel:'').($doc?','.$doc:''))->orderBy($name?:$cols[0])->limit(50);
                        $rows = $q->get();
                        if ($rows->count()>0) {
                            $lista = $rows->map(function($r) use ($name,$tel,$doc){
                                $line = '• '.($r->{$name?:array_key_first((array)$r)} ?? '(sin nombre)');
                                if ($doc && isset($r->{$doc})) $line .= ' · '.$r->{$doc};
                                if ($tel && isset($r->{$tel})) $line .= ' · '.$r->{$tel};
                                return $line;
                            })->implode("\n");
                            return response()->json(['success'=>true,'data'=>['text'=>'Proveedores registrados:\n'.$lista]]);
                        }
                        return response()->json(['success'=>true,'data'=>['text'=>'No hay proveedores registrados.']]);
                    }
                }
                return response()->json(['success'=>true,'data'=>['text'=>'No se encontró la tabla de proveedores.']]);
            }
        }

        if (str_contains($bajo, 'predic') && str_contains($bajo, 'venta')) {
            $rango = $request->input('range', '7d');
            $pred = $servicio->ventas($rango);
            if ($plain) { unset($pred['visualization']); }
            return response()->json([
                'success' => true,
                'data' => $pred,
            ]);
        }

        if ((str_contains($bajo, 'top') || str_contains($bajo, 'mas vendido')) && str_contains($bajo, 'producto')) {
            $pred = $servicio->topProductos('mes');
            if ($plain) { unset($pred['visualization']); }
            return response()->json([
                'success' => true,
                'data' => $pred,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'text' => 'Consulta recibida. Puedo predecir ventas y listar productos más vendidos. Por favor indica: "predicción ventas 7 días" o "producto más vendido del mes".',
            ],
        ]);
    }
}
