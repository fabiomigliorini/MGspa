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
 * nenhum) e "valortotal" e a soma dos dois -- a FACE do vale, o credito
 * que a emissao vai gerar. So o avulso e digitado; os outros dois o
 * service calcula.
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
        'valortotal' => 'float',
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
