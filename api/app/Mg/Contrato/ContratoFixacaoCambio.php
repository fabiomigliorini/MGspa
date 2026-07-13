<?php

namespace Mg\Contrato;

use Mg\MgModel;
use Mg\Contrato\ContratoFixacao;

/**
 * Travamento de cambio de uma fixacao dolarizada: 1 fixacao : N travas.
 * Cada trava fixa a cotacao (R$/moeda) de uma fatia do valor em moeda
 * estrangeira. Total = 1 linha; parcial = N linhas. A moeda e HERDADA da
 * fixacao (tblcontratofixacao.codmoeda) — a trava so guarda valor + cotacao.
 *
 * Toda operacao aqui dispara ContratoFixacaoService::recalcular() na fixacao-pai
 * (regrava totalmoeda/saldomoeda/totalbrl/liquidobrl).
 */
class ContratoFixacaoCambio extends MgModel
{
    protected $table = 'tblcontratofixacaocambio';
    protected $primaryKey = 'codcontratofixacaocambio';

    protected $appends = ['usuariocriacao', 'usuarioalteracao'];

    protected $fillable = [
        'codcontratofixacao',
        'data',
        'valor',
        'cotacao',
        'observacao',
        'inativo',
    ];

    protected $casts = [
        'codcontratofixacaocambio' => 'integer',
        'codcontratofixacao' => 'integer',
        'data' => 'date',
        'valor' => 'float',
        'cotacao' => 'float',
        'inativo' => 'datetime',
        'criacao' => 'datetime',
        'alteracao' => 'datetime',
        'codusuariocriacao' => 'integer',
        'codusuarioalteracao' => 'integer',
    ];

    // Chaves Estrangeiras
    public function ContratoFixacao()
    {
        return $this->belongsTo(ContratoFixacao::class, 'codcontratofixacao', 'codcontratofixacao');
    }
}
