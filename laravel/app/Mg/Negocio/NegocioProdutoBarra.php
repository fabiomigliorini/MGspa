<?php

/**
 * Created by php artisan gerador:model.
 * Date: 16/Dec/2024 09:40:21
 */

namespace Mg\Negocio;

use Mg\MgModel;
use Mg\CupomFiscal\CupomFiscalProdutoBarra;
use Mg\Estoque\EstoqueMovimento;
use Mg\Negocio\NegocioProdutoBarraPedidoItem;
use Mg\NotaFiscal\NotaFiscalProdutoBarra;
use Mg\Mercos\MercosPedidoItem;
use Mg\Negocio\Negocio;
use Mg\Produto\ProdutoBarra;
use Mg\Usuario\Usuario;

class NegocioProdutoBarra extends MgModel
{
    protected $table = 'tblnegocioprodutobarra';
    protected $primaryKey = 'codnegocioprodutobarra';


    protected $fillable = [
        'codnegocio',
        'codnegocioprodutobarradevolucao',
        'codprodutobarra',
        'codusuarioconferencia',
        'conferencia',
        'inativo',
        'observacoes',
        'percentualdesconto',
        'quantidade',
        'uuid',
        'valordesconto',
        'valorfrete',
        'valoroutras',
        'valorprodutos',
        'valorseguro',
        'valortotal',
        'valorunitario'
    ];

    protected $dates = [
        'alteracao',
        'conferencia',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codnegocio' => 'integer',
        'codnegocioprodutobarra' => 'integer',
        'codnegocioprodutobarradevolucao' => 'integer',
        'codprodutobarra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuarioconferencia' => 'integer',
        'codusuariocriacao' => 'integer',
        'percentualdesconto' => 'float',
        'quantidade' => 'float',
        'valordesconto' => 'float',
        'valorfrete' => 'float',
        'valoroutras' => 'float',
        'valorprodutos' => 'float',
        'valorseguro' => 'float',
        'valortotal' => 'float',
        'valorunitario' => 'float'
    ];


    // Chaves Estrangeiras
    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function NegocioProdutoBarraDevolucao()
    {
        return $this->belongsTo(NegocioProdutoBarra::class, 'codnegocioprodutobarradevolucao', 'codnegocioprodutobarra');
    }

    public function ProdutoBarra()
    {
        return $this->belongsTo(ProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioConferencia()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioconferencia', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function y()
    {
        return $this->hasMany(CupomFiscalProdutoBarra::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }

    public function EstoqueMovimentoS()
    {
        return $this->hasMany(EstoqueMovimento::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }

    public function MercosPedidoItemS()
    {
        return $this->hasMany(MercosPedidoItem::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }

    public function NegocioProdutoBarraDevolucaoS()
    {
        return $this->hasMany(NegocioProdutoBarra::class, 'codnegocioprodutobarradevolucao', 'codnegocioprodutobarra');
    }

    public function NegocioProdutoBarraPedidoItemS()
    {
        return $this->hasMany(NegocioProdutoBarraPedidoItem::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }

    public function NotaFiscalProdutoBarraS()
    {
        return $this->hasMany(NotaFiscalProdutoBarra::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }
}
