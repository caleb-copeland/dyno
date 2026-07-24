<?php

namespace App\Filament\Resources\Exercises\Schemas;

use App\Enums\FocusArea;
use App\Enums\PrescriptionType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ExerciseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('focus_area')
                    ->options(FocusArea::class)
                    ->required(),
                Select::make('prescription_type')
                    ->options(PrescriptionType::class)
                    ->required(),
                Textarea::make('instructions')
                    ->rows(4)
                    ->columnSpanFull(),
                TextInput::make('media_url')
                    ->url()
                    ->maxLength(2048),
                Toggle::make('is_finger_intensive')
                    ->label('Finger-intensive')
                    ->helperText('The scheduler treats this as a maximal finger load — it drives the hard injury rules, so set it per exercise, not per focus area.')
                    ->default(false),
            ]);
    }
}
