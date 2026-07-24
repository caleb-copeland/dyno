<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * active=1 is only checked as a credential at Auth::attempt() time, so a user
 * deactivated mid-session (or re-authenticated via a remember-me cookie, which
 * bypasses the credential check entirely) would otherwise keep access for the
 * full session lifetime. This enforces active on every authenticated request.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->active) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        return $next($request);
    }
}
