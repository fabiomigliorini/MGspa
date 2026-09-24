<?php

namespace Mg\Vale;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;

/**
 * Modelo de vale compras (catalogo do kit escolar). PK = codvalemodelo.
 *
 * "modelo" e a descricao unica do kit -- nasceu da concatenacao de
 * modelo + turma + ano do catalogo antigo (ver database/vale_catalogo.sql).
 * "valorprodutos" e a face do kit: soma dos itens, e o crédito que o vale
 * emitido vai gerar.
 * "codpessoafavorecido" e opcional: sem escola o vale e ao portador e o
 * favorecido vira Consumidor na emissao.
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
        'valorprodutos',
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
        'valorprodutos' => 'float',
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
