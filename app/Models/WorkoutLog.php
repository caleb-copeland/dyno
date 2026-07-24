<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutLog extends Model
{
    protected $fillable = [
        'user_id',
        'workout_id',
        'scheduled_session_id',
        'started_at',
        'completed_at',
        'notes',
        'perceived_effort',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function isComplete(): bool
    {
        return ! is_null($this->completed_at);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    public function setLogs(): HasMany
    {
        return $this->hasMany(SetLog::class);
    }
}
