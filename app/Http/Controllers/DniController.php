<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReniecService;
use Exception;

class DniController extends Controller
{
    /**
     * Consulta DNI usando ReniecService y retorna JSON para pruebas.
     */
    public function consultar(Request $request, $dni = null)
    {
        $dni = $dni ?? $request->input('dni');

        $request->merge(['dni' => $dni]);
        $request->validate([
            'dni' => 'required|digits:8'
        ]);

        $servicio = new ReniecService();

        try {
            $datos = $servicio->consultarDni($dni);
            return response()->json([
                'success' => true,
                'data' => $datos
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'diagnostico' => $servicio->diagnostico(),
            ], 400);
        }
    }
}