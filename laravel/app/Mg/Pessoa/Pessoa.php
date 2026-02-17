<?php

/**
 * Created by php artisan gerador:model.
 * Date: 16/Feb/2026 21:59:59
 */

namespace Mg\Pessoa;

use Mg\Certidao\CertidaoEmissor;
use Carbon\Carbon;

use Mg\MgModel;
use Mg\Cheque\Cheque;
use Mg\Cobranca\CobrancaHistorico;
use Mg\CupomFiscal\CupomFiscal;
use Mg\Filial\Filial;
use Mg\Titulo\LiquidacaoTitulo;
use Mg\Meta\MetaFilialPessoa;
use Mg\Negocio\Negocio;
use Mg\NfeTerceiro\NfeTerceiro;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiro;
use Mg\Pessoa\PessoaCertidao;
use Mg\Pessoa\RegistroSpc;
use Mg\Titulo\Titulo;
use Mg\Titulo\TituloAgrupamento;
use Mg\Usuario\Usuario;
use Mg\ValeCompra\ValeCompra;
use Mg\ValeCompra\ValeCompraModelo;
use Mg\Veiculo\Veiculo;
use Mg\Mdfe\MdfeVeiculo;
use Mg\Mercos\MercosCliente;
use Mg\PagarMe\PagarMePedido;
use Mg\Pessoa\PessoaConta;
use Mg\Pessoa\PessoaEmail;
use Mg\Pessoa\PessoaEndereco;
use Mg\Pessoa\PessoaTelefone;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Colaborador\Colaborador;
use Mg\NotaFiscal\NotaFiscalPagamento;
use Mg\Meta\MetaVendedor;
use Mg\Portador\Portador;
use Mg\Pessoa\Dependente;
use Mg\Meta\BonificacaoEvento;
use Mg\Meta\MetaUnidadeNegocioPessoa;
use Mg\Cidade\Cidade;
use Mg\Pessoa\EstadoCivil;
use Mg\FormaPagamento\FormaPagamento;
use Mg\Pessoa\GrupoCliente;
use Mg\GrupoEconomico\GrupoEconomico;
use Mg\Pessoa\Sexo;
use Mg\Cidade\Estado;
use Mg\Pessoa\Etnia;
use Mg\Pessoa\GrauInstrucao;

class Pessoa extends MgModel
{

    // TODO: Passar essas constantes para Service
    const NOTAFISCAL_TRATAMENTOPADRAO = 0;
    const NOTAFISCAL_SEMPRE = 1;
    const NOTAFISCAL_SOMENTE_FECHAMENTO = 2;
    const NOTAFISCAL_NUNCA = 9;

    const CONSUMIDOR = 1;

    protected $table = 'tblpessoa';
    protected $primaryKey = 'codpessoa';


    protected $fillable = [
        'bairro',
        'bairrocobranca',
        'cep',
        'cepcobranca',
        'cliente',
        'cnpj',
        'codcidade',
        'codcidadecobranca',
        'codcidadenascimento',
        'codestadocivil',
        'codestadoctps',
        'codetnia',
        'codformapagamento',
        'codgrauinstrucao',
        'codgrupocliente',
        'codgrupoeconomico',
        'codsexo',
        'comissao',
        'complemento',
        'complementocobranca',
        'conjuge',
        'consumidor',
        'contato',
        'credito',
        'creditobloqueado',
        'crt',
        'ctps',
        'desconto',
        'email',
        'emailcobranca',
        'emailnfe',
        'emissaoctps',
        'endereco',
        'enderecocobranca',
        'fantasia',
        'fisica',
        'fornecedor',
        'ie',
        'inativo',
        'mae',
        'mensagemvenda',
        'nascimento',
        'notafiscal',
        'numero',
        'numerocobranca',
        'observacoes',
        'pai',
        'pessoa',
        'pispasep',
        'rg',
        'rntrc',
        'seriectps',
        'telefone1',
        'telefone2',
        'telefone3',
        'tipotransportador',
        'tituloeleitor',
        'titulosecao',
        'titulozona',
        'toleranciaatraso',
        'vendedor'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'emissaoctps',
        'inativo',
        'nascimento'
    ];

    protected $casts = [
        'cliente' => 'boolean',
        'cnpj' => 'float',
        'codcidade' => 'integer',
        'codcidadecobranca' => 'integer',
        'codcidadenascimento' => 'integer',
        'codestadocivil' => 'integer',
        'codestadoctps' => 'integer',
        'codetnia' => 'integer',
        'codformapagamento' => 'integer',
        'codgrauinstrucao' => 'integer',
        'codgrupocliente' => 'integer',
        'codgrupoeconomico' => 'integer',
        'codpessoa' => 'integer',
        'codsexo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'comissao' => 'integer',
        'consumidor' => 'boolean',
        'credito' => 'float',
        'creditobloqueado' => 'boolean',
        'crt' => 'integer',
        'desconto' => 'float',
        'fisica' => 'boolean',
        'fornecedor' => 'boolean',
        'notafiscal' => 'integer',
        'pispasep' => 'float',
        'tipotransportador' => 'integer',
        'tituloeleitor' => 'float',
        'titulosecao' => 'float',
        'titulozona' => 'float',
        'toleranciaatraso' => 'integer',
        'vendedor' => 'boolean'
    ];

    // TODO: Passar essa Funcao para Service
    public function certidaoSefazMT()
    {
        return $this->PessoaCertidaoS()->where('validade', '>=', Carbon::createMidnightDate())
            ->ativo()
            ->where('codcertidaoemissor', CertidaoEmissor::SEFAZ_MT)
            ->orderBy('validade', 'desc')
            ->first();
    }

    // Chaves Estrangeiras
    public function Cidade()
    {
        return $this->belongsTo(Cidade::class, 'codcidade', 'codcidade');
    }

    public function CidadeCobranca()
    {
        return $this->belongsTo(Cidade::class, 'codcidadecobranca', 'codcidade');
    }

    public function CidadeNascimento()
    {
        return $this->belongsTo(Cidade::class, 'codcidadenascimento', 'codcidade');
    }

    public function EstadoCivil()
    {
        return $this->belongsTo(EstadoCivil::class, 'codestadocivil', 'codestadocivil');
    }

    public function EstadoCtps()
    {
        return $this->belongsTo(Estado::class, 'codestadoctps', 'codestado');
    }

    public function Etnia()
    {
        return $this->belongsTo(Etnia::class, 'codetnia', 'codetnia');
    }

    public function FormaPagamento()
    {
        return $this->belongsTo(FormaPagamento::class, 'codformapagamento', 'codformapagamento');
    }

    public function GrauInstrucao()
    {
        return $this->belongsTo(GrauInstrucao::class, 'codgrauinstrucao', 'codgrauinstrucao');
    }

    public function GrupoCliente()
    {
        return $this->belongsTo(GrupoCliente::class, 'codgrupocliente', 'codgrupocliente');
    }

    public function GrupoEconomico()
    {
        return $this->belongsTo(GrupoEconomico::class, 'codgrupoeconomico', 'codgrupoeconomico');
    }

    public function Sexo()
    {
        return $this->belongsTo(Sexo::class, 'codsexo', 'codsexo');
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
    public function BonificacaoEventoS()
    {
        return $this->hasMany(BonificacaoEvento::class, 'codpessoa', 'codpessoa');
    }

    public function ChequeS()
    {
        return $this->hasMany(Cheque::class, 'codpessoa', 'codpessoa');
    }

    public function CobrancaHistoricoS()
    {
        return $this->hasMany(CobrancaHistorico::class, 'codpessoa', 'codpessoa');
    }

    public function ColaboradorS()
    {
        return $this->hasMany(Colaborador::class, 'codpessoa', 'codpessoa');
    }

    public function CupomFiscalS()
    {
        return $this->hasMany(CupomFiscal::class, 'codpessoa', 'codpessoa');
    }

    public function DependenteS()
    {
        return $this->hasMany(Dependente::class, 'codpessoa', 'codpessoa');
    }

    public function DependeteResponsavelS()
    {
        return $this->hasMany(Dependente::class, 'codpessoaresponsavel', 'codpessoa');
    }

    public function FilialS()
    {
        return $this->hasMany(Filial::class, 'codpessoa', 'codpessoa');
    }

    public function LiquidacaoTituloS()
    {
        return $this->hasMany(LiquidacaoTitulo::class, 'codpessoa', 'codpessoa');
    }

    public function LiquidacaoTituloCartaoS()
    {
        return $this->hasMany(LiquidacaoTitulo::class, 'codpessoacartao', 'codpessoa');
    }

    public function MdfeVeiculoCondutorS()
    {
        return $this->hasMany(MdfeVeiculo::class, 'codpessoacondutor', 'codpessoa');
    }

    public function MercosClienteS()
    {
        return $this->hasMany(MercosCliente::class, 'codpessoa', 'codpessoa');
    }

    public function MetaFilialPessoaS()
    {
        return $this->hasMany(MetaFilialPessoa::class, 'codpessoa', 'codpessoa');
    }

    public function MetaUnidadeNegocioPessoaS()
    {
        return $this->hasMany(MetaUnidadeNegocioPessoa::class, 'codpessoa', 'codpessoa');
    }

    public function MetaVendedorS()
    {
        return $this->hasMany(MetaVendedor::class, 'codpessoa', 'codpessoa');
    }

    public function NegocioS()
    {
        return $this->hasMany(Negocio::class, 'codpessoa', 'codpessoa');
    }

    public function NegocioTransportadorS()
    {
        return $this->hasMany(Negocio::class, 'codpessoatransportador', 'codpessoa');
    }

    public function NegocioVendedorS()
    {
        return $this->hasMany(Negocio::class, 'codpessoavendedor', 'codpessoa');
    }

    public function NegocioFormaPagamentoS()
    {
        return $this->hasMany(NegocioFormaPagamento::class, 'codpessoa', 'codpessoa');
    }

    public function NfeTerceiroS()
    {
        return $this->hasMany(NfeTerceiro::class, 'codpessoa', 'codpessoa');
    }

    public function NotaFiscalS()
    {
        return $this->hasMany(NotaFiscal::class, 'codpessoa', 'codpessoa');
    }

    public function NotaFiscalTransportadorS()
    {
        return $this->hasMany(NotaFiscal::class, 'codpessoatransportador', 'codpessoa');
    }

    public function NotaFiscalPagamentoS()
    {
        return $this->hasMany(NotaFiscalPagamento::class, 'codpessoa', 'codpessoa');
    }

    public function NotaFiscalTerceiroS()
    {
        return $this->hasMany(NotaFiscalTerceiro::class, 'codpessoa', 'codpessoa');
    }

    public function PagarMePedidoS()
    {
        return $this->hasMany(PagarMePedido::class, 'codpessoa', 'codpessoa');
    }

    public function PessoaCertidaoS()
    {
        return $this->hasMany(PessoaCertidao::class, 'codpessoa', 'codpessoa');
    }

    public function PessoaContaS()
    {
        return $this->hasMany(PessoaConta::class, 'codpessoa', 'codpessoa');
    }

    public function PessoaEmailS()
    {
        return $this->hasMany(PessoaEmail::class, 'codpessoa', 'codpessoa');
    }

    public function PessoaEnderecoS()
    {
        return $this->hasMany(PessoaEndereco::class, 'codpessoa', 'codpessoa');
    }

    public function PessoaTelefoneS()
    {
        return $this->hasMany(PessoaTelefone::class, 'codpessoa', 'codpessoa');
    }

    public function PortadorS()
    {
        return $this->hasMany(Portador::class, 'codpessoa', 'codpessoa');
    }

    public function RegistroSpcS()
    {
        return $this->hasMany(RegistroSpc::class, 'codpessoa', 'codpessoa');
    }

    public function TituloS()
    {
        return $this->hasMany(Titulo::class, 'codpessoa', 'codpessoa');
    }

    public function TituloAgrupamentoS()
    {
        return $this->hasMany(TituloAgrupamento::class, 'codpessoa', 'codpessoa');
    }

    public function UsuarioS()
    {
        return $this->hasMany(Usuario::class, 'codpessoa', 'codpessoa');
    }

    public function ValeCompraS()
    {
        return $this->hasMany(ValeCompra::class, 'codpessoa', 'codpessoa');
    }

    public function ValeCompraFavorecidoS()
    {
        return $this->hasMany(ValeCompra::class, 'codpessoafavorecido', 'codpessoa');
    }

    public function ValeCompraModeloFavorecidoS()
    {
        return $this->hasMany(ValeCompraModelo::class, 'codpessoafavorecido', 'codpessoa');
    }

    public function VeiculoS()
    {
        return $this->hasMany(Veiculo::class, 'codpessoaproprietario', 'codpessoa');
    }
}
