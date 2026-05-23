<?php
/**
 * Created by php artisan gerador:model.
 * Date: 11/Jun/2025 10:00:03
 */

namespace Mg\Woo;

use Mg\MgModel;
use Mg\Produto\ProdutoImagem;
use Mg\Woo\WooProduto;

class WooProdutoImagem extends MgModel
{
    protected $table = 'tblwooprodutoimagem';
    protected $primaryKey = 'codwooprodutoimagem';


    protected $fillable = [
        'codprodutoimagem',
        'codwooproduto',
        'id'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codprodutoimagem' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codwooproduto' => 'integer',
        'codwooprodutoimagem' => 'integer',
        'id' => 'integer'
    ];


    // Chaves Estrangeiras
    public function ProdutoImagem()
    {
        return $this->belongsTo(ProdutoImagem::class, 'codprodutoimagem', 'codprodutoimagem');
    }

    public function WooProduto()
    {
        return $this->belongsTo(WooProduto::class, 'codwooproduto', 'codwooproduto');
    }

}