<?php

namespace App\Http\Controllers\IA;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\IaRecomendacion;
use Illuminate\Support\Facades\Log;

class RecomendacionController extends Controller
{
    public function index(Request $request)
    {
        $tipo = $request->query('type');
        $q = IaRecomendacion::query()->orderByDesc('created_at');
        if ($tipo) { $q->where('tipo', $tipo); }
        $data = $q->limit(200)->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $payload = array_merge($request->all(), (array) $request->json()->all());
        $title = $payload['title'] ?? null;
        $description = $payload['description'] ?? null;
        $question = $payload['question'] ?? null;
        $type = $payload['type'] ?? 'recomendacion';
        $impact = $payload['impact'] ?? 'Medio';
        if (!$title || !$description) {
            return response()->json(['success' => false, 'message' => 'Datos incompletos'], 422);
        }
        try {
            $rec = IaRecomendacion::create([
                'titulo' => $title,
                'pregunta' => $question,
                'descripcion' => $description,
                'tipo' => $type,
                'impacto' => $impact,
            ]);
            return response()->json(['success' => true, 'data' => $rec]);
        } catch (\Throwable $e) {
            Log::error('IA cards store failed', ['error' => $e->getMessage(), 'payload' => $payload]);
            return response()->json(['success' => false, 'message' => 'Error al guardar'], 500);
        }
    }

    public function destroy($id)
    {
        $rec = IaRecomendacion::find($id);
        if (!$rec) { return response()->json(['success' => false, 'message' => 'No encontrado'], 404); }
        $rec->delete();
        return response()->json(['success' => true, 'data' => ['deleted' => true]]);
    }

    public function add(Request $request)
    {
        // Inserción vía GET para compatibilidad rápida en hosting
        $title = $request->query('title');
        $description = $request->query('description');
        $question = $request->query('question');
        $type = $request->query('type', 'recomendacion');
        $impact = $request->query('impact', 'Medio');
        if (!$title || !$description) {
            return response()->json(['success' => false, 'message' => 'Datos incompletos'], 422);
        }
        try {
            $rec = IaRecomendacion::create([
                'titulo' => $title,
                'pregunta' => $question,
                'descripcion' => $description,
                'tipo' => $type,
                'impacto' => $impact,
            ]);
            return response()->json(['success' => true, 'data' => $rec]);
        } catch (\Throwable $e) {
            Log::error('IA cards add failed', ['error' => $e->getMessage(), 'q' => $request->query()]);
            return response()->json(['success' => false, 'message' => 'Error al guardar'], 500);
        }
    }
}
