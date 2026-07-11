<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas de Auth/OAuth/OIDC
|--------------------------------------------------------------------------
| Vivem em routes/web.php sob paths raiz spec-compliant:
|   /oauth/token, /oauth/revoke, /oauth/introspect (RFC 6749/7009/7662)
|   /oauth/authorize × 3 (consent + approve + deny — RFC 6749)
|   /userinfo (OIDC Core 1.0 §5.3)
|   /login (HTML page com fetch JS pra /oauth/token)
|
| Aqui em routes/api.php só ficam as rotas de domínio (v1/...).
*/

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
    Route::get('feriado/{codferiado}', [\Mg\Feriado\FeriadoController::class, 'show'])->whereNumber('codferiado');
    Route::post('feriado/', [\Mg\Feriado\FeriadoController::class, 'store']);
    Route::put('feriado/{codferiado}', [\Mg\Feriado\FeriadoController::class, 'update']);
    Route::delete('feriado/{codferiado}', [\Mg\Feriado\FeriadoController::class, 'destroy']);
    Route::post('feriado/{codferiado}/inativo', [\Mg\Feriado\FeriadoController::class, 'inativar']);
    Route::delete('feriado/{codferiado}/inativo', [\Mg\Feriado\FeriadoController::class, 'ativar']);
    Route::post('feriado/gerar-ano', [\Mg\Feriado\FeriadoController::class, 'gerarAno']);

    // TipoSetor (migrado em 23/05/2026)
    Route::get('tipo-setor/', [\Mg\Filial\TipoSetorController::class, 'index']);
    Route::get('tipo-setor/{codtiposetor}', [\Mg\Filial\TipoSetorController::class, 'show'])->whereNumber('codtiposetor');
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
    Route::get('certidao-emissor/{codcertidaoemissor}', [\Mg\Certidao\CertidaoEmissorController::class, 'show'])->whereNumber('codcertidaoemissor');
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
    Route::get('select/moeda', [\Mg\Select\SelectMoedaController::class, 'index']);

    // Selects: resolução por id (objeto único ou 404) — padrão GET select/{ent}/{id}
    Route::get('select/pessoa/{id}', [\Mg\Select\SelectPessoaController::class, 'show'])->whereNumber('id');
    Route::get('select/cidade/{id}', [\Mg\Select\SelectCidadeController::class, 'show'])->whereNumber('id');
    Route::get('select/impressora/{id}', [\Mg\Select\SelectImpressoraController::class, 'show']);
    Route::get('select/filial/{id}', [\Mg\Select\SelectFilialController::class, 'show'])->whereNumber('id');
    Route::get('select/estoque-local/{id}', [\Mg\Select\SelectEstoqueLocalController::class, 'show'])->whereNumber('id');
    Route::get('select/estado/{id}', [\Mg\Select\SelectEstadoController::class, 'show'])->whereNumber('id');
    Route::get('select/veiculo-tipo/{id}', [\Mg\Select\SelectVeiculoTipoController::class, 'show'])->whereNumber('id');
    Route::get('select/veiculo/{id}', [\Mg\Select\SelectVeiculoController::class, 'show'])->whereNumber('id');
    Route::get('select/produto-barra/{id}', [\Mg\Select\SelectProdutoBarraController::class, 'show'])->whereNumber('id');
    Route::get('select/usuario/{id}', [\Mg\Select\SelectUsuarioController::class, 'show'])->whereNumber('id');
    Route::get('select/portador/{id}', [\Mg\Select\SelectPortadorController::class, 'show'])->whereNumber('id');
    Route::get('select/natureza-operacao/{id}', [\Mg\Select\SelectNaturezaOperacaoController::class, 'show'])->whereNumber('id');
    Route::get('select/grupo-economico/{id}', [\Mg\Select\SelectGrupoEconomicoController::class, 'show'])->whereNumber('id');
    Route::get('select/tipo-produto/{id}', [\Mg\Select\SelectTipoProdutoController::class, 'show'])->whereNumber('id');
    Route::get('select/tributo/{id}', [\Mg\Select\SelectTributoController::class, 'show'])->whereNumber('id');
    Route::get('select/tipo-titulo/{id}', [\Mg\Select\SelectTipoTituloController::class, 'show'])->whereNumber('id');
    Route::get('select/conta-contabil/{id}', [\Mg\Select\SelectContaContabilController::class, 'show'])->whereNumber('id');
    Route::get('select/banco/{id}', [\Mg\Select\SelectBancoController::class, 'show'])->whereNumber('id');
    Route::get('select/estoque-movimento-tipo/{id}', [\Mg\Select\SelectEstoqueMovimentoTipoController::class, 'show'])->whereNumber('id');
    Route::get('select/tributacao/{id}', [\Mg\Select\SelectTributacaoController::class, 'show'])->whereNumber('id');
    Route::get('select/moeda/{id}', [\Mg\Select\SelectMoedaController::class, 'show']);

    // Selects novos (entidades LOCAL pequenas, padrão index + show)
    Route::get('select/forma-pagamento', [\Mg\Select\SelectFormaPagamentoController::class, 'index']);
    Route::get('select/forma-pagamento/{id}', [\Mg\Select\SelectFormaPagamentoController::class, 'show'])->whereNumber('id');
    Route::get('select/grupo-cliente', [\Mg\Select\SelectGrupoClienteController::class, 'index']);
    Route::get('select/grupo-cliente/{id}', [\Mg\Select\SelectGrupoClienteController::class, 'show'])->whereNumber('id');
    Route::get('select/tipo-movimento-titulo', [\Mg\Select\SelectTipoMovimentoTituloController::class, 'index']);
    Route::get('select/tipo-movimento-titulo/{id}', [\Mg\Select\SelectTipoMovimentoTituloController::class, 'show'])->whereNumber('id');
    Route::get('select/setor', [\Mg\Select\SelectSetorController::class, 'index']);
    Route::get('select/setor/{id}', [\Mg\Select\SelectSetorController::class, 'show'])->whereNumber('id');
    Route::get('select/tipo-setor', [\Mg\Select\SelectTipoSetorController::class, 'index']);
    Route::get('select/tipo-setor/{id}', [\Mg\Select\SelectTipoSetorController::class, 'show'])->whereNumber('id');
    Route::get('select/unidade-negocio', [\Mg\Select\SelectUnidadeNegocioController::class, 'index']);
    Route::get('select/unidade-negocio/{id}', [\Mg\Select\SelectUnidadeNegocioController::class, 'show'])->whereNumber('id');
    Route::get('select/cheque-motivo-devolucao', [\Mg\Select\SelectChequeMotivoDevolucaoController::class, 'index']);
    Route::get('select/cheque-motivo-devolucao/{id}', [\Mg\Select\SelectChequeMotivoDevolucaoController::class, 'show'])->whereNumber('id');
    Route::get('select/cargo', [\Mg\Select\SelectCargoController::class, 'index']);
    Route::get('select/cargo/{id}', [\Mg\Select\SelectCargoController::class, 'show'])->whereNumber('id');
    Route::get('select/estado-civil', [\Mg\Select\SelectEstadoCivilController::class, 'index']);
    Route::get('select/estado-civil/{id}', [\Mg\Select\SelectEstadoCivilController::class, 'show'])->whereNumber('id');
    Route::get('select/etnia', [\Mg\Select\SelectEtniaController::class, 'index']);
    Route::get('select/etnia/{id}', [\Mg\Select\SelectEtniaController::class, 'show'])->whereNumber('id');
    Route::get('select/grau-instrucao', [\Mg\Select\SelectGrauInstrucaoController::class, 'index']);
    Route::get('select/grau-instrucao/{id}', [\Mg\Select\SelectGrauInstrucaoController::class, 'show'])->whereNumber('id');
    Route::get('select/cfop', [\Mg\Select\SelectCfopController::class, 'index']);
    Route::get('select/cfop/{id}', [\Mg\Select\SelectCfopController::class, 'show'])->whereNumber('id');
    Route::get('select/certidao-emissor', [\Mg\Select\SelectCertidaoEmissorController::class, 'index']);
    Route::get('select/certidao-emissor/{id}', [\Mg\Select\SelectCertidaoEmissorController::class, 'show'])->whereNumber('id');
    Route::get('select/certidao-tipo', [\Mg\Select\SelectCertidaoTipoController::class, 'index']);
    Route::get('select/certidao-tipo/{id}', [\Mg\Select\SelectCertidaoTipoController::class, 'show'])->whereNumber('id');

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

    // Cheque + Motivo de Devolução + Repasse (migrado de MGLara em 31/05/2026)
    Route::get('cheque-motivo-devolucao/autocompletar', [\Mg\Cheque\ChequeMotivoDevolucaoController::class, 'autocompletar']);
    Route::get('cheque-motivo-devolucao', [\Mg\Cheque\ChequeMotivoDevolucaoController::class, 'index']);
    Route::get('cheque-motivo-devolucao/{codchequemotivodevolucao}', [\Mg\Cheque\ChequeMotivoDevolucaoController::class, 'show']);
    Route::post('cheque-motivo-devolucao', [\Mg\Cheque\ChequeMotivoDevolucaoController::class, 'store']);
    Route::put('cheque-motivo-devolucao/{codchequemotivodevolucao}', [\Mg\Cheque\ChequeMotivoDevolucaoController::class, 'update']);
    Route::delete('cheque-motivo-devolucao/{codchequemotivodevolucao}', [\Mg\Cheque\ChequeMotivoDevolucaoController::class, 'destroy']);

    Route::get('cheque-repasse/cheques-para-repassar', [\Mg\Cheque\ChequeRepasseController::class, 'chequesParaRepassar']);
    Route::get('cheque-repasse/{codchequerepasse}/pdf', [\Mg\Cheque\ChequeRepasseController::class, 'borderoPdf']);
    Route::get('cheque-repasse', [\Mg\Cheque\ChequeRepasseController::class, 'index']);
    Route::get('cheque-repasse/{codchequerepasse}', [\Mg\Cheque\ChequeRepasseController::class, 'show']);
    Route::post('cheque-repasse', [\Mg\Cheque\ChequeRepasseController::class, 'store']);
    Route::post('cheque-repasse/{codchequerepasse}/inativo', [\Mg\Cheque\ChequeRepasseController::class, 'inativar']);
    Route::delete('cheque-repasse/{codchequerepasse}/inativo', [\Mg\Cheque\ChequeRepasseController::class, 'ativar']);
    Route::post('cheque-repasse/{codchequerepasse}/cheque/{codchequerepassecheque}/devolver', [\Mg\Cheque\ChequeRepasseController::class, 'devolverCheque']);

    Route::get('cheque/consulta-cmc7/{cmc7}', [\Mg\Cheque\ChequeController::class, 'consultaCmc7']);
    Route::get('cheque/consulta-emitente/{cnpj}', [\Mg\Cheque\ChequeController::class, 'consultaEmitente']);
    Route::get('cheque', [\Mg\Cheque\ChequeController::class, 'index']);
    Route::get('cheque/{codcheque}', [\Mg\Cheque\ChequeController::class, 'show']);
    Route::post('cheque', [\Mg\Cheque\ChequeController::class, 'store']);
    Route::put('cheque/{codcheque}', [\Mg\Cheque\ChequeController::class, 'update']);

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

    // Cidade trio (migrado em 23/05/2026)
    Route::apiResource('pais', \Mg\Cidade\PaisController::class)->parameters(['pais' => 'codpais']);
    Route::apiResource('pais.estado', \Mg\Cidade\EstadoController::class)->parameters(['pais' => 'codpais', 'estado' => 'codestado']);
    Route::apiResource('pais.estado.cidade', \Mg\Cidade\CidadeController::class)->parameters(['pais' => 'codpais', 'estado' => 'codestado', 'cidade' => 'codcidade']);

    // Marca (migrado em 23/05/2026 — planilha-pedido/distribuição ficam proxiadas pro legacy)
    Route::get('marca/autocompletar', [\Mg\Marca\MarcaController::class, 'autocompletar']);
    Route::get('marca/{id}/detalhes', [\Mg\Marca\MarcaController::class, 'detalhes']);
    Route::post('marca/{id}/inativo', [\Mg\Marca\MarcaController::class, 'inativar']);
    Route::delete('marca/{id}/inativo', [\Mg\Marca\MarcaController::class, 'ativar']);
    Route::apiResource('marca', \Mg\Marca\MarcaController::class)->parameters(['marca' => 'id']);

    // Cultura / Variedade / TabelaDesconto (dominio Mg\Cultura) — criado 03/06/2026
    Route::get('cultura/{codcultura}/resumo', [\Mg\Cultura\CulturaController::class, 'resumo'])->whereNumber('codcultura');
    Route::post('cultura/{codcultura}/inativo', [\Mg\Cultura\CulturaController::class, 'inativar']);
    Route::delete('cultura/{codcultura}/inativo', [\Mg\Cultura\CulturaController::class, 'ativar']);
    Route::apiResource('cultura', \Mg\Cultura\CulturaController::class)->parameters(['cultura' => 'codcultura']);

    Route::post('variedade/{codvariedade}/inativo', [\Mg\Cultura\VariedadeController::class, 'inativar']);
    Route::delete('variedade/{codvariedade}/inativo', [\Mg\Cultura\VariedadeController::class, 'ativar']);
    Route::apiResource('variedade', \Mg\Cultura\VariedadeController::class)->parameters(['variedade' => 'codvariedade']);

    Route::post('tabela-desconto/{codtabeladesconto}/inativo', [\Mg\Cultura\TabelaDescontoController::class, 'inativar']);
    Route::delete('tabela-desconto/{codtabeladesconto}/inativo', [\Mg\Cultura\TabelaDescontoController::class, 'ativar']);
    Route::apiResource('tabela-desconto', \Mg\Cultura\TabelaDescontoController::class)->parameters(['tabela-desconto' => 'codtabeladesconto']);

    Route::post('cultura-tributo/{codculturatributo}/inativo', [\Mg\Cultura\CulturaTributoController::class, 'inativar']);
    Route::delete('cultura-tributo/{codculturatributo}/inativo', [\Mg\Cultura\CulturaTributoController::class, 'ativar']);
    Route::apiResource('cultura-tributo', \Mg\Cultura\CulturaTributoController::class)->parameters(['cultura-tributo' => 'codculturatributo']);

    // Fazenda / Talhao (dominio Mg\Fazenda) — criado 03/06/2026
    Route::get('fazenda/{codfazenda}/resumo', [\Mg\Fazenda\FazendaController::class, 'resumo']);
    Route::post('fazenda/{codfazenda}/inativo', [\Mg\Fazenda\FazendaController::class, 'inativar']);
    Route::delete('fazenda/{codfazenda}/inativo', [\Mg\Fazenda\FazendaController::class, 'ativar']);
    Route::apiResource('fazenda', \Mg\Fazenda\FazendaController::class)->parameters(['fazenda' => 'codfazenda']);

    Route::get('talhao/mapa', [\Mg\Fazenda\TalhaoController::class, 'mapa']);
    Route::post('talhao/{codtalhao}/inativo', [\Mg\Fazenda\TalhaoController::class, 'inativar']);
    Route::delete('talhao/{codtalhao}/inativo', [\Mg\Fazenda\TalhaoController::class, 'ativar']);
    Route::apiResource('talhao', \Mg\Fazenda\TalhaoController::class)->parameters(['talhao' => 'codtalhao']);

    // Safra (dominio Mg\Safra) — criado 03/06/2026
    Route::post('safra/{codsafra}/inativo', [\Mg\Safra\SafraController::class, 'inativar']);
    Route::delete('safra/{codsafra}/inativo', [\Mg\Safra\SafraController::class, 'ativar']);
    Route::get('safra/{codsafra}/comercial', [\Mg\Safra\SafraController::class, 'comercial']); // KPIs comerciais (contratos)
    Route::apiResource('safra', \Mg\Safra\SafraController::class)->parameters(['safra' => 'codsafra']);

    // Plantio aninhado na safra: safra/{codsafra}/plantio
    Route::post('safra/{codsafra}/plantio/{codplantio}/inativo', [\Mg\Fazenda\PlantioController::class, 'inativar']);
    Route::delete('safra/{codsafra}/plantio/{codplantio}/inativo', [\Mg\Fazenda\PlantioController::class, 'ativar']);
    Route::apiResource('safra.plantio', \Mg\Fazenda\PlantioController::class)->parameters(['safra' => 'codsafra', 'plantio' => 'codplantio']);

    // Carga unificada (recebimento/expedicao/transferencia) — Mg\Grao, offline (uuid).
    // O extrato (tblmovimentograo) e gerado pelo servidor a partir dos pontos.
    Route::get('carga', [\Mg\Grao\CargaController::class, 'index']);
    Route::post('carga/sincronizar', [\Mg\Grao\CargaController::class, 'sincronizar']);
    Route::post('carga/recalcular', [\Mg\Grao\CargaController::class, 'recalcular']);
    Route::post('carga/{codcarga}/inativo', [\Mg\Grao\CargaController::class, 'inativar']);
    Route::delete('carga/{codcarga}/inativo', [\Mg\Grao\CargaController::class, 'ativar']);
    Route::get('carga/{codcarga}', [\Mg\Grao\CargaController::class, 'show'])->whereNumber('codcarga');

    // Unidade armazenadora (silo proprio / armazem de terceiro / silo bag) — Mg\Grao
    Route::post('unidade-armazenadora/{codunidadearmazenadora}/inativo', [\Mg\Grao\UnidadeArmazenadoraController::class, 'inativar']);
    Route::delete('unidade-armazenadora/{codunidadearmazenadora}/inativo', [\Mg\Grao\UnidadeArmazenadoraController::class, 'ativar']);
    Route::apiResource('unidade-armazenadora', \Mg\Grao\UnidadeArmazenadoraController::class)
        ->parameters(['unidade-armazenadora' => 'codunidadearmazenadora']);

    // Extrato/razao de grao (movimento) + ajustes manuais comerciais — Mg\Grao
    Route::get('movimento-grao/saldos-unidades', [\Mg\Grao\MovimentoGraoController::class, 'saldosUnidades']);
    Route::get('movimento-grao', [\Mg\Grao\MovimentoGraoController::class, 'index']);
    Route::post('movimento-grao', [\Mg\Grao\MovimentoGraoController::class, 'store']);
    Route::post('movimento-grao/{codmovimentograo}/inativo', [\Mg\Grao\MovimentoGraoController::class, 'inativar']);
    Route::delete('movimento-grao/{codmovimentograo}/inativo', [\Mg\Grao\MovimentoGraoController::class, 'ativar']);

    // Unidade de referência fiscal (UPF-MT, UR municipal) — Mg\UnidadeReferencia
    Route::post('unidade-referencia/importar-upf-mt', [\Mg\UnidadeReferencia\UnidadeReferenciaController::class, 'importarUpfMt']);
    Route::post('unidade-referencia/{id}/valor', [\Mg\UnidadeReferencia\UnidadeReferenciaController::class, 'storeValor'])->whereNumber('id');
    Route::put('unidade-referencia/{id}/valor/{codvalor}', [\Mg\UnidadeReferencia\UnidadeReferenciaController::class, 'updateValor'])->whereNumber('id');
    Route::delete('unidade-referencia/{id}/valor/{codvalor}', [\Mg\UnidadeReferencia\UnidadeReferenciaController::class, 'destroyValor']);
    Route::post('unidade-referencia/{id}/inativo', [\Mg\UnidadeReferencia\UnidadeReferenciaController::class, 'inativar']);
    Route::delete('unidade-referencia/{id}/inativo', [\Mg\UnidadeReferencia\UnidadeReferenciaController::class, 'ativar']);
    Route::apiResource('unidade-referencia', \Mg\UnidadeReferencia\UnidadeReferenciaController::class)->parameters(['unidade-referencia' => 'id']);

    // Contrato de venda — Mg\Contrato (criado 03/06/2026)
    // Preview do preço líquido (deduções) — antes do apiResource p/ não colidir.
    Route::get('contrato/calculo', [\Mg\Contrato\ContratoController::class, 'calculo']);
    Route::get('safra/{codsafra}/proximo-numero-contrato', [\Mg\Contrato\ContratoController::class, 'proximoNumero']); // sugestão de Nº Nosso
    Route::get('contrato/{codcontrato}/carga/{codcarga}/emissao', [\Mg\Contrato\ContratoController::class, 'emissao']); // plano de NF triangular (preview)
    Route::post('contrato/{codcontrato}/inativo', [\Mg\Contrato\ContratoController::class, 'inativar']);
    Route::delete('contrato/{codcontrato}/inativo', [\Mg\Contrato\ContratoController::class, 'ativar']);
    Route::post('contrato/{codcontrato}/barter', [\Mg\Contrato\ContratoController::class, 'marcarBarter']); // settlement em insumos
    Route::delete('contrato/{codcontrato}/barter', [\Mg\Contrato\ContratoController::class, 'desmarcarBarter']);
    Route::apiResource('contrato', \Mg\Contrato\ContratoController::class)->parameters(['contrato' => 'codcontrato']);

    // Fixacoes e pagamentos aninhados no contrato
    Route::post('contrato/{codcontrato}/fixacao/{codfixacao}/inativo', [\Mg\Contrato\ContratoFixacaoController::class, 'inativar']);
    Route::delete('contrato/{codcontrato}/fixacao/{codfixacao}/inativo', [\Mg\Contrato\ContratoFixacaoController::class, 'ativar']);
    Route::apiResource('contrato.fixacao', \Mg\Contrato\ContratoFixacaoController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['contrato' => 'codcontrato', 'fixacao' => 'codfixacao']);

    // Quitar/reabrir a fixacao (marca "recebida" mesmo com diferencinha de imposto)
    Route::post('contrato/{codcontrato}/fixacao/{codfixacao}/quitar', [\Mg\Contrato\ContratoFixacaoController::class, 'quitar']);
    Route::delete('contrato/{codcontrato}/fixacao/{codfixacao}/quitar', [\Mg\Contrato\ContratoFixacaoController::class, 'reabrir']);

    // Travas de cambio aninhadas na fixacao
    Route::post('contrato/{codcontrato}/fixacao/{codfixacao}/cambio/{codcambio}/inativo', [\Mg\Contrato\ContratoFixacaoCambioController::class, 'inativar']);
    Route::delete('contrato/{codcontrato}/fixacao/{codfixacao}/cambio/{codcambio}/inativo', [\Mg\Contrato\ContratoFixacaoCambioController::class, 'ativar']);
    Route::apiResource('contrato.fixacao.cambio', \Mg\Contrato\ContratoFixacaoCambioController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['contrato' => 'codcontrato', 'fixacao' => 'codfixacao', 'cambio' => 'codcambio']);

    // Anexos (PDFs) do contrato
    Route::get('contrato/{codcontrato}/anexo', [\Mg\Contrato\ContratoAnexoController::class, 'index']);
    Route::post('contrato/{codcontrato}/anexo', [\Mg\Contrato\ContratoAnexoController::class, 'store']);
    Route::get('contrato/{codcontrato}/anexo/{nome}/download', [\Mg\Contrato\ContratoAnexoController::class, 'download'])->where('nome', '.*');
    Route::delete('contrato/{codcontrato}/anexo/{nome}', [\Mg\Contrato\ContratoAnexoController::class, 'destroy'])->where('nome', '.*');

    // Recebimentos aninhados na fixacao (1 fixacao : N recebimentos)
    Route::post('contrato/{codcontrato}/fixacao/{codfixacao}/pagamento/{codpagamento}/inativo', [\Mg\Contrato\ContratoPagamentoController::class, 'inativar']);
    Route::delete('contrato/{codcontrato}/fixacao/{codfixacao}/pagamento/{codpagamento}/inativo', [\Mg\Contrato\ContratoPagamentoController::class, 'ativar']);
    Route::apiResource('contrato.fixacao.pagamento', \Mg\Contrato\ContratoPagamentoController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['contrato' => 'codcontrato', 'fixacao' => 'codfixacao', 'pagamento' => 'codpagamento']);

    // Plano de emissao de NF (operacao triangular) aninhado no contrato
    Route::post('contrato/{codcontrato}/nota/{codnota}/inativo', [\Mg\Contrato\ContratoNotaController::class, 'inativar']);
    Route::delete('contrato/{codcontrato}/nota/{codnota}/inativo', [\Mg\Contrato\ContratoNotaController::class, 'ativar']);
    Route::apiResource('contrato.nota', \Mg\Contrato\ContratoNotaController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['contrato' => 'codcontrato', 'nota' => 'codnota']);

    // Moeda (cadastro compartilhado; CRUD no app contas)
    Route::post('moeda/{moeda}/inativo', [\Mg\Moeda\MoedaController::class, 'inativar']);
    Route::delete('moeda/{moeda}/inativo', [\Mg\Moeda\MoedaController::class, 'ativar']);
    Route::apiResource('moeda', \Mg\Moeda\MoedaController::class)->parameters(['moeda' => 'moeda']);

    // UnidadeMedida (migrado em 31/05/2026)
    Route::get('unidade-medida/autocompletar', [\Mg\Produto\UnidadeMedidaController::class, 'autocompletar']);
    Route::post('unidade-medida/{id}/inativo', [\Mg\Produto\UnidadeMedidaController::class, 'inativar']);
    Route::delete('unidade-medida/{id}/inativo', [\Mg\Produto\UnidadeMedidaController::class, 'ativar']);
    Route::apiResource('unidade-medida', \Mg\Produto\UnidadeMedidaController::class)->parameters(['unidade-medida' => 'id']);

    // TipoProduto (migrado em 31/05/2026 — sem inativo, não há coluna)
    Route::get('tipo-produto/autocompletar', [\Mg\Produto\TipoProdutoController::class, 'autocompletar']);
    Route::apiResource('tipo-produto', \Mg\Produto\TipoProdutoController::class)->parameters(['tipo-produto' => 'id']);

    // Hierarquia de Produto — seção/família/grupo/subgrupo (migrado em 31/05/2026)
    Route::get('hierarquia-produto/filhos', [\Mg\Produto\HierarquiaProdutoController::class, 'filhos']);

    Route::get('secao-produto/autocompletar', [\Mg\Produto\SecaoProdutoController::class, 'autocompletar']);
    Route::post('secao-produto/{id}/inativo', [\Mg\Produto\SecaoProdutoController::class, 'inativar']);
    Route::delete('secao-produto/{id}/inativo', [\Mg\Produto\SecaoProdutoController::class, 'ativar']);
    Route::apiResource('secao-produto', \Mg\Produto\SecaoProdutoController::class)->parameters(['secao-produto' => 'id']);

    Route::get('familia-produto/autocompletar', [\Mg\Produto\FamiliaProdutoController::class, 'autocompletar']);
    Route::post('familia-produto/{id}/inativo', [\Mg\Produto\FamiliaProdutoController::class, 'inativar']);
    Route::delete('familia-produto/{id}/inativo', [\Mg\Produto\FamiliaProdutoController::class, 'ativar']);
    Route::apiResource('familia-produto', \Mg\Produto\FamiliaProdutoController::class)->parameters(['familia-produto' => 'id']);

    Route::get('grupo-produto/autocompletar', [\Mg\Produto\GrupoProdutoController::class, 'autocompletar']);
    Route::post('grupo-produto/{id}/inativo', [\Mg\Produto\GrupoProdutoController::class, 'inativar']);
    Route::delete('grupo-produto/{id}/inativo', [\Mg\Produto\GrupoProdutoController::class, 'ativar']);
    Route::apiResource('grupo-produto', \Mg\Produto\GrupoProdutoController::class)->parameters(['grupo-produto' => 'id']);

    Route::get('subgrupo-produto/autocompletar', [\Mg\Produto\SubGrupoProdutoController::class, 'autocompletar']);
    Route::post('subgrupo-produto/{id}/inativo', [\Mg\Produto\SubGrupoProdutoController::class, 'inativar']);
    Route::delete('subgrupo-produto/{id}/inativo', [\Mg\Produto\SubGrupoProdutoController::class, 'ativar']);
    Route::apiResource('subgrupo-produto', \Mg\Produto\SubGrupoProdutoController::class)->parameters(['subgrupo-produto' => 'id']);

    // NCM — árvore pai/filho (migrado em 31/05/2026)
    Route::get('ncm/autocompletar', [\Mg\NaturezaOperacao\NcmController::class, 'autocompletar']);
    Route::get('ncm/arvore', [\Mg\NaturezaOperacao\NcmController::class, 'arvore']);
    Route::post('ncm/{id}/inativo', [\Mg\NaturezaOperacao\NcmController::class, 'inativar']);
    Route::delete('ncm/{id}/inativo', [\Mg\NaturezaOperacao\NcmController::class, 'ativar']);
    Route::apiResource('ncm', \Mg\NaturezaOperacao\NcmController::class)->parameters(['ncm' => 'id']);

    // Usuario + GrupoUsuario (migrado em 23/05/2026)
    Route::get('usuario/todos', [\Mg\Usuario\UsuarioController::class, 'index']);
    Route::get('usuario/{id}/autor', [\Mg\Usuario\UsuarioController::class, 'autor']);
    Route::get('usuario/{id}/grupos', [\Mg\Usuario\UsuarioController::class, 'grupos'])->name('usuario.grupos');
    Route::post('usuario/{id}/grupos', [\Mg\Usuario\UsuarioController::class, 'gruposAdicionar'])->name('usuario.grupos.adicionar');
    Route::delete('usuario/{id}/grupos', [\Mg\Usuario\UsuarioController::class, 'gruposRemover'])->name('usuario.grupos.remover');
    Route::get('usuario/{id}/detalhes', [\Mg\Usuario\UsuarioController::class, 'detalhes'])->name('usuario.detalhes');
    Route::delete('usuario/{id}/inativo', [\Mg\Usuario\UsuarioController::class, 'ativar'])->name('usuario.ativar');
    Route::delete('usuario/{id}', [\Mg\Usuario\UsuarioController::class, 'destroy']);
    Route::put('usuario/senha', [\Mg\Usuario\UsuarioController::class, 'alterarMinhaSenha']);
    Route::put('usuario/{id}/senha', [\Mg\Usuario\UsuarioController::class, 'alterarSenha']);
    Route::put('usuario/{id}/grupos-usuarios', [\Mg\Usuario\UsuarioController::class, 'gruposAdicionarERemover']);
    Route::post('usuario/criar', [\Mg\Usuario\UsuarioController::class, 'novoUsuario']);
    Route::post('usuario/{id}/inativo', [\Mg\Usuario\UsuarioController::class, 'inativar'])->name('usuario.inativar');
    Route::apiResource('usuario', \Mg\Usuario\UsuarioController::class)->parameters(['usuario' => 'id']);

    Route::get('grupo-usuario/todos', [\Mg\Usuario\GrupoUsuarioController::class, 'index']);
    Route::get('grupo-usuario/{id}/autor', [\Mg\Usuario\GrupoUsuarioController::class, 'autor']);
    Route::get('grupo-usuario/{id}', [\Mg\Usuario\GrupoUsuarioController::class, 'detalhes']);
    Route::delete('grupo-usuario/{id}/inativo', [\Mg\Usuario\GrupoUsuarioController::class, 'ativar'])->name('grupo-usuario.ativar');
    Route::post('grupo-usuario/{id}/inativo', [\Mg\Usuario\GrupoUsuarioController::class, 'inativar'])->name('grupo-usuario.inativar');
    Route::apiResource('grupo-usuario', \Mg\Usuario\GrupoUsuarioController::class)->parameters(['grupo-usuario' => 'id']);
    Route::delete('grupo-usuario/{id}', [\Mg\Usuario\GrupoUsuarioController::class, 'destroy']);
    Route::put('grupo-usuario/{id}/alterar', [\Mg\Usuario\GrupoUsuarioController::class, 'update']);

    // Filial (migrado em 23/05/2026)
    Route::get('filial/{id}/autor', [\Mg\Filial\FilialController::class, 'autor']);
    Route::apiResource('filial', \Mg\Filial\FilialController::class)->parameters(['filial' => 'id']);

    // GrupoEconomico (migrado em 23/05/2026)
    Route::get('grupoeconomico/', [\Mg\GrupoEconomico\GrupoEconomicoController::class, 'index']);
    Route::get('grupoeconomico/select', [\Mg\GrupoEconomico\GrupoEconomicoController::class, 'pesquisaGrupoEconomico']);
    Route::post('grupoeconomico/', [\Mg\GrupoEconomico\GrupoEconomicoController::class, 'create']);
    Route::delete('pessoa/{codpessoa}/grupoeconomico/{codgrupoeconomico}/removerdogrupo', [\Mg\GrupoEconomico\GrupoEconomicoController::class, 'deletaPessoadoGrupo']);
    Route::get('grupo-economico/{codgrupoeconomico}/totais-negocios', [\Mg\GrupoEconomico\GrupoEconomicoController::class, 'totaisNegocios']);
    Route::get('grupo-economico/{codgrupoeconomico}/titulos-abertos', [\Mg\GrupoEconomico\GrupoEconomicoController::class, 'titulosAbertos']);
    Route::get('grupo-economico/{codgrupoeconomico}/nfe-terceiro', [\Mg\GrupoEconomico\GrupoEconomicoController::class, 'nfeTerceiro']);
    Route::get('grupo-economico/{codgrupoeconomico}/negocios', [\Mg\GrupoEconomico\GrupoEconomicoController::class, 'negocios']);
    Route::get('grupo-economico/{codgrupoeconomico}/top-produtos', [\Mg\GrupoEconomico\GrupoEconomicoController::class, 'topProdutos']);
    Route::get('grupoeconomico/{codgrupoeconomico}/', [\Mg\GrupoEconomico\GrupoEconomicoController::class, 'show']);
    Route::post('grupoeconomico/{codgrupoeconomico}/inativo', [\Mg\GrupoEconomico\GrupoEconomicoController::class, 'inativar']);
    Route::delete('grupoeconomico/{codgrupoeconomico}/inativo', [\Mg\GrupoEconomico\GrupoEconomicoController::class, 'ativar']);
    Route::put('grupoeconomico/{codgrupoeconomico}/', [\Mg\GrupoEconomico\GrupoEconomicoController::class, 'update']);
    Route::delete('grupoeconomico/{codgrupoeconomico}/', [\Mg\GrupoEconomico\GrupoEconomicoController::class, 'delete']);

    // Pedido (migrado em 23/05/2026 — produtos-para-transferir fica proxiado pro legacy)
    Route::get('pedido/{id}/item', [\Mg\Pedido\PedidoController::class, 'indexItem']);
    Route::get('pedido/{id}/item/{iditem}', [\Mg\Pedido\PedidoController::class, 'showItem']);
    Route::post('pedido/{id}/item', [\Mg\Pedido\PedidoController::class, 'storeItem']);
    Route::put('pedido/{id}/item/{iditem}', [\Mg\Pedido\PedidoController::class, 'updateItem']);
    Route::delete('pedido/{id}/item/{iditem}', [\Mg\Pedido\PedidoController::class, 'destroyItem']);
    Route::apiResource('pedido', \Mg\Pedido\PedidoController::class)->parameters(['pedido' => 'id']);

    // CaixaMercadoria (stub literal do legacy — migrado em 23/05/2026)
    Route::apiResource('caixa-mercadoria', \Mg\CaixaMercadoria\CaixaMercadoriaController::class)->parameters(['caixa-mercadoria' => 'id']);
});

/*
|--------------------------------------------------------------------------
| Bulk-import de rotas legacy (Marco 3+ — sessão 24/05/2026)
|--------------------------------------------------------------------------
| Todas as controllers do MGspa/laravel foram copiadas em massa para
| /opt/www/MGspa/api/app/Mg/ via `cp -rn`. Aqui registramos as rotas com
| FQN literal (mesma sintaxe do legacy). Como o autoload do api novo
| encontra as classes em Mg\\..., os controllers são instanciados aqui.
|
| ORDEM DE PRECEDÊNCIA: o bloco "Rotas migradas" acima define versões
| modernas de alguns endpoints e — por estar registrado ANTES — toma
| precedência sobre as duplicatas deste bloco. Quando uma controller
| for "promovida" no bloco superior, basta deletar a entrada equivalente
| daqui (ou deixar — a primeira sempre vence).
|
| Pontos de atenção pra testes de segunda:
|  - `cors` middleware: removido (CORS é nativo no L13)
|  - PdvNegocioPrazoService::emAberto retorna 0 no PessoaResource
|    (Pdv ainda não foi inteligentemente integrado)
|  - PessoaService::importar foi simplificado pra usar só ReceitaWS
|    (consultaSefazCadastro de NFePHPService pode falhar até ajustar
|    dependências da lib nfephp-org no composer)
|  - Observers (PessoaObserver, ColaboradorObserver, FeriasObserver,
|    DependenteObserver, NotaFiscalObserver) NÃO estão registrados em
|    bootstrap/app.php — quando precisar do comportamento, registrar
|    via Model::observe(...) em AppServiceProvider ou via boot.
|  - Console/Commands foram copiados pra app/Console/Commands/ —
|    L13 slim auto-descobre. Pode falhar autoload se algum command
|    referenciar service não migrado.
*/
Route::middleware(['auth:api'])->prefix('v1')->group(function () {

    // Permissões
    Route::apiResource('permissao', '\Mg\Permissao\PermissaoController');

    // Stone Connect (consumido pelo Quasar — telas stone-connect)
    Route::group(['prefix' => 'stone-connect'], function () {
        Route::group(['prefix' => 'filial'], function () {
            Route::post('', '\Mg\Stone\Connect\FilialController@store');
            Route::get('', '\Mg\Stone\Connect\FilialController@index');
            Route::get('{codstonefilial}', '\Mg\Stone\Connect\FilialController@show');
            Route::get('{codstonefilial}/webhook', '\Mg\Stone\Connect\FilialController@showWebhook');
        });
        Route::group(['prefix' => 'pos'], function () {
            Route::post('', '\Mg\Stone\Connect\PosController@store');
            Route::delete('{codstonepos}', '\Mg\Stone\Connect\PosController@destroy');
        });
    });

    // Pessoa
    Route::post('pessoa/importar', '\Mg\Pessoa\PessoaController@importar');
    Route::get('pessoa/verifica-ie-sefaz', '\Mg\Pessoa\PessoaController@verificaIeSefaz');
    Route::get('pessoa/{codpessoa}/anexo/{status}/{arquivo}', '\Mg\Pessoa\PessoaAnexoController@get');

    // PDV (público)
    Route::group(['prefix' => 'pdv'], function () {
        Route::put('dispositivo', '\Mg\Pdv\PdvController@putDispositivo');
        Route::get('negocio/{codnegocio}/romaneio', '\Mg\Pdv\PdvController@romaneio')
            ->name('pdv.negocio.romaneio')
            ->withoutMiddleware('auth:api')->middleware('auth_or_signed');
        Route::get('negocio/{codnegocio}/vale', '\Mg\Pdv\PdvController@vale')
            ->name('pdv.negocio.vale')
            ->withoutMiddleware('auth:api')->middleware('auth_or_signed');
        Route::get('negocio/{codnegocio}/comanda', '\Mg\Pdv\PdvController@comanda');
        Route::get('negocio/{codnegocio}/anexo/{pasta}/{anexo}', '\Mg\Pdv\PdvAnexoController@show');
    });

    // Produto
    Route::post('produto/unifica-variacoes', '\Mg\Produto\ProdutoController@unificaVariacoes');
    Route::post('produto/unifica-barras', '\Mg\Produto\ProdutoController@unificaBarras');
    Route::post('produto/embalagem-para-unidade', '\Mg\Produto\ProdutoController@embalagemParaUnidade');

    // Etiqueta
    Route::get('etiqueta/arquivo/{arquivo}', '\Mg\Etiqueta\EtiquetaController@arquivo')
        ->name('etiqueta.arquivo')
        ->withoutMiddleware('auth:api')->middleware('auth_or_signed');

    // NFeTerceiro
    // POSTs ficam em auth:api (MGsis manda Bearer via $.ajaxSetup)
    // GETs de XML/DANFE/guia-st são abertos via <a target=_blank>/iframe — usam
    // auth_or_cookie pra cair no cookie access_token quando o browser navega top-level.
    Route::post('nfe-terceiro/{codnfeterceiro}/manifestacao', '\Mg\NfeTerceiro\NfeTerceiroController@manifestacao');
    Route::post('nfe-terceiro/{codnfeterceiro}/download', '\Mg\NfeTerceiro\NfeTerceiroController@download');
    Route::get('nfe-terceiro/{codnfeterceiro}/xml', '\Mg\NfeTerceiro\NfeTerceiroController@xml')
        ->withoutMiddleware('auth:api')->middleware('auth_or_cookie');
    Route::get('nfe-terceiro/{codnfeterceiro}/danfe', '\Mg\NfeTerceiro\NfeTerceiroController@danfe')
        ->withoutMiddleware('auth:api')->middleware('auth_or_cookie');
    Route::get('nfe-terceiro/{codnfeterceiro}/guia-st/{codtitulonfeterceiro}/pdf', '\Mg\NfeTerceiro\NfeTerceiroController@guiaStPdf')
        ->withoutMiddleware('auth:api')->middleware('auth_or_cookie');

    // NOTA FISCAL
    Route::prefix('nota-fiscal')->group(function () {
        Route::get('lacunas', '\Mg\NotaFiscal\NotaFiscalController@detectarLacunas');
        Route::post('criar-para-inutilizar', '\Mg\NotaFiscal\NotaFiscalController@criarParaInutilizar');
        Route::get('relatorio', '\Mg\NotaFiscal\NotaFiscalController@relatorio');
        // Rotas específicas de Transferencia/Dashboard ANTES do apiResource
        // (caso contrário `nota-fiscal/notas-por-emitir` casa com `nota-fiscal/{codnotafiscal}`)
        Route::get('notas-por-emitir', '\Mg\NotaFiscal\NotaFiscalTransferenciaController@NotasPorEmitir');
        Route::get('notas-nao-autorizadas', '\Mg\NotaFiscal\NotaFiscalTransferenciaController@NotasNaoAutorizadas');
        Route::get('notas-emitidas', '\Mg\NotaFiscal\NotaFiscalTransferenciaController@NotasEmitidas');
        Route::get('notas-lancadas', '\Mg\NotaFiscal\NotaFiscalTransferenciaController@NotasLancadas');
        Route::get('dashboard-transferencia', '\Mg\NotaFiscal\NotaFiscalTransferenciaController@index');
        Route::get('gera-transferencias/{codfilial}', '\Mg\NotaFiscal\NotaFiscalTransferenciaController@GerarNovaTransferencia');
        Route::apiResource('/', '\Mg\NotaFiscal\NotaFiscalController')->parameters(['' => 'codnotafiscal']);
        Route::put('{codnotafiscal}/status', '\Mg\NotaFiscal\NotaFiscalController@updateStatus');
        Route::post('{codnotafiscal}/duplicar', '\Mg\NotaFiscal\NotaFiscalController@duplicar');
        Route::post('{codnotafiscal}/devolucao', '\Mg\NotaFiscal\NotaFiscalController@devolucao');
        Route::get('{codnotafiscal}/unificar', '\Mg\NotaFiscal\NotaFiscalController@listarParaUnificar');
        Route::post('{codnotafiscal}/unificar', '\Mg\NotaFiscal\NotaFiscalController@unificar');
        Route::post('{codnotafiscal}/unificar-itens', '\Mg\NotaFiscal\NotaFiscalController@unificarItens');
        Route::post('{codnotafiscal}/criar', '\Mg\NotaFiscal\NotaFiscalController@criar');
        Route::post('{codnotafiscal}/enviar-sincrono', '\Mg\NotaFiscal\NotaFiscalController@enviarSincrono');
        Route::post('{codnotafiscal}/consultar', '\Mg\NotaFiscal\NotaFiscalController@consultar');
        Route::post('{codnotafiscal}/cancelar', '\Mg\NotaFiscal\NotaFiscalController@cancelar');
        Route::post('{codnotafiscal}/inutilizar', '\Mg\NotaFiscal\NotaFiscalController@inutilizar');
        Route::post('{codnotafiscal}/mail', '\Mg\NotaFiscal\NotaFiscalController@mail');
        Route::get('{codnotafiscal}/danfe', '\Mg\NotaFiscal\NotaFiscalController@danfe')
            ->name('nota-fiscal.danfe')
            ->withoutMiddleware('auth:api')->middleware('auth_or_signed');
        Route::get('{codnotafiscal}/xml', '\Mg\NotaFiscal\NotaFiscalController@xml');
        Route::post('{codnotafiscal}/imprimir', '\Mg\NotaFiscal\NotaFiscalController@imprimir');
        Route::post('{codnotafiscal}/incorporar-valores', '\Mg\NotaFiscal\NotaFiscalController@incorporarValores');
        Route::post('{codnotafiscal}/recalcular-tributacao', '\Mg\NotaFiscal\NotaFiscalController@recalcularTributacao');
        Route::post('{codnotafiscal}/carta-correcao', '\Mg\NotaFiscal\NotaFiscalController@cartaCorrecao');
        Route::get('{codnotafiscal}/carta-correcao/pdf', '\Mg\NotaFiscal\NotaFiscalCartaCorrecaoController@pdf');
        Route::get('{codnotafiscal}/espelho', '\Mg\NotaFiscal\NotaFiscalController@espelho');
        Route::apiResource('{codnotafiscal}/item', '\Mg\NotaFiscal\NotaFiscalProdutoBarraController')
            ->parameters(['item' => 'codnotafiscalprodutobarra']);
        Route::apiResource('{codnotafiscal}/pagamento', '\Mg\NotaFiscal\NotaFiscalPagamentoController')
            ->parameters(['pagamento' => 'codnotafiscalpagamento']);
        Route::apiResource('{codnotafiscal}/duplicata', '\Mg\NotaFiscal\NotaFiscalDuplicatasController')
            ->parameters(['duplicata' => 'codnotafiscalduplicatas']);
        Route::apiResource('{codnotafiscal}/referenciada', '\Mg\NotaFiscal\NotaFiscalReferenciadaController')
            ->parameters(['referenciada' => 'codnotafiscalreferenciada']);
    });

    // Negocio
    Route::get('negocio/{codnegocio}/comanda', '\Mg\Negocio\NegocioController@comanda')
        ->name('negocio.comanda')
        ->withoutMiddleware('auth:api')->middleware('auth_or_signed');
    Route::post('negocio/{codnegocio}/comanda/imprimir', '\Mg\Negocio\NegocioController@comandaImprimir');
    Route::post('negocio/{codnegocio}/unificar/{codnegociocomanda}', '\Mg\Negocio\NegocioController@unificar');
    Route::get('negocio/{codnegocio}/boleto-bb/pdf', '\Mg\Negocio\NegocioController@BoletoBbPdf')
        ->withoutMiddleware('auth:api')->middleware('auth_or_cookie');
    Route::post('negocio/{codnegocio}/boleto-bb/registrar', '\Mg\Negocio\NegocioController@BoletoBbRegistrar');
    Route::post('negocio/{codnegocio}/identificar-vendedor/{codpessoavendedor}', '\Mg\Negocio\NegocioController@identificarVendedor');

    // Boleto BB PDF aberto em iframe pelo MGsis — usa auth_or_cookie
    Route::get('titulo/{codtitulo}/boleto-bb/{codtituloboleto}/pdf', '\Mg\Titulo\BoletoBb\BoletoBbController@pdf')
        ->withoutMiddleware('auth:api')->middleware('auth_or_cookie');

    // PagarMe
    Route::post('pagar-me/webhook/', '\Mg\PagarMe\PagarMeController@webhook');
    Route::post('pagar-me/pedido/', '\Mg\PagarMe\PagarMeController@criarPedido');
    Route::post('pagar-me/pedido/{codpagarmepedido}/consultar', '\Mg\PagarMe\PagarMeController@consultarPedido');
    Route::delete('pagar-me/pedido/{codpagarmepdido}', '\Mg\PagarMe\PagarMeController@cancelarPedido');

    // Pix
    // Landing page pública de pagamento PIX (cliente abre o link p/ pagar).
    // TODO segurança: codpixcob é PK sequencial → enumerável. Migrar p/ auth_or_signed
    // (signed URL) como a rota pix.cob.pdf logo abaixo, antes de considerar definitivo.
    Route::get('pix/cob/{codpixcob}/detalhes', '\Mg\Pix\PixController@detalhes')
        ->withoutMiddleware('auth:api');
    Route::post('pix/cob/criar-negocio/{codnegocio}', '\Mg\Pix\PixController@criarPixCobNegocio');
    Route::post('pix/cob/{codpixcob}/transmitir', '\Mg\Pix\PixController@transmitirPixCob');
    Route::post('pix/cob/{codpixcob}/consultar', '\Mg\Pix\PixController@consultarPixCob');
    Route::get('pix/cob/{codpixcob}/brcode', '\Mg\Pix\PixController@brCodePixCob');
    Route::get('pix/cob/{codpixcob}', '\Mg\Pix\PixController@show');
    Route::post('pix/cob/{codpixcob}/imprimir-qr-code', '\Mg\Pix\PixController@imprimirQrCode');
    Route::get('pix/cob/{codpixcob}/pdf', '\Mg\Pix\PixController@pdf')
        ->name('pix.cob.pdf')
        ->withoutMiddleware('auth:api')->middleware('auth_or_signed');
    Route::match(['POST', 'PUT', 'PATCH'], 'pix/webhook', '\Mg\Pix\PixController@webhook');

    // Pessoa autocomplete + comanda
    Route::get('pessoa/autocomplete', '\Mg\Pessoa\PessoaController@autocomplete');
    Route::get('pessoa/{codpessoa}/comanda-vendedor', '\Mg\Pessoa\PessoaController@comandaVendedor')
        ->name('pessoa.comanda-vendedor')
        ->withoutMiddleware('auth:api')->middleware('auth_or_signed');
    Route::post('pessoa/{codpessoa}/comanda-vendedor/imprimir', '\Mg\Pessoa\PessoaController@comandaVendedorImprimir');

    // NFePHP
    Route::get('nfe-php/{id}/criar', '\Mg\NFePHP\NFePHPController@criar');
    Route::get('nfe-php/{id}/enviar', '\Mg\NFePHP\NFePHPController@enviar');
    Route::get('nfe-php/{id}/enviar-sincrono', '\Mg\NFePHP\NFePHPController@enviarSincrono');
    Route::get('nfe-php/{id}/consultar-recibo', '\Mg\NFePHP\NFePHPController@consultarRecibo');
    Route::get('nfe-php/{id}/consultar', '\Mg\NFePHP\NFePHPController@consultar');
    Route::get('nfe-php/{id}/imprimir', '\Mg\NFePHP\NFePHPController@imprimir');
    Route::get('nfe-php/{id}/cancelar', '\Mg\NFePHP\NFePHPController@cancelar');
    Route::get('nfe-php/{id}/inutilizar', '\Mg\NFePHP\NFePHPController@inutilizar');
    Route::get('nfe-php/{id}/carta-correcao', '\Mg\NFePHP\NFePHPController@cartaCorrecao');
    Route::get('nfe-php/{id}/mail', '\Mg\NFePHP\NFePHPController@mail');
    Route::get('nfe-php/{id}/mail-cancelamento', '\Mg\NFePHP\NFePHPController@mailCancelamento');
    Route::get('nfe-php/{id}/resolver', '\Mg\NFePHP\NFePHPController@resolver');
    Route::get('nfe-php/pendentes', '\Mg\NFePHP\NFePHPController@pendentes');
    Route::get('nfe-php/resolver-pendentes', '\Mg\NFePHP\NFePHPController@resolverPendentes');
    Route::post('nfe-php/dist-dfe/{codfilial}/{nsu?}', '\Mg\NFePHP\NFePHPController@distDfe');
    Route::get('nfe-php/{id}/sefaz-status', '\Mg\NFePHP\NFePHPController@sefazStatus');
    Route::get('nfe-php/{id}/csc-consulta', '\Mg\NFePHP\NFePHPController@cscConsulta');
    Route::get('dfe/distribuicao', '\Mg\Dfe\DfeController@distribuicao');
    Route::get('dfe/distribuicao/{coddistribuicaodfe}/xml', '\Mg\Dfe\DfeController@xml');
    Route::get('dfe/distribuicao/{coddistribuicaodfe}/processar', '\Mg\Dfe\DfeController@processar');
    Route::get('dfe/distribuicao/{coddistribuicaodfe}/consultar-sefaz', '\Mg\Dfe\DfeController@consultarSefaz');
    Route::get('dfe/filiais-habilitadas', '\Mg\Dfe\DfeController@filiaisHabilitadas');

    // Titulo Agrupamento mail + Titulo Relatorio
    Route::post('titulo/agrupamento/{codtituloagrupamento}/mail', '\Mg\Titulo\TituloAgrupamentoController@mail');
    Route::get('titulo/relatorio', '\Mg\Titulo\TituloRelatorioController@relatorio');
    Route::get('titulo/relatorio-pdf', '\Mg\Titulo\TituloRelatorioController@relatorioPdf');

    // ============================================================
    // WOO
    // ============================================================
    Route::group(['prefix' => 'woo'], function () {
        Route::post('produto/{codproduto}/exportar', '\Mg\Woo\WooProdutoController@exportar');
        Route::post('produto/', '\Mg\Woo\WooProdutoController@store');
        Route::put('produto/{codwooproduto}', '\Mg\Woo\WooProdutoController@update');
        Route::delete('produto/{id}/inativo', '\Mg\Woo\WooProdutoController@ativar');
        Route::post('produto/{id}/inativo', '\Mg\Woo\WooProdutoController@inativar');
        Route::get('pedido', '\Mg\Woo\WooPedidoController@index');
        Route::get('pedido/painel', '\Mg\Woo\WooPedidoController@painel');
        Route::post('pedido/buscar-novos', '\Mg\Woo\WooPedidoController@buscarNovos');
        Route::post('pedido/buscar-por-alteracao', '\Mg\Woo\WooPedidoController@buscarPorAlteracao');
        Route::post('pedido/{id}/reprocessar', '\Mg\Woo\WooPedidoController@reprocessar');
        Route::put('pedido/{id}/status', '\Mg\Woo\WooPedidoController@alteraStatus');
    });

    // ============================================================
    // PDV
    // ============================================================
    Route::group(['prefix' => 'pdv'], function () {
        Route::get('produto-count', '\Mg\Pdv\PdvController@produtoCount');
        Route::get('produto', '\Mg\Pdv\PdvController@produto');
        Route::get('produto/{barras}', '\Mg\Pdv\PdvController@produtoBarras');
        Route::get('produto/{barras}/detalhe', '\Mg\Pdv\PdvController@produtoDetalhe');
        Route::get('pessoa-count', '\Mg\Pdv\PdvController@pessoaCount');
        Route::get('pessoa/cnpj/{cnpj}', '\Mg\Pdv\PdvController@pessoaPeloCnpj');
        Route::get('pessoa', '\Mg\Pdv\PdvController@pessoa');
        Route::post('pessoa', '\Mg\Pdv\PdvController@postPessoa');
        Route::get('natureza-operacao', '\Mg\Pdv\PdvController@naturezaOperacao');
        Route::get('estoque-local', '\Mg\Pdv\PdvController@estoqueLocal');
        Route::get('forma-pagamento', '\Mg\Pdv\PdvController@formaPagamento');
        Route::get('prancheta', '\Mg\Pdv\PdvController@getPrancheta');
        Route::put('prancheta', '\Mg\Pdv\PdvController@putPrancheta');
        Route::get('impressora', '\Mg\Pdv\PdvController@impressora');
        Route::put('negocio', '\Mg\Pdv\PdvController@putNegocio');
        Route::get('negocio', '\Mg\Pdv\PdvController@getNegocios');
        Route::get('negocio/conferencia', '\Mg\Pdv\PdvController@conferencia');
        Route::get('negocio/{codnegocio}', '\Mg\Pdv\PdvController@getNegocio');
        Route::delete('negocio/{codnegocio}', '\Mg\Pdv\PdvController@deleteNegocio');
        Route::post('negocio/{codnegocio}/apropriar', '\Mg\Pdv\PdvController@apropriar');
        Route::post('negocio/{codnegocio}/fechar', '\Mg\Pdv\PdvController@fecharNegocio');
        Route::post('negocio/{codnegocio}/romaneio/{impressora}', '\Mg\Pdv\PdvController@imprimirRomaneio');
        Route::post('negocio/{codnegocio}/vale/{impressora}', '\Mg\Pdv\PdvController@imprimirVale');
        Route::post('negocio/{codnegocio}/comanda/{impressora}', '\Mg\Pdv\PdvController@imprimirComanda');
        Route::post('negocio/{codnegocio}/unificar/{codnegociocomanda}', '\Mg\Pdv\PdvController@unificarComanda');
        Route::post('negocio/{codnegocio}/devolucao', '\Mg\Pdv\PdvController@devolucao');
        Route::post('negocio/{codnegocio}/anexo', '\Mg\Pdv\PdvAnexoController@upload');
        Route::get('negocio/{codnegocio}/anexo', '\Mg\Pdv\PdvAnexoController@listagem');
        Route::delete('negocio/{codnegocio}/anexo/{pasta}/{anexo}', '\Mg\Pdv\PdvAnexoController@excluir');
        Route::post('negocio/anexo/sugerir', '\Mg\Pdv\PdvAnexoController@sugerir');
        Route::post('negocio/anexo/procurar', '\Mg\Pdv\PdvAnexoController@procurar');
        Route::get('negocio/anexo/faltando/{ano}/{mes}', '\Mg\Pdv\PdvAnexoController@faltando');
        Route::post('negocio/{codnegocio}/ignorar-confissao', '\Mg\Pdv\PdvAnexoController@ignorarConfissao');
        Route::get('orcamento', '\Mg\Pdv\PdvController@getOrcamentos');
        Route::post('pix/cob', '\Mg\Pdv\PdvController@criarPixCob');
        Route::post('pagar-me/pedido', '\Mg\Pdv\PdvController@criarPagarMePedido');
        Route::post('pagar-me/pedido/{codpagarmepedido}/consultar', '\Mg\Pdv\PdvController@consultarPagarMePedido');
        Route::patch('pagar-me/pedido/pendentes', '\Mg\Pdv\PdvController@importarPagarMePedidosPendentes');
        Route::get('pagar-me/pedido/pendentes', '\Mg\Pdv\PdvController@pagarMePedidosPendentes');
        Route::delete('pagar-me/pedido/{codpagarmepedido}', '\Mg\Pdv\PdvController@cancelarPagarMePedido');
        Route::post('negocio/{codnegocio}/nota-fiscal', '\Mg\Pdv\PdvController@notaFiscal');
        Route::get('dispositivo', '\Mg\Pdv\PdvController@getDispositivo');
        Route::post('dispositivo/{codpdv}/autorizado', '\Mg\Pdv\PdvController@autorizar');
        Route::delete('dispositivo/{codpdv}/autorizado', '\Mg\Pdv\PdvController@desautorizar');
        Route::post('dispositivo/{codpdv}/inativo', '\Mg\Pdv\PdvController@inativar');
        Route::delete('dispositivo/{codpdv}/inativo', '\Mg\Pdv\PdvController@reativar');
        Route::put('dispositivo/{codpdv}/editar', '\Mg\Pdv\PdvController@update');
        Route::get('vale/{codtitulo}', '\Mg\Pdv\PdvController@buscarVale');
        Route::get('liquidacao', '\Mg\Pdv\PdvLiquidacaoController@getLiquidacoes');
        // Saurus
        Route::post('saurus/registrar-pos', '\Mg\Pdv\PdvController@registrarPosSaurus');
        Route::post('saurus/verificar-leitura', '\Mg\Pdv\PdvController@verificarLeituraSaurus');
        Route::post('saurus/pedido', '\Mg\Pdv\PdvController@criarSaurusPedido');
        Route::post('saurus/pedido/{codsauruspedido}/consultar', '\Mg\Pdv\PdvController@consultarSaurusPedido');
        Route::delete('saurus/pedido/{codsauruspedido}', '\Mg\Pdv\PdvController@cancelarSaurusPedido');
        Route::get('saurus/pdvs', '\Mg\Pdv\PdvController@listaPdvsSaurus');
        Route::post('saurus/pdv/{codsauruspdv}', '\Mg\Pdv\PdvController@editarPdvSaurus');
        Route::get('saurus/pdv/{codsauruspdv}/inativar', '\Mg\Pdv\PdvController@inativarPdvSaurus');
        Route::get('saurus/pdv/{codsauruspdv}/ativar', '\Mg\Pdv\PdvController@ativarPdvSaurus');
        Route::get('saurus/pedido/{codsauruspedido}/reenviar', '\Mg\Pdv\PdvController@reenviarSaurusPedido');
    });

    // METAS
    Route::get('meta/', '\Mg\Meta\MetaController@index');
    Route::post('meta/', '\Mg\Meta\MetaController@store');
    Route::get('meta/{codmeta}', '\Mg\Meta\MetaController@show');
    Route::put('meta/{codmeta}', '\Mg\Meta\MetaController@update');
    Route::delete('meta/{codmeta}', '\Mg\Meta\MetaController@destroy');
    Route::post('meta/{codmeta}/bloquear', '\Mg\Meta\MetaController@bloquear');
    Route::post('meta/{codmeta}/desbloquear', '\Mg\Meta\MetaController@desbloquear');
    Route::post('meta/{codmeta}/reprocessar', '\Mg\Meta\MetaController@reprocessar');
    Route::post('meta/{codmeta}/finalizar', '\Mg\Meta\MetaController@finalizar');
    Route::post('meta/{codmeta}/inativo', '\Mg\Meta\MetaController@inativar');
    Route::delete('meta/{codmeta}/inativo', '\Mg\Meta\MetaController@ativar');
    Route::post('meta/{codmeta}/unidade', '\Mg\Meta\MetaController@storeUnidade');
    Route::put('meta/{codmeta}/unidade/{codunidadenegocio}', '\Mg\Meta\MetaController@updateUnidade');
    Route::delete('meta/{codmeta}/unidade/{codunidadenegocio}', '\Mg\Meta\MetaController@destroyUnidade');
    Route::post('meta/{codmeta}/unidade/{codunidadenegocio}/pessoa', '\Mg\Meta\MetaController@storePessoa');
    Route::put('meta/{codmeta}/pessoa/{id}', '\Mg\Meta\MetaController@updatePessoa');
    Route::delete('meta/{codmeta}/pessoa/{id}', '\Mg\Meta\MetaController@destroyPessoa');
    Route::post('meta/{codmeta}/pessoa/{idPessoa}/fixo', '\Mg\Meta\MetaController@storeFixo');
    Route::put('meta/{codmeta}/fixo/{id}', '\Mg\Meta\MetaController@updateFixo');
    Route::delete('meta/{codmeta}/fixo/{id}', '\Mg\Meta\MetaController@destroyFixo');
    Route::get('meta/{codmeta}/dashboard', '\Mg\Meta\MetaDashboardController@dashboard');
    Route::get('meta/{codmeta}/dashboard/{codpessoa}', '\Mg\Meta\MetaDashboardController@dashboardPessoa');
    Route::get('meta/{codmeta}/dashboard/{codpessoa}/eventos', '\Mg\Meta\MetaDashboardController@dashboardPessoaEventos');

    // Pessoa core
    Route::get('pessoa/aniversario-colaboradores', '\Mg\Pessoa\PessoaController@aniversariosColaboradores');
    Route::get('pessoa/', '\Mg\Pessoa\PessoaController@index');
    Route::get('pessoa/aniversarios/', '\Mg\Pessoa\PessoaController@aniversarios');
    Route::post('pessoa/', '\Mg\Pessoa\PessoaController@create');
    Route::get('pessoa/formadepagamento', '\Mg\Pessoa\PessoaController@formapagamento');
    Route::get('pessoa/{codpessoa}', '\Mg\Pessoa\PessoaController@show');
    Route::put('pessoa/{codpessoa}', '\Mg\Pessoa\PessoaController@update');
    Route::delete('pessoa/{codpessoa}', '\Mg\Pessoa\PessoaController@delete');
    Route::post('pessoa/{codpessoa}/inativo', '\Mg\Pessoa\PessoaController@inativar');
    Route::delete('pessoa/{codpessoa}/inativo', '\Mg\Pessoa\PessoaController@ativar');
    Route::post('pessoa/{codpessoa}/transferir-mercos-id', '\Mg\Pessoa\PessoaController@transferirMercosId');
    Route::post('pessoa/{codpessoa}/anexo', '\Mg\Pessoa\PessoaAnexoController@upload');
    Route::get('pessoa/{codpessoa}/anexo', '\Mg\Pessoa\PessoaAnexoController@index');
    Route::put('pessoa/{codpessoa}/anexo/ativo/{nome}', '\Mg\Pessoa\PessoaAnexoController@update');
    Route::delete('pessoa/{codpessoa}/anexo/ativo/{nome}', '\Mg\Pessoa\PessoaAnexoController@inativar');
    Route::delete('pessoa/{codpessoa}/anexo/inativo/{nome}', '\Mg\Pessoa\PessoaAnexoController@delete');
    Route::patch('pessoa/{codpessoa}/anexo/inativo/{nome}', '\Mg\Pessoa\PessoaAnexoController@ativar');

    // Pessoa Telefone / Email / Endereco
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

    Route::get('pessoa/{codpessoa}/email/', '\Mg\Pessoa\PessoaEmailController@index');
    Route::post('pessoa/{codpessoa}/email/', '\Mg\Pessoa\PessoaEmailController@create');
    Route::get('pessoa/{codpessoa}/email/{codpessoaemail}/', '\Mg\Pessoa\PessoaEmailController@show');
    Route::put('pessoa/{codpessoa}/email/{codpessoaemail}/', '\Mg\Pessoa\PessoaEmailController@update');
    Route::delete('pessoa/{codpessoa}/email/{codpessoaemail}/', '\Mg\Pessoa\PessoaEmailController@delete');
    Route::post('pessoa/{codpessoa}/email/{codpessoaemail}/cima', '\Mg\Pessoa\PessoaEmailController@cima');
    Route::post('pessoa/{codpessoa}/email/{codpessoaemail}/baixo', '\Mg\Pessoa\PessoaEmailController@baixo');
    Route::post('pessoa/{codpessoa}/email/{codpessoaemail}/inativo', '\Mg\Pessoa\PessoaEmailController@inativar');
    Route::delete('pessoa/{codpessoa}/email/{codpessoaemail}/inativo', '\Mg\Pessoa\PessoaEmailController@ativar');
    Route::get('pessoa/{codpessoa}/email/{codpessoaemail}/verificar', '\Mg\Pessoa\PessoaEmailController@verificarEmail');
    Route::post('pessoa/{codpessoa}/email/{codpessoaemail}/verificar', '\Mg\Pessoa\PessoaEmailController@confirmaEmail');

    Route::get('pessoa/{codpessoa}/endereco/', '\Mg\Pessoa\PessoaEnderecoController@index');
    Route::post('pessoa/{codpessoa}/endereco/', '\Mg\Pessoa\PessoaEnderecoController@create');
    Route::get('pessoa/{codpessoa}/endereco/{codpessoaendereco}/', '\Mg\Pessoa\PessoaEnderecoController@show');
    Route::put('pessoa/{codpessoa}/endereco/{codpessoaendereco}/', '\Mg\Pessoa\PessoaEnderecoController@update');
    Route::delete('pessoa/{codpessoa}/endereco/{codpessoaendereco}/', '\Mg\Pessoa\PessoaEnderecoController@delete');
    Route::post('pessoa/{codpessoa}/endereco/{codpessoaendereco}/cima', '\Mg\Pessoa\PessoaEnderecoController@cima');
    Route::post('pessoa/{codpessoa}/endereco/{codpessoaendereco}/baixo', '\Mg\Pessoa\PessoaEnderecoController@baixo');
    Route::post('pessoa/{codpessoa}/endereco/{codpessoaendereco}/inativo', '\Mg\Pessoa\PessoaEnderecoController@inativar');
    Route::delete('pessoa/{codpessoa}/endereco/{codpessoaendereco}/inativo', '\Mg\Pessoa\PessoaEnderecoController@ativar');

    // Certidão (Pessoa Certidão sem prefix pessoa)
    Route::post('certidao/', '\Mg\Pessoa\PessoaCertidaoController@create');
    Route::get('certidao/{codpessoacertidao}/', '\Mg\Pessoa\PessoaCertidaoController@show');
    Route::put('certidao/{codpessoacertidao}/', '\Mg\Pessoa\PessoaCertidaoController@update');
    Route::delete('certidao/{codpessoacertidao}/', '\Mg\Pessoa\PessoaCertidaoController@delete');
    Route::get('select/certidao/emissor', '\Mg\Pessoa\PessoaCertidaoController@selectCertidaoEmissor');
    Route::get('select/certidao/tipo', '\Mg\Pessoa\PessoaCertidaoController@selectCertidaoTipo');
    Route::post('certidao/{codpessoacertidao}/inativo', '\Mg\Pessoa\PessoaCertidaoController@inativar');
    Route::delete('certidao/{codpessoacertidao}/inativo', '\Mg\Pessoa\PessoaCertidaoController@ativar');

    // Colaborador
    Route::post('colaborador/', '\Mg\Colaborador\ColaboradorController@create');
    Route::get('pessoa/{codpessoa}/colaborador/', '\Mg\Colaborador\ColaboradorController@show');
    Route::put('colaborador/{codcolaborador}/', '\Mg\Colaborador\ColaboradorController@update');
    Route::delete('colaborador/{codcolaborador}/', '\Mg\Colaborador\ColaboradorController@delete');
    Route::get('colaborador/{codcolaborador}/ficha', '\Mg\Colaborador\ColaboradorFichaController@ficha');
    Route::post('colaborador/{codcolaborador}/ficha', '\Mg\Colaborador\ColaboradorFichaController@uploadFicha');

    // Colaborador Cargo
    Route::post('colaborador/cargo/', '\Mg\Colaborador\ColaboradorCargoController@create');
    Route::get('colaborador/{codcolaborador}/cargo/{codcolaboradorcargo}/', '\Mg\Colaborador\ColaboradorCargoController@show');
    Route::put('colaborador/{codcolaborador}/cargo/{codcolaboradorcargo}/', '\Mg\Colaborador\ColaboradorCargoController@update');
    Route::delete('colaborador/cargo/{codcolaboradorcargo}/', '\Mg\Colaborador\ColaboradorCargoController@delete');
    Route::get('comissao-caixas', '\Mg\Colaborador\ColaboradorCargoController@comissaoCaixas');

    // Ferias
    Route::post('colaborador/{codcolaborador}/ferias/', '\Mg\Colaborador\FeriasController@create');
    Route::get('colaborador/{codcolaborador}/ferias/{codferias}/', '\Mg\Colaborador\FeriasController@show');
    Route::put('colaborador/{codcolaborador}/ferias/{codferias}/', '\Mg\Colaborador\FeriasController@update');
    Route::put('ferias/atualiza-todas-ferias/', '\Mg\Colaborador\FeriasController@AtualizaTodasFerias');
    Route::delete('colaborador/{codcolaborador}/ferias/{codferias}/', '\Mg\Colaborador\FeriasController@delete');
    Route::get('programacao-ferias/{ano}', '\Mg\Colaborador\FeriasController@programacaoFerias');

    // Pessoa Conta
    Route::get('pessoa/{codpessoa}/conta/', '\Mg\Pessoa\PessoaContaController@index');
    Route::post('pessoa/{codpessoa}/conta/', '\Mg\Pessoa\PessoaContaController@create');
    Route::get('pessoa/{codpessoa}/conta/{codpessoaconta}/', '\Mg\Pessoa\PessoaContaController@show');
    Route::put('pessoa/{codpessoa}/conta/{codpessoaconta}/', '\Mg\Pessoa\PessoaContaController@update');
    Route::delete('pessoa/{codpessoa}/conta/{codpessoaconta}/', '\Mg\Pessoa\PessoaContaController@delete');
    Route::get('pessoa/conta/banco/select', '\Mg\Pessoa\PessoaContaController@selectBanco');
    Route::post('pessoa/conta/{codpessoaconta}/inativo', '\Mg\Pessoa\PessoaContaController@inativar');
    Route::delete('pessoa/conta/{codpessoaconta}/inativo', '\Mg\Pessoa\PessoaContaController@ativar');

    // CertidaoTipo (legacy quebrado — porta literal)
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

    // Cobranca Histórico
    Route::get('pessoa/{codpessoa}/cobrancahistorico/', '\Mg\Cobranca\CobrancaHistoricoController@index');
    Route::post('pessoa/{codpessoa}/cobrancahistorico/', '\Mg\Cobranca\CobrancaHistoricoController@create');
    Route::get('pessoa/{codpessoa}/cobrancahistorico/{codcobrancahistorico}/', '\Mg\Cobranca\CobrancaHistoricoController@show');
    Route::put('pessoa/{codpessoa}/cobrancahistorico/{codcobrancahistorico}/', '\Mg\Cobranca\CobrancaHistoricoController@update');
    Route::delete('pessoa/{codpessoa}/cobrancahistorico/{codcobrancahistorico}/', '\Mg\Cobranca\CobrancaHistoricoController@delete');

    // Dependente
    Route::post('dependente/', '\Mg\Pessoa\DependenteController@create');
    Route::put('dependente/{coddependente}/', '\Mg\Pessoa\DependenteController@update');
    Route::delete('dependente/{coddependente}/', '\Mg\Pessoa\DependenteController@delete');
    Route::post('dependente/{coddependente}/inativo', '\Mg\Pessoa\DependenteController@inativar');
    Route::delete('dependente/{coddependente}/inativo', '\Mg\Pessoa\DependenteController@ativar');

    // Produto (CRUD migrado em 31/05/2026)
    Route::group(['prefix' => 'produto'], function () {
        Route::get('', '\Mg\Produto\ProdutoController@index');
        Route::post('', '\Mg\Produto\ProdutoController@store');
        Route::get('{codproduto}', '\Mg\Produto\ProdutoController@show');
        Route::put('{codproduto}', '\Mg\Produto\ProdutoController@update');
        Route::post('{codproduto}/inativo', '\Mg\Produto\ProdutoController@inativar');
        Route::delete('{codproduto}/inativo', '\Mg\Produto\ProdutoController@ativar');

        // Variações
        Route::post('{codproduto}/variacao', '\Mg\Produto\ProdutoVariacaoController@store');
        Route::put('{codproduto}/variacao/{codprodutovariacao}', '\Mg\Produto\ProdutoVariacaoController@update');
        Route::delete('{codproduto}/variacao/{codprodutovariacao}', '\Mg\Produto\ProdutoVariacaoController@destroy');
        Route::post('{codproduto}/variacao/{codprodutovariacao}/descontinuar', '\Mg\Produto\ProdutoVariacaoController@descontinuar');
        Route::delete('{codproduto}/variacao/{codprodutovariacao}/descontinuar', '\Mg\Produto\ProdutoVariacaoController@reativar');

        // Barras
        Route::post('{codproduto}/barra', '\Mg\Produto\ProdutoBarraController@store');
        Route::put('{codproduto}/barra/{codprodutobarra}', '\Mg\Produto\ProdutoBarraController@update');
        Route::delete('{codproduto}/barra/{codprodutobarra}', '\Mg\Produto\ProdutoBarraController@destroy');

        // Embalagens
        Route::post('{codproduto}/embalagem', '\Mg\Produto\ProdutoEmbalagemController@store');
        Route::put('{codproduto}/embalagem/{codprodutoembalagem}', '\Mg\Produto\ProdutoEmbalagemController@update');
        Route::delete('{codproduto}/embalagem/{codprodutoembalagem}', '\Mg\Produto\ProdutoEmbalagemController@destroy');

        // Abas de leitura
        Route::get('{codproduto}/estoque', '\Mg\Produto\ProdutoController@estoque');
        Route::get('{codproduto}/negocios', '\Mg\Produto\ProdutoController@negocios');
        Route::get('{codproduto}/notas', '\Mg\Produto\ProdutoController@notas');
        Route::get('{codproduto}/compras', '\Mg\Produto\ProdutoController@compras');

        // Integrações
        Route::get('{codproduto}/mercos', '\Mg\Produto\ProdutoController@mercos');
        Route::post('{codproduto}/mercos/exportar', '\Mg\Produto\ProdutoController@mercosExportar');
        Route::get('{codproduto}/woo', '\Mg\Produto\ProdutoController@woo');

        // Imagens — reordenar/remover (upload reusa /imagem com Slim)
        Route::put('{codproduto}/imagem/ordem', '\Mg\Produto\ProdutoController@imagemOrdem');
        Route::delete('{codproduto}/imagem/{codprodutoimagem}', '\Mg\Produto\ProdutoController@imagemRemover');
    });

    // CEST select (migrado em 31/05/2026)
    Route::get('cest/autocompletar', '\Mg\NaturezaOperacao\CestController@autocompletar');

    // Estoque-saldo grid (agrupamento/drill — migrado em 31/05/2026)
    Route::get('estoque-saldo-grid', '\Mg\Estoque\EstoqueSaldoGridController@index');

    // Estoque-saldo relatórios PDF (migrado em 31/05/2026)
    Route::get('estoque-saldo/relatorio/comparativo-vendas', '\Mg\Estoque\EstoqueSaldoRelatorioController@comparativoVendas');
    Route::get('estoque-saldo/relatorio/fisico-fiscal', '\Mg\Estoque\EstoqueSaldoRelatorioController@fisicoFiscal');
    Route::get('estoque-saldo/relatorio/transferencias', '\Mg\Estoque\EstoqueSaldoRelatorioController@transferencias');

    // NotaFiscal Dashboard
    Route::prefix('nota-fiscal/dashboard')->group(function () {
        Route::get('sefaz/status/{codfilial}', '\Mg\NotaFiscal\Dashboard\DashboardSefazController@status');
        Route::get('kpis/gerais', '\Mg\NotaFiscal\Dashboard\DashboardKpisController@gerais');
        Route::get('kpis/por-natureza', '\Mg\NotaFiscal\Dashboard\DashboardKpisController@porNatureza');
        Route::get('kpis/por-filial', '\Mg\NotaFiscal\Dashboard\DashboardKpisController@porFilial');
        Route::get('graficos/volume-mensal', '\Mg\NotaFiscal\Dashboard\DashboardGraficosController@volumeMensal');
        Route::get('graficos/erro-por-filial', '\Mg\NotaFiscal\Dashboard\DashboardGraficosController@erroPorFilial');
    });

    // NotaFiscal Controle
    Route::prefix('nota-fiscal/controle')->group(function () {
        Route::get('negocios-sem-nfce', '\Mg\NotaFiscal\Controle\ControleController@negociosSemNfce');
        Route::post('gerar-nfce-faltantes', '\Mg\NotaFiscal\Controle\ControleController@gerarNfceFaltantes');
        Route::post('gerar-transferencias', '\Mg\NotaFiscal\Controle\ControleController@gerarTransferencias');
    });

    // NotaFiscal Transferencia
    Route::get('nota-fiscal/dashboard-transferencia', '\Mg\NotaFiscal\NotaFiscalTransferenciaController@index');
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

    // Negocio apiResource
    Route::apiResource('negocio', '\Mg\Negocio\NegocioController');

    // Estoque (estatistica + conferencia)
    Route::apiResource('estoque-estatistica', '\Mg\Estoque\EstoqueEstatisticaController');
    Route::get('estoque-saldo-conferencia/busca-listagem', '\Mg\Estoque\EstoqueSaldoConferenciaController@buscaListagem');
    Route::post('estoque-saldo-conferencia', '\Mg\Estoque\EstoqueSaldoConferenciaController@store')->name('estoque-conferencia.store');
    Route::post('estoque-saldo-conferencia/zerar-produto', '\Mg\Estoque\EstoqueSaldoConferenciaController@zerarProduto');
    Route::get('estoque-saldo-conferencia/busca-produto', '\Mg\Estoque\EstoqueSaldoConferenciaController@buscaProduto');
    Route::post('estoque-saldo-conferencia/{id}/inativo', '\Mg\Estoque\EstoqueSaldoConferenciaController@inativar')->name('estoque-conferencia.inativar');

    // Imagem
    Route::apiResource('imagem', '\Mg\Imagem\ImagemController');
    Route::delete('imagem/{id}/inativo', '\Mg\Imagem\ImagemController@ativar')->name('imagem.ativar');
    Route::post('imagem/{id}/inativo', '\Mg\Imagem\ImagemController@inativar')->name('imagem.inativar');

    // Marca planilhas
    Route::put('marca/{id}/planilha/distribuicao-saldo-deposito', '\Mg\Marca\MarcaController@criarPlanilhaDistribuicaoSaldoDeposito');
    Route::put('marca/{id}/planilha/pedido', '\Mg\Marca\MarcaController@criarPlanilhaPedido');

    // Dominio
    Route::get('dominio/empresas', '\Mg\Dominio\DominioController@empresas');
    Route::post('dominio/estoque', '\Mg\Dominio\DominioController@estoque');
    Route::post('dominio/produto', '\Mg\Dominio\DominioController@produto');
    Route::post('dominio/pessoa', '\Mg\Dominio\DominioController@pessoa');
    Route::post('dominio/entrada', '\Mg\Dominio\DominioController@entrada');
    Route::post('dominio/nfe-saida', '\Mg\Dominio\DominioController@nfeSaida');
    Route::post('dominio/nfe-entrada', '\Mg\Dominio\DominioController@nfeEntrada');
    Route::post('dominio/acumulador', '\Mg\Dominio\DominioController@salvarAcumulador');
    Route::delete('dominio/acumulador/{coddominioacumulador}', '\Mg\Dominio\DominioController@excluirAcumulador');

    // Pix (com auth)
    Route::get('pix/portadores', '\Mg\Pix\PixController@portadores');
    Route::post('pix/consultar', '\Mg\Pix\PixController@consultarPixTodos');
    Route::post('pix/{codportador}/consultar', '\Mg\Pix\PixController@consultarPix');
    Route::get('pix/', '\Mg\Pix\PixController@index');
    Route::get('pix/descobre-nome', '\Mg\Pix\PixController@descobreNome');

    // TituloBoleto + Titulo CRUD + Liquidacao + Agrupamento
    Route::get('titulo/boleto/abertos/resumo', '\Mg\Titulo\TituloBoletoController@abertosResumo');
    Route::get('titulo/boleto/abertos', '\Mg\Titulo\TituloBoletoController@abertosLista');
    Route::get('titulo/boleto/liquidados/navegacao', '\Mg\Titulo\TituloBoletoController@liquidadosNavegacao');
    Route::get('titulo/boleto/baixados', '\Mg\Titulo\TituloBoletoController@baixadosLista');
    Route::get('titulo/abertos-para-fechamento', '\Mg\Titulo\TituloController@abertosParaFechamento');
    Route::get('titulo/listagem/relatorio', '\Mg\Titulo\TituloController@relatorioListagem');
    Route::get('titulo', '\Mg\Titulo\TituloController@index');
    Route::post('titulo', '\Mg\Titulo\TituloController@store');
    Route::get('titulo/{codtitulo}', '\Mg\Titulo\TituloController@show')->where('codtitulo', '[0-9]+');
    Route::put('titulo/{codtitulo}', '\Mg\Titulo\TituloController@update')->where('codtitulo', '[0-9]+');
    Route::post('titulo/{codtitulo}/estornar', '\Mg\Titulo\TituloController@estornar')->where('codtitulo', '[0-9]+');

    Route::get('liquidacao-titulo', '\Mg\Titulo\LiquidacaoTituloController@index');
    Route::get('liquidacao-titulo/relatorio', '\Mg\Titulo\LiquidacaoTituloController@relatorio');
    Route::get('liquidacao-titulo/{id}', '\Mg\Titulo\LiquidacaoTituloController@show')->where('id', '[0-9]+');
    Route::post('liquidacao-titulo', '\Mg\Titulo\LiquidacaoTituloController@store');
    Route::put('liquidacao-titulo/{id}', '\Mg\Titulo\LiquidacaoTituloController@update')->where('id', '[0-9]+');
    Route::post('liquidacao-titulo/{id}/estornar', '\Mg\Titulo\LiquidacaoTituloController@estornar')->where('id', '[0-9]+');
    Route::get('liquidacao-titulo/{id}/recibo', '\Mg\Titulo\LiquidacaoTituloController@recibo');
    Route::get('liquidacao-titulo/{id}/recibo-recebimento', '\Mg\Titulo\LiquidacaoTituloController@reciboRecebimento');
    Route::get('liquidacao-titulo/{id}/recibo-pagamento', '\Mg\Titulo\LiquidacaoTituloController@reciboPagamento');

    Route::get('titulo-agrupamento', '\Mg\Titulo\TituloAgrupamentoController@index');
    Route::get('titulo-agrupamento/pendentes', '\Mg\Titulo\TituloAgrupamentoController@pendentes');
    Route::get('titulo-agrupamento/relatorio', '\Mg\Titulo\TituloAgrupamentoController@relatorio');
    Route::get('titulo-agrupamento/relatorio-pendentes', '\Mg\Titulo\TituloAgrupamentoController@relatorioPendentes');
    Route::get('titulo-agrupamento/{id}', '\Mg\Titulo\TituloAgrupamentoController@show')->where('id', '[0-9]+');
    Route::get('titulo-agrupamento/{id}/relatorio', '\Mg\Titulo\TituloAgrupamentoController@relatorioDetalhe')->where('id', '[0-9]+');
    Route::post('titulo-agrupamento', '\Mg\Titulo\TituloAgrupamentoController@store');
    Route::put('titulo-agrupamento/{id}', '\Mg\Titulo\TituloAgrupamentoController@update')->where('id', '[0-9]+');
    Route::post('titulo-agrupamento/{id}/estornar', '\Mg\Titulo\TituloAgrupamentoController@estornar')->where('id', '[0-9]+');
    Route::post('titulo-agrupamento/{id}/gerar-nota-fiscal', '\Mg\Titulo\TituloAgrupamentoController@gerarNotaFiscal')->where('id', '[0-9]+');

    // BoletoBb
    Route::post('titulo/{codtitulo}/boleto-bb', '\Mg\Titulo\BoletoBb\BoletoBbController@registrar');
    Route::get('titulo/{codtitulo}/boleto-bb/{codtituloboleto}', '\Mg\Titulo\BoletoBb\BoletoBbController@show');
    Route::post('titulo/{codtitulo}/boleto-bb/{codtituloboleto}/consultar', '\Mg\Titulo\BoletoBb\BoletoBbController@consultar');
    Route::post('titulo/{codtitulo}/boleto-bb/{codtituloboleto}/baixar', '\Mg\Titulo\BoletoBb\BoletoBbController@baixar');

    // Portador
    Route::get('portador', '\Mg\Portador\PortadorController@index');
    Route::get('portador/intervalo-saldos', '\Mg\Portador\PortadorController@getIntervaloSaldos');
    Route::get('portador/lista-saldos', '\Mg\Portador\PortadorController@listaSaldos');
    Route::post('portador', '\Mg\Portador\PortadorController@store');
    Route::put('portador/{codportador}', '\Mg\Portador\PortadorController@update');
    Route::delete('portador/{codportador}', '\Mg\Portador\PortadorController@destroy');
    Route::post('portador/{codportador}/inativo', '\Mg\Portador\PortadorController@inativar');
    Route::delete('portador/{codportador}/inativo', '\Mg\Portador\PortadorController@ativar');
    Route::get('portador/{codportador}', '\Mg\Portador\PortadorController@show');
    Route::get('portador/{codportador}/info', '\Mg\Portador\PortadorController@info');
    Route::get('portador/{codportador}/extratos', '\Mg\Portador\PortadorController@listaExtratos');
    Route::get('portador/{codportador}/saldos-portador', '\Mg\Portador\PortadorController@listaSaldosPortador');
    Route::get('portador/{codportador}/consulta-extrato', '\Mg\Portador\PortadorController@consultaExtrato');
    Route::post('portador/importar-ofx', '\Mg\Portador\PortadorController@importarOfx');

    // Mercos
    Route::post('pdv/mercos/pedido/importar/{alterado_apos?}', '\Mg\Pdv\PdvMercosController@importarPedido');
    Route::get('pdv/mercos/pedido/', '\Mg\Pdv\PdvMercosController@listagemPedido');
    Route::post('pdv/negocio/{codnegocio}/mercos/{codpedido}/reimportar', '\Mg\Pdv\PdvMercosController@reimportar');
    Route::post('pdv/negocio/{codnegocio}/mercos/{codpedido}/faturamento', '\Mg\Pdv\PdvMercosController@exportarFaturamento');

    // RH
    Route::prefix('rh')->group(function () {
        Route::get('meu-painel/periodos', '\Mg\Rh\MeuPainelController@periodos');
        Route::get('meu-painel/{codperiodo}', '\Mg\Rh\MeuPainelController@index');
        Route::get('meu-painel/{codperiodo}/colaborador/{codperiodocolaborador}', '\Mg\Rh\MeuPainelController@colaborador');

        Route::get('periodo', '\Mg\Rh\PeriodoController@index');
        Route::post('periodo', '\Mg\Rh\PeriodoController@store');
        Route::post('periodo/{codperiodo}/duplicar', '\Mg\Rh\PeriodoController@duplicar');
        Route::post('periodo/{codperiodo}/importar-estrutura', '\Mg\Rh\PeriodoController@importarEstrutura');
        Route::post('periodo/{codperiodo}/fechar', '\Mg\Rh\PeriodoController@fechar');
        Route::post('periodo/{codperiodo}/reabrir', '\Mg\Rh\PeriodoController@reabrir');
        Route::put('periodo/{codperiodo}', '\Mg\Rh\PeriodoController@update');
        Route::delete('periodo/{codperiodo}', '\Mg\Rh\PeriodoController@destroy');

        Route::get('periodo/{codperiodo}/colaborador/disponiveis', '\Mg\Rh\PeriodoColaboradorController@disponiveis');
        Route::get('periodo/{codperiodo}/colaborador', '\Mg\Rh\PeriodoColaboradorController@index');
        Route::post('periodo/{codperiodo}/colaborador', '\Mg\Rh\PeriodoColaboradorController@store');
        Route::delete('periodo/{codperiodo}/colaborador/{codperiodocolaborador}', '\Mg\Rh\PeriodoColaboradorController@destroy');
        Route::post('periodo/{codperiodo}/colaborador/{codperiodocolaborador}/encerrar', '\Mg\Rh\PeriodoColaboradorController@encerrar');
        Route::post('periodo/{codperiodo}/colaborador/{codperiodocolaborador}/estornar', '\Mg\Rh\PeriodoColaboradorController@estornar');
        Route::post('periodo/{codperiodo}/colaborador/{codperiodocolaborador}/recalcular', '\Mg\Rh\PeriodoColaboradorController@recalcular');
        Route::patch('periodo/{codperiodo}/colaborador/{codperiodocolaborador}/gestor', '\Mg\Rh\PeriodoColaboradorController@toggleGestor');

        Route::post('periodo-colaborador/{codperiodocolaborador}/setor', '\Mg\Rh\PeriodoColaboradorSetorController@store');
        Route::put('periodo-colaborador-setor/{codperiodocolaboradorsetor}', '\Mg\Rh\PeriodoColaboradorSetorController@update');
        Route::delete('periodo-colaborador-setor/{codperiodocolaboradorsetor}', '\Mg\Rh\PeriodoColaboradorSetorController@destroy');

        Route::post('periodo-colaborador/{codperiodocolaborador}/rubrica', '\Mg\Rh\ColaboradorRubricaController@store');
        Route::put('rubrica/{codcolaboradorrubrica}', '\Mg\Rh\ColaboradorRubricaController@update');
        Route::delete('rubrica/{codcolaboradorrubrica}', '\Mg\Rh\ColaboradorRubricaController@destroy');
        Route::patch('rubrica/{codcolaboradorrubrica}/concedido', '\Mg\Rh\ColaboradorRubricaController@toggleConcedido');

        Route::get('periodo/{codperiodo}/indicador', '\Mg\Rh\IndicadorController@index');
        Route::post('periodo/{codperiodo}/indicador', '\Mg\Rh\IndicadorController@store');
        Route::get('indicador/{codindicador}/lancamento', '\Mg\Rh\IndicadorController@lancamentos');
        Route::put('indicador/{codindicador}/meta', '\Mg\Rh\IndicadorController@atualizarMeta');
        Route::post('indicador/{codindicador}/lancamento', '\Mg\Rh\IndicadorController@lancamentoManual');
        Route::put('indicador-lancamento/{codindicadorlancamento}', '\Mg\Rh\IndicadorController@atualizarLancamento');
        Route::delete('indicador-lancamento/{codindicadorlancamento}', '\Mg\Rh\IndicadorController@excluirLancamento');
        Route::delete('indicador/{codindicador}', '\Mg\Rh\IndicadorController@destroy');

        Route::post('periodo/{codperiodo}/reprocessar', '\Mg\Rh\IndicadorController@reprocessar');
        Route::get('periodo/{codperiodo}/reprocessar', '\Mg\Rh\IndicadorController@progressoReprocessamento');
        Route::delete('periodo/{codperiodo}/reprocessar', '\Mg\Rh\IndicadorController@cancelarReprocessamento');

        Route::get('dashboard/{codperiodo}', '\Mg\Rh\DashboardController@index');

        Route::prefix('periodo/{codperiodo}/acertos')->group(function () {
            Route::get('/', '\Mg\Rh\AcertoController@index');
            Route::get('/recibos', '\Mg\Rh\AcertoController@recibos');
            Route::get('/{codperiodocolaborador}/recibos', '\Mg\Rh\AcertoController@recibosColaborador');
            Route::get('/relatorio-folha', '\Mg\Rh\AcertoController@relatorioFolha');
            Route::get('/{codperiodocolaborador}/titulos', '\Mg\Rh\AcertoController@titulos');
            Route::post('/{codperiodocolaborador}/efetivar', '\Mg\Rh\AcertoController@efetivar');
            Route::post('/{codperiodocolaborador}/estornar', '\Mg\Rh\AcertoController@estornar');
        });
    });

    // NfeTerceiro (com auth)
    Route::prefix('nfe-terceiro')->group(function () {
        Route::get('/', '\Mg\NfeTerceiro\NfeTerceiroController@index');
        Route::post('upload-xml', '\Mg\NfeTerceiro\NfeTerceiroController@uploadXml');
        Route::get('{codnfeterceiro}', '\Mg\NfeTerceiro\NfeTerceiroController@show');
        Route::put('{codnfeterceiro}', '\Mg\NfeTerceiro\NfeTerceiroController@update');
        Route::post('{codnfeterceiro}/revisao', '\Mg\NfeTerceiro\NfeTerceiroController@revisao');
        Route::post('{codnfeterceiro}/conferencia', '\Mg\NfeTerceiro\NfeTerceiroController@conferencia');
        Route::get('{codnfeterceiro}/icmsst', '\Mg\NfeTerceiro\NfeTerceiroController@icmsst');
        Route::post('{codnfeterceiro}/gerar-guia-st', '\Mg\NfeTerceiro\NfeTerceiroController@gerarGuiaSt');
        Route::get('{codnfeterceiro}/validar-importacao', '\Mg\NfeTerceiro\NfeTerceiroController@validarImportacao');
        Route::post('{codnfeterceiro}/importar', '\Mg\NfeTerceiro\NfeTerceiroController@importar');
        Route::post('{codnfeterceiro}/buscar-item', '\Mg\NfeTerceiro\NfeTerceiroController@buscarItem');
        Route::put('{codnfeterceiro}/item/{codnfeterceiroitem}', '\Mg\NfeTerceiro\NfeTerceiroController@updateItem');
        Route::post('{codnfeterceiro}/item/{codnfeterceiroitem}/conferencia', '\Mg\NfeTerceiro\NfeTerceiroController@conferenciaItem');
        Route::post('{codnfeterceiro}/item/{codnfeterceiroitem}/dividir', '\Mg\NfeTerceiro\NfeTerceiroController@dividirItem');
        Route::get('{codnfeterceiro}/item/{codnfeterceiroitem}/analise', '\Mg\NfeTerceiro\NfeTerceiroController@analiseItem');
        Route::post('{codnfeterceiro}/marcar-tipo-produto', '\Mg\NfeTerceiro\NfeTerceiroController@marcarTipoProduto');
        Route::post('{codnfeterceiro}/conferir-todos', '\Mg\NfeTerceiro\NfeTerceiroController@conferirTodos');
        Route::post('{codnfeterceiro}/informar-complemento', '\Mg\NfeTerceiro\NfeTerceiroController@informarComplemento');
    });

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
