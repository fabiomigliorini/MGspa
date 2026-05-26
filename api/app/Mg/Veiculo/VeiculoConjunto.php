<?php

namespace Mg\Veiculo;

use Mg\Usuario\Usuario;
use Mg\MgModel;

class VeiculoConjunto extends MgModel
{
    protected $table = 'tblveiculoconjunto';
    protected $primaryKey = 'codveiculoconjunto';

    protected $fillable = [
        'inativo',
        'veiculoconjunto',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codveiculoconjunto' => 'integer',
    ];

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function VeiculoConjuntoVeiculoS()
    {
        return $this->hasMany(VeiculoConjuntoVeiculo::class, 'codveiculoconjunto', 'codveiculoconjunto');
    }
}
