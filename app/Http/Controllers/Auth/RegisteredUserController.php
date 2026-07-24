<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view — only reachable with a valid, unused invite
     * token. Anything else 404s so there is no public endpoint to probe.
     */
    public function create(Request $request): View
    {
        $token = (string) $request->query('token', '');
        $invite = Invite::findByToken($token);

        if (! $invite || ! $invite->isValid()) {
            abort(404);
        }

        return view('auth.register', [
            'inviteToken' => $token,
            'inviteEmail' => $invite->email,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Redeem the invite and create the user atomically so a token cannot be
        // used twice by concurrent requests. Lock the row for the check-and-set.
        $user = DB::transaction(function () use ($validated) {
            $invite = Invite::where('token_hash', Invite::hashToken($validated['token']))
                ->lockForUpdate()
                ->first();

            if (! $invite || ! $invite->isValid() || $invite->email !== strtolower($validated['email'])) {
                throw ValidationException::withMessages([
                    'email' => 'This invitation is invalid, expired, already used, or issued for a different email address.',
                ]);
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $invite->markUsedBy($user);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return redirect(route('dashboard', absolute: false));
    }
}
