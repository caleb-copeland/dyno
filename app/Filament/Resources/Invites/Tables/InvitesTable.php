<?php

namespace App\Filament\Resources\Invites\Tables;

use App\Actions\SendInvite;
use App\Models\Invite;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->state(fn (Invite $record): string => match (true) {
                        ! is_null($record->used_at) => 'used',
                        ! is_null($record->expires_at) && $record->expires_at->isPast() => 'expired',
                        default => 'pending',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'used' => 'gray',
                        'expired' => 'danger',
                        default => 'success',
                    }),

                TextColumn::make('invitedBy.name')
                    ->label('Invited by')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('used_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                // The raw token is never stored, so a lost link can't be shown
                // again — issue a fresh one for the same email instead.
                Action::make('regenerate')
                    ->label('Resend')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (Invite $record): bool => is_null($record->used_at))
                    ->requiresConfirmation()
                    ->modalDescription('Issues a fresh link (the old one stops working) and emails it.')
                    ->action(function (Invite $record) {
                        $raw = $record->rotateToken();
                        $emailed = app(SendInvite::class)->handle($record, $raw);

                        Notification::make()
                            ->title($emailed ? "New link emailed to {$record->email}" : 'New link generated — email failed to send')
                            ->body("Shown once:\n\n".route('register', ['token' => $raw]))
                            ->persistent()
                            ->{$emailed ? 'success' : 'warning'}()
                            ->send();
                    }),

                DeleteAction::make()
                    ->label('Revoke'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
