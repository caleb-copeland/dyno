<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushController extends Controller
{
    /** The VAPID public key the browser needs to subscribe. */
    public function key(): JsonResponse
    {
        abort_unless(config('webpush.enabled'), 404);

        return response()->json(['key' => config('webpush.vapid.public_key')]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            // The server POSTs to this URL later (reminder cron) — require a
            // real https URL and refuse IP-literal/localhost hosts so the
            // endpoint can't be aimed at internal services (SSRF). Genuine
            // push-service endpoints are always https on a public hostname.
            'endpoint' => ['required', 'string', 'max:1024', 'url:https', function ($attribute, $value, $fail) {
                $host = parse_url($value, PHP_URL_HOST);
                if (! is_string($host)
                    || strcasecmp($host, 'localhost') === 0
                    || str_ends_with(strtolower($host), '.localhost')
                    || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) !== false) {
                    $fail('The push endpoint must be a public https URL.');
                }
            }],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'max:32'],
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => PushSubscription::hashEndpoint($data['endpoint'])],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? null,
            ],
        );

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'string']]);

        $request->user()->pushSubscriptions()
            ->where('endpoint_hash', PushSubscription::hashEndpoint($data['endpoint']))
            ->delete();

        return response()->json(['ok' => true]);
    }
}
