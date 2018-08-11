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



Route::group(['prefix' => 'v1'], function () {

        // NATUREZA DA OPERACAO
        Route::apiResource('natureza-operacao/autocompletar', '\Mg\NaturezaOperacao\NaturezaOperacaoController');

        // Pessoa autocomplete
        Route::get('pessoa/autocomplete', '\Mg\Pessoa\PessoaController@autocomplete');

        // NFeTerceiro
        Route::get('nfe-terceiro/lista-notas', '\Mg\NFeTerceiro\NFeTerceiroController@listaNotas');
        Route::get('nfe-terceiro/consulta-sefaz', '\Mg\NFeTerceiro\NFeTerceiroController@consultaSefaz');
        Route::get('nfe-terceiro/ultima-nsu', '\Mg\NFeTerceiro\NFeTerceiroController@ultimaNSU');
        Route::get('nfe-terceiro/armazena-dados', '\Mg\NFeTerceiro\NFeTerceiroController@armazenaDadosConsulta');
        Route::get('nfe-terceiro/manifestacao', '\Mg\NFeTerceiro\NFeTerceiroController@manifestacao');
        Route::get('nfe-terceiro/download-nfe', '\Mg\NFeTerceiro\NFeTerceiroController@downloadNFeTerceiro');
        Route::get('nfe-terceiro/lista-item', '\Mg\NFeTerceiro\NFeTerceiroController@listaItem');
        Route::get('nfe-terceiro/busca-nfeterceiro', '\Mg\NFeTerceiro\NFeTerceiroController@buscaNfeTerceiro');
        Route::get('nfe-terceiro/atualiza-item', '\Mg\NFeTerceiro\NFeTerceiroController@atualizaItem');
        Route::get('nfe-terceiro/atualiza-nfe', '\Mg\NFeTerceiro\NFeTerceiroController@atualizaNFe');
        // Route::get('nfe-terceiro/armazena-evento', '\Mg\NFeTerceiro\NFeTerceiroController@armazenaDadosEvento');
        // Route::get('nfe-terceiro/{filial}/{chave}/carregar-xml', '\Mg\NFeTerceiro\NFeTerceiroController@carregarXml');

        // NFePHP
        Route::get('nfe-php/{id}/criar', '\Mg\NFePHP\NFePHPController@criar');
        Route::get('nfe-php/{id}/enviar', '\Mg\NFePHP\NFePHPController@enviar');
        Route::get('nfe-php/{id}/enviar-sincrono', '\Mg\NFePHP\NFePHPController@enviarSincrono');
        Route::get('nfe-php/{id}/consultar-recibo', '\Mg\NFePHP\NFePHPController@consultarRecibo');
        Route::get('nfe-php/{id}/consultar', '\Mg\NFePHP\NFePHPController@consultar');
        Route::get('nfe-php/{id}/danfe', '\Mg\NFePHP\NFePHPController@danfe');
        Route::get('nfe-php/{id}/imprimir', '\Mg\NFePHP\NFePHPController@imprimir');
        Route::get('nfe-php/{id}/cancelar', '\Mg\NFePHP\NFePHPController@cancelar');
        Route::get('nfe-php/{id}/inutilizar', '\Mg\NFePHP\NFePHPController@inutilizar');
        Route::get('nfe-php/{id}/carta-correcao', '\Mg\NFePHP\NFePHPController@cartaCorrecao');

        Route::get('nfe-php/{id}/mail', '\Mg\NFePHP\NFePHPController@mail');
        Route::get('nfe-php/{id}/mail-cancelamento', '\Mg\NFePHP\NFePHPController@mailCancelamento');

        Route::get('nfe-php/{id}/xml', '\Mg\NFePHP\NFePHPController@xml');

        Route::get('nfe-php/{id}/resolver', '\Mg\NFePHP\NFePHPController@resolver');
        Route::get('nfe-php/pendentes', '\Mg\NFePHP\NFePHPController@pendentes');
        Route::get('nfe-php/resolver-pendentes', '\Mg\NFePHP\NFePHPController@resolverPendentes');

        Route::get('nfe-php/{id}/sefaz-status', '\Mg\NFePHP\NFePHPController@sefazStatus');
        Route::get('nfe-php/{id}/csc-consulta', '\Mg\NFePHP\NFePHPController@cscConsulta');

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
        Route::apiResource('pessoa', '\Mg\Pessoa\PessoaController');
        Route::post('pessoa/novapessoa', '\Mg\Pessoa\PessoaController@novaPessoa');

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

    });

});
