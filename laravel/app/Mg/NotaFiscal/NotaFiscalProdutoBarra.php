<?php
/**
 * Created by php artisan gerador:model.
 * Date: 24/Jan/2024 14:45:06
 */

namespace Mg\NotaFiscal;

use Mg\MgModel;
use Mg\Estoque\EstoqueMovimento;
use Mg\NaturezaOperacao\Cfop;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Produto\ProdutoBarra;
use Mg\Usuario\Usuario;

class NotaFiscalProdutoBarra extends MgModel
{
    protected $table = 'tblnotafiscalprodutobarra';
    protected $primaryKey = 'codnotafiscalprodutobarra';


    protected $fillable = [
        'certidaosefazmt',
        'codcfop',
        'codnegocioprodutobarra',
        'codnotafiscal',
        'codnotafiscalprodutobarraorigem',
        'codprodutobarra',
        'cofinsbase',
        'cofinscst',
        'cofinspercentual',
        'cofinsvalor',
        'csllbase',
        'csllpercentual',
        'csllvalor',
        'csosn',
        'descricaoalternativa',
        'fethabkg',
        'fethabvalor',
        'funruralpercentual',
        'funruralvalor',
        'iagrokg',
        'iagrovalor',
        'icmsbase',
        'icmsbasepercentual',
        'icmscst',
        'icmspercentual',
        'icmsstbase',
        'icmsstpercentual',
        'icmsstvalor',
        'icmsvalor',
        'ipibase',
        'ipicst',
        'ipipercentual',
        'ipivalor',
        'irpjbase',
        'irpjpercentual',
        'irpjvalor',
        'observacoes',
        'pedido',
        'pedidoitem',
        'pisbase',
        'piscst',
        'pispercentual',
        'pisvalor',
        'quantidade',
        'senarpercentual',
        'senarvalor',
        'valordesconto',
        'valorfrete',
        'valoroutras',
        'valorseguro',
        'valortotal',
        'valorunitario'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'certidaosefazmt' => 'boolean',
        'codcfop' => 'integer',
        'codnegocioprodutobarra' => 'integer',
        'codnotafiscal' => 'integer',
        'codnotafiscalprodutobarra' => 'integer',
        'codnotafiscalprodutobarraorigem' => 'integer',
        'codprodutobarra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'cofinsbase' => 'float',
        'cofinscst' => 'float',
        'cofinspercentual' => 'float',
        'cofinsvalor' => 'float',
        'csllbase' => 'float',
        'csllpercentual' => 'float',
        'csllvalor' => 'float',
        'fethabkg' => 'float',
        'fethabvalor' => 'float',
        'funruralpercentual' => 'float',
        'funruralvalor' => 'float',
        'iagrokg' => 'float',
        'iagrovalor' => 'float',
        'icmsbase' => 'float',
        'icmsbasepercentual' => 'float',
        'icmscst' => 'float',
        'icmspercentual' => 'float',
        'icmsstbase' => 'float',
        'icmsstpercentual' => 'float',
        'icmsstvalor' => 'float',
        'icmsvalor' => 'float',
        'ipibase' => 'float',
        'ipicst' => 'float',
        'ipipercentual' => 'float',
        'ipivalor' => 'float',
        'irpjbase' => 'float',
        'irpjpercentual' => 'float',
        'irpjvalor' => 'float',
        'pedidoitem' => 'integer',
        'pisbase' => 'float',
        'piscst' => 'float',
        'pispercentual' => 'float',
        'pisvalor' => 'float',
        'quantidade' => 'float',
        'senarpercentual' => 'float',
        'senarvalor' => 'float',
        'valordesconto' => 'float',
        'valorfrete' => 'float',
        'valoroutras' => 'float',
        'valorseguro' => 'float',
        'valortotal' => 'float',
        'valorunitario' => 'float'
    ];


    // Chaves Estrangeiras
    public function Cfop()
    {
        return $this->belongsTo(Cfop::class, 'codcfop', 'codcfop');
    }

    public function NegocioProdutoBarra()
    {
        return $this->belongsTo(NegocioProdutoBarra::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }

    public function NotaFiscal()
    {
        return $this->belongsTo(NotaFiscal::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function NotaFiscalProdutoBarraOrigem()
    {
        return $this->belongsTo(NotaFiscalProdutoBarra::class, 'codnotafiscalprodutobarraorigem', 'codnotafiscalprodutobarra');
    }

    public function ProdutoBarra()
    {
        return $this->belongsTo(ProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
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
    public function EstoqueMovimentoS()
    {
        return $this->hasMany(EstoqueMovimento::class, 'codnotafiscalprodutobarra', 'codnotafiscalprodutobarra');
    }

    public function NotaFiscalProdutoBarraS()
    {
        return $this->hasMany(NotaFiscalProdutoBarra::class, 'codnotafiscalprodutobarraorigem', 'codnotafiscalprodutobarra');
    }

}