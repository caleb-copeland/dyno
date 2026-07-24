<?php

namespace App\Models;

use App\Enums\FocusArea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestResult extends Model
{
    protected $fillable = [
        'user_id',
        'focus_area',
        'metric',
        'value',
        'unit',
        'tested_at',
    ];

    protected function casts(): array
    {
        return [
            'focus_area' => FocusArea::class,
            'value' => 'decimal:2',
            'tested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
