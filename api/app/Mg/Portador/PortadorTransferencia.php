<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:35:19
 */

namespace Mg\Portador;

use Mg\MgModel;
use Mg\Portador\PortadorMovimento;
use Mg\Portador\Portador;

class PortadorTransferencia extends MgModel
{
    protected $table = 'tblportadortransferencia';
    protected $primaryKey = 'codportadortransferencia';


    protected $fillable = [
        'codportadordestino',
        'codportadororigem',
        'inativo',
        'lancamento',
        'valor'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codportadordestino' => 'integer',
        'codportadororigem' => 'integer',
        'codportadortransferencia' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'lancamento' => 'datetime',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function PortadorDestino()
    {
        return $this->belongsTo(Portador::class, 'codportadordestino', 'codportador');
    }

    public function PortadorOrigem()
    {
        return $this->belongsTo(Portador::class, 'codportadororigem', 'codportador');
    }


    // Tabelas Filhas
    public function PortadorMovimentoS()
    {
        return $this->hasMany(PortadorMovimento::class, 'codportadortransferencia', 'codportadortransferencia');
    }

}
