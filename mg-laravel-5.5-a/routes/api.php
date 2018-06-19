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
        Route::get('usuario/{id}/autor', '\Mg\Usuario\UsuarioController@autor');
        Route::get('usuario/{id}/grupos', '\Mg\Usuario\UsuarioController@grupos')->name('usuario.grupos');
        Route::post('usuario/{id}/grupos', '\Mg\Usuario\UsuarioController@gruposAdicionar')->name('usuario.grupos.adicionar');
        Route::delete('usuario/{id}/grupos', '\Mg\Usuario\UsuarioController@gruposRemover')->name('usuario.grupos.remover');
        Route::get('usuario/{id}/detalhes', '\Mg\Usuario\UsuarioController@detalhes')->name('usuario.detalhes');
        Route::delete('usuario/{id}/inativo', '\Mg\Usuario\UsuarioController@ativar')->name('usuario.ativar');
        Route::post('usuario/{id}/inativo', '\Mg\Usuario\UsuarioController@inativar')->name('usuario.inativar');

        Route::apiResource('usuario', '\Mg\Usuario\UsuarioController');

        // Grupos de usuário
        Route::get('grupo-usuario/{id}/autor', '\Mg\Usuario\GrupoUsuarioController@autor');
        Route::get('grupo-usuario/{id}/detalhes', '\Mg\Usuario\GrupoUsuarioController@detalhes')->name('grupo-usuario.detalhes');
        Route::delete('grupo-usuario/{id}/inativo', '\Mg\Usuario\GrupoUsuarioController@ativar')->name('grupo-usuario.ativar');
        Route::post('grupo-usuario/{id}/inativo', '\Mg\Usuario\GrupoUsuarioController@inativar')->name('grupo-usuario.inativar');
        Route::apiResource('grupo-usuario', '\Mg\Usuario\GrupoUsuarioController');

        // Impressoras
        Route::get('impressora', '\Mg\Usuario\ImpressoraController@index');

        // Filiais
        Route::apiResource('filial', '\Mg\Filial\FilialController');

        // Pessoas
        Route::get('pessoa/autocomplete', '\Mg\Pessoa\PessoaController@autocomplete');
        Route::apiResource('pessoa', '\Mg\Pessoa\PessoaController');

        // Permissões
        Route::apiResource('permissao', '\Mg\Permissao\PermissaoController');

        // Estoque Estatística
        Route::apiResource('estoque-estatistica', '\Mg\Estoque\EstoqueEstatisticaController');

        // Estoque Conferência
        Route::get('estoque-saldo-conferencia/busca-listagem', '\Mg\Estoque\EstoqueSaldoConferenciaController@buscaListagem');
        Route::post('estoque-saldo-conferencia', '\Mg\Estoque\EstoqueSaldoConferenciaController@store')->name('estoque-conferencia.store');
        Route::post('estoque-saldo-conferencia/zerar-produto', '\Mg\Estoque\EstoqueSaldoConferenciaController@zerarProduto');
        Route::get('estoque-saldo-conferencia/busca-produto', '\Mg\Estoque\EstoqueSaldoConferenciaController@buscaProduto');
        Route::post('estoque-saldo-conferencia/{id}/inativo', '\Mg\Estoque\EstoqueSaldoConferenciaController@inativar')->name('estoque-conferencia.inativar');

        // Imagem
        Route::apiResource('imagem', '\Mg\Imagem\ImagemController');
        Route::delete('imagem/{id}/inativo', '\Mg\Imagem\ImagemController@ativar')->name('imagem.ativar');
        Route::post('imagem/{id}/inativo', '\Mg\Imagem\ImagemController@inativar')->name('imagem.inativar');

        // Marcas
        Route::get('marca/autocompletar', '\Mg\Marca\MarcaController@autocompletar');
        Route::get('marca/{id}/detalhes', '\Mg\Marca\MarcaController@detalhes')->name('marca.detalhes');
        Route::delete('marca/{id}/inativo', '\Mg\Marca\MarcaController@ativar')->name('marca.ativar');
        Route::post('marca/{id}/inativo', '\Mg\Marca\MarcaController@inativar')->name('marca.inativar');
        Route::apiResource('marca', '\Mg\Marca\MarcaController');

        Route::get('estoque-local', '\Mg\Estoque\EstoqueLocalController@index');
        Route::get('estoque-local/{id}', '\Mg\Estoque\EstoqueLocalController@show');

        //Route::get('nfephp', '\Mg\Nfephp\NfeController@show');
        Route::get('nfe-php/cria-xml/{id}', '\Mg\NfePhp\NfePhpController@criaXml');


    });

});
