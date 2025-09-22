<?php
/**
 * Created by php artisan gerador:model.
 * Date: 22/Sep/2025 17:58:36
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\CupomFiscal\CupomFiscalProdutoBarra;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\NfeTerceiro\NfeTerceiroItem;
use Mg\NotaFiscal\NotaFiscalProdutoBarra;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroProdutoBarra;
use Mg\ValeCompra\ValeCompraModeloProdutoBarra;
use Mg\ValeCompra\ValeCompraProdutoBarra;
use Mg\Produto\Prancheta;
use Mg\Woo\WooProduto;
use Mg\Marca\Marca;
use Mg\Produto\Produto;
use Mg\Produto\ProdutoEmbalagem;
use Mg\Produto\ProdutoVariacao;
use Mg\Usuario\Usuario;

class ProdutoBarra extends MgModel
{
    protected $table = 'tblprodutobarra';
    protected $primaryKey = 'codprodutobarra';


    protected $fillable = [
        'barras',
        'codmarca',
        'codproduto',
        'codprodutoembalagem',
        'codprodutovariacao',
        'referencia',
        'variacao'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codmarca' => 'integer',
        'codproduto' => 'integer',
        'codprodutobarra' => 'integer',
        'codprodutoembalagem' => 'integer',
        'codprodutovariacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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

    public function ProdutoEmbalagem()
    {
        return $this->belongsTo(ProdutoEmbalagem::class, 'codprodutoembalagem', 'codprodutoembalagem');
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
    public function CupomFiscalProdutoBarraS()
    {
        return $this->hasMany(CupomFiscalProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function NegocioProdutoBarraS()
    {
        return $this->hasMany(NegocioProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function NfeTerceiroItemS()
    {
        return $this->hasMany(NfeTerceiroItem::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function NotaFiscalProdutoBarraS()
    {
        return $this->hasMany(NotaFiscalProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function NotaFiscalTerceiroProdutoBarraS()
    {
        return $this->hasMany(NotaFiscalTerceiroProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function PranchetaS()
    {
        return $this->hasMany(Prancheta::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function ValeCompraModeloProdutoBarraS()
    {
        return $this->hasMany(ValeCompraModeloProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function ValeCompraProdutoBarraS()
    {
        return $this->hasMany(ValeCompraProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function WooProdutoUnidadeS()
    {
        return $this->hasMany(WooProduto::class, 'codprodutobarraunidade', 'codprodutobarra');
    }

    // Atributo descricao
    public function getDescricaoAttribute()
    {
        $descr = "{$this->Produto->produto} {$this->ProdutoVariacao->variacao}";
        if ($this->codprodutoembalagem) {
            $quant = formataNumero($this->ProdutoEmbalagem->quantidade, 0);
            $descr = "{$descr} C/{$quant}";
        }
        return trim($descr);
    }

    // Unidade Medida
    public function UnidadeMedida()
    {
        if (!empty($this->codprodutoembalagem)) {
            return $this->ProdutoEmbalagem->UnidadeMedida();
        }
        return $this->Produto->UnidadeMedida();
    }

}
