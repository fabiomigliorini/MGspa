<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Feb/2026 16:58:19
 */

namespace Mg\Rh;

use Mg\MgModel;
use Mg\Rh\Indicador;
use Mg\Rh\PeriodoColaborador;

class Periodo extends MgModel
{
    protected $table = 'tblperiodo';
    protected $primaryKey = 'codperiodo';


    protected $fillable = [
        'diasuteis',
        'observacoes',
        'periodofinal',
        'periodoinicial',
        'status'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'periodofinal',
        'periodoinicial'
    ];

    protected $casts = [
        'codperiodo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'diasuteis' => 'integer'
    ];


    // Tabelas Filhas
    public function IndicadorS()
    {
        return $this->hasMany(Indicador::class, 'codperiodo', 'codperiodo');
    }

    public function PeriodoColaboradorS()
    {
        return $this->hasMany(PeriodoColaborador::class, 'codperiodo', 'codperiodo');
    }

}