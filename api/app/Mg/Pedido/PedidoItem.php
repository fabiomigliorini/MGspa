<?php

namespace Mg\Pedido;

use Mg\Usuario\Usuario;
use Mg\MgModel;

class PedidoItem extends MgModel
{
    public const STATUS_PENDENTE = 10;
    public const STATUS_ATENDIDO = 20;
    public const STATUS_CANCELADO = 90;

    protected $table = 'tblpedidoitem';
    protected $primaryKey = 'codpedidoitem';

    protected $fillable = [
        'codpedido',
        'codprodutovariacao',
        'indstatus',
        'quantidade',
    ];

    protected $casts = [
        'codpedido' => 'integer',
        'codpedidoitem' => 'integer',
        'codprodutovariacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'indstatus' => 'integer',
        'quantidade' => 'float',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
    ];

    public function Pedido()
    {
        return $this->belongsTo(Pedido::class, 'codpedido', 'codpedido');
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
