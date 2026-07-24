<?php

namespace App\Filament\Resources\Workouts\Schemas;

use App\Enums\FocusArea;
use App\Enums\Level;
use App\Enums\PrescriptionBasis;
use App\Models\Exercise;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class WorkoutForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Workout')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('focus_area')
                            ->options(FocusArea::options())
                            ->required(),
                        Select::make('level')
                            ->options(Level::options()),
                        TextInput::make('estimated_minutes')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(600)
                            ->suffix('min'),
                        Toggle::make('is_published')
                            ->default(true)
                            ->helperText('Unpublish to retire a workout without breaking past logs.'),
                        Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Exercises')
                    ->description('Ordered — drag to reorder. This order is what the runner follows.')
                    ->schema([
                        Repeater::make('workoutExercises')
                            ->relationship()
                            ->orderColumn('position')
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => self::itemLabel($state))
                            ->addActionLabel('Add exercise')
                            ->columns(4)
                            ->schema([
                                Select::make('exercise_id')
                                    ->label('Exercise')
                                    ->options(fn () => Exercise::orderBy('name')->get()
                                        ->mapWithKeys(fn (Exercise $e) => [
                                            $e->id => $e->name.' · '.$e->focus_area->label(),
                                        ]))
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(4),

                                TextInput::make('sets')
                                    ->numeric()->minValue(1)->default(3)->required(),
                                TextInput::make('target_reps')
                                    ->numeric()->minValue(1)->label('Reps'),
                                TextInput::make('target_duration_s')
                                    ->numeric()->minValue(1)->label('Hold (s)'),
                                TextInput::make('rest_s')
                                    ->numeric()->minValue(0)->label('Rest (s)'),

                                Select::make('prescription_basis')
                                    ->options(PrescriptionBasis::options())
                                    ->default(PrescriptionBasis::Fixed->value)
                                    ->live()
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('percent_of_test')
                                    ->numeric()->minValue(0)->maxValue(2)->step(0.01)
                                    ->label('% of tested max (0–1)')
                                    ->helperText('e.g. 0.80')
                                    ->visible(fn (Get $get): bool => $get('prescription_basis') === PrescriptionBasis::PercentOfTest->value)
                                    ->columnSpan(2),

                                Section::make('Interval protocol (hangboard)')
                                    ->description('Only for interval-type exercises, e.g. 7s on / 3s off × 6.')
                                    ->columns(3)
                                    ->collapsed()
                                    ->columnSpanFull()
                                    ->schema([
                                        TextInput::make('interval_work_s')
                                            ->numeric()->minValue(1)->label('Work (s)'),
                                        TextInput::make('interval_rest_s')
                                            ->numeric()->minValue(0)->label('Off (s)'),
                                        TextInput::make('interval_reps')
                                            ->numeric()->minValue(1)->label('Reps'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    private static function itemLabel(array $state): ?string
    {
        if (empty($state['exercise_id'])) {
            return null;
        }

        $name = Exercise::find($state['exercise_id'])?->name ?? 'Exercise';
        $sets = $state['sets'] ?? null;

        return $sets ? "{$name} — {$sets} sets" : $name;
    }
}
