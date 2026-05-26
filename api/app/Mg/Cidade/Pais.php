<?php

namespace Mg\Cidade;

use Mg\Usuario\Usuario;
use Mg\MgModel;

class Pais extends MgModel
{
    protected $table = 'tblpais';
    protected $primaryKey = 'codpais';

    protected $fillable = [
        'pais',
        'sigla',
        'codigooficial',
    ];

    protected $casts = [
        'codpais' => 'integer',
        'codigooficial' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
    ];

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function EstadoS()
    {
        return $this->hasMany(Estado::class, 'codpais', 'codpais');
    }
}
