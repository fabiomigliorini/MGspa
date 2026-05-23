<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('login');
});

/*
| GET /login?redirect_uri=...
|
| Mesma lógica do MGAuth: se já há cookie access_token válido, redireciona
| direto pra redirect_uri (ou pra AUTH_DEFAULT_REDIRECT). Se não, renderiza
| o form de login. O form POSTa em `/api/oauth/token` (route name 'auth').
*/
Route::get('/login', function (Request $request) {
    $accessToken = $request->cookie('access_token');

    if ($accessToken) {
        $request->headers->set('Authorization', 'Bearer ' . $accessToken);

        if (Auth::guard('api')->check()) {
            return redirect()->to(
                $request->query('redirect_uri') ?? config('services.auth.default_redirect')
            );
        }
    }

    return view('login', [
        'redirect_uri' => $request->query('redirect_uri'),
        'error' => $request->boolean('error'),
    ]);
})->name('login');
