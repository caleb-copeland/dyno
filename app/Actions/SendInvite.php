<?php

namespace App\Actions;

use App\Models\Invite;
use App\Notifications\InviteNotification;
use Illuminate\Support\Facades\Notification;
use Throwable;

/** Emails an invite link. Returns false (and reports) on failure so the admin can fall back to copying it. */
class SendInvite
{
    public function handle(Invite $invite, string $rawToken): bool
    {
        try {
            Notification::route('mail', $invite->email)->notify(new InviteNotification(
                url: route('register', ['token' => $rawToken]),
                expiresAt: $invite->expires_at,
                invitedBy: $invite->invitedBy?->name,
            ));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
