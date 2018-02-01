<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use Exception;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class JWT extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $token = $this->auth->setRequest($request)->getToken()) {
            return response()->json(['mensagem' => 'Usuário não autenticado'], 401);
        }

        try {
            $user = $this->auth->authenticate($token);
        } catch (TokenExpiredException $e) {
            return response()->json(['mensagem' => 'Sessão expirada'], 401);
        } catch (JWTException $e) {
            return response()->json(['mensagem' => 'Token inválido'], 401);
        }

        if (! $user) {
            return response()->json(['mensagem' => 'Usuário não encontrado'], 401);
        }

        // $this->events->fire('tymon.jwt.valid', $user);

        return $next($request);
    }
}
