<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Feb/2026 11:34:28
 */

namespace Mg\Negocio;

use Mg\MgModel;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\Pedido\PedidoItem;
use Mg\Usuario\Usuario;

class NegocioProdutoBarraPedidoItem extends MgModel
{
    protected $table = 'tblnegocioprodutobarrapedidoitem';
    protected $primaryKey = 'codnegocioprodutobarrapedidoitem';


    protected $fillable = [
        'codnegocioprodutobarra',
        'codpedidoitem'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codnegocioprodutobarra' => 'integer',
        'codnegocioprodutobarrapedidoitem' => 'integer',
        'codpedidoitem' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function NegocioProdutoBarra()
    {
        return $this->belongsTo(NegocioProdutoBarra::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }

    public function PedidoItem()
    {
        return $this->belongsTo(PedidoItem::class, 'codpedidoitem', 'codpedidoitem');
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