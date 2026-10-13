<?php

namespace Mg\Pdv;

use Mg\MgModel;
use Mg\Usuario\Usuario;

// Historico do IP e da localizacao que o navegador informou (TASK-46): uma linha por periodo no
// mesmo lugar, de criacao (a 1a sincronizacao) a alteracao (a ultima), com quantas foram. O atual
// e' a ultima linha.
class PdvLocalizacao extends MgModel
{
    protected $table = 'tblpdvlocalizacao';
    protected $primaryKey = 'codpdvlocalizacao';


    protected $fillable = [
        'codpdv',
        'ip',
        'latitude',
        'longitude',
        'precisao',
        'sincronizacoes'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codpdv' => 'integer',
        'codpdvlocalizacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'precisao' => 'float',
        'sincronizacoes' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Pdv()
    {
        return $this->belongsTo(Pdv::class, 'codpdv', 'codpdv');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }
}
