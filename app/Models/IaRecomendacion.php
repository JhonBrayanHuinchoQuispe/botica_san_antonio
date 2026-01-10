<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IaRecomendacion extends Model
{
    protected $table = 'ia_recomendaciones';

    protected $fillable = [
        'titulo',
        'pregunta',
        'descripcion',
        'tipo',
        'impacto',
    ];
}