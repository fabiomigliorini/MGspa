<?php
/**
 * Created by php artisan gerador:model.
 * Date: 24/Sep/2021 15:12:40
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
        'tipoofx'
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