<?php

namespace Mg\Negocio;

use Mg\MgModel;

class NegocioProdutoBarra extends MGModel
{
    protected $table = 'tblnegocioprodutobarra';
    protected $primaryKey = 'codnegociprodutobarra';
    protected $fillable = [
        'codnegocio',
        'quantidade',
        'valorunitario',
        'valortotal',
        'codprodutobarra',
        'codcaixamercadoria',
        'codnegocioprodutobarradevolucao'
    ];
    protected $dates = [
        'conferencia',
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

    public function UsuarioConferencia()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioconferencia', 'codusuario');
    }

    public function NegocioProdutoBarraDevolucao()
    {
        return $this->belongsTo(NegocioProdutoBarra::class, 'codnegocioprodutobarradevolucao', 'codnegocioprodutobarradevolucao');
    }

    public function ProdutoBarra()
    {
        return $this->belongsTo(ProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    // Tabelas Filhas
    public function NegocioProdutoBarraPedidoItemS()
    {
        return $this->hasMany(NegocioProdutoBarraPedidoItem::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }
}
