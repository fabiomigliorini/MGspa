<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:38:04
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Produto\PranchetaCategoria;
use Mg\Produto\ProdutoBarra;

class Prancheta extends MgModel
{
    protected $table = 'tblprancheta';
    protected $primaryKey = 'codprancheta';


    protected $fillable = [
        'codpranchetacategoria',
        'codprodutobarra',
        'descricao',
        'observacoes',
        'ordem'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codprancheta' => 'integer',
        'codpranchetacategoria' => 'integer',
        'codprodutobarra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'ordem' => 'integer'
    ];


    // Chaves Estrangeiras
    public function PranchetaCategoria()
    {
        return $this->belongsTo(PranchetaCategoria::class, 'codpranchetacategoria', 'codpranchetacategoria');
    }

    public function ProdutoBarra()
    {
        return $this->belongsTo(ProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

}
