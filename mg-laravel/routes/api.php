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
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'Auth\LoginController@authenticate');
    Route::get('logout', 'Auth\LoginController@logout');
    Route::get('check', 'Auth\LoginController@check');
    Route::get('refresh', 'Auth\LoginController@refreshToken');
});

Route::group(['middleware'=>['cors', 'api', 'jwt.auth']], function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:api');


    // Usuários
    Route::get('usuario/{id}/autor', 'UsuarioController@autor');
    Route::get('usuario/impressoras', 'UsuarioController@impressoras');
    Route::resource('usuario', 'UsuarioController');

    // Marcas
    Route::resource('marca', 'MarcaController');

    // Permissões de Grupo
    Route::resource('permissao', 'PermissaoController');

    // Grupos de usuário
    Route::resource('grupo-usuario', 'GrupoUsuarioController');

    // Marcas
    //Route::resource('produto', 'ProdutoController');
    //Route::post('produto/{id}/inativo', 'ProdutoController@activate');
    //Route::delete('produto/{id}/inativo', 'ProdutoController@inactivate');
    Route::resource('produto', 'ProdutoController');
    //Route::options('produto/{id}', 'ProdutoController@options');

    Route::resource('natureza-operacao', 'NaturezaOperacaoController');

    // Rotas Dinamicas

    // EstoqueLocalProdutoVariacao
    Route::resource('estoque-local-produto-variacao', 'EstoqueLocalProdutoVariacaoController');

    // Cidade
    Route::resource('cidade', 'CidadeController');

    // Estado
    Route::resource('estado', 'EstadoController');

    // Pais
    Route::resource('pais', 'PaisController');

});
