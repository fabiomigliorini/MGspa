<?php
/**
 * Created by php artisan gerador:model.
 * Date: 22/Sep/2025 17:30:56
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
        'exportacao',
        'id',
        'inativo',
        'integracao'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'exportacao',
        'inativo'
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