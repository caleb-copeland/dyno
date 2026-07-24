<?php

namespace App\Filament\Resources\Invites\Pages;

use App\Filament\Resources\Invites\InviteResource;
use App\Models\Invite;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CreateInvite extends CreateRecord
{
    protected static string $resource = InviteResource::class;

    /** Raw token — held only for this request so we can show the link once. */
    protected ?string $rawToken = null;

    protected function handleRecordCreation(array $data): Model
    {
        [$invite, $raw] = Invite::issue(
            email: $data['email'],
            invitedBy: Auth::id(),
            expiresAt: isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
        );

        $this->rawToken = $raw;

        return $invite;
    }

    protected function afterCreate(): void
    {
        $url = route('register', ['token' => $this->rawToken]);

        Notification::make()
            ->title('Invite created')
            ->body("Copy this one-time link now — it is not stored and cannot be shown again:\n\n{$url}")
            ->persistent()
            ->success()
            ->send();
    }

    protected function getCreatedNotification(): ?Notification
    {
        // Suppressed — afterCreate() sends the link notification instead.
        return null;
    }
}
