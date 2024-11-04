<?php

namespace Mg\Usuario;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Laravel\Passport\HasApiTokens;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Filial\Filial;
use Mg\Imagem\Imagem;
use Mg\Portador\Portador;

class Usuario extends MGModel implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, HasApiTokens;

    protected $table = 'tblusuario';
    protected $primaryKey = 'codusuario';
    protected $fillable = [
        'usuario',
        'senha',
        'codfilial',
        'codpessoa',
        'impressoratelanegocio',
        'codportador',
        'impressoratermica',
        'ultimoacesso',
        'inativo',
        'impressoramatricial',
        'remember_token',
        'codimagem'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'ultimoacesso',
        'inativo',
    ];

    // protected $hidden = ['senha', 'remember_token'];

    public function findForPassport(string $username): Usuario
    {
        return $this->where('usuario', $username)->first();
    }


    public function getAuthPassword() 
    {
        if (!empty($this->inativo)) {
            return null;
	}
        return $this->senha;
    }
    /**
    * @return mixed
    */
   

    /**
    * @return array
    */
    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Imagem()
    {
        return $this->belongsTo(Imagem::class, 'codimagem', 'codimagem');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function GrupoUsuarioUsuarioS()
    {
        return $this->hasMany(GrupoUsuarioUsuario::class, 'codusuario', 'codusuario');
    }

    public function CestAlteracaoS()
    {
        return $this->hasMany(Cest::class, 'codusuario', 'codusuarioalteracao');
    }

    public function CestCriacaoS()
    {
        return $this->hasMany(Cest::class, 'codusuario', 'codusuariocriacao');
    }

    public function EstoqueLocalAlteracaoS()
    {
        return $this->hasMany(EstoqueLocal::class, 'codusuario', 'codusuarioalteracao');
    }

    public function EstoqueLocalCriacaoS()
    {
        return $this->hasMany(EstoqueLocal::class, 'codusuario', 'codusuariocriacao');
    }

    public function EstoqueMesAlteracaoS()
    {
        return $this->hasMany(EstoqueMes::class, 'codusuario', 'codusuarioalteracao');
    }

    public function EstoqueMesCriacaoS()
    {
        return $this->hasMany(EstoqueMes::class, 'codusuario', 'codusuariocriacao');
    }

    public function EstoqueMovimentoAlteracaoS()
    {
        return $this->hasMany(EstoqueMovimento::class, 'codusuario', 'codusuarioalteracao');
    }

    public function EstoqueMovimentoCriacaoS()
    {
        return $this->hasMany(EstoqueMovimento::class, 'codusuario', 'codusuariocriacao');
    }

    public function EstoqueSaldoConferenciaAlteracaoS()
    {
        return $this->hasMany(EstoqueSaldoConferencia::class, 'codusuario', 'codusuarioalteracao');
    }

    public function EstoqueSaldoConferenciaCriacaoS()
    {
        return $this->hasMany(EstoqueSaldoConferencia::class, 'codusuario', 'codusuariocriacao');
    }

    public function FamiliaProdutoAlteracaoS()
    {
        return $this->hasMany(FamiliaProduto::class, 'codusuario', 'codusuariocriacao');
    }

    public function FamiliaProdutoCriacaoS()
    {
        return $this->hasMany(FamiliaProduto::class, 'codusuario', 'codusuarioalteracao');
    }

    public function GrupoUsuarioAlteracaoS()
    {
        return $this->hasMany(GrupoUsuario::class, 'codusuario', 'codusuarioalteracao');
    }

    public function GrupoUsuarioCriacaoS()
    {
        return $this->hasMany(GrupoUsuario::class, 'codusuario', 'codusuariocriacao');
    }

    public function GrupoUsuario()
    {
        return $this->belongsToMany(GrupoUsuario::class, 'tblgrupousuariousuario', 'codusuario', 'codgrupousuario')->withPivot('codgrupousuario', 'codfilial');
    }

    public function PermissaoAlteracaoS()
    {
        return $this->hasMany(Permissao::class, 'codusuario', 'codusuarioalteracao');
    }

    public function PermissaoCriacaoS()
    {
        return $this->hasMany(Permissao::class, 'codusuario', 'codusuariocriacao');
    }

    public function RegulamentoIcmsStMtAlteracaoS()
    {
        return $this->hasMany(RegulamentoIcmsStMt::class, 'codusuario', 'codusuarioalteracao');
    }

    public function RegulamentoIcmsStMtCriacaoS()
    {
        return $this->hasMany(RegulamentoIcmsStMt::class, 'codusuario', 'codusuariocriacao');
    }

    public function BancoAlteracaoS()
    {
        return $this->hasMany(Banco::class, 'codusuario', 'codusuarioalteracao');
    }

    public function BancoCriacaoS()
    {
        return $this->hasMany(Banco::class, 'codusuario', 'codusuariocriacao');
    }

    public function BaseRemotaAlteracaoS()
    {
        return $this->hasMany(BaseRemota::class, 'codusuario', 'codusuarioalteracao');
    }

    public function BaseRemotaCriacaoS()
    {
        return $this->hasMany(BaseRemota::class, 'codusuario', 'codusuariocriacao');
    }

    public function BoletoMotivoOcorrenciaAlteracaoS()
    {
        return $this->hasMany(BoletoMotivoOcorrencia::class, 'codusuario', 'codusuarioalteracao');
    }

    public function BoletoMotivoOcorrenciaCriacaoS()
    {
        return $this->hasMany(BoletoMotivoOcorrencia::class, 'codusuario', 'codusuariocriacao');
    }

    public function BoletoRetornoAlteracaoS()
    {
        return $this->hasMany(BoletoRetorno::class, 'codusuario', 'codusuarioalteracao');
    }

    public function BoletoRetornoCriacaoS()
    {
        return $this->hasMany(BoletoRetorno::class, 'codusuario', 'codusuariocriacao');
    }

    public function BoletoTipoOcorrenciaAlteracaoS()
    {
        return $this->hasMany(BoletoTipoOcorrencia::class, 'codusuario', 'codusuarioalteracao');
    }

    public function BoletoTipoOcorrenciaCriacaoS()
    {
        return $this->hasMany(BoletoTipoOcorrencia::class, 'codusuario', 'codusuariocriacao');
    }

    public function CfopAlteracaoS()
    {
        return $this->hasMany(Cfop::class, 'codusuario', 'codusuarioalteracao');
    }

    public function CfopCriacaoS()
    {
        return $this->hasMany(Cfop::class, 'codusuario', 'codusuariocriacao');
    }

    public function ChequeAlteracaoS()
    {
        return $this->hasMany(Cheque::class, 'codusuario', 'codusuarioalteracao');
    }

    public function ChequeCriacaoS()
    {
        return $this->hasMany(Cheque::class, 'codusuario', 'codusuariocriacao');
    }

    public function ChequeEmitenteAlteracaoS()
    {
        return $this->hasMany(ChequeEmitente::class, 'codusuario', 'codusuarioalteracao');
    }

    public function ChequeEmitenteCriacaoS()
    {
        return $this->hasMany(ChequeEmitente::class, 'codusuario', 'codusuariocriacao');
    }

    public function CidadeAlteracaoS()
    {
        return $this->hasMany(Cidade::class, 'codusuario', 'codusuarioalteracao');
    }

    public function CidadeCriacaoS()
    {
        return $this->hasMany(Cidade::class, 'codusuario', 'codusuariocriacao');
    }

    public function CobrancaAlteracaoS()
    {
        return $this->hasMany(Cobranca::class, 'codusuario', 'codusuarioalteracao');
    }

    public function CobrancaCriacaoS()
    {
        return $this->hasMany(Cobranca::class, 'codusuario', 'codusuariocriacao');
    }

    public function CobrancaHistoricoAlteracaoS()
    {
        return $this->hasMany(CobrancaHistorico::class, 'codusuario', 'codusuarioalteracao');
    }

    public function CobrancaHistoricoCriacaoS()
    {
        return $this->hasMany(CobrancaHistorico::class, 'codusuario', 'codusuariocriacao');
    }

    public function CobrancaHistoricoTituloAlteracaoS()
    {
        return $this->hasMany(CobrancaHistoricoTitulo::class, 'codusuario', 'codusuarioalteracao');
    }

    public function CobrancaHistoricoTituloCriacaoS()
    {
        return $this->hasMany(CobrancaHistoricoTitulo::class, 'codusuario', 'codusuariocriacao');
    }

    public function CodigoAlteracaoS()
    {
        return $this->hasMany(Codigo::class, 'codusuario', 'codusuarioalteracao');
    }

    public function CodigoCriacaoS()
    {
        return $this->hasMany(Codigo::class, 'codusuario', 'codusuariocriacao');
    }

    public function ContaContabilAlteracaoS()
    {
        return $this->hasMany(ContaContabil::class, 'codusuario', 'codusuarioalteracao');
    }

    public function ContaContabilCriacaoS()
    {
        return $this->hasMany(ContaContabil::class, 'codusuario', 'codusuariocriacao');
    }

    public function CupomFiscalAlteracaoS()
    {
        return $this->hasMany(CupomFiscal::class, 'codusuario', 'codusuarioalteracao');
    }

    public function CupomFiscalCriacaoS()
    {
        return $this->hasMany(CupomFiscal::class, 'codusuario', 'codusuariocriacao');
    }

    public function CupomFiscalProdutoBarraAlteracaoS()
    {
        return $this->hasMany(CupomFiscalProdutoBarra::class, 'codusuario', 'codusuarioalteracao');
    }

    public function CupomFiscalProdutoBarraCriacaoS()
    {
        return $this->hasMany(CupomFiscalProdutoBarra::class, 'codusuario', 'codusuariocriacao');
    }

    public function EcfAlteracaoS()
    {
        return $this->hasMany(Ecf::class, 'codusuario', 'codusuarioalteracao');
    }

    public function EcfCriacaoS()
    {
        return $this->hasMany(Ecf::class, 'codusuario', 'codusuariocriacao');
    }

    public function EcfS()
    {
        return $this->hasMany(Ecf::class, 'codusuario', 'codusuario');
    }

    public function EcfReducaozAlteracaoS()
    {
        return $this->hasMany(EcfReducaoz::class, 'codusuario', 'codusuarioalteracao');
    }

    public function EcfReducaozCriacaoS()
    {
        return $this->hasMany(EcfReducaoz::class, 'codusuario', 'codusuariocriacao');
    }

    public function EmpresaAlteracaoS()
    {
        return $this->hasMany(Empresa::class, 'codusuario', 'codusuarioalteracao');
    }

    public function EmpresaCriacaoS()
    {
        return $this->hasMany(Empresa::class, 'codusuario', 'codusuariocriacao');
    }

    public function EstadoCivilAlteracaoS()
    {
        return $this->hasMany(EstadoCivil::class, 'codusuario', 'codusuarioalteracao');
    }

    public function EstadoCivilCriacaoS()
    {
        return $this->hasMany(EstadoCivil::class, 'codusuario', 'codusuariocriacao');
    }

    public function EstadoAlteracaoS()
    {
        return $this->hasMany(Estado::class, 'codusuario', 'codusuarioalteracao');
    }

    public function EstadoCriacaoS()
    {
        return $this->hasMany(Estado::class, 'codusuario', 'codusuariocriacao');
    }

    public function EstoqueMovimentoTipoAlteracaoS()
    {
        return $this->hasMany(EstoqueMovimentoTipo::class, 'codusuario', 'codusuarioalteracao');
    }

    public function EstoqueMovimentoTipoCriacaoS()
    {
        return $this->hasMany(EstoqueMovimentoTipo::class, 'codusuario', 'codusuariocriacao');
    }

    public function EstoqueSaldoAlteracaoS()
    {
        return $this->hasMany(EstoqueSaldo::class, 'codusuario', 'codusuarioalteracao');
    }

    public function EstoqueSaldoCriacaoS()
    {
        return $this->hasMany(EstoqueSaldo::class, 'codusuario', 'codusuariocriacao');
    }

    public function FilialAcbrNfeMonitorS()
    {
        return $this->hasMany(Filial::class, 'codusuario', 'acbrnfemonitorcodusuario');
    }

    public function FilialAlteracaoS()
    {
        return $this->hasMany(Filial::class, 'codusuario', 'codusuarioalteracao');
    }

    public function FilialCriacaoS()
    {
        return $this->hasMany(Filial::class, 'codusuario', 'codusuariocriacao');
    }

    public function FormaPagamentoAlteracaoS()
    {
        return $this->hasMany(FormaPagamento::class, 'codusuario', 'codusuarioalteracao');
    }

    public function FormaPagamentoCriacaoS()
    {
        return $this->hasMany(FormaPagamento::class, 'codusuario', 'codusuariocriacao');
    }

    public function GrupoClienteAlteracaoS()
    {
        return $this->hasMany(GrupoCliente::class, 'codusuario', 'codusuarioalteracao');
    }

    public function GrupoClienteCriacaoS()
    {
        return $this->hasMany(GrupoCliente::class, 'codusuario', 'codusuariocriacao');
    }

    public function GrupoProdutoAlteracaoS()
    {
        return $this->hasMany(GrupoProduto::class, 'codusuario', 'codusuarioalteracao');
    }

    public function GrupoProdutoS()
    {
        return $this->hasMany(GrupoProduto::class, 'codusuario', 'codusuariocriacao');
    }

    public function IbptaxAlteracaoS()
    {
        return $this->hasMany(Ibptax::class, 'codusuario', 'codusuarioalteracao');
    }

    public function IbptaxCriacaoS()
    {
        return $this->hasMany(Ibptax::class, 'codusuario', 'codusuariocriacao');
    }

    public function LiquidacaoTituloAlteracaoS()
    {
        return $this->hasMany(LiquidacaoTitulo::class, 'codusuario', 'codusuarioalteracao');
    }

    public function LiquidacaoTituloCriacaoS()
    {
        return $this->hasMany(LiquidacaoTitulo::class, 'codusuario', 'codusuariocriacao');
    }

    public function LiquidacaoTituloEstornoS()
    {
        return $this->hasMany(LiquidacaoTitulo::class, 'codusuario', 'codusuarioestorno');
    }

    public function LiquidacaoTituloS()
    {
        return $this->hasMany(LiquidacaoTitulo::class, 'codusuario', 'codusuario');
    }

    public function MarcaAlteracaoS()
    {
        return $this->hasMany(Marca::class, 'codusuario', 'codusuarioalteracao');
    }

    public function MarcaCriacaoS()
    {
        return $this->hasMany(Marca::class, 'codusuario', 'codusuariocriacao');
    }

    public function MenuAlteracaoS()
    {
        return $this->hasMany(Menu::class, 'codusuario', 'codusuarioalteracao');
    }

    public function MenuCriacaoS()
    {
        return $this->hasMany(Menu::class, 'codusuario', 'codusuariocriacao');
    }

    public function MovimentoTituloAlteracaoS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codusuario', 'codusuarioalteracao');
    }

    public function MovimentoTituloCriacaoS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codusuario', 'codusuariocriacao');
    }

    public function NaturezaOperacaoAlteracaoS()
    {
        return $this->hasMany(NaturezaOperacao::class, 'codusuario', 'codusuarioalteracao');
    }

    public function NaturezaOperacaoCriacaoS()
    {
        return $this->hasMany(NaturezaOperacao::class, 'codusuario', 'codusuariocriacao');
    }

    public function NcmAlteracaoS()
    {
        return $this->hasMany(Ncm::class, 'codusuario', 'codusuarioalteracao');
    }

    public function NcmCriacaoS()
    {
        return $this->hasMany(Ncm::class, 'codusuario', 'codusuariocriacao');
    }

    public function NegocioAcertoEntregaS()
    {
        return $this->hasMany(Negocio::class, 'codusuario', 'codusuarioacertoentrega');
    }

    public function NegocioAlteracaoS()
    {
        return $this->hasMany(Negocio::class, 'codusuario', 'codusuarioalteracao');
    }

    public function NegocioCriacaoS()
    {
        return $this->hasMany(Negocio::class, 'codusuario', 'codusuariocriacao');
    }

    public function NegocioS()
    {
        return $this->hasMany(Negocio::class, 'codusuario', 'codusuario');
    }

    public function NegocioFormaPagamentoAlteracaoS()
    {
        return $this->hasMany(NegocioFormaPagamento::class, 'codusuario', 'codusuarioalteracao');
    }

    public function NegocioFormaPagamentoCriacaoS()
    {
        return $this->hasMany(NegocioFormaPagamento::class, 'codusuario', 'codusuariocriacao');
    }

    public function NegocioProdutoBarraAlteracaoS()
    {
        return $this->hasMany(NegocioProdutoBarra::class, 'codusuario', 'codusuarioalteracao');
    }

    public function NegocioProdutoBarraCriacaoS()
    {
        return $this->hasMany(NegocioProdutoBarra::class, 'codusuario', 'codusuariocriacao');
    }

    public function NegocioStatusAlteracaoS()
    {
        return $this->hasMany(NegocioStatus::class, 'codusuario', 'codusuarioalteracao');
    }

    public function NegocioStatusCriacaoS()
    {
        return $this->hasMany(NegocioStatus::class, 'codusuario', 'codusuariocriacao');
    }

    public function NfeTerceiroAlteracaoS()
    {
        return $this->hasMany(NfeTerceiro::class, 'codusuario', 'codusuarioalteracao');
    }

    public function NfeTerceiroCriacaoS()
    {
        return $this->hasMany(NfeTerceiro::class, 'codusuario', 'codusuariocriacao');
    }

    public function NfeTerceiroDuplicataAlteracaoS()
    {
        return $this->hasMany(NfeTerceiroDuplicata::class, 'codusuario', 'codusuarioalteracao');
    }

    public function NfeTerceiroDuplicataCriacaoS()
    {
        return $this->hasMany(NfeTerceiroDuplicata::class, 'codusuario', 'codusuariocriacao');
    }

    public function NfeTerceiroItemAlteracaoS()
    {
        return $this->hasMany(NfeTerceiroItem::class, 'codusuario', 'codusuarioalteracao');
    }

    public function NfeTerceiroItemCriacaoS()
    {
        return $this->hasMany(NfeTerceiroItem::class, 'codusuario', 'codusuariocriacao');
    }

    public function NotaFiscalCartaCorrecaoAlteracaoS()
    {
        return $this->hasMany(NotaFiscalCartaCorrecao::class, 'codusuario', 'codusuarioalteracao');
    }

    public function NotaFiscalCartaCorrecaoCriacaoS()
    {
        return $this->hasMany(NotaFiscalCartaCorrecao::class, 'codusuario', 'codusuariocriacao');
    }

    public function NotaFiscalAlteracaoS()
    {
        return $this->hasMany(NotaFiscal::class, 'codusuario', 'codusuarioalteracao');
    }

    public function NotaFiscalCriacaoS()
    {
        return $this->hasMany(NotaFiscal::class, 'codusuario', 'codusuariocriacao');
    }

    public function NotaFiscalDuplicatasAlteracaoS()
    {
        return $this->hasMany(NotaFiscalDuplicatas::class, 'codusuario', 'codusuarioalteracao');
    }

    public function NotaFiscalDuplicatasCriacaoS()
    {
        return $this->hasMany(NotaFiscalDuplicatas::class, 'codusuario', 'codusuariocriacao');
    }

    public function NotaFiscalProdutoBarraAlteracaoS()
    {
        return $this->hasMany(NotaFiscalProdutoBarra::class, 'codusuario', 'codusuarioalteracao');
    }

    public function NotaFiscalProdutoBarraCriacaoS()
    {
        return $this->hasMany(NotaFiscalProdutoBarra::class, 'codusuario', 'codusuariocriacao');
    }

    public function NotaFiscalReferenciadaAlteracaoS()
    {
        return $this->hasMany(NotaFiscalReferenciada::class, 'codusuario', 'codusuarioalteracao');
    }

    public function NotaFiscalReferenciadaCriacaoS()
    {
        return $this->hasMany(NotaFiscalReferenciada::class, 'codusuario', 'codusuariocriacao');
    }

    public function OperacaoAlteracaoS()
    {
        return $this->hasMany(Operacao::class, 'codusuario', 'codusuarioalteracao');
    }

    public function OperacaoCriacaoS()
    {
        return $this->hasMany(Operacao::class, 'codusuario', 'codusuariocriacao');
    }

    public function PaisAlteracaoS()
    {
        return $this->hasMany(Pais::class, 'codusuario', 'codusuarioalteracao');
    }

    public function PaisCriacaoS()
    {
        return $this->hasMany(Pais::class, 'codusuario', 'codusuariocriacao');
    }

    public function ParametrosGeraisAlteracaoS()
    {
        return $this->hasMany(ParametrosGerais::class, 'codusuario', 'codusuarioalteracao');
    }

    public function ParametrosGeraisCriacaoS()
    {
        return $this->hasMany(ParametrosGerais::class, 'codusuario', 'codusuariocriacao');
    }

    public function PessoaAlteracaoS()
    {
        return $this->hasMany(Pessoa::class, 'codusuario', 'codusuarioalteracao');
    }

    public function PessoaCriacaoS()
    {
        return $this->hasMany(Pessoa::class, 'codusuario', 'codusuariocriacao');
    }

    public function PortadorAlteracaoS()
    {
        return $this->hasMany(Portador::class, 'codusuario', 'codusuarioalteracao');
    }

    public function PortadorCriacaoS()
    {
        return $this->hasMany(Portador::class, 'codusuario', 'codusuariocriacao');
    }

    public function ProdutoBarraAlteracaoS()
    {
        return $this->hasMany(ProdutoBarra::class, 'codusuario', 'codusuarioalteracao');
    }

    public function ProdutoBarraCriacaoS()
    {
        return $this->hasMany(ProdutoBarra::class, 'codusuario', 'codusuariocriacao');
    }

    public function ProdutoAlteracaoS()
    {
        return $this->hasMany(Produto::class, 'codusuario', 'codusuarioalteracao');
    }

    public function ProdutoCriacaoS()
    {
        return $this->hasMany(Produto::class, 'codusuario', 'codusuariocriacao');
    }

    public function ProdutoEmbalagemAlteracaoS()
    {
        return $this->hasMany(ProdutoEmbalagem::class, 'codusuario', 'codusuarioalteracao');
    }

    public function ProdutoEmbalagemCriacaoS()
    {
        return $this->hasMany(ProdutoEmbalagem::class, 'codusuario', 'codusuariocriacao');
    }

    public function ProdutoHistoricoPrecoAlteracaoS()
    {
        return $this->hasMany(ProdutoHistoricoPreco::class, 'codusuario', 'codusuarioalteracao');
    }

    public function ProdutoHistoricoPrecoCriacaoS()
    {
        return $this->hasMany(ProdutoHistoricoPreco::class, 'codusuario', 'codusuariocriacao');
    }

    public function RegistroSpcAlteracaoS()
    {
        return $this->hasMany(RegistroSpc::class, 'codusuario', 'codusuarioalteracao');
    }

    public function RegistroSpcCriacaoS()
    {
        return $this->hasMany(RegistroSpc::class, 'codusuario', 'codusuariocriacao');
    }

    public function SexoAlteracaoS()
    {
        return $this->hasMany(Sexo::class, 'codusuario', 'codusuarioalteracao');
    }

    public function SexoCriacaoS()
    {
        return $this->hasMany(Sexo::class, 'codusuario', 'codusuariocriacao');
    }

    public function SubGrupoProdutoAlteracaoS()
    {
        return $this->hasMany(SubGrupoProduto::class, 'codusuario', 'codusuarioalteracao');
    }

    public function SubGrupoProdutCriacaooS()
    {
        return $this->hasMany(SubGrupoProduto::class, 'codusuario', 'codusuariocriacao');
    }

    public function TipoMovimentoTituloAlteracaoS()
    {
        return $this->hasMany(TipoMovimentoTitulo::class, 'codusuario', 'codusuarioalteracao');
    }

    public function TipoMovimentoTituloCriacaoS()
    {
        return $this->hasMany(TipoMovimentoTitulo::class, 'codusuario', 'codusuariocriacao');
    }

    public function TipoProdutoAlteracaoS()
    {
        return $this->hasMany(TipoProduto::class, 'codusuario', 'codusuarioalteracao');
    }

    public function TipoProdutoCriacaoS()
    {
        return $this->hasMany(TipoProduto::class, 'codusuario', 'codusuariocriacao');
    }

    public function TipoTituloAlteracaoS()
    {
        return $this->hasMany(TipoTitulo::class, 'codusuario', 'codusuarioalteracao');
    }

    public function TipoTituloCriacaoS()
    {
        return $this->hasMany(TipoTitulo::class, 'codusuario', 'codusuariocriacao');
    }

    public function TituloAgrupamentoAlteracaoS()
    {
        return $this->hasMany(TituloAgrupamento::class, 'codusuario', 'codusuarioalteracao');
    }

    public function TituloAgrupamentoCriacaoS()
    {
        return $this->hasMany(TituloAgrupamento::class, 'codusuario', 'codusuariocriacao');
    }

    public function TituloAlteracaoS()
    {
        return $this->hasMany(Titulo::class, 'codusuario', 'codusuarioalteracao');
    }

    public function TituloCriacaoS()
    {
        return $this->hasMany(Titulo::class, 'codusuario', 'codusuariocriacao');
    }

    public function TributacaoAlteracaoS()
    {
        return $this->hasMany(Tributacao::class, 'codusuario', 'codusuarioalteracao');
    }

    public function TributacaoCriacaoS()
    {
        return $this->hasMany(Tributacao::class, 'codusuario', 'codusuariocriacao');
    }

    public function TributacaoNaturezaOperacaoAlteracaoS()
    {
        return $this->hasMany(TributacaoNaturezaOperacao::class, 'codusuario', 'codusuarioalteracao');
    }

    public function TributacaoNaturezaOperacaoCriacaoS()
    {
        return $this->hasMany(TributacaoNaturezaOperacao::class, 'codusuario', 'codusuariocriacao');
    }

    public function UnidadeMedidaAlteracaoS()
    {
        return $this->hasMany(UnidadeMedida::class, 'codusuario', 'codusuarioalteracao');
    }

    public function UnidadeMedidaCriacaoS()
    {
        return $this->hasMany(UnidadeMedida::class, 'codusuario', 'codusuariocriacao');
    }

    public function MetaCriacaoS()
    {
        return $this->hasMany(Meta::class, 'codusuario', 'codusuarioalteracao');
    }

    public function MetaAlteracaoS()
    {
        return $this->hasMany(Meta::class, 'codusuario', 'codusuariocriacao');
    }

    public function MetaFilialCriacaoS()
    {
        return $this->hasMany(MetaFilial::class, 'codusuario', 'codusuarioalteracao');
    }

    public function MetaFilialAlteracaoS()
    {
        return $this->hasMany(MetaFilial::class, 'codusuario', 'codusuariocriacao');
    }

    public function MetaFilialPessoaCriacaoS()
    {
        return $this->hasMany(MetaFilialPessoa::class, 'codusuario', 'codusuarioalteracao');
    }

    public function MetaFilialPessoaAlteracaoS()
    {
        return $this->hasMany(MetaFilialPessoa::class, 'codusuario', 'codusuariocriacao');
    }

    public function CargoCriacaoS()
    {
        return $this->hasMany(Cargo::class, 'codusuario', 'codusuarioalteracao');
    }

    public function CargoAlteracaoS()
    {
        return $this->hasMany(Cargo::class, 'codusuario', 'codusuariocriacao');
    }

    public function UsuarioAlteracaoS()
    {
        return $this->hasMany(Usuario::class, 'codusuario', 'codusuarioalteracao');
    }

    public function UsuarioCriacaoS()
    {
        return $this->hasMany(Usuario::class, 'codusuario', 'codusuariocriacao');
    }

    public function can($permissao = null, $codfilial = null)
    {
        $query = Permissao::where('permissao', $permissao);

        $query->join('tblgrupousuariopermissao', 'tblgrupousuariopermissao.codpermissao', '=', 'tblpermissao.codpermissao')
            ->join('tblgrupousuariousuario', 'tblgrupousuariousuario.codgrupousuario', '=', 'tblgrupousuariopermissao.codgrupousuario')
            ->where('tblgrupousuariousuario.codusuario', $this->codusuario);

        if (!empty($codfilial)) {
            $query->where('tblgrupousuariousuario.codfilial', $codfilial);
        }

        $count = $query->count();

        return $count > 0;
    }
}
