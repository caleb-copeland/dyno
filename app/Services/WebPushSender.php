<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushSender
{
    public function enabled(): bool
    {
        return (bool) config('webpush.enabled');
    }

    /**
     * Send one notification. Returns true on success. Prunes the subscription
     * if the push service reports it gone (404/410).
     *
     * @param  array<string,mixed>  $payload
     */
    public function send(PushSubscription $sub, array $payload): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $webPush = new WebPush(['VAPID' => [
            'subject' => config('webpush.vapid.subject'),
            'publicKey' => config('webpush.vapid.public_key'),
            'privateKey' => config('webpush.vapid.private_key'),
        ]]);

        $subscription = Subscription::create([
            'endpoint' => $sub->endpoint,
            'publicKey' => $sub->public_key,
            'authToken' => $sub->auth_token,
            'contentEncoding' => $sub->content_encoding ?: 'aesgcm',
        ]);

        $report = $webPush->sendOneNotification($subscription, json_encode($payload));

        if (! $report->isSuccess()) {
            $status = $report->getResponse()?->getStatusCode();
            if (in_array($status, [404, 410], true)) {
                $sub->delete(); // subscription expired/unsubscribed
            }

            return false;
        }

        return true;
    }
}
