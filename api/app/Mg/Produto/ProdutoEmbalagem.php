<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:32:47
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Mercos\MercosProduto;
use Mg\Produto\Produto;
use Mg\Produto\ProdutoBarra;
use Mg\Produto\ProdutoHistoricoPreco;
use Mg\Produto\ProdutoImagem;
use Mg\Produto\UnidadeMedida;
use Mg\Usuario\Usuario;

class ProdutoEmbalagem extends MgModel
{
    protected $table = 'tblprodutoembalagem';
    protected $primaryKey = 'codprodutoembalagem';


    protected $fillable = [
        'altura',
        'codopencart',
        'codproduto',
        'codprodutoimagem',
        'codunidademedida',
        'descricaosite',
        'largura',
        'peso',
        'preco',
        'profundidade',
        'quantidade',
        'vendesite'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'altura' => 'float',
        'codopencart' => 'integer',
        'codproduto' => 'integer',
        'codprodutoembalagem' => 'integer',
        'codprodutoimagem' => 'integer',
        'codunidademedida' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'largura' => 'float',
        'peso' => 'float',
        'preco' => 'float',
        'profundidade' => 'float',
        'quantidade' => 'float',
        'vendesite' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function Produto()
    {
        return $this->belongsTo(Produto::class, 'codproduto', 'codproduto');
    }

    public function ProdutoImagem()
    {
        return $this->belongsTo(ProdutoImagem::class, 'codprodutoimagem', 'codprodutoimagem');
    }

    public function UnidadeMedida()
    {
        return $this->belongsTo(UnidadeMedida::class, 'codunidademedida', 'codunidademedida');
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
    public function MercosProdutoS()
    {
        return $this->hasMany(MercosProduto::class, 'codprodutoembalagem', 'codprodutoembalagem');
    }

    public function ProdutoCompraS()
    {
        return $this->hasMany(Produto::class, 'codprodutoembalagemcompra', 'codprodutoembalagem');
    }

    public function ProdutoTransferenciaS()
    {
        return $this->hasMany(Produto::class, 'codprodutoembalagemtransferencia', 'codprodutoembalagem');
    }

    public function ProdutoBarraS()
    {
        return $this->hasMany(ProdutoBarra::class, 'codprodutoembalagem', 'codprodutoembalagem');
    }

    public function ProdutoHistoricoPrecoS()
    {
        return $this->hasMany(ProdutoHistoricoPreco::class, 'codprodutoembalagem', 'codprodutoembalagem');
    }

}
