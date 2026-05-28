<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:38:10
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Produto\Prancheta;
use Mg\Produto\PranchetaCategoria;

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

    protected $casts = [
        'alteracao' => 'datetime',
        'codpranchetacategoria' => 'integer',
        'codpranchetacategoriapai' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'ordem' => 'integer'
    ];


    // Chaves Estrangeiras
    public function PranchetaCategoriaPai()
    {
        return $this->belongsTo(PranchetaCategoria::class, 'codpranchetacategoriapai', 'codpranchetacategoria');
    }


    // Tabelas Filhas
    public function PranchetaS()
    {
        return $this->hasMany(Prancheta::class, 'codpranchetacategoria', 'codpranchetacategoria');
    }

    public function PranchetaCategoriaPaiS()
    {
        return $this->hasMany(PranchetaCategoria::class, 'codpranchetacategoriapai', 'codpranchetacategoria');
    }

}
