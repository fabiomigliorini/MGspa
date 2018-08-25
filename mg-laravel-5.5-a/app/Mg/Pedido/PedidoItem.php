<?php

namespace Mg\Pedido;

use Mg\MgModel;

const PEDIDO_PENDENTE         = 10;
const PEDIDO_ATENDIDO         = 20;
const PEDIDO_CANCELADO        = 90;


class PedidoItem extends MGModel
{
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
