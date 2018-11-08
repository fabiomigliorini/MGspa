<?php

namespace Mg\Pessoa;

use Mg\MgModel;
use Mg\Cidade\Cidade;

use DB;
use Carbon\Carbon;


class Pessoa extends MGModel
{

    const NOTAFISCAL_TRATAMENTOPADRAO = 0;
    const NOTAFISCAL_SEMPRE = 1;
    const NOTAFISCAL_SOMENTE_FECHAMENTO = 2;
    const NOTAFISCAL_NUNCA = 9;

    const CONSUMIDOR = 1;

    protected $table = 'tblpessoa';
    protected $primaryKey = 'codpessoa';
    protected $fillable = [
        'pessoa',
        'fantasia',
        'inativo',
        'cliente',
        'fornecedor',
        'fisica',
        'codsexo',
        'cnpj',
        'ie',
        'consumidor',
        'contato',
        'codestadocivil',
        'conjuge',
        'endereco',
        'numero',
        'complemento',
        'codcidade',
        'bairro',
        'cep',
        'enderecocobranca',
        'numerocobranca',
        'complementocobranca',
        'codcidadecobranca',
        'bairrocobranca',
        'cepcobranca',
        'telefone1',
        'telefone2',
        'telefone3',
        'email',
        'emailnfe',
        'emailcobranca',
        'codformapagamento',
        'credito',
        'creditobloqueado',
        'observacoes',
        'mensagemvenda',
        'vendedor',
        'rg',
        'desconto',
        'notafiscal',
        'toleranciaatraso',
        'codgrupocliente',
    ];
    protected $dates = [
        'inativo',
        'alteracao',
        'criacao',
    ];


    public function getCobrancanomesmoenderecoAttribute()
    {
        if (
            ($this->enderecocobranca    <>  $this->endereco   ) or
            ($this->numerocobranca      <>  $this->numero     ) or
            ($this->complementocobranca <>  $this->complemento) or
            ($this->bairrocobranca      <>  $this->bairro     ) or
            ($this->codcidadecobranca   <>  $this->codcidade  ) or
            ($this->cepcobranca         <>  $this->cep        )
           )
            return false;
        else
            return true;
    }


    /*
    public function Usuario()
    {
        return $this->hasMany(Usuario::class, 'codpessoa', 'codpessoa');
    }
    */


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

    public function ValeCompraModeloFavorecidoS()
    {
        return $this->hasMany(ValeCompraModelo::class, 'codpessoa', 'codpessoafavorecido');
    }

    public function ValeCompraS()
    {
        return $this->hasMany(ValeCompra::class, 'codpessoa', 'codpessoa');
    }

    public function ValeCompraFavorecidoS()
    {
        return $this->hasMany(ValeCompra::class, 'codpessoa', 'codpessoafavorecido');
    }

    public function MetaFilialPessoaS()
    {
        return $this->hasMany(MetaFilialPessoa::class, 'codpessoa', 'codpessoa');
    }

    public function CobrancaHistoricoS()
    {
        return $this->hasMany(CobrancaHistorico::class, 'codpessoa', 'codpessoa');
    }

    public function CupomfiscalS()
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

    public function NegocioPessoaS()
    {
        return $this->hasMany(Negocio::class, 'codpessoa', 'codpessoa');
    }

    public function NegocioVendedorS()
    {
        return $this->hasMany(Negocio::class, 'codpessoa', 'codpessoavendedor');
    }

    public function NfeTerceiroS()
    {
        return $this->hasMany(NfeTerceiro::class, 'codpessoa', 'codpessoa');
    }

    public function NotaFiscalS()
    {
        return $this->hasMany(NotaFiscal::class, 'codpessoa', 'codpessoa');
    }

    public function RegistroSpcS()
    {
        return $this->hasMany(RegistroSpc::class, 'codpessoa', 'codpessoa');
    }

    public function TituloAgrupamentoS()
    {
        return $this->hasMany(TituloAgrupamento::class, 'codpessoa', 'codpessoa');
    }

    public function TituloS()
    {
        return $this->hasMany(Titulo::class, 'codpessoa', 'codpessoa');
    }

    public function UsuarioS()
    {
        return $this->hasMany(Usuario::class, 'codpessoa', 'codpessoa');
    }

    public function ChequeS()
    {
        return $this->hasMany(Cheque::class, 'codpessoa', 'codpessoa');
    }

    public static function getNotaFiscalOpcoes()
    {
        return array(
            self::NOTAFISCAL_TRATAMENTOPADRAO => "Tratamento Padrão",
            self::NOTAFISCAL_SEMPRE => "Sempre",
            self::NOTAFISCAL_SOMENTE_FECHAMENTO => "Somente no Fechamento",
            self::NOTAFISCAL_NUNCA => "Nunca Emitir",
        );
    }

    public function getNotaFiscalRange()
    {
        return array(
            self::NOTAFISCAL_TRATAMENTOPADRAO,
            self::NOTAFISCAL_SEMPRE,
            self::NOTAFISCAL_SOMENTE_FECHAMENTO,
            self::NOTAFISCAL_NUNCA,
        );
    }

    public function getNotaFiscalDescricao()
    {
        $opcoes = $this->getNotaFiscalOpcoes();
        if (!isset($this->notafiscal))
            return null;

        return isset($opcoes[$this->notafiscal]) ? $opcoes[$this->notafiscal] : "Tipo Desconhecido ({$this->notafiscal})";
    }

    public function totalTitulos()
    {
        $query = DB::select('
                SELECT SUM(saldo) AS saldo, MIN(vencimento) AS vencimento
                FROM tbltitulo
                WHERE codpessoa = :codpessoa AND saldo != 0',
                ['codpessoa' => $this->codpessoa]
        )[0];

        $query->vencimentodias = 0;

        if ($query->vencimento) {
            $venc = Carbon::createFromFormat("Y-m-d", $query->vencimento);
            $hoje = Carbon::now();
            $query->vencimentodias = $dif = $hoje->diffInDays($venc, false);
        }

        return $query;
    }

    public function scopeId($query, $codpessoa)
    {
        if (trim($codpessoa) === '')
            return;

        $query->where('codpessoa', $codpessoa);
    }

    public function scopePessoa($query, $pessoa)
    {
        if (trim($pessoa) === '')
            return;

        $pessoa = explode(' ', trim($pessoa));

        $query->where(function ($q1) use ($pessoa) {
            $q1->orWhere(function ($q2) use ($pessoa)
            {
                foreach ($pessoa as $str)
                    $q2->where('fantasia', 'ILIKE', "%$str%");
            });

            $q1->orWhere(function($q2) use ($pessoa)
            {
                foreach ($pessoa as $str)
                    $q2->where('pessoa', 'ILIKE', "%$str%");
            });
        });

    }


    public static function vendedoresOrdenadoPorNome()
    {
        return self::where('vendedor', true)->orderBy('pessoa', 'asc');
    }
}
