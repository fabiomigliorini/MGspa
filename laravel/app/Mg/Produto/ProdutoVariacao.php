<?php
/**
 * Created by php artisan gerador:model.
 * Date: 22/Sep/2025 17:32:36
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Estoque\EstoqueLocalProdutoVariacao;
use Mg\Pedido\PedidoItem;
use Mg\Produto\ProdutoBarra;
use Mg\Mercos\MercosProduto;
use Mg\Produto\ProdutoImagemProdutoVariacao;
use Mg\Woo\WooProduto;
use Mg\Marca\Marca;
use Mg\Produto\Produto;
use Mg\Produto\ProdutoImagem;
use Mg\Usuario\Usuario;

class ProdutoVariacao extends MgModel
{
    protected $table = 'tblprodutovariacao';
    protected $primaryKey = 'codprodutovariacao';


    protected $fillable = [
        'codmarca',
        'codopencart',
        'codproduto',
        'codprodutoimagem',
        'custoultimacompra',
        'dataultimacompra',
        'descontinuado',
        'estoquemaximo',
        'estoqueminimo',
        'inativo',
        'lotecompra',
        'quantidadeultimacompra',
        'referencia',
        'variacao',
        'vendaanoquantidade',
        'vendaanovalor',
        'vendabimestrequantidade',
        'vendabimestrevalor',
        'vendadiaquantidadeprevisao',
        'vendainicio',
        'vendasemestrequantidade',
        'vendasemestrevalor',
        'vendaultimocalculo'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'dataultimacompra',
        'descontinuado',
        'inativo',
        'vendainicio',
        'vendaultimocalculo'
    ];

    protected $casts = [
        'codmarca' => 'integer',
        'codopencart' => 'integer',
        'codproduto' => 'integer',
        'codprodutoimagem' => 'integer',
        'codprodutovariacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'custoultimacompra' => 'float',
        'estoquemaximo' => 'integer',
        'estoqueminimo' => 'integer',
        'lotecompra' => 'float',
        'quantidadeultimacompra' => 'float',
        'vendaanoquantidade' => 'float',
        'vendaanovalor' => 'float',
        'vendabimestrequantidade' => 'float',
        'vendabimestrevalor' => 'float',
        'vendadiaquantidadeprevisao' => 'float',
        'vendasemestrequantidade' => 'float',
        'vendasemestrevalor' => 'float'
    ];


    // Chaves Estrangeiras
    public function Marca()
    {
        return $this->belongsTo(Marca::class, 'codmarca', 'codmarca');
    }

    public function Produto()
    {
        return $this->belongsTo(Produto::class, 'codproduto', 'codproduto');
    }

    public function ProdutoImagem()
    {
        return $this->belongsTo(ProdutoImagem::class, 'codprodutoimagem', 'codprodutoimagem');
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
    public function EstoqueLocalProdutoVariacaoS()
    {
        return $this->hasMany(EstoqueLocalProdutoVariacao::class, 'codprodutovariacao', 'codprodutovariacao');
    }

    public function MercosProdutoS()
    {
        return $this->hasMany(MercosProduto::class, 'codprodutovariacao', 'codprodutovariacao');
    }

    public function PedidoItemS()
    {
        return $this->hasMany(PedidoItem::class, 'codprodutovariacao', 'codprodutovariacao');
    }

    public function ProdutoBarraS()
    {
        return $this->hasMany(ProdutoBarra::class, 'codprodutovariacao', 'codprodutovariacao');
    }

    public function ProdutoImagemProdutoVariacaoS()
    {
        return $this->hasMany(ProdutoImagemProdutoVariacao::class, 'codprodutovariacao', 'codprodutovariacao');
    }

    public function WooProdutoS()
    {
        return $this->hasMany(WooProduto::class, 'codprodutovariacao', 'codprodutovariacao');
    }

}