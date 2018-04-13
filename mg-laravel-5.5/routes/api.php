<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['prefix' => 'v1/auth'], function () {
    Route::post('login', 'Auth\LoginController@authenticate');
    Route::get('logout', 'Auth\LoginController@logout');
    Route::get('check', 'Auth\LoginController@check');
    Route::get('refresh', 'Auth\LoginController@refreshToken');
    Route::get('user', 'Auth\LoginController@getAuthenticatedUser');
});

Route::group(['middleware' => ['cors', 'api', 'jwt-auth']], function () {

    Route::group(['prefix' => 'v1'], function () {

        // Usuários
        Route::get('usuario/{id}/autor', '\Usuario\UsuarioController@autor');
        Route::get('usuario/{id}/grupos', '\Usuario\UsuarioController@grupos')->name('usuario.grupos');
        Route::post('usuario/{id}/grupos', '\Usuario\UsuarioController@gruposAdicionar')->name('usuario.grupos.adicionar');
        Route::delete('usuario/{id}/grupos', '\Usuario\UsuarioController@gruposRemover')->name('usuario.grupos.remover');
        Route::get('usuario/{id}/detalhes', '\Usuario\UsuarioController@detalhes')->name('usuario.detalhes');
        Route::delete('usuario/{id}/inativo', '\Usuario\UsuarioController@ativar')->name('usuario.ativar');
        Route::post('usuario/{id}/inativo', '\Usuario\UsuarioController@inativar')->name('usuario.inativar');

        Route::apiResource('usuario', '\Usuario\UsuarioController');

        // Grupos de usuário
        Route::get('grupo-usuario/{id}/autor', '\Usuario\GrupoUsuarioController@autor');
        Route::get('grupo-usuario/{id}/detalhes', '\Usuario\GrupoUsuarioController@detalhes')->name('grupo-usuario.detalhes');
        Route::delete('grupo-usuario/{id}/inativo', '\Usuario\GrupoUsuarioController@ativar')->name('grupo-usuario.ativar');
        Route::post('grupo-usuario/{id}/inativo', '\Usuario\GrupoUsuarioController@inativar')->name('grupo-usuario.inativar');
        Route::apiResource('grupo-usuario', '\Usuario\GrupoUsuarioController');

        // Impressoras
        Route::get('impressora', '\Usuario\ImpressoraController@index');

        // Filiais
        Route::apiResource('filial', '\Filial\FilialController');

        // Pessoas
        Route::get('pessoa/autocomplete', '\Pessoa\PessoaController@autocomplete');
        Route::apiResource('pessoa', '\Pessoa\PessoaController');

        // Permissões
        Route::apiResource('permissao', '\Permissao\PermissaoController');

        // Estoque
        Route::apiResource('estoque-estatistica', '\Estoque\EstoqueEstatisticaController');

        // Imagem
        Route::apiResource('imagem', '\Imagem\ImagemController');
        Route::delete('imagem/{id}/inativo', '\Imagem\ImagemController@ativar')->name('imagem.ativar');
        Route::post('imagem/{id}/inativo', '\Imagem\ImagemController@inativar')->name('imagem.inativar');

        // Marcas
        Route::get('marca/{id}/detalhes', '\Marca\MarcaController@detalhes')->name('marca.detalhes');
        Route::delete('marca/{id}/inativo', '\Marca\MarcaController@ativar')->name('marca.ativar');
        Route::post('marca/{id}/inativo', '\Marca\MarcaController@inativar')->name('marca.inativar');
        Route::apiResource('marca', '\Marca\MarcaController');

    });

});
