<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    protected $fillable = [
        'user_id',
        'active',
        'week_start_day',
        'training_days',
        'climbing_days',
        'frequencies',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'training_days' => 'array',
            'climbing_days' => 'array',
            'frequencies' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ScheduledSession::class)
            ->orderBy('day_of_week')
            ->orderBy('position');
    }
}
