<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:29:12
 */

namespace Mg\Negocio;

use Mg\MgModel;
use Mg\Negocio\Negocio;
use Mg\Usuario\Usuario;

class NegocioStatus extends MgModel
{
    const ABERTO = 1;
    const FECHADO = 2;
    const CANCELADO = 3;

    protected $table = 'tblnegociostatus';
    protected $primaryKey = 'codnegociostatus';


    protected $fillable = [
        'negociostatus'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codnegociostatus' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime'
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
    public function NegocioS()
    {
        return $this->hasMany(Negocio::class, 'codnegociostatus', 'codnegociostatus');
    }

}
