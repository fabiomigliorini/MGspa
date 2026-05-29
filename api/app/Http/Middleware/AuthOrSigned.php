<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;

class AuthOrSigned
{
    public function handle($request, Closure $next)
    {
        if ($request->hasValidSignature()) {
            return $next($request);
        }
        return app(Authenticate::class)->handle($request, $next, 'api');
    }
}
