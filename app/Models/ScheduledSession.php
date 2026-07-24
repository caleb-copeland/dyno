<?php

namespace App\Models;

use App\Enums\FocusArea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledSession extends Model
{
    protected $fillable = [
        'schedule_id',
        'day_of_week',
        'focus_area',
        'workout_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'focus_area' => FocusArea::class,
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }
}
