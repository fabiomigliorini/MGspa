<?php
/**
 * Created by php artisan gerador:model.
 * Date: 11/Jun/2025 10:00:12
 */

namespace Mg\Woo;

use Mg\MgModel;
use Mg\Woo\WooProdutoImagem;
use Mg\Produto\Produto;
use Mg\Produto\ProdutoVariacao;

class WooProduto extends MgModel
{
    protected $table = 'tblwooproduto';
    protected $primaryKey = 'codwooproduto';


    protected $fillable = [
        'codproduto',
        'codprodutovariacao',
        'id'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codproduto' => 'integer',
        'codprodutovariacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codwooproduto' => 'integer',
        'id' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Produto()
    {
        return $this->belongsTo(Produto::class, 'codproduto', 'codproduto');
    }

    public function ProdutoVariacao()
    {
        return $this->belongsTo(ProdutoVariacao::class, 'codprodutovariacao', 'codprodutovariacao');
    }


    // Tabelas Filhas
    public function WooProdutoImagemS()
    {
        return $this->hasMany(WooProdutoImagem::class, 'codwooproduto', 'codwooproduto');
    }

}