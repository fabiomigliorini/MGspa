<?php

namespace Mg\Colaborador;

use App\Models\Usuario;
use Mg\MgModel;

class Cargo extends MgModel
{
    protected $table = 'tblcargo';
    protected $primaryKey = 'codcargo';

    protected $fillable = [
        'adicional',
        'cargo',
        'comissaocaixa',
        'inativo',
        'salario',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'adicional' => 'float',
        'codcargo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'comissaocaixa' => 'float',
        'salario' => 'float',
    ];

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }
}
