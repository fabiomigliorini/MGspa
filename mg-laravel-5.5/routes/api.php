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

        Route::get('usuario/{id}/autor', '\Usuario\UsuarioController@author');
        Route::get('usuario/{id}/grupos', '\Usuario\UsuarioController@groups')->name('usuario.groups');
        Route::post('usuario/{id}/grupos', '\Usuario\UsuarioController@groupsCreate')->name('usuario.groups.create');
        Route::delete('usuario/{id}/grupos', '\Usuario\UsuarioController@groupsDestroy')->name('usuario.groups.destroy');
        Route::get('usuario/{id}/detalhes', '\Usuario\UsuarioController@details')->name('usuario.details');
        Route::delete('usuario/{id}/inativo', '\Usuario\UsuarioController@activate')->name('usuario.activate');
        Route::post('usuario/{id}/inativo', '\Usuario\UsuarioController@inactivate')->name('usuario.inactivate');

        Route::apiResource('usuario', '\Usuario\UsuarioController');

        // Grupos de usuário
        Route::get('grupo-usuario/{id}/autor', '\Usuario\GrupoUsuarioController@author');
        Route::get('grupo-usuario/{id}/detalhes', '\Usuario\GrupoUsuarioController@details')->name('grupo-usuario.details');
        Route::delete('grupo-usuario/{id}/inativo', '\Usuario\GrupoUsuarioController@activate')->name('grupo-usuario.activate');
        Route::post('grupo-usuario/{id}/inativo', '\Usuario\GrupoUsuarioController@inactivate')->name('grupo-usuario.inactivate');
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
        Route::delete('imagem/{id}/inativo', '\Imagem\ImagemController@activate')->name('imagem.activate');
        Route::post('imagem/{id}/inativo', '\Imagem\ImagemController@inactivate')->name('imagem.inactivate');

        // Marcas
        Route::get('marca/{id}/detalhes', '\Marca\MarcaController@details')->name('marca.details');
        Route::delete('marca/{id}/inativo', '\Marca\MarcaController@activate')->name('marca.activate');
        Route::post('marca/{id}/inativo', '\Marca\MarcaController@inactivate')->name('marca.inactivate');
        Route::apiResource('marca', '\Marca\MarcaController');

    });

});
