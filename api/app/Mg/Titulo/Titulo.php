<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:21:15
 */

namespace Mg\Titulo;

use Mg\MgModel;
use Mg\Boleto\BoletoRetorno;
use Mg\Cheque\Cheque;
use Mg\Cobranca\Cobranca;
use Mg\Cobranca\CobrancaHistoricoTitulo;
use Mg\Titulo\MovimentoTitulo;
use Mg\Negocio\NegocioParcela;
use Mg\Pagamento\Pagamento;
use Mg\NfeTerceiro\NfeTerceiroDuplicata;
use Mg\Rh\PeriodoColaborador;
use Mg\Titulo\TituloBoleto;
use Mg\Titulo\TituloNfeTerceiro;
use Mg\ContaContabil\ContaContabil;
use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;
use Mg\Portador\Portador;
use Mg\Titulo\TipoTitulo;
use Mg\Titulo\TituloAgrupamento;
use Mg\Usuario\Usuario;

class Titulo extends MgModel
{
    protected $table = 'tbltitulo';
    protected $primaryKey = 'codtitulo';


    protected $fillable = [
        'boleto',
        'codcontacontabil',
        'codfilial',
        'codnegocioparcela',
        'codpessoa',
        'codportador',
        'codtipotitulo',
        'codtituloagrupamento',
        'emissao',
        'estornado',
        'fatura',
        'gerencial',
        'nossonumero',
        'numero',
        'observacao',
        'remessa',
        'saldo',
        'transacao',
        'transacaoliquidacao',
        'valor',
        'vencimento',
        'vencimentooriginal'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'boleto' => 'boolean',
        'codcontacontabil' => 'integer',
        'codfilial' => 'integer',
        'codnegocioparcela' => 'integer',
        'codpessoa' => 'integer',
        'codportador' => 'integer',
        'codtipotitulo' => 'integer',
        'codtitulo' => 'integer',
        'codtituloagrupamento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'emissao' => 'date',
        'estornado' => 'datetime',
        'gerencial' => 'boolean',
        'remessa' => 'integer',
        'saldo' => 'float',
        'transacao' => 'date',
        'transacaoliquidacao' => 'date',
        'valor' => 'float',
        'vencimento' => 'date',
        'vencimentooriginal' => 'date'
    ];


    /**
     * Título a receber? Valor e saldo têm sinal: positivo é a receber,
     * negativo é a pagar. Basta um dos dois negativo para ser a pagar — um
     * a receber pago a maior vira crédito do cliente.
     */
    public function ehReceber(): bool
    {
        return !((float) $this->saldo < 0 || (float) $this->valor < 0);
    }


    // Chaves Estrangeiras
    public function ContaContabil()
    {
        return $this->belongsTo(ContaContabil::class, 'codcontacontabil', 'codcontacontabil');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function NegocioParcela()
    {
        return $this->belongsTo(NegocioParcela::class, 'codnegocioparcela', 'codnegocioparcela');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

    public function TipoTitulo()
    {
        return $this->belongsTo(TipoTitulo::class, 'codtipotitulo', 'codtipotitulo');
    }

    public function TituloAgrupamento()
    {
        return $this->belongsTo(TituloAgrupamento::class, 'codtituloagrupamento', 'codtituloagrupamento');
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
    public function BoletoRetornoS()
    {
        return $this->hasMany(BoletoRetorno::class, 'codtitulo', 'codtitulo');
    }

    public function ChequeS()
    {
        return $this->hasMany(Cheque::class, 'codtitulo', 'codtitulo');
    }

    public function CobrancaS()
    {
        return $this->hasMany(Cobranca::class, 'codtitulo', 'codtitulo');
    }

    public function CobrancaHistoricoTituloS()
    {
        return $this->hasMany(CobrancaHistoricoTitulo::class, 'codtitulo', 'codtitulo');
    }

    public function MovimentoTituloS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codtitulo', 'codtitulo');
    }

    public function MovimentoTituloRelacionadoS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codtitulorelacionado', 'codtitulo');
    }

    // vale consumido como pagamento
    public function PagamentoS()
    {
        return $this->hasMany(Pagamento::class, 'codtitulo', 'codtitulo');
    }

    public function NfeTerceiroDuplicataS()
    {
        return $this->hasMany(NfeTerceiroDuplicata::class, 'codtitulo', 'codtitulo');
    }

    public function PeriodoColaboradorS()
    {
        return $this->hasMany(PeriodoColaborador::class, 'codtitulo', 'codtitulo');
    }

    public function TituloBoletoS()
    {
        return $this->hasMany(TituloBoleto::class, 'codtitulo', 'codtitulo');
    }

    public function TituloNfeTerceiroS()
    {
        return $this->hasMany(TituloNfeTerceiro::class, 'codtitulo', 'codtitulo');
    }

}
