<?php
/**
 * Created by php artisan gerador:model.
 * Date: 29/Dec/2023 20:34:02
 */

namespace Mg\NaturezaOperacao;

use Mg\MgModel;
use Mg\Negocio\Negocio;
use Mg\NfeTerceiro\NfeTerceiro;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiro;
use Mg\NaturezaOperacao\TributacaoNaturezaOperacao;
use Mg\ContaContabil\ContaContabil;
use Mg\Estoque\EstoqueMovimentoTipo;
use Mg\Titulo\TipoTitulo;
use Mg\Usuario\Usuario;

class NaturezaOperacao extends MgModel
{
    const FINNFE_NORMAL = 1;
    const FINNFE_COMPLEMENTAR = 2;
    const FINNFE_AJUSTE = 3;
    const FINNFE_DEVOLUCAO_RETORNO = 4;
    protected $table = 'tblnaturezaoperacao';
    protected $primaryKey = 'codnaturezaoperacao';


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

}