<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;
use Mg\Auth\AuthController;
use Mg\Auth\UserInfoController;

Route::get('/', function () {
    return redirect('login');
});

/*
|--------------------------------------------------------------------------
| GET /login — página HTML (UX, não spec)
|--------------------------------------------------------------------------
| Se já tem cookie access_token válido, redireciona pra redirect_uri.
| Senão renderiza form que via JavaScript POSTa em /oauth/token.
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

/*
|--------------------------------------------------------------------------
| OAuth2 / OIDC endpoints (RFC 6749, 7009, 7662 + OIDC Core 1.0)
|--------------------------------------------------------------------------
| Vivem em routes/web.php (não routes/api.php) porque:
|   - /oauth/authorize precisa session+CSRF (middleware web)
|   - paths sem prefixo /api (padrão de mercado: Google/GitHub/Auth0)
|   - routes/api.php adiciona prefixo /api automaticamente via withRouting
*/

// POST /oauth/token (RFC 6749 §3.2) — JSON, sem session
Route::post('/oauth/token', [AuthController::class, 'token'])
    ->name('passport.token');

// POST /oauth/revoke (RFC 7009) — client_credentials no body/Basic Auth
Route::post('/oauth/revoke', [AuthController::class, 'revoke'])
    ->name('oauth.revoke');

// POST /oauth/introspect (RFC 7662) — throttle dedicado pq MGLara/MGsis batem por request
Route::middleware('throttle:600,1')->group(function () {
    Route::post('/oauth/introspect', [AuthController::class, 'introspect'])
        ->name('oauth.introspect');
});

// GET /userinfo (OIDC Core 1.0 §5.3) — Bearer auth
// Sem middleware auth:api porque o Passport's BearerTokenValidator redireciona
// (302) quando token é inválido/revogado; queremos 401 JSON com WWW-Authenticate
// header per spec OIDC. O controller resolve guard manualmente.
Route::get('/userinfo', [UserInfoController::class, 'show'])
    ->name('oidc.userinfo');

// /oauth/authorize × 3 (RFC 6749 §3.1) — consent screen do Passport
// Route names `passport.authorizations.*` LITERAIS — a view de consent depende
Route::prefix('oauth')->name('passport.')->group(function () {
    Route::middleware('web')->group(function () {
        Route::get('authorize', [AuthorizationController::class, 'authorize'])
            ->name('authorizations.authorize');
    });

    Route::middleware(['web', 'auth'])->group(function () {
        Route::post('authorize', [ApproveAuthorizationController::class, 'approve'])
            ->name('authorizations.approve');
        Route::delete('authorize', [DenyAuthorizationController::class, 'deny'])
            ->name('authorizations.deny');
    });
});
