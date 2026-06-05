<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 22:20:40
 */

namespace Mg\Safra;

use Mg\MgModel;
use Mg\Safra\CargaColheitaPlantio;
use Mg\Safra\Safra;

class CargaColheita extends MgModel
{
    protected $table = 'tblcargacolheita';
    protected $primaryKey = 'codcargacolheita';


    protected $fillable = [
        'avariados',
        'codsafra',
        'data',
        'descontoavariados',
        'descontoimpureza',
        'descontoumidade',
        'etapa',
        'impureza',
        'inativo',
        'motorista',
        'observacao',
        'pesobruto',
        'pesoliquido',
        'pesoliquidoseco',
        'placa',
        'tara',
        'umidade',
        'uuid'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'avariados' => 'float',
        'codcargacolheita' => 'integer',
        'codsafra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'data' => 'datetime',
        'descontoavariados' => 'float',
        'descontoimpureza' => 'float',
        'descontoumidade' => 'float',
        'impureza' => 'float',
        'inativo' => 'datetime',
        'pesobruto' => 'float',
        'pesoliquido' => 'float',
        'pesoliquidoseco' => 'float',
        'tara' => 'float',
        'umidade' => 'float'
    ];


    // Chaves Estrangeiras
    public function Safra()
    {
        return $this->belongsTo(Safra::class, 'codsafra', 'codsafra');
    }


    // Tabelas Filhas
    public function CargaColheitaPlantioS()
    {
        return $this->hasMany(CargaColheitaPlantio::class, 'codcargacolheita', 'codcargacolheita');
    }

}
