<?php
namespace App\Http\Controllers\Inventario\categoria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Categoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = Categoria::query();
        if ($request->has('search')) {
            $query->where('nombre', 'like', '%'.$request->search.'%');
        }
        $categorias = $query->withCount('productos')->orderBy('nombre')->get();
        return response()->json(['success' => true, 'data' => $categorias]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias,nombre',
            'descripcion' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Obtener el siguiente ID manualmente
            $lastId = DB::table('categorias')->max('id') ?? 0;
            $nextId = $lastId + 1;

            $categoria = new Categoria();
            $categoria->id = $nextId;
            $categoria->nombre = $request->nombre;
            $categoria->descripcion = $request->descripcion;
            $categoria->estado = 'activo';
            $categoria->save();

            DB::commit();

            return response()->json(['success' => true, 'data' => $categoria]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar categoría: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la categoría: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias,nombre,'.$categoria->id,
            'descripcion' => 'nullable|string|max:255',
        ]);
        $categoria->update($validated);
        return response()->json(['success' => true, 'data' => $categoria]);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $categoria = Categoria::findOrFail($id);
            
            if ($categoria->productos()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una categoría que tiene productos asociados.'
                ], 409); // 409 Conflict
            }

            // Eliminar la categoria
            $categoria->delete();
            
            // Reordenar IDs después de eliminar, replicando la lógica de productos
            $categorias = DB::table('categorias')
                ->orderBy('id')
                ->get();
                
            $counter = 1;
            foreach ($categorias as $c) {
                DB::table('categorias')
                    ->where('id', $c->id)
                    ->update(['id' => $counter]);
                $counter++;
            }
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada correctamente',
                'needsRefresh' => true
            ]);
            
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Error al eliminar categoría: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la categoría: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $categoria = Categoria::withCount('productos')->find($id);
        if (!$categoria) {
            return response()->json(['success' => false, 'message' => 'Categoría no encontrada'], 404);
        }
        return response()->json(['success' => true, 'data' => $categoria]);
    }
}
