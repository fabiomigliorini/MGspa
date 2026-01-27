<?php

namespace Mg\Tributacao;

use Mg\MgModel;
use Mg\Usuario\Usuario;

class Tributacao extends MgModel
{
    protected $table = 'tbltributacao';
    protected $primaryKey = 'codtributacao';

    protected $fillable = [
        'tributacao',
        'aliquotaicmsecf',
    ];

    protected $dates = [
        'alteracao',
        'criacao',
    ];

    protected $casts = [
        'codtributacao' => 'integer',
        'aliquotaicmsecf' => 'float',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
    ];

    // Chaves Estrangeiras
    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }
}
