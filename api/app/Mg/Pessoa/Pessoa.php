<?php

namespace Mg\Pessoa;

use App\Models\Usuario;
use Carbon\Carbon;
use Mg\Certidao\CertidaoEmissor;
use Mg\Cidade\Cidade;
use Mg\Cidade\Estado;
use Mg\Cobranca\CobrancaHistorico;
use Mg\FormaPagamento\FormaPagamento;
use Mg\GrupoEconomico\GrupoEconomico;
use Mg\Mercos\MercosCliente;
use Mg\MgModel;

class Pessoa extends MgModel
{
    public const NOTAFISCAL_TRATAMENTOPADRAO = 0;
    public const NOTAFISCAL_SEMPRE = 1;
    public const NOTAFISCAL_SOMENTE_FECHAMENTO = 2;
    public const NOTAFISCAL_NUNCA = 9;
    public const CONSUMIDOR = 1;

    protected $table = 'tblpessoa';
    protected $primaryKey = 'codpessoa';

    protected $fillable = [
        'bairro', 'bairrocobranca', 'cep', 'cepcobranca', 'cliente', 'cnpj',
        'codcidade', 'codcidadecobranca', 'codcidadenascimento', 'codestadocivil',
        'codestadoctps', 'codetnia', 'codformapagamento', 'codgrauinstrucao',
        'codgrupocliente', 'codgrupoeconomico', 'codsexo', 'comissao',
        'complemento', 'complementocobranca', 'conjuge', 'consumidor', 'contato',
        'credito', 'creditobloqueado', 'crt', 'ctps', 'desconto', 'email',
        'emailcobranca', 'emailnfe', 'emissaoctps', 'endereco', 'enderecocobranca',
        'fantasia', 'fisica', 'fornecedor', 'ie', 'inativo', 'mae', 'mensagemvenda',
        'nascimento', 'notafiscal', 'numero', 'numerocobranca', 'observacoes',
        'pai', 'pessoa', 'pispasep', 'rg', 'rntrc', 'seriectps', 'telefone1',
        'telefone2', 'telefone3', 'tipotransportador', 'tituloeleitor',
        'titulosecao', 'titulozona', 'toleranciaatraso', 'vendedor',
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
        'vendedor' => 'boolean',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'emissaoctps' => 'datetime',
        'inativo' => 'datetime',
        'nascimento' => 'datetime',
    ];

    public function certidaoSefazMT()
    {
        return $this->PessoaCertidaoS()
            ->where('validade', '>=', Carbon::createMidnightDate())
            ->ativo()
            ->where('codcertidaoemissor', CertidaoEmissor::SEFAZ_MT)
            ->orderBy('validade', 'desc')
            ->first();
    }

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

    public function CobrancaHistoricoS()
    {
        return $this->hasMany(CobrancaHistorico::class, 'codpessoa', 'codpessoa');
    }

    public function DependenteS()
    {
        return $this->hasMany(Dependente::class, 'codpessoa', 'codpessoa');
    }

    public function DependeteResponsavelS()
    {
        return $this->hasMany(Dependente::class, 'codpessoaresponsavel', 'codpessoa');
    }

    public function MercosClienteS()
    {
        return $this->hasMany(MercosCliente::class, 'codpessoa', 'codpessoa');
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

    public function RegistroSpcS()
    {
        return $this->hasMany(RegistroSpc::class, 'codpessoa', 'codpessoa');
    }

    public function UsuarioS()
    {
        return $this->hasMany(Usuario::class, 'codpessoa', 'codpessoa');
    }

    public function TituloS()
    {
        return $this->hasMany(\Mg\Titulo\Titulo::class, 'codpessoa', 'codpessoa');
    }

    public function NegocioS()
    {
        return $this->hasMany(\Mg\Negocio\Negocio::class, 'codpessoa', 'codpessoa');
    }

    public function NotaFiscalS()
    {
        return $this->hasMany(\Mg\NotaFiscal\NotaFiscal::class, 'codpessoa', 'codpessoa');
    }

    public function NfeTerceiroS()
    {
        return $this->hasMany(\Mg\NfeTerceiro\NfeTerceiro::class, 'codpessoa', 'codpessoa');
    }

    public function NotaFiscalTerceiroS()
    {
        return $this->hasMany(\Mg\NotaFiscalTerceiro\NotaFiscalTerceiro::class, 'codpessoa', 'codpessoa');
    }

    public function ColaboradorS()
    {
        return $this->hasMany(\Mg\Colaborador\Colaborador::class, 'codpessoa', 'codpessoa');
    }

    public function FilialS()
    {
        return $this->hasMany(\Mg\Filial\Filial::class, 'codpessoa', 'codpessoa');
    }
}
