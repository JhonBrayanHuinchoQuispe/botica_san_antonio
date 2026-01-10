<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImageOptimizationViewController extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    /**
     * Mostrar la vista de optimización de imágenes
     */
    public function index()
    {
        return view('admin.image-optimization');
    }
}