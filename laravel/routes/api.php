<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

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

Route::middleware('auth:api')->get('v1/auth/user', '\Mg\Usuario\UsuarioController@permissoesUsuarios');

Route::middleware('auth:api')->get('v1/auth/logout', function (Request $request) {
    $user =  $request->user();
    $accessToken = $user->token();
    DB::table('oauth_refresh_tokens')
        ->where('access_token_id', $accessToken->id)
        ->delete();
    $user->token()->delete();
    return response()->json([
        'message' => 'Logout Realizado com sucesso',
        'session' => session()->all()
    ]);
});
###################################################################################

Route::get('quasar', 'Auth\SSOController@getLoginQuasar');
Route::get('quasar/callback', 'Auth\SSOController@getCallback');

Route::group(['prefix' => 'v1/cielo-lio'], function () {
    Route::post('', '\Mg\Lio\LioController@callback');
});

Route::group(['prefix' => 'v1/auth'], function () {
    Route::post('login', 'Auth\SSOController@login');
    // Route::get('logout', 'Auth\LoginController@logoutt');
    // Route::get('check', 'Auth\LoginController@check');
    //  Route::get('refresh', 'Auth\LoginController@refreshToken');
    //  Route::get('user', 'Auth\SSOController@getAuthenticatedUser');
});

Route::group(['prefix' => 'v1'], function () {

    // PDV
    Route::put('pdv/dispositivo', '\Mg\Pdv\PdvController@dispositivo');

    // Produto
    Route::post('produto/unifica-variacoes', '\Mg\Produto\ProdutoController@unificaVariacoes');
    Route::post('produto/unifica-barras', '\Mg\Produto\ProdutoController@unificaBarras');
    Route::post('produto/embalagem-para-unidade', '\Mg\Produto\ProdutoController@embalagemParaUnidade');

    // Etiqueta
    Route::get('etiqueta/arquivo/{arquivo}', '\Mg\Etiqueta\EtiquetaController@arquivo');

    // NFeTerceiro
    Route::post('nfe-terceiro/{codnfeterceiro}/manifestacao', '\Mg\NfeTerceiro\NfeTerceiroController@manifestacao');
    Route::get('nfe-terceiro/{codnfeterceiro}/xml', '\Mg\NfeTerceiro\NfeTerceiroController@xml');
    Route::get('nfe-terceiro/{codnfeterceiro}/danfe', '\Mg\NfeTerceiro\NfeTerceiroController@danfe');

    // Negocio
    Route::get('negocio/{codnegocio}/comanda', '\Mg\Negocio\NegocioController@comanda');
    Route::post('negocio/{codnegocio}/comanda/imprimir', '\Mg\Negocio\NegocioController@comandaImprimir');
    Route::post('negocio/{codnegocio}/unificar/{codnegociocomanda}', '\Mg\Negocio\NegocioController@unificar');
    Route::get('negocio/{codnegocio}/boleto-bb/pdf', '\Mg\Negocio\NegocioController@BoletoBbPdf');
    Route::post('negocio/{codnegocio}/boleto-bb/registrar', '\Mg\Negocio\NegocioController@BoletoBbRegistrar');
    Route::post('negocio/{codnegocio}/identificar-vendedor/{codpessoavendedor}', '\Mg\Negocio\NegocioController@identificarVendedor');

    // Boletos BB
    Route::post('titulo/{codtitulo}/boleto-bb', '\Mg\Titulo\BoletoBb\BoletoBbController@registrar');
    Route::get('titulo/{codtitulo}/boleto-bb/{codtituloboleto}', '\Mg\Titulo\BoletoBb\BoletoBbController@show');
    Route::post('titulo/{codtitulo}/boleto-bb/{codtituloboleto}/consultar', '\Mg\Titulo\BoletoBb\BoletoBbController@consultar');
    Route::post('titulo/{codtitulo}/boleto-bb/{codtituloboleto}/baixar', '\Mg\Titulo\BoletoBb\BoletoBbController@baixar');
    Route::get('titulo/{codtitulo}/boleto-bb/{codtituloboleto}/pdf', '\Mg\Titulo\BoletoBb\BoletoBbController@pdf');

    // Stone
    Route::group(['prefix' => 'stone-connect'], function () {
        // Pre Transacao
        Route::group(['prefix' => 'pre-transacao'], function () {
            Route::post('', '\Mg\Stone\Connect\PreTranscaoController@store');
            Route::get('{codstonepretransacao}', '\Mg\Stone\Connect\PreTranscaoController@show');
        });
        // Webooks
        Route::group(['prefix' => 'webhook'], function () {
            Route::post('pos-application', '\Mg\Stone\Connect\WebhookController@posApplication');
            Route::post('pre-transaction-status', '\Mg\Stone\Connect\WebhookController@preTransactionStatus');
            Route::post('processed-transaction', '\Mg\Stone\Connect\WebhookController@processedTransaction');
            Route::post('print-note-status', '\Mg\Stone\Connect\WebhookController@printNoteStatus');
        });
    });

    // PagarMe
    Route::post('pagar-me/webhook/', '\Mg\PagarMe\PagarMeController@webhook');
    Route::post('pagar-me/pedido/', '\Mg\PagarMe\PagarMeController@criarPedido');
    Route::post('pagar-me/pedido/{codpagarmepedido}/consultar', '\Mg\PagarMe\PagarMeController@consultarPedido');
    Route::delete('pagar-me/pedido/{codpagarmepdido}', '\Mg\PagarMe\PagarMeController@cancelarPedido');

    // Pix Cob
    Route::get('pix/cob/{codpixcob}/detalhes', '\Mg\Pix\PixController@detalhes');
    Route::post('pix/cob/criar-negocio/{codnegocio}', '\Mg\Pix\PixController@criarPixCobNegocio');
    Route::post('pix/cob/{codpixcob}/transmitir', '\Mg\Pix\PixController@transmitirPixCob');
    Route::post('pix/cob/{codpixcob}/consultar', '\Mg\Pix\PixController@consultarPixCob');
    Route::get('pix/cob/{codpixcob}/brcode', '\Mg\Pix\PixController@brCodePixCob');
    Route::get('pix/cob/{codpixcob}', '\Mg\Pix\PixController@show');
    Route::post('pix/cob/{codpixcob}/imprimir-qr-code', '\Mg\Pix\PixController@imprimirQrCode');
    Route::get('pix/cob/{codpixcob}/pdf', '\Mg\Pix\PixController@pdf');
    Route::match(['POST', 'PUT', 'PATCH'], 'pix/webhook', '\Mg\Pix\PixController@webhook');

    // NATUREZA DA OPERACAO
    Route::apiResource('natureza-operacao/autocompletar', '\Mg\NaturezaOperacao\NaturezaOperacaoController');

    // Pessoa autocomplete
    Route::get('pessoa/autocomplete', '\Mg\Pessoa\PessoaController@autocomplete');
    Route::get('pessoa/{codpessoa}/comanda-vendedor', '\Mg\Pessoa\PessoaController@comandaVendedor');
    Route::post('pessoa/{codpessoa}/comanda-vendedor/imprimir', '\Mg\Pessoa\PessoaController@comandaVendedorImprimir');

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
    Route::get('dfe/distribuicao/{coddistribuicaodfe}/processar', '\Mg\Dfe\DfeController@processar');
    Route::get('dfe/filiais-habilitadas', '\Mg\Dfe\DfeController@filiaisHabilitadas');
});

Route::group(['middleware' => ['auth:api', 'cors']], function () {
    Route::group(['prefix' => 'v1'], function () {

        //PDV
        Route::get('pdv/produto-count', '\Mg\Pdv\PdvController@produtoCount');
        Route::get('pdv/produto', '\Mg\Pdv\PdvController@produto');
        Route::get('pdv/pessoa-count', '\Mg\Pdv\PdvController@pessoaCount');
        Route::get('pdv/pessoa', '\Mg\Pdv\PdvController@pessoa');
        Route::get('pdv/natureza-operacao', '\Mg\Pdv\PdvController@naturezaOperacao');
        Route::get('pdv/estoque-local', '\Mg\Pdv\PdvController@estoqueLocal');
        Route::get('pdv/forma-pagamento', '\Mg\Pdv\PdvController@formaPagamento');
        Route::get('pdv/impressora', '\Mg\Pdv\PdvController@impressora');
        Route::put('pdv/negocio', '\Mg\Pdv\PdvController@putNegocio');
        Route::get('pdv/negocio/{id}', '\Mg\Pdv\PdvController@getNegocio');

        // Allan - daqui pra baixo
        Route::get('pessoa/', '\Mg\Pessoa\PessoaController@index');
        Route::post('pessoa/', '\Mg\Pessoa\PessoaController@create');
        Route::get('pessoa/formadepagamento', '\Mg\Pessoa\PessoaController@formapagamento');
        Route::get('pessoa/{codpessoa}', '\Mg\Pessoa\PessoaController@show');
        Route::put('pessoa/{codpessoa}', '\Mg\Pessoa\PessoaController@update');
        Route::delete('pessoa/{codpessoa}', '\Mg\Pessoa\PessoaController@delete');
        Route::post('pessoa/{codpessoa}/inativo', '\Mg\Pessoa\PessoaController@inativar');
        Route::delete('pessoa/{codpessoa}/inativo', '\Mg\Pessoa\PessoaController@ativar');
        Route::post('pessoa/importar', '\Mg\Pessoa\PessoaController@importar');


        //GrupoCliente
        Route::get('grupocliente/', '\Mg\Pessoa\GrupoClienteController@index');


        // Pessoa Telefone
        Route::get('pessoa/{codpessoa}/telefone/', '\Mg\Pessoa\PessoaTelefoneController@index');
        Route::post('pessoa/{codpessoa}/telefone/', '\Mg\Pessoa\PessoaTelefoneController@create');
        Route::get('pessoa/{codpessoa}/telefone/{codpessoatelefone}/', '\Mg\Pessoa\PessoaTelefoneController@show');
        Route::put('pessoa/{codpessoa}/telefone/{codpessoatelefone}/', '\Mg\Pessoa\PessoaTelefoneController@update');
        Route::delete('pessoa/{codpessoa}/telefone/{codpessoatelefone}/', '\Mg\Pessoa\PessoaTelefoneController@delete');
        Route::post('pessoa/{codpessoa}/telefone/{codpessoatelefone}/cima', '\Mg\Pessoa\PessoaTelefoneController@cima');
        Route::post('pessoa/{codpessoa}/telefone/{codpessoatelefone}/baixo', '\Mg\Pessoa\PessoaTelefoneController@baixo');
        Route::post('pessoa/{codpessoa}/telefone/{codpessoatelefone}/inativo', '\Mg\Pessoa\PessoaTelefoneController@inativar');
        Route::delete('pessoa/{codpessoa}/telefone/{codpessoatelefone}/inativo', '\Mg\Pessoa\PessoaTelefoneController@ativar');
        Route::get('pessoa/{codpessoa}/telefone/{codpessoatelefone}/verificar', '\Mg\Pessoa\PessoaTelefoneController@verificar');
        Route::post('pessoa/{codpessoa}/telefone/{codpessoatelefone}/verificar', '\Mg\Pessoa\PessoaTelefoneController@confirmaVerificacao');

        // Pessoa Email
        Route::get('pessoa/{codpessoa}/email/', '\Mg\Pessoa\PessoaEmailController@index');
        Route::post('pessoa/{codpessoa}/email/', '\Mg\Pessoa\PessoaEmailController@create');
        Route::get('pessoa/{codpessoa}/email/{codpessoatelefone}/', '\Mg\Pessoa\PessoaEmailController@show');
        Route::put('pessoa/{codpessoa}/email/{codpessoatelefone}/', '\Mg\Pessoa\PessoaEmailController@update');
        Route::delete('pessoa/{codpessoa}/email/{codpessoatelefone}/', '\Mg\Pessoa\PessoaEmailController@delete');
        Route::post('pessoa/{codpessoa}/email/{codpessoatelefone}/cima', '\Mg\Pessoa\PessoaEmailController@cima');
        Route::post('pessoa/{codpessoa}/email/{codpessoatelefone}/baixo', '\Mg\Pessoa\PessoaEmailController@baixo');
        Route::post('pessoa/{codpessoa}/email/{codpessoatelefone}/inativo', '\Mg\Pessoa\PessoaEmailController@inativar');
        Route::delete('pessoa/{codpessoa}/email/{codpessoatelefone}/inativo', '\Mg\Pessoa\PessoaEmailController@ativar');
        Route::get('pessoa/{codpessoa}/email/{codpessoatelefone}/verificar', '\Mg\Pessoa\PessoaEmailController@verificarEmail');
        Route::post('pessoa/{codpessoa}/email/{codpessoatelefone}/verificar', '\Mg\Pessoa\PessoaEmailController@confirmaEmail');

        // Pessoa Endereço
        Route::get('pessoa/{codpessoa}/endereco/', '\Mg\Pessoa\PessoaEnderecoController@index');
        Route::post('pessoa/{codpessoa}/endereco/', '\Mg\Pessoa\PessoaEnderecoController@create');
        Route::get('pessoa/{codpessoa}/endereco/{codpessoaendereco}/', '\Mg\Pessoa\PessoaEnderecoController@show');
        Route::put('pessoa/{codpessoa}/endereco/{codpessoaendereco}/', '\Mg\Pessoa\PessoaEnderecoController@update');
        Route::delete('pessoa/{codpessoa}/endereco/{codpessoaendereco}/', '\Mg\Pessoa\PessoaEnderecoController@delete');
        Route::post('pessoa/{codpessoa}/endereco/{codpessoaendereco}/cima', '\Mg\Pessoa\PessoaEnderecoController@cima');
        Route::post('pessoa/{codpessoa}/endereco/{codpessoaendereco}/baixo', '\Mg\Pessoa\PessoaEnderecoController@baixo');
        Route::post('pessoa/{codpessoa}/endereco/{codpessoaendereco}/inativo', '\Mg\Pessoa\PessoaEnderecoController@inativar');
        Route::delete('pessoa/{codpessoa}/endereco/{codpessoaendereco}/inativo', '\Mg\Pessoa\PessoaEnderecoController@ativar');

        // Pessoa Certidão
        // Route::get('pessoa/{codpessoa}/certidao/', '\Mg\Pessoa\PessoaCertidaoController@index');
        Route::post('certidao/', '\Mg\Pessoa\PessoaCertidaoController@create');
        Route::get('certidao/{codpessoacertidao}/', '\Mg\Pessoa\PessoaCertidaoController@show');
        Route::put('certidao/{codpessoacertidao}/', '\Mg\Pessoa\PessoaCertidaoController@update');
        Route::delete('certidao/{codpessoacertidao}/', '\Mg\Pessoa\PessoaCertidaoController@delete');
        Route::get('select/certidao/emissor', '\Mg\Pessoa\PessoaCertidaoController@selectCertidaoEmissor');
        Route::get('select/certidao/tipo', '\Mg\Pessoa\PessoaCertidaoController@selectCertidaoTipo');
        Route::post('certidao/{codpessoacertidao}/inativo', '\Mg\Pessoa\PessoaCertidaoController@inativar');
        Route::delete('certidao/{codpessoacertidao}/inativo', '\Mg\Pessoa\PessoaCertidaoController@ativar');





        // Pessoa Conta
        Route::get('pessoa/{codpessoa}/conta/', '\Mg\Pessoa\PessoaContaController@index');
        Route::post('pessoa/{codpessoa}/conta/', '\Mg\Pessoa\PessoaContaController@create');
        Route::get('pessoa/{codpessoa}/conta/{codpessoaconta}/', '\Mg\Pessoa\PessoaContaController@show');
        Route::put('pessoa/{codpessoa}/conta/{codpessoaconta}/', '\Mg\Pessoa\PessoaContaController@update');
        Route::delete('pessoa/{codpessoa}/conta/{codpessoaconta}/', '\Mg\Pessoa\PessoaContaController@delete');

        // Grupo Economico
        Route::get('grupoeconomico/', '\Mg\GrupoEconomico\GrupoEconomicoController@index');
        Route::get('grupoeconomico/select', '\Mg\GrupoEconomico\GrupoEconomicoController@pesquisaGrupoEconomico');
        Route::get('grupoeconomico/select', '\Mg\GrupoEconomico\GrupoEconomicoController@pesquisaGrupoEconomico');
        Route::post('grupoeconomico/', '\Mg\GrupoEconomico\GrupoEconomicoController@create');
        Route::delete('pessoa/{codpessoa}/grupoeconomico/{codgrupoeconomico}/removerdogrupo', '\Mg\GrupoEconomico\GrupoEconomicoController@deletaPessoadoGrupo');

        Route::get('grupoeconomico/{codgrupoeconomico}/', '\Mg\GrupoEconomico\GrupoEconomicoController@show');
        Route::post('grupoeconomico/{codgrupoeconomico}/inativo', '\Mg\GrupoEconomico\GrupoEconomicoController@inativar');
        Route::delete('grupoeconomico/{codgrupoeconomico}/inativo', '\Mg\GrupoEconomico\GrupoEconomicoController@ativar');


        Route::put('grupoeconomico/{codgrupoeconomico}/', '\Mg\GrupoEconomico\GrupoEconomicoController@update');
        Route::delete('grupoeconomico/{codgrupoeconomico}/', '\Mg\GrupoEconomico\GrupoEconomicoController@delete');

        // Certidão Emissor
        Route::get('certidao/{codpessoa}/certidaoemissor/', '\Mg\Certidao\CertidaoEmissorController@index');
        Route::post('certidao/{codpessoa}/certidaoemissor/', '\Mg\Certidao\CertidaoEmissorController@create');
        Route::get('certidao/{codpessoa}/certidaoemissor/{codcertidaoemissor}/', '\Mg\Certidao\CertidaoEmissorController@show');
        Route::put('certidao/{codpessoa}/certidaoemissor/{codcertidaoemissor}/', '\Mg\Certidao\CertidaoEmissorController@update');
        Route::delete('certidao/{codpessoa}/certidaoemissor/{codcertidaoemissor}/', '\Mg\Certidao\CertidaoEmissorController@delete');

        // CertidaoTipo
        Route::get('certidao/{codpessoa}/certidaotipo/', '\Mg\Certidao\CertidaoTipoController@index');
        Route::post('certidao/{codpessoa}/certidaotipo/', '\Mg\Certidao\CertidaoTipoController@create');
        Route::get('certidao/{codpessoa}/certidaotipo/{codcertidaotipo}/', '\Mg\Certidao\CertidaoTipoController@show');
        Route::put('certidao/{codpessoa}/certidaotipo/{codcertidaotipo}/', '\Mg\Certidao\CertidaoTipoController@update');
        Route::delete('certidao/{codpessoa}/certidaotipo/{codcertidaotipo}/', '\Mg\Certidao\CertidaoTipoController@delete');

        // RegistroSpc
        Route::get('pessoa/{codpessoa}/registrospc/', '\Mg\Pessoa\RegistroSpcController@index');
        Route::post('pessoa/{codpessoa}/registrospc/', '\Mg\Pessoa\RegistroSpcController@create');
        Route::get('pessoa/{codpessoa}/registrospc/{codregistrospc}/', '\Mg\Pessoa\RegistroSpcController@show');
        Route::put('pessoa/{codpessoa}/registrospc/{codregistrospc}/', '\Mg\Pessoa\RegistroSpcController@update');
        Route::delete('pessoa/{codpessoa}/registrospc/{codregistrospc}/', '\Mg\Pessoa\RegistroSpcController@delete');

        // Pessoa Cobrança Historico
        Route::get('pessoa/{codpessoa}/cobrancahistorico/', '\Mg\Cobranca\CobrancaHistoricoController@index');
        Route::post('pessoa/{codpessoa}/cobrancahistorico/', '\Mg\Cobranca\CobrancaHistoricoController@create');
        Route::get('pessoa/{codpessoa}/cobrancahistorico/{codcobrancahistorico}/', '\Mg\Cobranca\CobrancaHistoricoController@show');
        Route::put('pessoa/{codpessoa}/cobrancahistorico/{codcobrancahistorico}/', '\Mg\Cobranca\CobrancaHistoricoController@update');
        Route::delete('pessoa/{codpessoa}/cobrancahistorico/{codcobrancahistorico}/', '\Mg\Cobranca\CobrancaHistoricoController@delete');


        Route::group(['prefix' => 'produto'], function () {
            Route::get('{codproduto}', '\Mg\Produto\ProdutoController@show');
        });

        // Stone Connect
        Route::group(['prefix' => 'stone-connect'], function () {

            // Filial
            Route::group(['prefix' => 'filial'], function () {
                Route::post('', '\Mg\Stone\Connect\FilialController@store');
                Route::get('', '\Mg\Stone\Connect\FilialController@index');
                Route::get('{codstonefilial}', '\Mg\Stone\Connect\FilialController@show');
                Route::get('{codstonefilial}/webhook', '\Mg\Stone\Connect\FilialController@showWebhook');
            });

            // POS
            Route::group(['prefix' => 'pos'], function () {
                Route::post('', '\Mg\Stone\Connect\PosController@store');
                Route::delete('{codstonepos}', '\Mg\Stone\Connect\PosController@destroy');
            });
        });


        // NOTA FISCAL TRANSFERENCIA
        Route::get('nota-fiscal/dashboard', '\Mg\NotaFiscal\NotaFiscalTransferenciaController@index');
        Route::get('nota-fiscal/gera-transferencias/{codfilial}', '\Mg\NotaFiscal\NotaFiscalTransferenciaController@GerarNovaTransferencia');
        Route::get('nota-fiscal/notas-por-emitir', '\Mg\NotaFiscal\NotaFiscalTransferenciaController@NotasPorEmitir');
        Route::get('nota-fiscal/notas-nao-autorizadas', '\Mg\NotaFiscal\NotaFiscalTransferenciaController@NotasNaoAutorizadas');
        Route::get('nota-fiscal/notas-emitidas', '\Mg\NotaFiscal\NotaFiscalTransferenciaController@NotasEmitidas');
        Route::get('nota-fiscal/notas-lancadas', '\Mg\NotaFiscal\NotaFiscalTransferenciaController@NotasLancadas');


        // MDFe
        Route::post('mdfe/criar-da-nota-fiscal/{codnotafiscal}', '\Mg\Mdfe\MdfeController@criarDaNotaFiscal');
        Route::post('mdfe/criar-da-nfechave/{nfechave}', '\Mg\Mdfe\MdfeController@criarDaNfeChave');
        Route::post('mdfe/{codmdfe}/criar-xml', '\Mg\Mdfe\MdfeController@criarXml');
        Route::post('mdfe/{codmdfe}/enviar', '\Mg\Mdfe\MdfeController@enviar');
        Route::post('mdfe/{codmdfe}/consultar-envio/{codmdfeenviosefaz?}', '\Mg\Mdfe\MdfeController@consultarEnvio');
        Route::post('mdfe/{codmdfe}/consultar', '\Mg\Mdfe\MdfeController@consultar');
        Route::post('mdfe/{codmdfe}/cancelar', '\Mg\Mdfe\MdfeController@cancelar');
        Route::post('mdfe/{codmdfe}/encerrar', '\Mg\Mdfe\MdfeController@encerrar');
        Route::get('mdfe/{codmdfe}/damdfe', '\Mg\Mdfe\MdfeController@damdfe');
        Route::get('mdfe/{codmdfe}', '\Mg\Mdfe\MdfeController@show');
        Route::get('mdfe', '\Mg\Mdfe\MdfeController@index');
        Route::put('mdfe/{codmdfe}', '\Mg\Mdfe\MdfeController@update');

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

        // Autocomplete / Select
        //Route::get('select/pessoa', '\Mg\Select\SelectPessoaController@index');
        Route::get('select/cidade', '\Mg\Select\SelectCidadeController@index');
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

        // Dominio
        Route::get('dominio/empresas', '\Mg\Dominio\DominioController@empresas');
        Route::post('dominio/estoque', '\Mg\Dominio\DominioController@estoque');
        Route::post('dominio/produto', '\Mg\Dominio\DominioController@produto');
        Route::post('dominio/pessoa', '\Mg\Dominio\DominioController@pessoa');
        Route::post('dominio/entrada', '\Mg\Dominio\DominioController@entrada');
        Route::post('dominio/nfe-saida', '\Mg\Dominio\DominioController@nfeSaida');
        Route::post('dominio/nfe-entrada', '\Mg\Dominio\DominioController@nfeEntrada');

        // Pix
        Route::post('pix/{codportador}/consultar', '\Mg\Pix\PixController@consultarPix');
        Route::post('pix/consultar', '\Mg\Pix\PixController@consultarPixTodos');
        Route::get('pix/', '\Mg\Pix\PixController@index');

        // Portador
        Route::get('portador', '\Mg\Portador\PortadorController@index');
        Route::get('portador/{codportador}', '\Mg\Portador\PortadorController@show');
        Route::post('portador/importar-ofx', '\Mg\Portador\PortadorController@importarOfx');
    });
});
