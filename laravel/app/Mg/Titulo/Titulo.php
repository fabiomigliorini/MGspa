<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Apr/2024 12:22:23
 */

namespace Mg\Titulo;

use Mg\MgModel;
use Mg\Boleto\BoletoRetorno;
use Mg\Cheque\Cheque;
use Mg\Cobranca\Cobranca;
use Mg\Cobranca\CobrancaHistoricoTitulo;
use Mg\Titulo\MovimentoTitulo;
use Mg\NfeTerceiro\NfeTerceiroDuplicata;
use Mg\ValeCompra\ValeCompra;
use Mg\Titulo\TituloBoleto;
use Mg\Titulo\TituloNfeTerceiro;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\ContaContabil\ContaContabil;
use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;
use Mg\Portador\Portador;
use Mg\Titulo\TipoTitulo;
use Mg\Titulo\TituloAgrupamento;
use Mg\Usuario\Usuario;
use Mg\ValeCompra\ValeCompraFormaPagamento;

class Titulo extends MgModel
{
    protected $table = 'tbltitulo';
    protected $primaryKey = 'codtitulo';


    protected $fillable = [
        'boleto',
        'codcontacontabil',
        'codfilial',
        'codnegocioformapagamento',
        'codpessoa',
        'codportador',
        'codtipotitulo',
        'codtituloagrupamento',
        'codvalecompraformapagamento',
        'credito',
        'creditosaldo',
        'creditototal',
        'debito',
        'debitosaldo',
        'debitototal',
        'emissao',
        'estornado',
        'fatura',
        'gerencial',
        'nossonumero',
        'numero',
        'observacao',
        'remessa',
        'saldo',
        'sistema',
        'transacao',
        'transacaoliquidacao',
        'vencimento',
        'vencimentooriginal'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'emissao',
        'estornado',
        'sistema',
        'transacao',
        'transacaoliquidacao',
        'vencimento',
        'vencimentooriginal'
    ];

    protected $casts = [
        'boleto' => 'boolean',
        'codcontacontabil' => 'integer',
        'codfilial' => 'integer',
        'codnegocioformapagamento' => 'integer',
        'codpessoa' => 'integer',
        'codportador' => 'integer',
        'codtipotitulo' => 'integer',
        'codtitulo' => 'integer',
        'codtituloagrupamento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codvalecompraformapagamento' => 'integer',
        'credito' => 'float',
        'creditosaldo' => 'float',
        'creditototal' => 'float',
        'debito' => 'float',
        'debitosaldo' => 'float',
        'debitototal' => 'float',
        'gerencial' => 'boolean',
        'remessa' => 'integer',
        'saldo' => 'float'
    ];


    // Chaves Estrangeiras
    public function ContaContabil()
    {
        return $this->belongsTo(ContaContabil::class, 'codcontacontabil', 'codcontacontabil');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function NegocioFormaPagamento()
    {
        return $this->belongsTo(NegocioFormaPagamento::class, 'codnegocioformapagamento', 'codnegocioformapagamento');
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

    public function ValeCompraFormaPagamento()
    {
        return $this->belongsTo(ValeCompraFormaPagamento::class, 'codvalecompraformapagamento', 'codvalecompraformapagamento');
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

    public function NegocioFormaPagamentoS()
    {
        return $this->hasMany(NegocioFormaPagamento::class, 'codtitulo', 'codtitulo');
    }

    public function NfeTerceiroDuplicataS()
    {
        return $this->hasMany(NfeTerceiroDuplicata::class, 'codtitulo', 'codtitulo');
    }

    public function TituloBoletoS()
    {
        return $this->hasMany(TituloBoleto::class, 'codtitulo', 'codtitulo');
    }

    public function TituloNfeTerceiroS()
    {
        return $this->hasMany(TituloNfeTerceiro::class, 'codtitulo', 'codtitulo');
    }

    public function ValeCompraS()
    {
        return $this->hasMany(ValeCompra::class, 'codtitulo', 'codtitulo');
    }

}