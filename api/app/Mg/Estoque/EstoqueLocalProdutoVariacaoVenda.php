<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:39:02
 */

namespace Mg\Estoque;

use Mg\MgModel;
use Mg\Estoque\EstoqueLocalProdutoVariacao;
use Mg\Usuario\Usuario;

class EstoqueLocalProdutoVariacaoVenda extends MgModel
{
    protected $table = 'tblestoquelocalprodutovariacaovenda';
    protected $primaryKey = 'codestoquelocalprodutovariacaovenda';


    protected $fillable = [
        'codestoquelocalprodutovariacao',
        'ignorar',
        'mes',
        'quantidade',
        'valor',
        'vendadiaquantidade'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codestoquelocalprodutovariacao' => 'integer',
        'codestoquelocalprodutovariacaovenda' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'ignorar' => 'boolean',
        'mes' => 'date',
        'quantidade' => 'float',
        'valor' => 'float',
        'vendadiaquantidade' => 'float'
    ];


    // Chaves Estrangeiras
    public function EstoqueLocalProdutoVariacao()
    {
        return $this->belongsTo(EstoqueLocalProdutoVariacao::class, 'codestoquelocalprodutovariacao', 'codestoquelocalprodutovariacao');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

}
