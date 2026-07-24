<?php

return [
    /*
    | VAPID keys for Web Push. Generate a pair with `php artisan app:vapid` and
    | put them in .env. Without them, push is disabled and the app degrades
    | gracefully (the "Enable reminders" control hides itself).
    */
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@dyno.local'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    'enabled' => (bool) env('VAPID_PUBLIC_KEY') && (bool) env('VAPID_PRIVATE_KEY'),
];
