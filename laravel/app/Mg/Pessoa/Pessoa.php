<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Jun/2021 08:44:54
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
use Mg\Cidade\Cidade;
use Mg\Pessoa\EstadoCivil;
use Mg\FormaPagamento\FormaPagamento;
use Mg\Pessoa\GrupoCliente;
use Mg\GrupoEconomico\GrupoEconomico;
use Mg\Pessoa\Sexo;

class Pessoa extends MgModel
{

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
        'codestadocivil',
        'codformapagamento',
        'codgrupocliente',
        'codgrupoeconomico',
        'codsexo',
        'complemento',
        'complementocobranca',
        'conjuge',
        'consumidor',
        'contato',
        'credito',
        'creditobloqueado',
        'crt',
        'desconto',
        'email',
        'emailcobranca',
        'emailnfe',
        'endereco',
        'enderecocobranca',
        'fantasia',
        'fisica',
        'fornecedor',
        'ie',
        'inativo',
        'mensagemvenda',
        'notafiscal',
        'numero',
        'numerocobranca',
        'observacoes',
        'pessoa',
        'rg',
        'rntrc',
        'telefone1',
        'telefone2',
        'telefone3',
        'tipotransportador',
        'toleranciaatraso',
        'vendedor'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'cliente' => 'boolean',
        'cnpj' => 'float',
        'codcidade' => 'integer',
        'codcidadecobranca' => 'integer',
        'codestadocivil' => 'integer',
        'codformapagamento' => 'integer',
        'codgrupocliente' => 'integer',
        'codgrupoeconomico' => 'integer',
        'codpessoa' => 'integer',
        'codsexo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'consumidor' => 'boolean',
        'credito' => 'float',
        'creditobloqueado' => 'boolean',
        'crt' => 'integer',
        'desconto' => 'float',
        'fisica' => 'boolean',
        'fornecedor' => 'boolean',
        'notafiscal' => 'integer',
        'tipotransportador' => 'integer',
        'toleranciaatraso' => 'integer',
        'vendedor' => 'boolean'
    ];

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

    public function EstadoCivil()
    {
        return $this->belongsTo(EstadoCivil::class, 'codestadocivil', 'codestadocivil');
    }

    public function FormaPagamento()
    {
        return $this->belongsTo(FormaPagamento::class, 'codformapagamento', 'codformapagamento');
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
    public function ChequeS()
    {
        return $this->hasMany(Cheque::class, 'codpessoa', 'codpessoa');
    }

    public function CobrancaHistoricoS()
    {
        return $this->hasMany(CobrancaHistorico::class, 'codpessoa', 'codpessoa');
    }

    public function CupomFiscalS()
    {
        return $this->hasMany(CupomFiscal::class, 'codpessoa', 'codpessoa');
    }

    public function FilialS()
    {
        return $this->hasMany(Filial::class, 'codpessoa', 'codpessoa');
    }

    public function LiquidacaoTituloS()
    {
        return $this->hasMany(LiquidacaoTitulo::class, 'codpessoa', 'codpessoa');
    }

    public function MdfeVeiculoCondutorS()
    {
        return $this->hasMany(MdfeVeiculo::class, 'codpessoacondutor', 'codpessoa');
    }

    public function MetaFilialPessoaS()
    {
        return $this->hasMany(MetaFilialPessoa::class, 'codpessoa', 'codpessoa');
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

    public function NotaFiscalTerceiroS()
    {
        return $this->hasMany(NotaFiscalTerceiro::class, 'codpessoa', 'codpessoa');
    }

    public function PessoaCertidaoS()
    {
        return $this->hasMany(PessoaCertidao::class, 'codpessoa', 'codpessoa');
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