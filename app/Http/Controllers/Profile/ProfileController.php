<?php
namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit() {
        // Solo retorna una vista vacía o string
        return 'Perfil';
    }
    public function update(Request $request) {
        // Solo retorna success
        return response()->json(['success' => true]);
    }
    public function destroy() {
        // Solo retorna success
        return response()->json(['success' => true]);
    }
} 