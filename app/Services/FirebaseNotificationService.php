<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    private $serverKey;
    private $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        // Agregar tu Server Key de Firebase aquí
        $this->serverKey = env('FIREBASE_SERVER_KEY', 'tu_server_key_aqui');
    }

    /**
     * Enviar notificación a un dispositivo específico
     */
    public function sendToDevice($token, $title, $body, $data = [])
    {
        return $this->sendNotification([
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'badge' => 1,
            ],
            'data' => $data,
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'botica_notifications',
                    'sound' => 'default',
                    'priority' => 'high',
                ]
            ]
        ]);
    }

    /**
     * Enviar notificación a un tema (topic)
     */
    public function sendToTopic($topic, $title, $body, $data = [])
    {
        return $this->sendNotification([
            'to' => "/topics/{$topic}",
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'badge' => 1,
            ],
            'data' => $data,
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'botica_notifications',
                    'sound' => 'default',
                    'priority' => 'high',
                ]
            ]
        ]);
    }

    /**
     * Notificar stock bajo
     */
    public function notifyLowStock($productName, $currentStock, $minStock)
    {
        $title = "⚠️ Stock Bajo - {$productName}";
        $body = "Quedan solo {$currentStock} unidades (mínimo: {$minStock})";
        
        $data = [
            'type' => 'stock_bajo',
            'product_name' => $productName,
            'current_stock' => (string) $currentStock,
            'min_stock' => (string) $minStock,
        ];

        return $this->sendToTopic('inventario', $title, $body, $data);
    }

    /**
     * Notificar producto agotado
     */
    public function notifyOutOfStock($productName)
    {
        $title = "🚨 Producto Agotado";
        $body = "{$productName} se ha agotado completamente";
        
        $data = [
            'type' => 'agotado',
            'product_name' => $productName,
        ];

        return $this->sendToTopic('alertas_criticas', $title, $body, $data);
    }

    /**
     * Notificar venta completada
     */
    public function notifyNewSale($total, $products_count)
    {
        $title = "💰 Nueva Venta Registrada";
        $body = "Venta por S/ {$total} - {$products_count} productos";
        
        $data = [
            'type' => 'venta_completada',
            'total' => (string) $total,
            'products_count' => (string) $products_count,
        ];

        return $this->sendToTopic('inventario', $title, $body, $data);
    }

    /**
     * Notificar lote próximo a vencer
     */
    public function notifyExpiringBatch($productName, $loteNumber, $expiryDate)
    {
        $title = "📅 Lote Próximo a Vencer";
        $body = "{$productName} - Lote {$loteNumber} vence el {$expiryDate}";
        
        $data = [
            'type' => 'lote_vencimiento',
            'product_name' => $productName,
            'lote_number' => $loteNumber,
            'expiry_date' => $expiryDate,
        ];

        return $this->sendToTopic('alertas_criticas', $title, $body, $data);
    }

    /**
     * Método privado para enviar la notificación
     */
    private function sendNotification($payload)
    {
        try {
            Log::info('📤 Enviando notificación FCM', $payload);

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            $result = $response->json();
            
            if ($response->successful() && isset($result['success']) && $result['success'] > 0) {
                Log::info('✅ Notificación enviada exitosamente', $result);
                return [
                    'success' => true,
                    'message' => 'Notificación enviada',
                    'response' => $result
                ];
            } else {
                Log::error('❌ Error enviando notificación FCM', [
                    'payload' => $payload,
                    'response' => $result,
                    'status' => $response->status()
                ]);
                return [
                    'success' => false,
                    'message' => 'Error enviando notificación',
                    'response' => $result
                ];
            }
        } catch (\Exception $e) {
            Log::error('❌ Excepción enviando notificación FCM: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Probar notificación (para debugging)
     */
    public function sendTestNotification()
    {
        return $this->sendToTopic(
            'inventario',
            '🧪 Notificación de Prueba',
            'Esta es una notificación de prueba para verificar que el sistema funciona correctamente',
            [
                'type' => 'test',
                'timestamp' => now()->toISOString()
            ]
        );
    }
}