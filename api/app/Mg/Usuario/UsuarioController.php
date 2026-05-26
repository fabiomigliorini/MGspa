<?php

namespace Mg\Usuario;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Porta parcial do UsuarioController do MGspa/laravel.
 *
 * Por enquanto só o método `permissoesUsuarios` (rota `GET v1/auth/user`)
 * — é o que os 4 frontends Quasar usam pra validar a sessão. Outros
 * métodos do legacy (CRUD, novoUsuario, resetSenha, gruposAdicionar etc.)
 * serão portados conforme as telas de Usuário forem migradas.
 */
class UsuarioController extends Controller
{
    public function permissoesUsuarios(Request $request)
    {
        $usuario = Auth::user();
        $token = $request->user()->token();
        $expiresAt = $token?->expires_at;
        $expiresIn = $expiresAt ? max(0, Carbon::now()->diffInSeconds($expiresAt, false)) : null;

        return (new UsuarioResource($usuario))->additional([
            'meta' => [
                'expires_in' => $expiresIn,
                'expires_at' => $expiresAt?->toIso8601String(),
            ],
        ]);
    }
}
