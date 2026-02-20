<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Feb/2026 16:58:50
 */

namespace Mg\Rh;

use Mg\MgModel;
use Mg\Rh\Indicador;
use Mg\Rh\PeriodoColaborador;
use Mg\Rh\PeriodoColaboradorSetor;

class ColaboradorRubrica extends MgModel
{
    protected $table = 'tblcolaboradorrubrica';
    protected $primaryKey = 'codcolaboradorrubrica';


    protected $fillable = [
        'codindicador',
        'codindicadorcondicao',
        'codperiodocolaborador',
        'codperiodocolaboradorsetor',
        'concedido',
        'descontaabsenteismo',
        'descricao',
        'percentual',
        'recorrente',
        'tipocondicao',
        'tipovalor',
        'valorcalculado',
        'valorfixo'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codcolaboradorrubrica' => 'integer',
        'codindicador' => 'integer',
        'codindicadorcondicao' => 'integer',
        'codperiodocolaborador' => 'integer',
        'codperiodocolaboradorsetor' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'concedido' => 'boolean',
        'descontaabsenteismo' => 'boolean',
        'percentual' => 'float',
        'recorrente' => 'boolean',
        'valorcalculado' => 'float',
        'valorfixo' => 'float'
    ];


    // Chaves Estrangeiras
    public function Indicador()
    {
        return $this->belongsTo(Indicador::class, 'codindicador', 'codindicador');
    }

    public function IndicadorCondicao()
    {
        return $this->belongsTo(Indicador::class, 'codindicadorcondicao', 'codindicador');
    }

    public function PeriodoColaborador()
    {
        return $this->belongsTo(PeriodoColaborador::class, 'codperiodocolaborador', 'codperiodocolaborador');
    }

    public function PeriodoColaboradorSetor()
    {
        return $this->belongsTo(PeriodoColaboradorSetor::class, 'codperiodocolaboradorsetor', 'codperiodocolaboradorsetor');
    }

}