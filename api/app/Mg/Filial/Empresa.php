<?php

namespace Mg\Filial;

use App\Models\Usuario;
use Mg\MgModel;

class Empresa extends MgModel
{
    const MODOEMISSAONFCE_NORMAL = 1;
    const MODOEMISSAONFCE_OFFLINE = 9;

    protected $table = 'tblempresa';
    protected $primaryKey = 'codempresa';

    protected $fillable = [
        'contingenciadata',
        'contingenciajustificativa',
        'empresa',
        'fatorencargos',
        'modoemissaonfce',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'contingenciadata' => 'datetime',
        'criacao' => 'datetime',
        'codempresa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'fatorencargos' => 'float',
        'modoemissaonfce' => 'integer',
    ];

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function FilialS()
    {
        return $this->hasMany(Filial::class, 'codempresa', 'codempresa');
    }
}
