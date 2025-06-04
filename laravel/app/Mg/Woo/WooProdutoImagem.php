<?php
/**
 * Created by php artisan gerador:model.
 * Date: 04/Jun/2025 10:29:55
 */

namespace Mg\Woo;

use Mg\MgModel;
use Mg\Produto\ProdutoImagem;

class WooProdutoImagem extends MgModel
{
    protected $table = 'tblwooprodutoimagem';
    protected $primaryKey = 'codwooprodutoimagem';


    protected $fillable = [
        'codwooproduto',
        'codprodutoimagem',
        'id'
    ];

    protected $dates = [
        'criacao',
        'alteracao'
    ];

    protected $casts = [
        'codwooprodutoimagem' => 'integer',
        'codwooproduto' => 'integer',
        'codprodutoimagem' => 'integer',
        'codusuariocriacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'id' => 'integer',
    ];


    // Chaves Estrangeiras
    public function WooProduto()
    {
        return $this->belongsTo(WooProduto::class, 'codwooproduto', 'codwooproduto');
    }

    public function ProdutoImagem()
    {
        return $this->belongsTo(ProdutoImagem::class, 'codprodutoimagem', 'codprodutoimagem');
    }

}