<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:29:32
 */

namespace Mg\Lio;

use Mg\MgModel;
use Mg\Lio\LioPedido;
use Mg\Usuario\Usuario;

class LioPedidoStatus extends MgModel
{
    protected $table = 'tblliopedidostatus';
    protected $primaryKey = 'codliopedidostatus';


    protected $fillable = [
        'liopedidostatus',
        'sigla'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codliopedidostatus' => 'integer',
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
    public function LioPedidoS()
    {
        return $this->hasMany(LioPedido::class, 'codliopedidostatus', 'codliopedidostatus');
    }

}
