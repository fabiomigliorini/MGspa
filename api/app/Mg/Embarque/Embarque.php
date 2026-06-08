<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 23:47:46
 */

namespace Mg\Embarque;

use Mg\MgModel;
use Mg\Embarque\EmbarqueContrato;
use Mg\Embarque\EmbarqueOrigem;

class Embarque extends MgModel
{
    protected $table = 'tblembarque';
    protected $primaryKey = 'codembarque';


    protected $fillable = [
        'aprovado',
        'avariados',
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
        'pesotara',
        'placa',
        'placacarreta',
        'umidade',
        'uuid'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'aprovado' => 'datetime',
        'avariados' => 'float',
        'codembarque' => 'integer',
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
        'pesotara' => 'float',
        'umidade' => 'float'
    ];


    // Tabelas Filhas
    public function EmbarqueContratoS()
    {
        return $this->hasMany(EmbarqueContrato::class, 'codembarque', 'codembarque');
    }

    public function EmbarqueOrigemS()
    {
        return $this->hasMany(EmbarqueOrigem::class, 'codembarque', 'codembarque');
    }

}
