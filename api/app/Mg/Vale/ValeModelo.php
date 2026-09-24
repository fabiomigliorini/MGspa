<?php

namespace Mg\Vale;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;

/**
 * Modelo de vale compras (catalogo do kit escolar). PK = codvalemodelo.
 *
 * "modelo" e a descricao unica do kit -- nasceu da concatenacao de
 * modelo + turma + ano do catalogo antigo (ver database/vale_catalogo.sql).
 * O valor vem em tres partes: "valorprodutos" e a soma dos itens do kit,
 * "valoravulso" e digitado a mao (e o que permite modelo sem produto
 * nenhum) e "valorvale" e a soma dos dois -- a FACE do vale, o credito que
 * a emissao vai gerar. So o avulso e digitado; os outros dois o service
 * calcula.
 *
 * A face chama "valorvale" e nao "valortotal" (decisao 22): no negocio
 * "valortotal" ja quer dizer a fatia paga depois do rateio de desconto.
 * "codpessoafavorecido" e opcional: sem escola o vale e ao portador e o
 * favorecido vira Consumidor na emissao.
 *
 * NAO REGERAR com `gerador:model`: ele sobrescreve o arquivo inteiro e poe
 * TODAS as colunas no fillable. "valorprodutos" e "valorvale" ficam de fora de
 * proposito -- quem calcula os dois e o ValeModeloService.
 */
class ValeModelo extends MgModel
{
    protected $table = 'tblvalemodelo';
    protected $primaryKey = 'codvalemodelo';

    protected $appends = ['usuariocriacao', 'usuarioalteracao'];

    protected $fillable = [
        'codpessoafavorecido',
        'modelo',
        'observacoes',
        'valoravulso',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codpessoafavorecido' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codvalemodelo' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'valoravulso' => 'float',
        'valorprodutos' => 'float',
        'valorvale' => 'float',
    ];

    public function PessoaFavorecido()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoafavorecido', 'codpessoa');
    }

    public function ValeModeloProdutoBarraS()
    {
        return $this->hasMany(ValeModeloProdutoBarra::class, 'codvalemodelo', 'codvalemodelo')
            ->orderBy('codvalemodeloprodutobarra');
    }
}
