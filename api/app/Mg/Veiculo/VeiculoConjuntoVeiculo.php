<?php

namespace Mg\Veiculo;

use Mg\MgModel;

class VeiculoConjuntoVeiculo extends MgModel
{
    protected $table = 'tblveiculoconjuntoveiculo';
    protected $primaryKey = 'codveiculoconjuntoveiculo';

    protected $fillable = [
        'codveiculo',
        'codveiculoconjunto',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'codveiculoconjuntoveiculo' => 'integer',
        'codveiculo' => 'integer',
        'codveiculoconjunto' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
    ];

    public function Veiculo()
    {
        return $this->belongsTo(Veiculo::class, 'codveiculo', 'codveiculo');
    }

    public function VeiculoConjunto()
    {
        return $this->belongsTo(VeiculoConjunto::class, 'codveiculoconjunto', 'codveiculoconjunto');
    }
}
