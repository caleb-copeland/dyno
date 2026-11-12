<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount([
                'workoutLogs as sessions_count' => fn (Builder $q) => $q->whereNotNull('completed_at'),
            ]))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'admin' ? 'warning' : 'gray'),

                IconColumn::make('active')
                    ->boolean(),

                TextColumn::make('sessions_count')
                    ->label('Sessions')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->options(['member' => 'Member', 'admin' => 'Admin']),
                TernaryFilter::make('active'),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('toggleActive')
                    ->label(fn (User $record): string => $record->active ? 'Deactivate' : 'Reactivate')
                    ->icon(fn (User $record): string => $record->active ? 'heroicon-o-no-symbol' : 'heroicon-o-arrow-uturn-left')
                    ->color(fn (User $record): string => $record->active ? 'danger' : 'success')
                    ->visible(fn (User $record): bool => ! $record->is(Auth::user()))
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        // active isn't mass-assignable by design — set it explicitly.
                        $record->forceFill(['active' => ! $record->active])->save();

                        Notification::make()
                            ->title($record->active ? 'Member reactivated' : 'Member deactivated')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
