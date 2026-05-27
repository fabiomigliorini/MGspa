<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:43:27
 */

namespace Mg\Rh;

use Mg\MgModel;
use Mg\Rh\Indicador;
use Mg\Titulo\LiquidacaoTitulo;
use Mg\Rh\PeriodoColaborador;

class Periodo extends MgModel
{
    protected $table = 'tblperiodo';
    protected $primaryKey = 'codperiodo';


    protected $fillable = [
        'diasuteis',
        'observacoes',
        'percentualmaxdesconto',
        'periodofinal',
        'periodoinicial',
        'status'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codperiodo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'diasuteis' => 'integer',
        'percentualmaxdesconto' => 'float',
        'periodofinal' => 'datetime',
        'periodoinicial' => 'datetime'
    ];


    // Tabelas Filhas
    public function IndicadorS()
    {
        return $this->hasMany(Indicador::class, 'codperiodo', 'codperiodo');
    }

    public function LiquidacaoTituloS()
    {
        return $this->hasMany(LiquidacaoTitulo::class, 'codperiodo', 'codperiodo');
    }

    public function PeriodoColaboradorS()
    {
        return $this->hasMany(PeriodoColaborador::class, 'codperiodo', 'codperiodo');
    }

}
