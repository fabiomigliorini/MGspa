<?php

namespace Mg\Pedido;

use Mg\MgModel;

class PedidoItem extends MGModel
{
    const STATUS_PENDENTE         = 10;
    const STATUS_ATENDIDO         = 20;
    const STATUS_CANCELADO        = 90;

    protected $table = 'tblpedidoitem';
    protected $primaryKey = 'codpedidoitem';
    protected $fillable = [
        'codpedido',
        'codprodutovariacao',
        'quantidade',
        'indstatus'
    ];
    protected $dates = [
        'alteracao',
        'criacao'
    ];

    // Chaves Estrangeiras
    public function ProdutoVariacao()
    {
        return $this->belongsTo(ProdutoVariacao::class, 'codprodutovariacao', 'codprodutovariacao');
    }

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

    // Tabelas Filhas
    public function NegocioProdutoBarraPedidoItemS()
    {
        return $this->hasMany(NegocioProdutoBarraPedidoItem::class, 'codpedidoitem', 'codpedidoitem');
    }


}
