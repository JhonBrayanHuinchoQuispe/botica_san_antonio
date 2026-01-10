<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VentaProcesada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $ventaId;
    public float $total;
    public int $usuarioId;
    public array $resumen;

    public function __construct(int $ventaId, float $total, int $usuarioId, array $resumen = [])
    {
        $this->ventaId = $ventaId;
        $this->total = $total;
        $this->usuarioId = $usuarioId;
        $this->resumen = $resumen;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('ventas'),
            new PrivateChannel('users.' . $this->usuarioId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'VentaProcesada';
    }

    public function broadcastWith(): array
    {
        return [
            'venta_id' => $this->ventaId,
            'total' => $this->total,
            'usuario_id' => $this->usuarioId,
            'resumen' => $this->resumen,
            'timestamp' => now()->toISOString(),
        ];
    }
}