<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/Dec/2025 10:49:43
 */

namespace Mg\NaturezaOperacao;

use Mg\MgModel;
use Mg\Negocio\Negocio;
use Mg\NfeTerceiro\NfeTerceiro;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiro;
use Mg\NaturezaOperacao\TributacaoNaturezaOperacao;
use Mg\Tributacao\TributacaoRegra;
use Mg\ContaContabil\ContaContabil;
use Mg\Estoque\EstoqueMovimentoTipo;
use Mg\Titulo\TipoTitulo;
use Mg\Usuario\Usuario;

class NaturezaOperacao extends MgModel
{
    protected $table = 'tblnaturezaoperacao';
    protected $primaryKey = 'codnaturezaoperacao';

    // Constantes para Finalidade NFe
    const FINNFE_NORMAL = 1;
    const FINNFE_COMPLEMENTAR = 2;
    const FINNFE_AJUSTE = 3;
    const FINNFE_DEVOLUCAO = 4;

    const FINNFE_DESCRICOES = [
        self::FINNFE_NORMAL => 'Normal',
        self::FINNFE_COMPLEMENTAR => 'Complementar',
        self::FINNFE_AJUSTE => 'Ajuste',
        self::FINNFE_DEVOLUCAO => 'Devolução / Retorno',
    ];

    protected $fillable = [
        'codcontacontabil',
        'codestoquemovimentotipo',
        'codnaturezaoperacaodevolucao',
        'codoperacao',
        'codtipotitulo',
        'compra',
        'emitida',
        'estoque',
        'financeiro',
        'finnfe',
        'ibpt',
        'mensagemprocom',
        'naturezaoperacao',
        'observacoesnf',
        'preco',
        'transferencia',
        'venda',
        'vendadevolucao'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codcontacontabil' => 'integer',
        'codestoquemovimentotipo' => 'integer',
        'codnaturezaoperacao' => 'integer',
        'codnaturezaoperacaodevolucao' => 'integer',
        'codoperacao' => 'integer',
        'codtipotitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'compra' => 'boolean',
        'emitida' => 'boolean',
        'estoque' => 'boolean',
        'financeiro' => 'boolean',
        'finnfe' => 'integer',
        'ibpt' => 'boolean',
        'preco' => 'integer',
        'transferencia' => 'boolean',
        'venda' => 'boolean',
        'vendadevolucao' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function ContaContabil()
    {
        return $this->belongsTo(ContaContabil::class, 'codcontacontabil', 'codcontacontabil');
    }

    public function EstoqueMovimentoTipo()
    {
        return $this->belongsTo(EstoqueMovimentoTipo::class, 'codestoquemovimentotipo', 'codestoquemovimentotipo');
    }

    public function NaturezaOperacaoDevolucao()
    {
        return $this->belongsTo(NaturezaOperacao::class, 'codnaturezaoperacaodevolucao', 'codnaturezaoperacao');
    }

    public function TipoTitulo()
    {
        return $this->belongsTo(TipoTitulo::class, 'codtipotitulo', 'codtipotitulo');
    }

    public function Operacao()
    {
        return $this->belongsTo(Operacao::class, 'codoperacao', 'codoperacao');
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
    public function NaturezaOperacaoS()
    {
        return $this->hasMany(NaturezaOperacao::class, 'codnaturezaoperacaodevolucao', 'codnaturezaoperacao');
    }

    public function NegocioS()
    {
        return $this->hasMany(Negocio::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    public function NfeTerceiroS()
    {
        return $this->hasMany(NfeTerceiro::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    public function NotaFiscalS()
    {
        return $this->hasMany(NotaFiscal::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    public function NotaFiscalTerceiroS()
    {
        return $this->hasMany(NotaFiscalTerceiro::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    public function TributacaoNaturezaOperacaoS()
    {
        return $this->hasMany(TributacaoNaturezaOperacao::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    public function TributacaoRegraS()
    {
        return $this->hasMany(TributacaoRegra::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    // Accessor para descrição da finalidade NFe
    public function getFinnfeDescricaoAttribute(): ?string
    {
        return self::FINNFE_DESCRICOES[$this->finnfe] ?? null;
    }
}