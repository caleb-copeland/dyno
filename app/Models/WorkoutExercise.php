<?php

namespace App\Models;

use App\Enums\PrescriptionBasis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutExercise extends Model
{
    protected $fillable = [
        'workout_id',
        'exercise_id',
        'position',
        'sets',
        'target_reps',
        'target_duration_s',
        'rest_s',
        'interval_work_s',
        'interval_rest_s',
        'interval_reps',
        'prescription_basis',
        'percent_of_test',
    ];

    protected function casts(): array
    {
        return [
            'prescription_basis' => PrescriptionBasis::class,
            'percent_of_test' => 'decimal:2',
        ];
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
