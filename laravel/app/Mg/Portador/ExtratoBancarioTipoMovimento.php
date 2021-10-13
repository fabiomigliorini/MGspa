<?php
/**
 * Created by php artisan gerador:model.
 * Date: 13/Oct/2021 14:25:11
 */

namespace Mg\Portador;

use Mg\MgModel;
use Mg\Portador\ExtratoBancario;
use Mg\Usuario\Usuario;

class ExtratoBancarioTipoMovimento extends MgModel
{
    protected $table = 'tblextratobancariotipomovimento';
    protected $primaryKey = 'codextratobancariotipomovimento';


    protected $fillable = [
        'sigla',
        'tipo',
        'trntype'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codextratobancariotipomovimento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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
    public function ExtratoBancarioS()
    {
        return $this->hasMany(ExtratoBancario::class, 'codextratobancariotipomovimento', 'codextratobancariotipomovimento');
    }

}