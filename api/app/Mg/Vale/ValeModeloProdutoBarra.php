<?php

namespace Mg\Vale;

use Mg\MgModel;
use Mg\Produto\ProdutoBarra;

/**
 * Item do modelo de vale compras. PK = codvalemodeloprodutobarra.
 *
 * Item de vale e quantidade x preco, sem desconto/frete/seguro/outras
 * (decisao 19 do plano). Os nomes de valor sao os mesmos de
 * tblnegocioprodutobarra, entao semear o vale a partir do modelo
 * (milestone 2) e copia campo a campo.
 *
 * NAO REGERAR com `gerador:model`: ele sobrescreve o arquivo inteiro, e este
 * model tem docblock e relacoes escritos a mao.
 */
class ValeModeloProdutoBarra extends MgModel
{
    protected $table = 'tblvalemodeloprodutobarra';
    protected $primaryKey = 'codvalemodeloprodutobarra';

    protected $fillable = [
        'codvalemodelo',
        'codprodutobarra',
        'quantidade',
        'valorunitario',
        'valorprodutos',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codprodutobarra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codvalemodelo' => 'integer',
        'codvalemodeloprodutobarra' => 'integer',
        'criacao' => 'datetime',
        'quantidade' => 'float',
        'valorprodutos' => 'float',
        'valorunitario' => 'float',
    ];

    public function ProdutoBarra()
    {
        return $this->belongsTo(ProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function ValeModelo()
    {
        return $this->belongsTo(ValeModelo::class, 'codvalemodelo', 'codvalemodelo');
    }
}
