<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 19:33:23
 */

namespace Mg\Cultura;

use Mg\MgModel;
use Mg\Safra\Safra;
use Mg\Classificacao\TabelaClassificacao;
use Mg\Cultura\Variedade;
use Mg\Cultura\CulturaTributo;

class Cultura extends MgModel
{
    protected $table = 'tblcultura';
    protected $primaryKey = 'codcultura';



    protected $fillable = [
        'cultura',
        'inativo',
        'pesosaca',
        'icone',
        'cicloanos',
        'codtabelaclassificacao'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'cicloanos' => 'integer',
        'codcultura' => 'integer',
        'codtabelaclassificacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'pesosaca' => 'float'
    ];


    // Tabelas Filhas
    public function SafraS()
    {
        return $this->hasMany(Safra::class, 'codcultura', 'codcultura');
    }

    public function TabelaClassificacao()
    {
        return $this->belongsTo(TabelaClassificacao::class, 'codtabelaclassificacao', 'codtabelaclassificacao');
    }

    public function TabelaClassificacaoS()
    {
        return $this->hasMany(TabelaClassificacao::class, 'codcultura', 'codcultura');
    }

    public function VariedadeS()
    {
        return $this->hasMany(Variedade::class, 'codcultura', 'codcultura');
    }

    public function CulturaTributoS()
    {
        return $this->hasMany(CulturaTributo::class, 'codcultura', 'codcultura');
    }

}
