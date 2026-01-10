<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    protected WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => config('services.push.vapid_public'),
                'privateKey' => config('services.push.vapid_private'),
            ],
        ]);
    }

    public function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        $subs = PushSubscription::where('user_id', $userId)->get();
        foreach ($subs as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->p256dh,
                'authToken' => $sub->auth,
            ]);

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);

            $this->webPush->sendOneNotification($subscription, $payload);
        }

        foreach ($this->webPush->flush() as $report) {
            // Optionally log $report->isSuccess() / $report->getReason()
        }
    }
}