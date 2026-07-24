<?php

namespace App\Models;

use App\Enums\FocusArea;
use App\Enums\Level;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workout extends Model
{
    protected $fillable = [
        'name',
        'focus_area',
        'estimated_minutes',
        'level',
        'notes',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'focus_area' => FocusArea::class,
            'level' => Level::class,
            'is_published' => 'boolean',
        ];
    }

    /** The ordered exercises that make up this template. */
    public function workoutExercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class)->orderBy('position');
    }

    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'workout_exercises')
            ->withPivot(['position', 'sets', 'target_reps', 'target_duration_s', 'rest_s'])
            ->orderByPivot('position')
            ->withTimestamps();
    }
}
