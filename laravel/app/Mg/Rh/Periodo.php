<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/Feb/2026 15:28:50
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
        'diasuteis' => 'integer',
        'percentualmaxdesconto' => 'float'
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