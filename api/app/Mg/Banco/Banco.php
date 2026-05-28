<?php

/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:24:53
 */

namespace Mg\Banco;

use Mg\MgModel;
use Mg\Cheque\Cheque;
use Mg\Portador\Portador;
use Mg\Usuario\Usuario;

class Banco extends MgModel
{
    protected $table = 'tblbanco';
    protected $primaryKey = 'codbanco';


    protected $fillable = [
        'banco',
        'inativo',
        'numerobanco',
        'sigla'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codbanco' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'numerobanco' => 'integer'
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
    public function ChequeS()
    {
        return $this->hasMany(Cheque::class, 'codbanco', 'codbanco');
    }

    public function PortadorS()
    {
        return $this->hasMany(Portador::class, 'codbanco', 'codbanco');
    }
}
