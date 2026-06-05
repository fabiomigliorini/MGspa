<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 19:33:23
 */

namespace Mg\Cultura;

use Mg\MgModel;
use Mg\Safra\Safra;
use Mg\Cultura\TabelaDesconto;
use Mg\Cultura\Variedade;

class Cultura extends MgModel
{
    protected $table = 'tblcultura';
    protected $primaryKey = 'codcultura';


    protected $fillable = [
        'cultura',
        'inativo',
        'pesosaca'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcultura' => 'integer',
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

    public function TabelaDescontoS()
    {
        return $this->hasMany(TabelaDesconto::class, 'codcultura', 'codcultura');
    }

    public function VariedadeS()
    {
        return $this->hasMany(Variedade::class, 'codcultura', 'codcultura');
    }

}
