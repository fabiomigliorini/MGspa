<?php

namespace Mg\Negocio;

use Mg\MgModel;

class NegocioProdutoBarraPedidoItem extends MGModel
{
    protected $table = 'tblnegocioprodutobarrapedidoitem';
    protected $primaryKey = 'codnegociprodutobarrapedidoitem';
    protected $fillable = [
        'codpedidoitem',
        'codnegocioprodutobarra'
    ];
    protected $dates = [
        'alteracao',
        'criacao'
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

    public function NegocioProdutoBarra()
    {
        return $this->belongsTo(NegocioProdutoBarra::class, 'codnegociprodutobarra', 'codnegociprodutobarra');
    }

    public function PedidoItem()
    {
        return $this->belongsTo(PedidoItem::class, 'codpedidoitem', 'codpedidoitem');
    }

    // Tabelas Filhas    
}
