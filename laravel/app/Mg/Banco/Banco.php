<?php
/**
 * Created by php artisan gerador:model.
 * Date: 13/Oct/2021 14:31:37
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

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codbanco' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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