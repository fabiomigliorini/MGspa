<?php

namespace Mg\Tributacao;

use App\Models\Usuario;
use Mg\MgModel;

class Tributacao extends MgModel
{
    protected $table = 'tbltributacao';
    protected $primaryKey = 'codtributacao';

    protected $fillable = ['aliquotaicmsecf', 'tributacao'];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'codtributacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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
