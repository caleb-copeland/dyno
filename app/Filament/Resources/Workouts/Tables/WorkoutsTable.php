<?php

namespace App\Filament\Resources\Workouts\Tables;

use App\Enums\FocusArea;
use App\Enums\Level;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class WorkoutsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('focus_area')
                    ->badge()
                    ->color(fn (?FocusArea $state): string => $state?->filamentColor() ?? 'gray')
                    ->formatStateUsing(fn (?FocusArea $state): string => $state?->label() ?? '—')
                    ->sortable(),
                TextColumn::make('level')
                    ->badge()
                    ->color(fn (?Level $state): string => $state?->filamentColor() ?? 'gray')
                    ->formatStateUsing(fn (?Level $state): ?string => $state?->label())
                    ->placeholder('—'),
                TextColumn::make('workout_exercises_count')
                    ->counts('workoutExercises')
                    ->label('Exercises')
                    ->badge(),
                TextColumn::make('estimated_minutes')
                    ->label('Est.')
                    ->suffix(' min')
                    ->placeholder('—'),
                IconColumn::make('is_published')
                    ->boolean()
                    ->label('Published'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('focus_area')
                    ->options(FocusArea::options()),
                TernaryFilter::make('is_published'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
