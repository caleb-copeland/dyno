<?php

namespace App\Filament\Resources\Invites\Tables;

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
                    ->label('New link')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (Invite $record): bool => is_null($record->used_at))
                    ->requiresConfirmation()
                    ->action(function (Invite $record) {
                        $raw = bin2hex(random_bytes(32));
                        $record->forceFill([
                            'token_hash' => Invite::hashToken($raw),
                            'expires_at' => now()->addDays(14),
                        ])->save();

                        Notification::make()
                            ->title('New link generated')
                            ->body("Copy now — shown once:\n\n".route('register', ['token' => $raw]))
                            ->persistent()
                            ->success()
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
