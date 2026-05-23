<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
| À medida que controllers são migradas, registrar aqui com os MESMOS
| paths que tinham no legacy — assim, quando o cutover for feito, os
| frontends não precisam mudar nada.
*/

Route::middleware(['auth:api'])->prefix('v1')->group(function () {
    // Feriado (migrado em 23/05/2026)
    Route::get('feriado/', [\Mg\Feriado\FeriadoController::class, 'index']);
    Route::post('feriado/', [\Mg\Feriado\FeriadoController::class, 'store']);
    Route::put('feriado/{codferiado}', [\Mg\Feriado\FeriadoController::class, 'update']);
    Route::delete('feriado/{codferiado}', [\Mg\Feriado\FeriadoController::class, 'destroy']);
    Route::post('feriado/{codferiado}/inativo', [\Mg\Feriado\FeriadoController::class, 'inativar']);
    Route::delete('feriado/{codferiado}/inativo', [\Mg\Feriado\FeriadoController::class, 'ativar']);
    Route::post('feriado/gerar-ano', [\Mg\Feriado\FeriadoController::class, 'gerarAno']);

    // TipoSetor (migrado em 23/05/2026)
    Route::get('tipo-setor/', [\Mg\Filial\TipoSetorController::class, 'index']);
    Route::post('tipo-setor/', [\Mg\Filial\TipoSetorController::class, 'store']);
    Route::put('tipo-setor/{codtiposetor}', [\Mg\Filial\TipoSetorController::class, 'update']);
    Route::delete('tipo-setor/{codtiposetor}', [\Mg\Filial\TipoSetorController::class, 'destroy']);
    Route::post('tipo-setor/{codtiposetor}/inativo', [\Mg\Filial\TipoSetorController::class, 'inativar']);
    Route::delete('tipo-setor/{codtiposetor}/inativo', [\Mg\Filial\TipoSetorController::class, 'ativar']);

    // Setor (migrado em 23/05/2026)
    Route::get('setor/', [\Mg\Filial\SetorController::class, 'index']);
    Route::post('setor/', [\Mg\Filial\SetorController::class, 'store']);
    Route::put('setor/{codsetor}', [\Mg\Filial\SetorController::class, 'update']);
    Route::delete('setor/{codsetor}', [\Mg\Filial\SetorController::class, 'destroy']);
    Route::post('setor/{codsetor}/inativo', [\Mg\Filial\SetorController::class, 'inativar']);
    Route::delete('setor/{codsetor}/inativo', [\Mg\Filial\SetorController::class, 'ativar']);

    // UnidadeNegocio (migrado em 23/05/2026)
    Route::get('unidade-negocio/', [\Mg\Filial\UnidadeNegocioController::class, 'index']);
    Route::post('unidade-negocio/', [\Mg\Filial\UnidadeNegocioController::class, 'store']);
    Route::get('unidade-negocio/{codunidadenegocio}', [\Mg\Filial\UnidadeNegocioController::class, 'show']);
    Route::put('unidade-negocio/{codunidadenegocio}', [\Mg\Filial\UnidadeNegocioController::class, 'update']);
    Route::delete('unidade-negocio/{codunidadenegocio}', [\Mg\Filial\UnidadeNegocioController::class, 'destroy']);
    Route::post('unidade-negocio/{codunidadenegocio}/inativo', [\Mg\Filial\UnidadeNegocioController::class, 'inativar']);
    Route::delete('unidade-negocio/{codunidadenegocio}/inativo', [\Mg\Filial\UnidadeNegocioController::class, 'ativar']);

    // Etnia (migrado em 23/05/2026)
    Route::get('etnia/', [\Mg\Pessoa\EtniaController::class, 'index']);
    Route::post('etnia/', [\Mg\Pessoa\EtniaController::class, 'store']);
    Route::get('etnia/{codetnia}', [\Mg\Pessoa\EtniaController::class, 'show']);
    Route::put('etnia/{codetnia}', [\Mg\Pessoa\EtniaController::class, 'update']);
    Route::delete('etnia/{codetnia}', [\Mg\Pessoa\EtniaController::class, 'destroy']);
    Route::post('etnia/{codetnia}/inativo', [\Mg\Pessoa\EtniaController::class, 'inativar']);
    Route::delete('etnia/{codetnia}/inativo', [\Mg\Pessoa\EtniaController::class, 'ativar']);

    // EstadoCivil (migrado em 23/05/2026)
    Route::get('estado-civil/', [\Mg\Pessoa\EstadoCivilController::class, 'index']);
    Route::post('estado-civil/', [\Mg\Pessoa\EstadoCivilController::class, 'store']);
    Route::get('estado-civil/{codestadocivil}', [\Mg\Pessoa\EstadoCivilController::class, 'show']);
    Route::put('estado-civil/{codestadocivil}', [\Mg\Pessoa\EstadoCivilController::class, 'update']);
    Route::delete('estado-civil/{codestadocivil}', [\Mg\Pessoa\EstadoCivilController::class, 'destroy']);
    Route::post('estado-civil/{codestadocivil}/inativo', [\Mg\Pessoa\EstadoCivilController::class, 'inativar']);
    Route::delete('estado-civil/{codestadocivil}/inativo', [\Mg\Pessoa\EstadoCivilController::class, 'ativar']);

    // GrauInstrucao (migrado em 23/05/2026)
    Route::get('grau-instrucao/', [\Mg\Pessoa\GrauInstrucaoController::class, 'index']);
    Route::post('grau-instrucao/', [\Mg\Pessoa\GrauInstrucaoController::class, 'store']);
    Route::get('grau-instrucao/{codgrauinstrucao}', [\Mg\Pessoa\GrauInstrucaoController::class, 'show']);
    Route::put('grau-instrucao/{codgrauinstrucao}', [\Mg\Pessoa\GrauInstrucaoController::class, 'update']);
    Route::delete('grau-instrucao/{codgrauinstrucao}', [\Mg\Pessoa\GrauInstrucaoController::class, 'destroy']);
    Route::post('grau-instrucao/{codgrauinstrucao}/inativo', [\Mg\Pessoa\GrauInstrucaoController::class, 'inativar']);
    Route::delete('grau-instrucao/{codgrauinstrucao}/inativo', [\Mg\Pessoa\GrauInstrucaoController::class, 'ativar']);

    // Cargo (migrado em 23/05/2026)
    Route::get('cargo/', [\Mg\Colaborador\CargoController::class, 'index']);
    Route::post('cargo/', [\Mg\Colaborador\CargoController::class, 'create']);
    Route::get('cargo/{codcargo}/', [\Mg\Colaborador\CargoController::class, 'show']);
    Route::put('cargo/{codcargo}/', [\Mg\Colaborador\CargoController::class, 'update']);
    Route::delete('cargo/{codcargo}/', [\Mg\Colaborador\CargoController::class, 'delete']);
    Route::post('cargo/{codcargo}/inativo', [\Mg\Colaborador\CargoController::class, 'inativar']);
    Route::delete('cargo/{codcargo}/inativo', [\Mg\Colaborador\CargoController::class, 'ativar']);
    Route::get('cargo/pessoas-cargo/{codcargo}', [\Mg\Colaborador\CargoController::class, 'pessoasDoCargo']);

    // GrupoCliente (migrado em 23/05/2026)
    Route::get('grupocliente/', [\Mg\Pessoa\GrupoClienteController::class, 'index']); // legacy alias
    Route::get('grupo-cliente', [\Mg\Pessoa\GrupoClienteController::class, 'index']);
    Route::get('grupo-cliente/{codgrupocliente}', [\Mg\Pessoa\GrupoClienteController::class, 'show']);
    Route::post('grupo-cliente', [\Mg\Pessoa\GrupoClienteController::class, 'store']);
    Route::put('grupo-cliente/{codgrupocliente}', [\Mg\Pessoa\GrupoClienteController::class, 'update']);
    Route::post('grupo-cliente/{codgrupocliente}/inativo', [\Mg\Pessoa\GrupoClienteController::class, 'inativar']);
    Route::delete('grupo-cliente/{codgrupocliente}/inativo', [\Mg\Pessoa\GrupoClienteController::class, 'ativar']);
    Route::delete('grupo-cliente/{codgrupocliente}', [\Mg\Pessoa\GrupoClienteController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Proxy fallback pro MGspa/laravel legado
|--------------------------------------------------------------------------
| Catch-all: rotas ainda não migradas pra cá são repassadas pro backend
| antigo (api-mgspa-dev). Os frontends apontam só pra api-dev, e a
| migração controller-por-controller fica transparente.
|
| Controlado por env LEGACY_PROXY_ENABLED (default: true).
| Quando todas as rotas estiverem migradas, setar pra false e remover.
*/
if (filter_var(env('LEGACY_PROXY_ENABLED', true), FILTER_VALIDATE_BOOLEAN)) {
    Route::any('{any}', function (Request $request) {
        // `$request->path()` já vem com o prefixo "api/...". O legacy serve
        // sob o mesmo caminho, então LEGACY_API_URL aponta pra raiz do
        // backend antigo (sem o /api). Concatenamos aqui.
        $url = rtrim(config('services.legacy.url'), '/') . '/' . $request->path();

        $headers = collect($request->headers->all())
            ->except(['host', 'content-length'])
            ->mapWithKeys(fn ($v, $k) => [$k => is_array($v) ? ($v[0] ?? '') : $v])
            ->filter()
            ->toArray();

        $upstream = Http::withHeaders($headers)
            ->withOptions([
                'http_errors' => false,
                'verify' => filter_var(env('LEGACY_VERIFY_SSL', false), FILTER_VALIDATE_BOOLEAN),
            ])
            ->timeout(60)
            ->send($request->method(), $url, [
                'query' => $request->query(),
                'body' => $request->getContent(),
            ]);

        // Devolve a resposta upstream sem mexer em headers de hop-by-hop
        $excluded = ['transfer-encoding', 'content-length', 'connection'];
        $respHeaders = collect($upstream->headers())
            ->reject(fn ($_, $k) => in_array(strtolower($k), $excluded))
            ->mapWithKeys(fn ($v, $k) => [$k => is_array($v) ? implode(', ', $v) : $v])
            ->toArray();

        return response($upstream->body(), $upstream->status(), $respHeaders);
    })->where('any', '.*');
}
