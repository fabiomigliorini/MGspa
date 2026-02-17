<?php

/**
 * Created by php artisan gerador:model.
 * Date: 17/Feb/2026 11:34:41
 */

namespace Mg\Pedido;

use Mg\MgModel;
use Mg\Negocio\NegocioProdutoBarraPedidoItem;
use Mg\Pedido\Pedido;
use Mg\Produto\ProdutoVariacao;
use Mg\Usuario\Usuario;

class PedidoItem extends MgModel
{
    const STATUS_PENDENTE         = 10;
    const STATUS_ATENDIDO         = 20;
    const STATUS_CANCELADO        = 90;

    protected $table = 'tblpedidoitem';
    protected $primaryKey = 'codpedidoitem';


    protected $fillable = [
        'codpedido',
        'codprodutovariacao',
        'indstatus',
        'quantidade'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codpedido' => 'integer',
        'codpedidoitem' => 'integer',
        'codprodutovariacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'indstatus' => 'integer',
        'quantidade' => 'float'
    ];


    // Chaves Estrangeiras
    public function Pedido()
    {
        return $this->belongsTo(Pedido::class, 'codpedido', 'codpedido');
    }

    public function ProdutoVariacao()
    {
        return $this->belongsTo(ProdutoVariacao::class, 'codprodutovariacao', 'codprodutovariacao');
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
