<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;

class AuthOrCookie
{
    public function handle($request, Closure $next)
    {
        if (!$request->bearerToken() && ($cookie = $request->cookie('access_token'))) {
            $request->headers->set('Authorization', 'Bearer ' . $cookie);
        }
        return app(Authenticate::class)->handle($request, $next, 'api');
    }
}
