<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use JWTAuth;
use JWTAuthException;
use App\Models\Usuario;

class ApiAuthController extends Controller
{

    public function __construct()
    {
        $this->usuario = new Usuario;
    }

    public function login(Request $request){

        $credentials = [
          'usuario' => $request->usuario,
          'password' => $request->senha,
        ];

        $jwt = '';

        try {
            if (!$jwt = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'response' => 'error',
                    'message' => 'invalid_credentials',
                ], 401);
            }
        } catch (JWTAuthException $e) {
            return response()->json([
                'response' => 'error',
                'message' => 'failed_to_create_token',
            ], 500);
        }
        return response()->json([
            'response' => 'success',
            'result' => ['token' => $jwt]
        ]);
    }

    public function getAuthUser(Request $request){
        $user = JWTAuth::toUser($request->token);
        return response()->json(['result' => $user]);
    }
}
