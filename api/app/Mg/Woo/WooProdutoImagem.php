<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:41:28
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codprodutoimagem' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codwooproduto' => 'integer',
        'codwooprodutoimagem' => 'integer',
        'criacao' => 'datetime',
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
