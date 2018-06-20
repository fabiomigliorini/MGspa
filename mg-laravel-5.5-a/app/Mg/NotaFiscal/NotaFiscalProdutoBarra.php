<?php

namespace Mg\NotaFiscal;

use Mg\MgModel;
use Mg\Produto\ProdutoBarra;

class NotaFiscalProdutoBarra extends MGModel
{
    protected $table = 'tblnotafiscalprodutobarra';
    protected $primaryKey = 'codnotafiscalprodutobarra';
    protected $fillable = [
        'codnotafiscal',
        'codnotafiscalprodutobarraorigem',
        'codprodutobarra',
        'codcfop',
        'quantidade',
        'valorunitario',
        'valortotal',
        'csosn',
        'icmscst',
        'icmsbase',
        'icmspercentual',
        'icmsvalor',
        'icmsstbase',
        'icmsstpercentual',
        'icmsstvalor',
        'ipicst',
        'ipibase',
        'ipipercentual',
        'ipivalor',
        'piscst',
        'pisbase',
        'pispercentual',
        'pisvalor',
        'cofinscst',
        'cofinsbase',
        'cofinspercentual',
        'cofinsvalor',
        'csllbase',
        'csllpercentual',
        'csllvalor',
        'irpjbase',
        'irpjpercentual',
        'irpjvalor',
        'descricaoalternativa',
        'codnegocioprodutobarra',
    ];
    protected $dates = [
        'criacao',
        'alteracao',
    ];

    // Chaves Estrangeiras
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

    public function Cfop()
    {
        return $this->belongsTo(Cfop::class, 'codcfop', 'codcfop');
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
    public function NotaFiscalProdutoBarraOrigemS()
    {
        return $this->belongsTo(NotaFiscalProdutoBarra::class, 'codnotafiscalprodutobarraorigem', 'codnotafiscalprodutobarra');
    }

}
