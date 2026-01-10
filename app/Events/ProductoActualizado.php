<?php

namespace App\Events;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductoActualizado
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $producto;
    public $usuario;
    public $cambios;
    public $accion;

    /**
     * Create a new event instance.
     */
    public function __construct(Producto $producto, User $usuario, array $cambios, string $accion = 'actualizado')
    {
        $this->producto = $producto;
        $this->usuario = $usuario;
        $this->cambios = $cambios;
        $this->accion = $accion;
    }
}