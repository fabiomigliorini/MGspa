<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SSOController extends Controller
{
    public function login(Request $request)
    {
        try {
            $response = Http::post(env('SSO_HOST_QUASAR') . '/oauth/token', [
                'grant_type' => 'password',
                'client_id' => env('SSO_CLIENT_ID_QUASAR_LOGIN_FORM'),
                'client_secret' => env('SSO_SECRET_QUASAR_LOGIN_FORM'),
                'username' => $request->username,
                'password' => $request->password,
            ]);
            return $response->getBody();
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 500;
            $msg = match ($code) {
                400 => 'Requisição invalida. Usuário e senha obrigatório!',
                401 => 'Usuario ou senha incorretos, tente novamente',
                default => 'Algo deu errado no servidor. Tente novamente mais tarde',
            };
            return response()->json($msg, $code);
        }
    }

    public function getLoginQuasar(Request $request)
    {
        $query = http_build_query([
            'client_id' => env('SSO_CLIENT_ID_QUASAR'),
            'redirect_uri' => env('SSO_CALLBACK_QUASAR'),
            'response_type' => 'code',
            'scope' => env('SSO_SCOPES'),
            'state' => $request->state,
            'prompt' => false,
        ]);
        return redirect(env('SSO_HOST_QUASAR') . '/oauth/authorize?' . $query);
    }

    public function getCallback(Request $request)
    {
        $response = Http::asForm()->post(env('SSO_HOST_QUASAR') . '/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => env('SSO_CLIENT_ID_QUASAR'),
            'client_secret' => env('SSO_CLIENT_SECRET_QUASAR'),
            'redirect_uri' => env('SSO_CALLBACK_QUASAR'),
            'code' => $request->code,
        ]);
        try {
            $accessToken = $response['access_token'];
        } catch (\Throwable $th) {
            return redirect('login')->withError('Falha ao obter informações de login! Tente novamente.');
        }

        return match ($request->state) {
            'quasar-v1' => redirect(env('URL_REDIRECT_FRONTEND') . $accessToken),
            'quasar-v2' => redirect(env('URL_REDIRECT_FRONTEND_V2') . $accessToken),
            default => redirect(env('SSO_HOST_QUASAR')),
        };
    }
}
