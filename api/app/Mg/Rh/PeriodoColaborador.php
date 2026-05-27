<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:43:34
 */

namespace Mg\Rh;

use Mg\MgModel;
use Mg\Rh\ColaboradorRubrica;
use Mg\Rh\PeriodoColaboradorSetor;
use Mg\Colaborador\Colaborador;
use Mg\Rh\Periodo;
use Mg\Titulo\Titulo;

class PeriodoColaborador extends MgModel
{
    protected $table = 'tblperiodocolaborador';
    protected $primaryKey = 'codperiodocolaborador';


    protected $fillable = [
        'codcolaborador',
        'codperiodo',
        'codtitulo',
        'encerramento',
        'gestor',
        'status',
        'valortotal'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcolaborador' => 'integer',
        'codperiodo' => 'integer',
        'codperiodocolaborador' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'encerramento' => 'datetime',
        'gestor' => 'boolean',
        'valortotal' => 'float'
    ];


    // Chaves Estrangeiras
    public function Colaborador()
    {
        return $this->belongsTo(Colaborador::class, 'codcolaborador', 'codcolaborador');
    }

    public function Periodo()
    {
        return $this->belongsTo(Periodo::class, 'codperiodo', 'codperiodo');
    }

    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
    }


    // Tabelas Filhas
    public function ColaboradorRubricaS()
    {
        return $this->hasMany(ColaboradorRubrica::class, 'codperiodocolaborador', 'codperiodocolaborador');
    }

    public function PeriodoColaboradorSetorS()
    {
        return $this->hasMany(PeriodoColaboradorSetor::class, 'codperiodocolaborador', 'codperiodocolaborador');
    }

}
