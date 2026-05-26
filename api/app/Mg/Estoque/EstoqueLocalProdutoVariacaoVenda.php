<?php

namespace Mg\Estoque;

use Mg\MgModel;

class EstoqueLocalProdutoVariacaoVenda extends MgModel
{
    protected $table = 'tblestoquelocalprodutovariacaovenda';
    protected $primaryKey = 'codestoquelocalprodutovariacaovenda';
    protected $fillable = [
        'codestoquelocalprodutovariacao',
        'mes',
        'quantidade',
        'valor',
        'ignorar',
        'vendadiaquantidade',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
        'mes',
    ];

    // Chaves Estrangeiras
    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function EstoqueLocalProdutoVariacao()
    {
        return $this->belongsTo(EstoqueLocalProdutoVariacao::class, 'codestoquelocalprodutovariacao', 'codestoquelocalprodutovariacao');
    }


}
