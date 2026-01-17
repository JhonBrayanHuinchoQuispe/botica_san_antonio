<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        try {
            $credentialsPath = storage_path('app/firebase_credentials.json');
            
            if (!file_exists($credentialsPath)) {
                Log::warning('Firebase credentials file not found at: ' . $credentialsPath);
                return;
            }

            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Firebase init error: ' . $e->getMessage());
        }
    }

    /**
     * Enviar notificación a un tema (ej: 'almacen', 'gerencia')
     */
    public function sendToTopic($topic, $title, $body, $data = [])
    {
        if (!$this->messaging) return;

        try {
            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message);
            Log::info("FCM sent to topic {$topic}: {$title}");
        } catch (\Exception $e) {
            Log::error('FCM Send Error: ' . $e->getMessage());
        }
    }

    /**
     * Enviar alerta de stock bajo
     */
    public function sendStockAlert($productName, $currentStock, $minStock)
    {
        $status = $currentStock <= 0 ? 'AGOTADO' : 'BAJO';
        $emoji = $currentStock <= 0 ? '🔴' : '⚠️';
        
        $title = "{$emoji} Alerta de Stock: {$productName}";
        $body = "El producto ha alcanzado un nivel {$status}. Stock actual: {$currentStock}";

        // Enviar al tema 'inventario' (la app móvil debe suscribirse a este tema)
        $this->sendToTopic('inventario', $title, $body, [
            'type' => 'stock_alert',
            'product' => $productName,
            'stock' => $currentStock
        ]);
    }
}
