<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Feb/2026 16:59:05
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Mercos\MercosProduto;
use Mg\Produto\ProdutoBarra;
use Mg\Produto\ProdutoEmbalagem;
use Mg\Produto\ProdutoHistoricoPreco;
use Mg\Produto\ProdutoImagem;
use Mg\Produto\ProdutoVariacao;
use Mg\Woo\WooProduto;
use Mg\NaturezaOperacao\Cest;
use Mg\Marca\Marca;
use Mg\NaturezaOperacao\Ncm;
use Mg\Produto\SubGrupoProduto;
use Mg\Produto\TipoProduto;
use Mg\Tributacao\Tributacao;
use Mg\Produto\UnidadeMedida;
use Mg\Usuario\Usuario;
use Mg\Estoque\EstoqueLocal;
use Mg\Filial\TipoSetor;

class Produto extends MgModel
{
    protected $table = 'tblproduto';
    protected $primaryKey = 'codproduto';


    protected $fillable = [
        'abc',
        'abccategoria',
        'abcignorar',
        'abcposicao',
        'altura',
        'bonificacaoxerox',
        'codcest',
        'codcestanterior',
        'codestoquelocal',
        'codmarca',
        'codncm',
        'codopencart',
        'codopencartvariacao',
        'codprodutoembalagemcompra',
        'codprodutoembalagemtransferencia',
        'codprodutoimagem',
        'codsubgrupoproduto',
        'codtipoproduto',
        'codtiposetor',
        'codtributacao',
        'codunidademedida',
        'conferenciaperiodica',
        'descricaosite',
        'estoque',
        'importado',
        'inativo',
        'largura',
        'metadescriptionsite',
        'metakeywordsite',
        'observacoes',
        'peso',
        'preco',
        'produto',
        'profundidade',
        'referencia',
        'revisao',
        'site',
        'titulosite',
        'vendesite'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
        'revisao'
    ];

    protected $casts = [
        'abccategoria' => 'integer',
        'abcignorar' => 'boolean',
        'abcposicao' => 'integer',
        'altura' => 'float',
        'bonificacaoxerox' => 'boolean',
        'codcest' => 'integer',
        'codcestanterior' => 'integer',
        'codestoquelocal' => 'integer',
        'codmarca' => 'integer',
        'codncm' => 'integer',
        'codopencart' => 'integer',
        'codopencartvariacao' => 'integer',
        'codproduto' => 'integer',
        'codprodutoembalagemcompra' => 'integer',
        'codprodutoembalagemtransferencia' => 'integer',
        'codprodutoimagem' => 'integer',
        'codsubgrupoproduto' => 'integer',
        'codtipoproduto' => 'integer',
        'codtiposetor' => 'integer',
        'codtributacao' => 'integer',
        'codunidademedida' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'conferenciaperiodica' => 'boolean',
        'estoque' => 'boolean',
        'importado' => 'boolean',
        'largura' => 'float',
        'peso' => 'float',
        'preco' => 'float',
        'profundidade' => 'float',
        'site' => 'boolean',
        'vendesite' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function Cest()
    {
        return $this->belongsTo(Cest::class, 'codcest', 'codcest');
    }

    public function EstoqueLocal()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocal', 'codestoquelocal');
    }

    public function Marca()
    {
        return $this->belongsTo(Marca::class, 'codmarca', 'codmarca');
    }

    public function Ncm()
    {
        return $this->belongsTo(Ncm::class, 'codncm', 'codncm');
    }

    public function ProdutoEmbalagemCompra()
    {
        return $this->belongsTo(ProdutoEmbalagem::class, 'codprodutoembalagemcompra', 'codprodutoembalagem');
    }

    public function ProdutoEmbalagemTransferencia()
    {
        return $this->belongsTo(ProdutoEmbalagem::class, 'codprodutoembalagemtransferencia', 'codprodutoembalagem');
    }

    public function ProdutoImagem()
    {
        return $this->belongsTo(ProdutoImagem::class, 'codprodutoimagem', 'codprodutoimagem');
    }

    public function SubGrupoProduto()
    {
        return $this->belongsTo(SubGrupoProduto::class, 'codsubgrupoproduto', 'codsubgrupoproduto');
    }

    public function TipoProduto()
    {
        return $this->belongsTo(TipoProduto::class, 'codtipoproduto', 'codtipoproduto');
    }

    public function TipoSetor()
    {
        return $this->belongsTo(TipoSetor::class, 'codtiposetor', 'codtiposetor');
    }

    public function Tributacao()
    {
        return $this->belongsTo(Tributacao::class, 'codtributacao', 'codtributacao');
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
        return $this->hasMany(MercosProduto::class, 'codproduto', 'codproduto');
    }

    public function ProdutoBarraS()
    {
        return $this->hasMany(ProdutoBarra::class, 'codproduto', 'codproduto');
    }

    public function ProdutoEmbalagemS()
    {
        return $this->hasMany(ProdutoEmbalagem::class, 'codproduto', 'codproduto');
    }

    public function ProdutoHistoricoPrecoS()
    {
        return $this->hasMany(ProdutoHistoricoPreco::class, 'codproduto', 'codproduto');
    }

    public function ProdutoImagemS()
    {
        return $this->hasMany(ProdutoImagem::class, 'codproduto', 'codproduto');
    }

    public function ProdutoVariacaoS()
    {
        return $this->hasMany(ProdutoVariacao::class, 'codproduto', 'codproduto');
    }

    public function WooProdutoS()
    {
        return $this->hasMany(WooProduto::class, 'codproduto', 'codproduto');
    }

}