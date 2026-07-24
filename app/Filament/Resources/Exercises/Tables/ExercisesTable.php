<?php

namespace App\Filament\Resources\Exercises\Tables;

use App\Enums\FocusArea;
use App\Enums\PrescriptionType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ExercisesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('focus_area')
                    ->badge()
                    ->color(fn (?FocusArea $state): string => $state?->filamentColor() ?? 'gray')
                    ->formatStateUsing(fn (?FocusArea $state): string => $state?->label() ?? '—')
                    ->sortable(),
                TextColumn::make('prescription_type')
                    ->badge()
                    ->formatStateUsing(fn (?PrescriptionType $state): string => $state?->label() ?? '—'),
                IconColumn::make('is_finger_intensive')
                    ->label('Fingers')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('focus_area')
                    ->options(FocusArea::options()),
                TernaryFilter::make('is_finger_intensive')
                    ->label('Finger-intensive'),
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
