<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:43:40
 */

namespace Mg\Rh;

use Mg\MgModel;
use Mg\Rh\Indicador;
use Mg\Rh\PeriodoColaborador;
use Mg\Rh\Rubrica;

class ColaboradorRubrica extends MgModel
{
    protected $table = 'tblcolaboradorrubrica';
    protected $primaryKey = 'codcolaboradorrubrica';


    protected $fillable = [
        'codrubrica',
        'codindicador',
        'codindicadorcondicao',
        'codperiodocolaborador',
        'concedido',
        'descricao',
        'observacao',
        'percentual',
        'quantidade',
        'recorrente',
        'tipocondicao',
        'tipovalor',
        'valorcalculado',
        'valorfixo',
        'valorunitario',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcolaboradorrubrica' => 'integer',
        'codindicador' => 'integer',
        'codindicadorcondicao' => 'integer',
        'codperiodocolaborador' => 'integer',
        'codrubrica' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'concedido' => 'boolean',
        'criacao' => 'datetime',
        'percentual' => 'float',
        'quantidade' => 'float',
        'recorrente' => 'boolean',
        'valorcalculado' => 'float',
        'valorfixo' => 'float',
        'valorunitario' => 'float',
    ];


    // Chaves Estrangeiras
    public function Rubrica()
    {
        return $this->belongsTo(Rubrica::class, 'codrubrica', 'codrubrica');
    }

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
}
