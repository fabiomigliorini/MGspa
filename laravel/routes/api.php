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

Route::group(['prefix' => 'v1/cielo-lio'], function () {
    Route::post('', '\Mg\Lio\LioController@callback');
});


Route::group(['prefix' => 'v1/auth'], function () {
    Route::post('login', 'Auth\LoginController@authenticate');
    Route::get('logout', 'Auth\LoginController@logout');
    Route::get('check', 'Auth\LoginController@check');
    Route::get('refresh', 'Auth\LoginController@refreshToken');
    Route::get('user', 'Auth\LoginController@getAuthenticatedUser');
});

Route::group(['prefix' => 'v1'], function () {

    // MDFe
    Route::post('mdfe/criar-da-nota-fiscal/{codnotafiscal}', '\Mg\Mdfe\MdfeController@criarDaNotaFiscal');
    Route::post('mdfe/{codmdfe}/criar-xml', '\Mg\Mdfe\MdfeController@criarXml');
    Route::post('mdfe/{codmdfe}/enviar', '\Mg\Mdfe\MdfeController@enviar');
    Route::post('mdfe/{codmdfe}/consultar-envio/{codmdfeenviosefaz?}', '\Mg\Mdfe\MdfeController@consultarEnvio');
    Route::post('mdfe/{codmdfe}/consultar', '\Mg\Mdfe\MdfeController@consultar');
    Route::get('mdfe/{codmdfe}/damdfe', '\Mg\Mdfe\MdfeController@damdfe');

    // Pix Cob
    Route::post('pix/cob/criar-negocio/{codnegocio}', '\Mg\Pix\PixController@criarPixCobNegocio');
    Route::post('pix/cob/{codpixcob}/transmitir', '\Mg\Pix\PixController@transmitirPixCob');
    Route::post('pix/cob/{codpixcob}/consultar', '\Mg\Pix\PixController@consultarPixCob');
    Route::get('pix/cob/{codpixcob}/brcode', '\Mg\Pix\PixController@brCodePixCob');
    Route::get('pix/cob/{codpixcob}', '\Mg\Pix\PixController@show');

    // Pix
    Route::post('pix/consultar', '\Mg\Pix\PixController@consultarPix');

    // NATUREZA DA OPERACAO
    Route::apiResource('natureza-operacao/autocompletar', '\Mg\NaturezaOperacao\NaturezaOperacaoController');

    // Pessoa autocomplete
    Route::get('pessoa/autocomplete', '\Mg\Pessoa\PessoaController@autocomplete');

    // NotaFiscalTerceiro
    Route::get('nfe-terceiro/ultima-nsu', '\Mg\NotaFiscalTerceiro\NotaFiscalTerceiroController@ultimaNSU');
    Route::get('nfe-terceiro/manifestacao', '\Mg\NotaFiscalTerceiro\NotaFiscalTerceiroController@manifestacao');
    Route::get('nfe-terceiro/atualiza-item', '\Mg\NotaFiscalTerceiro\NotaFiscalTerceiroController@atualizaItem');
    Route::get('nfe-terceiro/atualiza-nfe', '\Mg\NotaFiscalTerceiro\NotaFiscalTerceiroController@atualizaNFe');

    Route::get('nfe-terceiro/consulta-sefaz', '\Mg\NotaFiscalTerceiro\NotaFiscalTerceiroController@consultaSefaz');
    Route::get('nfe-terceiro/armazena-dados', '\Mg\NotaFiscalTerceiro\NotaFiscalTerceiroController@armazenaDadosConsulta');
    Route::get('nfe-terceiro/lista-notas', '\Mg\NotaFiscalTerceiro\NotaFiscalTerceiroController@listaNotas');
    Route::get('nfe-terceiro/busca-nfeterceiro', '\Mg\NotaFiscalTerceiro\NotaFiscalTerceiroController@buscaNfeTerceiro');
    Route::get('nfe-terceiro/lista-item', '\Mg\NotaFiscalTerceiro\NotaFiscalTerceiroController@listaItem');
    Route::get('nfe-terceiro/download-nfe', '\Mg\NotaFiscalTerceiro\NotaFiscalTerceiroController@downloadNotaFiscalTerceiro');
    // Route::get('nfe-terceiro/{filial}/{chave}/carregar-xml', '\Mg\NotaFiscalTerceiro\NotaFiscalTerceiroController@carregarXml');
    // Route::get('nfe-terceiro/armazena-evento', '\Mg\NotaFiscalTerceiro\NotaFiscalTerceiroController@armazenaDadosEvento');

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

    Route::post('nfe-php/dist-dfe/{codfilial}/{nsu?}', '\Mg\NFePHP\NFePHPController@distDfe');

    Route::get('nfe-php/{id}/sefaz-status', '\Mg\NFePHP\NFePHPController@sefazStatus');
    Route::get('nfe-php/{id}/csc-consulta', '\Mg\NFePHP\NFePHPController@cscConsulta');

    Route::get('dfe/distribuicao', '\Mg\Dfe\DfeController@distribuicao');
    Route::get('dfe/distribuicao/{coddistribuicaodfe}/xml', '\Mg\Dfe\DfeController@xml');
    Route::get('dfe/filiais-habilitadas', '\Mg\Dfe\DfeController@filiaisHabilitadas');

});

Route::group(['middleware' => ['cors', 'api', 'jwt-auth']], function () {
    Route::group(['prefix' => 'v1'], function () {

        // Cielo Lio (Danilo)
        Route::get('lio/vendas-abertas', '\Mg\Lio\LioController@vendasAbertas');
        Route::post('lio/order', '\Mg\Lio\LioController@order');
        Route::post('lio/{id}/parse', '\Mg\Lio\LioController@parse');

        // Etiquetas
        Route::get('etiqueta/barras', '\Mg\Etiqueta\EtiquetaController@barras');
        Route::get('etiqueta/negocio', '\Mg\Etiqueta\EtiquetaController@negocio');
        Route::get('etiqueta/periodo', '\Mg\Etiqueta\EtiquetaController@periodo');
        Route::post('etiqueta/imprimir', '\Mg\Etiqueta\EtiquetaController@imprimir');

        // Boletos
        Route::get('boleto/retorno-pendente', '\Mg\Boleto\BoletoController@retornoPendente');
        Route::get('boleto/retorno-processado', '\Mg\Boleto\BoletoController@retornoProcessado');
        Route::get('boleto/retorno-falha', '\Mg\Boleto\BoletoController@retornoFalha');
        Route::get('boleto/retorno/{codportador}/{arquivo}/{dataretorno}', '\Mg\Boleto\BoletoController@retorno');
        Route::post('boleto/processar-retorno', '\Mg\Boleto\BoletoController@processarRetorno');
        Route::post('boleto/reprocessar-retorno', '\Mg\Boleto\BoletoController@reprocessarRetorno');

        // Dominio
        Route::post('dominio/estoque', '\Mg\Dominio\DominioController@estoque');
        Route::post('dominio/produto', '\Mg\Dominio\DominioController@produto');
        Route::post('dominio/pessoa', '\Mg\Dominio\DominioController@pessoa');
        Route::post('dominio/entrada', '\Mg\Dominio\DominioController@entrada');

        Route::get('boleto/remessa-pendente', '\Mg\Boleto\BoletoController@remessaPendente');
        Route::get('boleto/remessa-enviada', '\Mg\Boleto\BoletoController@remessaEnviada');
        Route::get('boleto/remessa/{codportador}/{remessa}', '\Mg\Boleto\BoletoController@remessa');
        Route::post('boleto/arquivar-remessa/{codportador}/{arquivo}', '\Mg\Boleto\BoletoController@arquivarRemessa');
        Route::post('boleto/gerar-remessa/{codportador}', '\Mg\Boleto\BoletoController@gerarRemessa');

        // Pedidos
        Route::apiResource('pedido', '\Mg\Pedido\PedidoController');
        Route::get('pedido/produtos-para-transferir/{codestoquelocalorigem}/{codestoquelocaldestino}', '\Mg\Pedido\PedidoController@produtosParaTransferir');
        Route::get('pedido/{id}/item', '\Mg\Pedido\PedidoController@indexItem');
        Route::get('pedido/{id}/item/{iditem}', '\Mg\Pedido\PedidoController@showItem');
        Route::post('pedido/{id}/item', '\Mg\Pedido\PedidoController@storeItem');
        Route::put('pedido/{id}/item/{iditem}', '\Mg\Pedido\PedidoController@updateItem');
        Route::delete('pedido/{id}/item/{iditem}', '\Mg\Pedido\PedidoController@destroyItem');

        // Caixa Mercadoria
        Route::apiResource('caixa-mercadoria', '\Mg\CaixaMercadoria\CaixaMercadoriaController');

        // Negocio
        Route::apiResource('negocio', '\Mg\Negocio\NegocioController');

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

        Route::put('marca/{id}/planilha/distribuicao-saldo-deposito', '\Mg\Marca\MarcaController@criarPlanilhaDistribuicaoSaldoDeposito');
        Route::put('marca/{id}/planilha/pedido', '\Mg\Marca\MarcaController@criarPlanilhaPedido');

        Route::get('estoque-local', '\Mg\Estoque\EstoqueLocalController@index');
        Route::get('estoque-local/{id}', '\Mg\Estoque\EstoqueLocalController@show');

        // Impressoras
        Route::get('select/impressora', '\Mg\Select\SelectImpressoraController@index');
        Route::get('select/filial', '\Mg\Select\SelectFilialController@index');
        Route::get('select/estado', '\Mg\Select\SelectEstadoController@index');
        Route::get('select/veiculo-tipo', '\Mg\Select\SelectVeiculoTipoController@index');
        Route::get('select/veiculo', '\Mg\Select\SelectVeiculoController@index');
        Route::get('select/produto-barra', '\Mg\Select\SelectProdutoBarraController@index');

        Route::get('veiculo', '\Mg\Veiculo\VeiculoController@index');
        Route::get('veiculo/{id}', '\Mg\Veiculo\VeiculoController@show');
        Route::put('veiculo/{id}', '\Mg\Veiculo\VeiculoController@update');
        Route::post('veiculo', '\Mg\Veiculo\VeiculoController@store');
        Route::post('veiculo/{id}/inativo', '\Mg\Veiculo\VeiculoController@inativar');
        Route::delete('veiculo/{id}/inativo', '\Mg\Veiculo\VeiculoController@ativar');
        Route::delete('veiculo/{id}', '\Mg\Veiculo\VeiculoController@delete');

        Route::get('veiculo-conjunto', '\Mg\Veiculo\VeiculoConjuntoController@index');
        Route::get('veiculo-conjunto/{id}', '\Mg\Veiculo\VeiculoConjuntoController@show');
        Route::put('veiculo-conjunto/{id}', '\Mg\Veiculo\VeiculoConjuntoController@update');
        Route::post('veiculo-conjunto/{id}/inativo', '\Mg\Veiculo\VeiculoConjuntoController@inativar');
        Route::delete('veiculo-conjunto/{id}/inativo', '\Mg\Veiculo\VeiculoConjuntoController@ativar');
        Route::delete('veiculo-conjunto/{id}', '\Mg\Veiculo\VeiculoConjuntoController@delete');
        Route::post('veiculo-conjunto', '\Mg\Veiculo\VeiculoConjuntoController@store');

        Route::get('veiculo-tipo', '\Mg\Veiculo\VeiculoTipoController@index');
        Route::get('veiculo-tipo/{id}', '\Mg\Veiculo\VeiculoTipoController@show');
        Route::put('veiculo-tipo/{id}', '\Mg\Veiculo\VeiculoTipoController@update');
        Route::post('veiculo-tipo/{id}/inativo', '\Mg\Veiculo\VeiculoTipoController@inativar');
        Route::delete('veiculo-tipo/{id}/inativo', '\Mg\Veiculo\VeiculoTipoController@ativar');
        Route::delete('veiculo-tipo/{id}', '\Mg\Veiculo\VeiculoTipoController@delete');
        Route::post('veiculo-tipo', '\Mg\Veiculo\VeiculoTipoController@store');

    });
});
