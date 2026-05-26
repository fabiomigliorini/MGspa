<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Feb/2026 16:58:28
 */

namespace Mg\Rh;

use Mg\MgModel;
use Mg\Rh\ColaboradorRubrica;
use Mg\Rh\IndicadorLancamento;
use Mg\Colaborador\Colaborador;
use Mg\Rh\Periodo;
use Mg\Filial\Setor;
use Mg\Filial\UnidadeNegocio;

class Indicador extends MgModel
{
    protected $table = 'tblindicador';
    protected $primaryKey = 'codindicador';


    protected $fillable = [
        'codcolaborador',
        'codperiodo',
        'codsetor',
        'codunidadenegocio',
        'meta',
        'tipo',
        'valoracumulado'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codcolaborador' => 'integer',
        'codindicador' => 'integer',
        'codperiodo' => 'integer',
        'codsetor' => 'integer',
        'codunidadenegocio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'meta' => 'float',
        'valoracumulado' => 'float'
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

    public function Setor()
    {
        return $this->belongsTo(Setor::class, 'codsetor', 'codsetor');
    }

    public function UnidadeNegocio()
    {
        return $this->belongsTo(UnidadeNegocio::class, 'codunidadenegocio', 'codunidadenegocio');
    }


    // Tabelas Filhas
    public function ColaboradorRubricaS()
    {
        return $this->hasMany(ColaboradorRubrica::class, 'codindicador', 'codindicador');
    }

    public function ColaboradorRubricaCondicaoS()
    {
        return $this->hasMany(ColaboradorRubrica::class, 'codindicadorcondicao', 'codindicador');
    }

    public function IndicadorLancamentoS()
    {
        return $this->hasMany(IndicadorLancamento::class, 'codindicador', 'codindicador');
    }

}