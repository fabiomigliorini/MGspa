<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    public function authenticate(Request $request)
    {
        // grab credentials from the request
        $credentials = [
          'usuario' => $request->usuario,
          'password' => $request->senha,
        ];

        try {
            // attempt to verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['mensagem' => 'Usuário ou senha inválido(a)'], 401);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['mensagem' => 'Erro ao cria Token'], 500);
        }

        // all good so return the token
        return response()->json(compact('token'));
    }

    public function check()
    {
        try {
            JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {

            return response(['autenticado' => false]);
        }

        return response(['autenticado' => true]);
    }
    /**
     * Refresh JWT Token
     *
     * @return mixed
     * @throws AccessDeniedHttpException
     * @throws BadRequestHttpException
     */
    public function refreshToken()
    {
        $token = JWTAuth::getToken();
        if (! $token) {
            return response(['mensagem' => 'Token não existe']);
        }

        try {
            $token = JWTAuth::refresh($token);
        } catch (TokenInvalidException $e) {
            return response(['mensagem' => 'Token inválido']);
        }

        return $token;
    }

    public function getAuthenticatedUser()
    {
    	try {

    		if (! $user = JWTAuth::parseToken()->authenticate()) {
    			return response()->json(['user_not_found'], 404);
    		}

    	} catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

    		return response()->json(['token_expired'], $e->getStatusCode());

    	} catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

    		return response()->json(['token_invalid'], $e->getStatusCode());

    	} catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

    		return response()->json(['token_absent'], $e->getStatusCode());

    	}

    	// the token is valid and we have found the user via the sub claim
    	return response()->json(compact('user'));
    }
    
    public function logout()
    {

        try {
            $token = JWTAuth::getToken();

            if ($token) {
                JWTAuth::invalidate($token);
            }

        } catch (JWTException $e) {
            return response()->json(['mensagem' => $e->getMessage()], 401);
        }

        return response()->json(['mensagem' => 'Logout realizado com sucesso!'], 200);
    }
}
