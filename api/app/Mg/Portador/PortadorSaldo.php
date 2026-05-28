<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:41:14
 */

namespace Mg\Portador;

use Mg\MgModel;
use Mg\Portador\Portador;

class PortadorSaldo extends MgModel
{
    protected $table = 'tblportadorsaldo';
    protected $primaryKey = 'codportadorsaldo';


    protected $fillable = [
        'codportador',
        'dia',
        'saldobancario',
        'saldobancarioanterior',
        'saldobancarioatual',
        'saldobancariodisponivel'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codportador' => 'integer',
        'codportadorsaldo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'dia' => 'date',
        'saldobancario' => 'float',
        'saldobancarioanterior' => 'float',
        'saldobancarioatual' => 'float',
        'saldobancariodisponivel' => 'float'
    ];


    // Chaves Estrangeiras
    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

}
