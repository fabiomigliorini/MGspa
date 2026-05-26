<?php
/**
 * Created by php artisan gerador:model.
 * Date: 19/May/2025 15:57:08
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

    protected $dates = [
        'alteracao',
        'criacao',
        'dia'
    ];

    protected $casts = [
        'codportador' => 'integer',
        'codportadorsaldo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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