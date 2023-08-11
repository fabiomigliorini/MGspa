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

    public function calculaTributacao()
    {
        $trib = TributacaoNaturezaOperacao
                ::where('codtributacao', $this->ProdutoBarra->Produto->codtributacao)
                ->where('codtipoproduto', $this->ProdutoBarra->Produto->codtipoproduto)
                ->where('bit', $this->ProdutoBarra->Produto->Ncm->bit)
                ->where('codnaturezaoperacao', $this->NotaFiscal->codnaturezaoperacao)
                ->whereRaw("('{$this->ProdutoBarra->Produto->Ncm->ncm}' ilike ncm || '%' or ncm is null)");

        if ($this->NotaFiscal->Pessoa->Cidade->codestado == $this->NotaFiscal->Filial->Pessoa->Cidade->codestado) {
            $trib->where('codestado', $this->NotaFiscal->Pessoa->Cidade->codestado);
            $filtroEstado = 'codestado = :codestado';
        } else {
            $trib->whereNull('codestado');
        }

        if (!($trib = $trib->first())) {
            echo '<h1>Erro Ao Calcular Tributacao</h1>';
            dd($this);
            return false;
        }

        //Traz codigos de tributacao
        $this->codcfop = $trib->codcfop;

        if ($this->NotaFiscal->Filial->crt == Filial::CRT_REGIME_NORMAL) {

            //CST's
            $this->icmscst = $trib->icmscst;
            $this->ipicst = $trib->ipicst;
            $this->piscst = $trib->piscst;
            $this->cofinscst = $trib->cofinscst;

            if (!empty($this->valortotalfinal) && ($this->NotaFiscal->emitida)) {
                //Calcula ICMS
                if (!empty($trib->icmslpbase)) {
                    $this->icmsbasepercentual = $trib->icmslpbase;
                    $this->icmsbase = round(($this->icmsbasepercentual * $this->valortotalfinal)/100, 2);
                }
                
                $this->icmspercentual = $trib->icmslppercentual;

                if ((!empty($this->icmsbase)) and (!empty($this->icmspercentual))) {
                    $this->icmsvalor = round(($this->icmsbase * $this->icmspercentual)/100, 2);
                }

                //Calcula PIS
                if ($trib->pispercentual > 0) {
                    $this->pisbase = $this->valortotalfinal;
                    $this->pispercentual = $trib->pispercentual;
                    $this->pisvalor = round(($this->pisbase * $this->pispercentual)/100, 2);
                }

                //Calcula Cofins
                if ($trib->cofinspercentual > 0) {
                    $this->cofinsbase = $this->valortotalfinal;
                    $this->cofinspercentual = $trib->cofinspercentual;
                    $this->cofinsvalor = round(($this->cofinsbase * $this->cofinspercentual)/100, 2);
                }

                //Calcula CSLL
                if ($trib->csllpercentual > 0) {
                    $this->csllbase = $this->valortotalfinal;
                    $this->csllpercentual = $trib->csllpercentual;
                    $this->csllvalor = round(($this->csllbase * $this->csllpercentual)/100, 2);
                }

                //Calcula IRPJ
                if ($trib->irpjpercentual > 0) {
                    $this->irpjbase = $this->valortotalfinal;
                    $this->irpjpercentual = $trib->irpjpercentual;
                    $this->irpjvalor = round(($this->irpjbase * $this->irpjpercentual)/100, 2);
                }
            }
        } else {
            $this->csosn = $trib->csosn;

            //Calcula ICMSs
            if (!empty($this->valortotalfinal) && ($this->NotaFiscal->emitida)) {
                if (!empty($trib->icmsbase)) {
                    $this->icmsbase = round(($trib->icmsbase * $this->valortotalfinal)/100, 2);
                }

                $this->icmspercentual = $trib->icmspercentual;

                if ((!empty($this->icmsbase)) and (!empty($this->icmspercentual))) {
                    $this->icmsvalor = round(($this->icmsbase * $this->icmspercentual)/100, 2);
                }
            }
        }
    }

}
