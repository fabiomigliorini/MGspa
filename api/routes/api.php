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
    // Auth/User — validação de sessão usada pelos 4 frontends Quasar
    // (pessoas/notas/contas/negocios). Substitui o endpoint `v1/auth/user`
    // do MGspa/laravel legado.
    Route::get('auth/user', [\Mg\Usuario\UsuarioController::class, 'permissoesUsuarios']);

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

    // Empresa (migrado em 23/05/2026)
    Route::get('empresa/', [\Mg\Filial\EmpresaController::class, 'index']);
    Route::post('empresa/', [\Mg\Filial\EmpresaController::class, 'store']);
    Route::get('empresa/{codempresa}', [\Mg\Filial\EmpresaController::class, 'show']);
    Route::put('empresa/{codempresa}', [\Mg\Filial\EmpresaController::class, 'update']);
    Route::delete('empresa/{codempresa}', [\Mg\Filial\EmpresaController::class, 'destroy']);

    // CertidaoEmissor (migrado em 23/05/2026)
    Route::get('certidao-emissor/', [\Mg\Certidao\CertidaoEmissorController::class, 'index']);
    Route::post('certidao-emissor/', [\Mg\Certidao\CertidaoEmissorController::class, 'store']);
    Route::put('certidao-emissor/{codcertidaoemissor}', [\Mg\Certidao\CertidaoEmissorController::class, 'update']);
    Route::delete('certidao-emissor/{codcertidaoemissor}', [\Mg\Certidao\CertidaoEmissorController::class, 'destroy']);
    Route::post('certidao-emissor/{codcertidaoemissor}/inativo', [\Mg\Certidao\CertidaoEmissorController::class, 'inativar']);
    Route::delete('certidao-emissor/{codcertidaoemissor}/inativo', [\Mg\Certidao\CertidaoEmissorController::class, 'ativar']);

    // EstoqueLocal (migrado em 23/05/2026) — só leitura
    Route::get('estoque-local', [\Mg\Estoque\EstoqueLocalController::class, 'index']);
    Route::get('estoque-local/{id}', [\Mg\Estoque\EstoqueLocalController::class, 'show']);

    // Veiculo (migrado em 23/05/2026)
    Route::get('veiculo', [\Mg\Veiculo\VeiculoController::class, 'index']);
    Route::get('veiculo/{id}', [\Mg\Veiculo\VeiculoController::class, 'show']);
    Route::put('veiculo/{id}', [\Mg\Veiculo\VeiculoController::class, 'update']);
    Route::post('veiculo', [\Mg\Veiculo\VeiculoController::class, 'store']);
    Route::post('veiculo/{id}/inativo', [\Mg\Veiculo\VeiculoController::class, 'inativar']);
    Route::delete('veiculo/{id}/inativo', [\Mg\Veiculo\VeiculoController::class, 'ativar']);
    Route::delete('veiculo/{id}', [\Mg\Veiculo\VeiculoController::class, 'delete']);

    // VeiculoTipo (migrado em 23/05/2026)
    Route::get('veiculo-tipo', [\Mg\Veiculo\VeiculoTipoController::class, 'index']);
    Route::get('veiculo-tipo/{id}', [\Mg\Veiculo\VeiculoTipoController::class, 'show']);
    Route::put('veiculo-tipo/{id}', [\Mg\Veiculo\VeiculoTipoController::class, 'update']);
    Route::post('veiculo-tipo', [\Mg\Veiculo\VeiculoTipoController::class, 'store']);
    Route::post('veiculo-tipo/{id}/inativo', [\Mg\Veiculo\VeiculoTipoController::class, 'inativar']);
    Route::delete('veiculo-tipo/{id}/inativo', [\Mg\Veiculo\VeiculoTipoController::class, 'ativar']);
    Route::delete('veiculo-tipo/{id}', [\Mg\Veiculo\VeiculoTipoController::class, 'delete']);

    // VeiculoConjunto (migrado em 23/05/2026)
    Route::get('veiculo-conjunto', [\Mg\Veiculo\VeiculoConjuntoController::class, 'index']);
    Route::get('veiculo-conjunto/{id}', [\Mg\Veiculo\VeiculoConjuntoController::class, 'show']);
    Route::put('veiculo-conjunto/{id}', [\Mg\Veiculo\VeiculoConjuntoController::class, 'update']);
    Route::post('veiculo-conjunto', [\Mg\Veiculo\VeiculoConjuntoController::class, 'store']);
    Route::post('veiculo-conjunto/{id}/inativo', [\Mg\Veiculo\VeiculoConjuntoController::class, 'inativar']);
    Route::delete('veiculo-conjunto/{id}/inativo', [\Mg\Veiculo\VeiculoConjuntoController::class, 'ativar']);
    Route::delete('veiculo-conjunto/{id}', [\Mg\Veiculo\VeiculoConjuntoController::class, 'delete']);

    // Selects (migrados em 23/05/2026) — todos GET com index estático
    Route::get('select/pessoa', [\Mg\Select\SelectPessoaController::class, 'index']);
    Route::get('select/cidade', [\Mg\Select\SelectCidadeController::class, 'index']);
    Route::get('select/impressora', [\Mg\Select\SelectImpressoraController::class, 'index']);
    Route::get('select/filial', [\Mg\Select\SelectFilialController::class, 'index']);
    Route::get('select/estoque-local', [\Mg\Select\SelectEstoqueLocalController::class, 'index']);
    Route::get('select/estado', [\Mg\Select\SelectEstadoController::class, 'index']);
    Route::get('select/veiculo-tipo', [\Mg\Select\SelectVeiculoTipoController::class, 'index']);
    Route::get('select/veiculo', [\Mg\Select\SelectVeiculoController::class, 'index']);
    Route::get('select/produto-barra', [\Mg\Select\SelectProdutoBarraController::class, 'index']);
    Route::get('select/usuario', [\Mg\Select\SelectUsuarioController::class, 'index']);
    Route::get('select/portador', [\Mg\Select\SelectPortadorController::class, 'index']);
    Route::get('select/natureza-operacao', [\Mg\Select\SelectNaturezaOperacaoController::class, 'index']);
    Route::get('select/grupo-economico', [\Mg\Select\SelectGrupoEconomicoController::class, 'index']);
    Route::get('select/tipo-produto', [\Mg\Select\SelectTipoProdutoController::class, 'index']);
    Route::get('select/tributo', [\Mg\Select\SelectTributoController::class, 'index']);
    Route::get('select/tipo-titulo', [\Mg\Select\SelectTipoTituloController::class, 'index']);
    Route::get('select/conta-contabil', [\Mg\Select\SelectContaContabilController::class, 'index']);
    Route::get('select/banco', [\Mg\Select\SelectBancoController::class, 'index']);
    Route::get('select/estoque-movimento-tipo', [\Mg\Select\SelectEstoqueMovimentoTipoController::class, 'index']);
    Route::get('select/tributacao', [\Mg\Select\SelectTributacaoController::class, 'index']);

    // Banco (migrado em 23/05/2026)
    Route::get('banco', [\Mg\Banco\BancoController::class, 'index']);
    Route::get('banco/{codbanco}', [\Mg\Banco\BancoController::class, 'show']);
    Route::post('banco', [\Mg\Banco\BancoController::class, 'store']);
    Route::put('banco/{codbanco}', [\Mg\Banco\BancoController::class, 'update']);
    Route::delete('banco/{codbanco}', [\Mg\Banco\BancoController::class, 'destroy']);
    Route::post('banco/{codbanco}/inativo', [\Mg\Banco\BancoController::class, 'inativar']);
    Route::delete('banco/{codbanco}/inativo', [\Mg\Banco\BancoController::class, 'ativar']);

    // ContaContabil (migrado em 23/05/2026)
    Route::get('conta-contabil', [\Mg\ContaContabil\ContaContabilController::class, 'index']);
    Route::get('conta-contabil/{codcontacontabil}', [\Mg\ContaContabil\ContaContabilController::class, 'show']);
    Route::post('conta-contabil', [\Mg\ContaContabil\ContaContabilController::class, 'store']);
    Route::put('conta-contabil/{codcontacontabil}', [\Mg\ContaContabil\ContaContabilController::class, 'update']);
    Route::delete('conta-contabil/{codcontacontabil}', [\Mg\ContaContabil\ContaContabilController::class, 'destroy']);
    Route::post('conta-contabil/{codcontacontabil}/inativo', [\Mg\ContaContabil\ContaContabilController::class, 'inativar']);
    Route::delete('conta-contabil/{codcontacontabil}/inativo', [\Mg\ContaContabil\ContaContabilController::class, 'ativar']);

    // FormaPagamento (migrado em 23/05/2026)
    Route::get('forma-pagamento', [\Mg\FormaPagamento\FormaPagamentoController::class, 'index']);
    Route::get('forma-pagamento/{codformapagamento}', [\Mg\FormaPagamento\FormaPagamentoController::class, 'show']);
    Route::post('forma-pagamento', [\Mg\FormaPagamento\FormaPagamentoController::class, 'store']);
    Route::put('forma-pagamento/{codformapagamento}', [\Mg\FormaPagamento\FormaPagamentoController::class, 'update']);
    Route::delete('forma-pagamento/{codformapagamento}', [\Mg\FormaPagamento\FormaPagamentoController::class, 'destroy']);
    Route::post('forma-pagamento/{codformapagamento}/inativo', [\Mg\FormaPagamento\FormaPagamentoController::class, 'inativar']);
    Route::delete('forma-pagamento/{codformapagamento}/inativo', [\Mg\FormaPagamento\FormaPagamentoController::class, 'ativar']);

    // TipoMovimentoTitulo (migrado em 23/05/2026)
    Route::get('tipo-movimento-titulo', [\Mg\Titulo\TipoMovimentoTituloController::class, 'index']);
    Route::get('tipo-movimento-titulo/{codtipomovimentotitulo}', [\Mg\Titulo\TipoMovimentoTituloController::class, 'show']);
    Route::post('tipo-movimento-titulo', [\Mg\Titulo\TipoMovimentoTituloController::class, 'store']);
    Route::put('tipo-movimento-titulo/{codtipomovimentotitulo}', [\Mg\Titulo\TipoMovimentoTituloController::class, 'update']);
    Route::delete('tipo-movimento-titulo/{codtipomovimentotitulo}', [\Mg\Titulo\TipoMovimentoTituloController::class, 'destroy']);
    Route::post('tipo-movimento-titulo/{codtipomovimentotitulo}/inativo', [\Mg\Titulo\TipoMovimentoTituloController::class, 'inativar']);
    Route::delete('tipo-movimento-titulo/{codtipomovimentotitulo}/inativo', [\Mg\Titulo\TipoMovimentoTituloController::class, 'ativar']);

    // TipoTitulo (migrado em 23/05/2026)
    Route::get('tipo-titulo', [\Mg\Titulo\TipoTituloController::class, 'index']);
    Route::get('tipo-titulo/{codtipotitulo}', [\Mg\Titulo\TipoTituloController::class, 'show']);
    Route::post('tipo-titulo', [\Mg\Titulo\TipoTituloController::class, 'store']);
    Route::put('tipo-titulo/{codtipotitulo}', [\Mg\Titulo\TipoTituloController::class, 'update']);
    Route::delete('tipo-titulo/{codtipotitulo}', [\Mg\Titulo\TipoTituloController::class, 'destroy']);
    Route::post('tipo-titulo/{codtipotitulo}/inativo', [\Mg\Titulo\TipoTituloController::class, 'inativar']);
    Route::delete('tipo-titulo/{codtipotitulo}/inativo', [\Mg\Titulo\TipoTituloController::class, 'ativar']);

    // CobrancaHistorico (nested em pessoa, migrado em 23/05/2026)
    Route::get('pessoa/{codpessoa}/cobrancahistorico/', [\Mg\Cobranca\CobrancaHistoricoController::class, 'index']);
    Route::post('pessoa/{codpessoa}/cobrancahistorico/', [\Mg\Cobranca\CobrancaHistoricoController::class, 'create']);
    Route::get('pessoa/{codpessoa}/cobrancahistorico/{codcobrancahistorico}/', [\Mg\Cobranca\CobrancaHistoricoController::class, 'show']);
    Route::put('pessoa/{codpessoa}/cobrancahistorico/{codcobrancahistorico}/', [\Mg\Cobranca\CobrancaHistoricoController::class, 'update']);
    Route::delete('pessoa/{codpessoa}/cobrancahistorico/{codcobrancahistorico}/', [\Mg\Cobranca\CobrancaHistoricoController::class, 'delete']);

    // RegistroSpc (nested em pessoa, migrado em 23/05/2026)
    Route::get('pessoa/{codpessoa}/registrospc/', [\Mg\Pessoa\RegistroSpcController::class, 'index']);
    Route::post('pessoa/{codpessoa}/registrospc/', [\Mg\Pessoa\RegistroSpcController::class, 'create']);
    Route::get('pessoa/{codpessoa}/registrospc/{codregistrospc}/', [\Mg\Pessoa\RegistroSpcController::class, 'show']);
    Route::put('pessoa/{codpessoa}/registrospc/{codregistrospc}/', [\Mg\Pessoa\RegistroSpcController::class, 'update']);
    Route::delete('pessoa/{codpessoa}/registrospc/{codregistrospc}/', [\Mg\Pessoa\RegistroSpcController::class, 'delete']);

    // PessoaCertidao (migrado em 23/05/2026)
    Route::post('certidao/', [\Mg\Pessoa\PessoaCertidaoController::class, 'create']);
    Route::get('certidao/{codpessoacertidao}/', [\Mg\Pessoa\PessoaCertidaoController::class, 'show']);
    Route::put('certidao/{codpessoacertidao}/', [\Mg\Pessoa\PessoaCertidaoController::class, 'update']);
    Route::delete('certidao/{codpessoacertidao}/', [\Mg\Pessoa\PessoaCertidaoController::class, 'delete']);
    Route::get('select/certidao/emissor', [\Mg\Pessoa\PessoaCertidaoController::class, 'selectCertidaoEmissor']);
    Route::get('select/certidao/tipo', [\Mg\Pessoa\PessoaCertidaoController::class, 'selectCertidaoTipo']);
    Route::post('certidao/{codpessoacertidao}/inativo', [\Mg\Pessoa\PessoaCertidaoController::class, 'inativar']);
    Route::delete('certidao/{codpessoacertidao}/inativo', [\Mg\Pessoa\PessoaCertidaoController::class, 'ativar']);

    // PessoaConta (nested em pessoa, migrado em 23/05/2026)
    Route::get('pessoa/{codpessoa}/conta/', [\Mg\Pessoa\PessoaContaController::class, 'index']);
    Route::post('pessoa/{codpessoa}/conta/', [\Mg\Pessoa\PessoaContaController::class, 'create']);
    Route::get('pessoa/{codpessoa}/conta/{codpessoaconta}/', [\Mg\Pessoa\PessoaContaController::class, 'show']);
    Route::put('pessoa/{codpessoa}/conta/{codpessoaconta}/', [\Mg\Pessoa\PessoaContaController::class, 'update']);
    Route::delete('pessoa/{codpessoa}/conta/{codpessoaconta}/', [\Mg\Pessoa\PessoaContaController::class, 'delete']);
    Route::get('pessoa/conta/banco/select', [\Mg\Pessoa\PessoaContaController::class, 'selectBanco']);
    Route::post('pessoa/conta/{codpessoaconta}/inativo', [\Mg\Pessoa\PessoaContaController::class, 'inativar']);
    Route::delete('pessoa/conta/{codpessoaconta}/inativo', [\Mg\Pessoa\PessoaContaController::class, 'ativar']);

    // Tributacao + TributacaoRegra + Tributo (migrado em 23/05/2026)
    Route::apiResource('tributacao/tributo', \Mg\Tributacao\TributoController::class)->parameters(['tributo' => 'tributo']);
    Route::apiResource('tributacao/regra', \Mg\Tributacao\TributacaoRegraController::class)->parameters(['regra' => 'regra']);
    Route::apiResource('tributacao', \Mg\Tributacao\TributacaoController::class)->parameters(['tributacao' => 'codtributacao']);

    // NaturezaOperacao trio (migrado em 23/05/2026)
    Route::apiResource('natureza-operacao/cfop', \Mg\NaturezaOperacao\CfopController::class)->parameters(['cfop' => 'codcfop']);
    Route::apiResource('natureza-operacao/{codnaturezaoperacao}/tributacao', \Mg\NaturezaOperacao\TributacaoNaturezaOperacaoController::class)->parameters(['tributacao' => 'codtributacaonaturezaoperacao']);
    Route::apiResource('natureza-operacao', \Mg\NaturezaOperacao\NaturezaOperacaoController::class)->parameters(['natureza-operacao' => 'codnaturezaoperacao']);
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
