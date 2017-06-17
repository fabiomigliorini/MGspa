<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

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

    #use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    #protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    #public function __construct()
    #{
    #    $this->middleware('guest')->except('logout');
    #}
    public function authenticate(Request $request)
    {
        // grab credentials from the request
        //$credentials = $request->only('email', 'password');
        $credentials = $request->only('usuario', 'password');

        try {
            // attempt to verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        // all good so return the token
        return response()->json(compact('token'));
    }

    public function check()
    {

        try {
            JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return response(['authenticated' => false]);
        }

        return response(['authenticated' => true]);
    }

    public function logout()
    {

        try {
            $token = JWTAuth::getToken();

            if ($token) {
                JWTAuth::invalidate($token);
            }

        } catch (JWTException $e) {
            return response()->json($e->getMessage(), 401);
        }

        return response()->json(['message' => 'Log out success'], 200);
    }

}
