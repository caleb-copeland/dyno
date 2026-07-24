<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SetLog extends Model
{
    protected $fillable = [
        'workout_log_id',
        'exercise_id',
        'set_number',
        'reps',
        'weight',
        'duration_s',
        'completed',
    ];

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
            'weight' => 'decimal:2',
        ];
    }

    public function workoutLog(): BelongsTo
    {
        return $this->belongsTo(WorkoutLog::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
