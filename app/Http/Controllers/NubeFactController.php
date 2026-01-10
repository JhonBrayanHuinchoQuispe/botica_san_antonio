<?php

namespace App\Http\Controllers;

use App\Services\NubeFactService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class NubeFactController extends Controller
{
    /**
     * Envía un payload arbitrario a Nubefact
     */
    public function enviar(Request $request)
    {
        try {
            $service = new NubeFactService();
            $payload = $request->all();
            $resultado = $service->enviar($payload);
            return response()->json($resultado);
        } catch (Exception $e) {
            Log::error('Error enviando a Nubefact: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera y envía una boleta de ejemplo a Nubefact (modo prueba).
     */
    public function boletaPrueba(Request $request)
    {
        try {
            $importe = (float) ($request->input('importe', 10.0));
            $service = new NubeFactService();
            $payload = $service->construirBoletaEjemplo($importe);
            $resultado = $service->enviar($payload);
            return response()->json($resultado);
        } catch (Exception $e) {
            Log::error('Error enviando boleta de prueba a Nubefact: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}