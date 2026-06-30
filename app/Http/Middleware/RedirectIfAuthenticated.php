<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $redirectTo = null;
        if (auth('web')->check()) {
            $redirectTo = '/dashboard';
        }

        if ($redirectTo != null) {
            return redirect($redirectTo);
        }

        $guards = empty($guards) ? [null] : $guards;
        foreach ($guards as $guard) {
            if (auth($guard)->check()) {
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
