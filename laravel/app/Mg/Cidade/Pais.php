<?php

namespace Mg\Cidade;

use Mg\MgModel;

class Pais extends MGModel
{
    protected $table = 'tblpais';
    protected $primaryKey = 'codpais';
    protected $fillable = [
        'pais',
        'sigla',
        'codigooficial',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
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

    // Tabelas Filhas
    public function EstadoS()
    {
        return $this->hasMany(Estado::class, 'codpais', 'codpais');
    }

}
