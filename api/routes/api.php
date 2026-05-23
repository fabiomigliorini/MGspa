<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas de auth — paridade com o antigo MGAuth
|--------------------------------------------------------------------------
| Paths reais ficam sob /api (prefixo automático do api: do withRouting).
| MGLara/MGsis/frontends Quasar batem nas mesmas URLs que batiam no MGAuth.
*/

Route::middleware(['throttle:api'])->group(function () {
    Route::post('oauth/token', [AuthController::class, 'getToken'])->name('auth');
    Route::post('oauth/token/json', [AuthController::class, 'getTokenJson']);
    Route::post('refresh', [AuthController::class, 'refreshToken']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
});

// check-token tem throttle próprio (600/min) porque é chamado a cada request
// pelos consumidores (MGLara/MGsis/frontends) — mesmo comportamento do MGAuth.
Route::middleware('throttle:600,1')->group(function () {
    Route::get('check-token', [AuthController::class, 'checkToken']);
});

/*
|--------------------------------------------------------------------------
| Rotas migradas do MGspa/laravel (Marcos 3+)
|--------------------------------------------------------------------------
| (vazio por enquanto — adicionar à medida que controllers forem migrados)
*/

/*
|--------------------------------------------------------------------------
| Proxy fallback pro MGspa/laravel legado
|--------------------------------------------------------------------------
| Catch-all: rotas ainda não migradas pra cá são repassadas pro backend
| antigo (api-mgspa-dev). Os frontends apontam só pra api-dev, e a
| migração controller-por-controller fica transparente.
|
| DESLIGADO no Marco 1 — Marco 1 valida só a parte de auth. Ativar quando
| começar os Marcos 3+. Comentado pra não interferir nos testes de auth.
*/
// Route::any('{any}', function (Request $request) {
//     $url = rtrim(config('services.legacy.url'), '/') . '/' . $request->path();
//     $upstream = \Illuminate\Support\Facades\Http::withHeaders(
//         collect($request->headers->all())
//             ->except(['host', 'content-length'])
//             ->map(fn ($v) => $v[0] ?? null)
//             ->filter()
//             ->toArray()
//     )->send($request->method(), $url, [
//         'query' => $request->query(),
//         'body' => $request->getContent(),
//     ]);
//     return response($upstream->body(), $upstream->status(), $upstream->headers());
// })->where('any', '.*');
