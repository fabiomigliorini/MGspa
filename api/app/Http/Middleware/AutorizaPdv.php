<?php

namespace App\Http\Middleware;

use Closure;
use Mg\Pdv\PdvService;

// Rotas que exigem o dispositivo (TASK-197): o uuid vem no cabecalho X-Pdv e o dispositivo
// ativo fica na requisicao para o controller ($request->attributes->get('pdv'))
class AutorizaPdv
{
    public function handle($request, Closure $next)
    {
        $request->attributes->set('pdv', PdvService::autoriza($request));
        return $next($request);
    }
}
