<?php

namespace App\Models;

use App\Enums\FocusArea;
use App\Enums\PrescriptionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    protected $fillable = [
        'name',
        'focus_area',
        'prescription_type',
        'instructions',
        'media_url',
        'is_finger_intensive',
    ];

    protected function casts(): array
    {
        return [
            'focus_area' => FocusArea::class,
            'prescription_type' => PrescriptionType::class,
            'is_finger_intensive' => 'boolean',
        ];
    }

    public function workoutExercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class);
    }

    public function workouts(): BelongsToMany
    {
        return $this->belongsToMany(Workout::class, 'workout_exercises')
            ->withPivot(['position', 'sets'])
            ->withTimestamps();
    }
}
