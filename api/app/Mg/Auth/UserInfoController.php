<?php

namespace Mg\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mg\Usuario\UsuarioResource;

/**
 * GET /userinfo — OIDC Core 1.0 §5.3.
 *
 * Retorna claims OIDC (sub, preferred_username, name) + custom claims MGspa
 * (permissoes, codusuario alias int, Pessoa, filial, etc.) no mesmo objeto plano.
 *
 * Por que não usar JsonResource::$wrap = null + Resource:
 *   reusamos a lógica de transformação do UsuarioResource (que monta `permissoes`,
 *   `Pessoa`, etc.) e fazemos merge inline com os claims OIDC obrigatórios.
 */
class UserInfoController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'Token ausente, inválido ou expirado.',
            ], 401)->header(
                'WWW-Authenticate',
                'Bearer error="invalid_token", error_description="Token ausente, inválido ou expirado."'
            );
        }

        $token = $user->token();
        $expiresAt = $token?->expires_at;
        $expiresIn = $expiresAt ? max(0, now()->diffInSeconds($expiresAt)) : null;

        $usuarioData = (new UsuarioResource($user))->toArray($request);

        return response()->json(array_merge(
            // OIDC standard claims (spec — primeiro pra ficar visível no top)
            [
                'sub' => (string) $user->codusuario,
                'preferred_username' => $user->usuario,
                'name' => $user->Pessoa?->pessoa,
            ],
            // MGspa custom claims (codusuario alias int, Pessoa, permissoes, filial, etc.)
            $usuarioData,
            // Token metadata
            [
                'meta' => [
                    'expires_in' => $expiresIn,
                    'expires_at' => $expiresAt?->toIso8601String(),
                ],
            ],
        ));
    }
}
