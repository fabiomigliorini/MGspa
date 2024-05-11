<?php
/**
 * Created by php artisan gerador:model.
 * Date: 11/May/2024 16:20:24
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Produto\Prancheta;

class PranchetaCategoria extends MgModel
{
    protected $table = 'tblpranchetacategoria';
    protected $primaryKey = 'codpranchetacategoria';


    protected $fillable = [
        'categoria',
        'codpranchetacategoriapai',
        'imagem',
        'observacoes',
        'ordem'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codpranchetacategoria' => 'integer',
        'codpranchetacategoriapai' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'ordem' => 'integer'
    ];


    // Tabelas Filhas
    public function PranchetaS()
    {
        return $this->hasMany(Prancheta::class, 'codpranchetacategoria', 'codpranchetacategoria');
    }

}