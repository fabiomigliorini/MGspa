<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Feb/2026 16:58:10
 */

namespace Mg\Filial;

use Mg\MgModel;
use Mg\Rh\Indicador;
use Mg\Pdv\Pdv;
use Mg\Rh\PeriodoColaboradorSetor;
use Mg\Filial\TipoSetor;
use Mg\Filial\UnidadeNegocio;

class Setor extends MgModel
{
    protected $table = 'tblsetor';
    protected $primaryKey = 'codsetor';


    protected $fillable = [
        'codtiposetor',
        'codunidadenegocio',
        'inativo',
        'indicadorcaixa',
        'indicadorcoletivo',
        'indicadorvendedor',
        'setor'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codsetor' => 'integer',
        'codtiposetor' => 'integer',
        'codunidadenegocio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'indicadorcaixa' => 'boolean',
        'indicadorcoletivo' => 'boolean',
        'indicadorvendedor' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function TipoSetor()
    {
        return $this->belongsTo(TipoSetor::class, 'codtiposetor', 'codtiposetor');
    }

    public function UnidadeNegocio()
    {
        return $this->belongsTo(UnidadeNegocio::class, 'codunidadenegocio', 'codunidadenegocio');
    }


    // Tabelas Filhas
    public function IndicadorS()
    {
        return $this->hasMany(Indicador::class, 'codsetor', 'codsetor');
    }

    public function PdvS()
    {
        return $this->hasMany(Pdv::class, 'codsetor', 'codsetor');
    }

    public function PeriodoColaboradorSetorS()
    {
        return $this->hasMany(PeriodoColaboradorSetor::class, 'codsetor', 'codsetor');
    }

}