<?php

namespace Mg\Negocio;

use Mg\MgModel;
use Mg\Produto\ProdutoBarra;

/**
 * Item de um vale emitido dentro do negocio. PK = codnegociovaleprodutobarra.
 *
 * Item de vale e' quantidade x preco, e so' (decisao 19 do plano):
 * desconto, frete, seguro, outras e juros param no cabecalho
 * (tblnegociovale) e NAO descem ate' aqui. Por isso a tabela tem metade
 * das colunas da de mercadoria — e some o rateio item a item.
 *
 * Os nomes de valor sao os mesmos de tblvalemodeloprodutobarra, entao
 * semear o vale a partir do modelo e' copia campo a campo, sem de-para.
 *
 * "inativo" e' soft-delete: na grade do vale se tira item e se ajusta
 * quantidade (decisao 15), mas nunca se apaga linha — o PDV offline
 * sincroniza por uuid e precisa da linha para contar o que saiu.
 */
class NegocioValeProdutoBarra extends MgModel
{
    protected $table = 'tblnegociovaleprodutobarra';
    protected $primaryKey = 'codnegociovaleprodutobarra';

    protected $fillable = [
        'codnegociovale',
        'codprodutobarra',
        'inativo',
        'ordenacao',
        'quantidade',
        'uuid',
        'valorprodutos',
        'valorunitario',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codnegociovale' => 'integer',
        'codnegociovaleprodutobarra' => 'integer',
        'codprodutobarra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'ordenacao' => 'datetime',
        'quantidade' => 'float',
        'valorprodutos' => 'float',
        'valorunitario' => 'float',
    ];

    // Chaves Estrangeiras
    public function NegocioVale()
    {
        return $this->belongsTo(NegocioVale::class, 'codnegociovale', 'codnegociovale');
    }

    public function ProdutoBarra()
    {
        return $this->belongsTo(ProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }
}
