<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/May/2021 15:32:03
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Produto\ProdutoBarra;
use Mg\Produto\ProdutoHistoricoPreco;
use Mg\Produto\Produto;
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

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'altura' => 'float',
        'codopencart' => 'integer',
        'codproduto' => 'integer',
        'codprodutoembalagem' => 'integer',
        'codprodutoimagem' => 'integer',
        'codunidademedida' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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
    public function ProdutoBarraS()
    {
        return $this->hasMany(ProdutoBarra::class, 'codprodutoembalagem', 'codprodutoembalagem');
    }

    public function ProdutoHistoricoPrecoS()
    {
        return $this->hasMany(ProdutoHistoricoPreco::class, 'codprodutoembalagem', 'codprodutoembalagem');
    }

}