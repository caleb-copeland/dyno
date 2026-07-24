<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // active=1 as an extra credential: deactivated users fail auth silently
        // (same generic error), without a partial login we then have to unwind.
        $credentials = $this->only('email', 'password') + ['active' => 1];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->accountKey());
            RateLimiter::hit($this->ipKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->accountKey());
        RateLimiter::clear($this->ipKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * Two independent limiters: per-account (stops targeting one user) and
     * per-IP (stops one host spraying many accounts). Either can lock out.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $accountBlocked = RateLimiter::tooManyAttempts($this->accountKey(), 5);
        $ipBlocked = RateLimiter::tooManyAttempts($this->ipKey(), 30);

        if (! $accountBlocked && ! $ipBlocked) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(
            RateLimiter::availableIn($this->accountKey()),
            RateLimiter::availableIn($this->ipKey()),
        );

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /** Per-account throttle key (5 attempts / decay). */
    public function accountKey(): string
    {
        return 'login:account:'.Str::transliterate(Str::lower($this->string('email')));
    }

    /** Per-IP throttle key (30 attempts / decay). */
    public function ipKey(): string
    {
        return 'login:ip:'.$this->ip();
    }
}
