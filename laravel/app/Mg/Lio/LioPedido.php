<?php
/**
 * Created by php artisan gerador:model.
 * Date: 14/Nov/2020 14:07:58
 */

namespace Mg\Lio;

use Mg\MgModel;
use Mg\Lio\LioPedidoPagamento;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Lio\LioPedidoStatus;
use Mg\Usuario\Usuario;

class LioPedido extends MgModel
{
    protected $table = 'tblliopedido';
    protected $primaryKey = 'codliopedido';


    protected $fillable = [
        'codliopedidostatus',
        'referencia',
        'uuid',
        'valorpago',
        'valorsaldo',
        'valortotal'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codliopedido' => 'integer',
        'codliopedidostatus' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'valorpago' => 'float',
        'valorsaldo' => 'float',
        'valortotal' => 'float'
    ];


    // Chaves Estrangeiras
    public function LioPedidoStatus()
    {
        return $this->belongsTo(LioPedidoStatus::class, 'codliopedidostatus', 'codliopedidostatus');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function LioPedidoPagamentoS()
    {
        return $this->hasMany(LioPedidoPagamento::class, 'codliopedido', 'codliopedido');
    }

    public function NegocioFormaPagamentoS()
    {
        return $this->hasMany(NegocioFormaPagamento::class, 'codliopedido', 'codliopedido');
    }

}