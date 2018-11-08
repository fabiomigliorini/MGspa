<?php

namespace Mg\NaturezaOperacao;

use Mg\MgModel;
use Mg\Estoque\EstoqueMovimentoTipo;
use Mg\NotaFiscal\NotaFiscal;

class NaturezaOperacao extends MGModel
{

    const FINNFE_NORMAL = 1;
    const FINNFE_COMPLEMENTAR = 2;
    const FINNFE_AJUSTE = 3;
    const FINNFE_DEVOLUCAO_RETORNO = 4;

    protected $table = 'tblnaturezaoperacao';
    protected $primaryKey = 'codnaturezaoperacao';
    protected $fillable = [
        'naturezaoperacao',
        'codoperacao',
        'observacoesnf',
        'codusuarioalteracao',
        'codusuariocriacao',
        'mensagemprocom',
        'codnaturezaoperacaodevolucao',
        'codtipotitulo',
        'codcontacontabil',
        'codestoquemovimentotipo'
    ];
    protected $dates = [
        'alteracao',
        'criacao',
    ];

    public function NaturezaOperacaoDevolucao()
    {
        return $this->belongsTo(NaturezaOperacao::class, 'codnaturezaoperacaodevolucao', 'codnaturezaoperacao');
    }

    public function TipoTitulo()
    {
        return $this->belongsTo(TipoTitulo::class, 'codtipotitulo', 'codtipotitulo');
    }

    public function ContaContabil()
    {
        return $this->belongsTo(TipoTitulo::class, 'codcontacontabil', 'codcontacontabil');
    }

    public function EstoqueMovimentoTipo()
    {
        return $this->belongsTo(EstoqueMovimentoTipo::class, 'codestoquemovimentotipo', 'codestoquemovimentotipo');
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
    public function NotaFiscalS()
    {
        return $this->hasMany(NotaFiscal::class, 'codoperacao', 'codoperacao');
    }

    public function NegocioS()
    {
        return $this->hasMany(Negocio::class, 'codoperacao', 'codoperacao');
    }

    public function NfeTerceiroS()
    {
        return $this->hasMany(NfeTerceiro::class, 'codoperacao', 'codoperacao');
    }

    public function TributacaoNaturezaOperacaoS()
    {
        return $this->hasMany(TributacaoNaturezaOperacao::class, 'codoperacao', 'codoperacao');
    }

    public function NaturezaOperacaoDevolucaoS()
    {
        return $this->hasMany(NaturezaOperacao::class, 'codnaturezaoperacaodevolucao', 'codnaturezaoperacao');
    }

}
