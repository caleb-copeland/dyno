<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Invite extends Model
{
    protected $fillable = [
        'email',
        'token_hash',
        'expires_at',
        'invited_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * Create an invite for an email and return [Invite, rawToken].
     * The raw token is shown once (in the invite link) and never persisted.
     *
     * @return array{0: self, 1: string}
     */
    public static function issue(string $email, ?int $invitedBy = null, ?Carbon $expiresAt = null): array
    {
        $raw = bin2hex(random_bytes(32));

        $invite = static::create([
            'email' => strtolower(trim($email)),
            'token_hash' => static::hashToken($raw),
            'expires_at' => $expiresAt ?? now()->addDays(14),
            'invited_by' => $invitedBy,
        ]);

        return [$invite, $raw];
    }

    public static function hashToken(string $raw): string
    {
        return hash('sha256', $raw);
    }

    /** Look up an invite by its raw token in constant-ish time via the unique hash index. */
    public static function findByToken(string $raw): ?self
    {
        if ($raw === '') {
            return null;
        }

        return static::where('token_hash', static::hashToken($raw))->first();
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNull('used_at')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function isValid(): bool
    {
        return is_null($this->used_at)
            && (is_null($this->expires_at) || $this->expires_at->isFuture());
    }

    public function markUsedBy(User $user): void
    {
        $this->forceFill([
            'used_at' => now(),
            'user_id' => $user->id,
        ])->save();
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
