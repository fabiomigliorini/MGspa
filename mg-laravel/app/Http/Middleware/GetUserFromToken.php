<?php

/*
 * This file is part of jwt-auth.
 *
 * (c) Sean Tymon <tymon148@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Http\Middleware;

use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Middleware\BaseMiddleware;

class GetUserFromToken extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        if (! $token = $this->auth->setRequest($request)->getToken()) {
            //return $this->respond('tymon.jwt.absent', 'Não autorizado', 401);
            return response()->json(['mensagem' => 'Não autorizado'], 401);
        }

        try {
            $user = $this->auth->authenticate($token);
        } catch (TokenExpiredException $e) {
            //return $this->respond('tymon.jwt.expired', 'token_expired', $e->getStatusCode(), [$e]);
            return response()->json(['mensagem' => 'Sessão expirada'], 401);

        } catch (JWTException $e) {
            //return $this->respond('tymon.jwt.invalid', 'token_invalid', $e->getStatusCode(), [$e]);
            return response()->json(['mensagem' => 'Token inválido'], 401);
        }

        if (! $user) {
            //return $this->respond('tymon.jwt.user_not_found', 'user_not_found', 404);
            return response()->json(['mensagem' => 'Usuário não encontrado'], 401);
        }

        $this->events->fire('tymon.jwt.valid', $user);

        return $next($request);
    }
}
