<?php

namespace Mg\Pdv;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mg\PagarMe\PagarMePos;
use Mg\Prancheta\Prancheta;
use Mg\Produto\PranchetaCategoria;

class PdvPranchetaService
{

    public static function categoriasCategoriaPrancheta($cat)
    {
        $sql = '
            select 
                cat.*
            from tblpranchetacategoria cat
            where cat.codpranchetacategoriapai = :codpranchetacategoria
            order by cat.ordem
        ';
        $cats = DB::select($sql, [
            'codpranchetacategoria' => $cat->codpranchetacategoria
        ]);
        foreach ($cats as $catFilha) {
            static::categoriasCategoriaPrancheta($catFilha);
        }
        $cat->categorias = $cats;

        $sql = '
            select
                pr.*,
                pv.codproduto,
                p.codproduto,
                pb.barras,
                p.produto,
                pv.variacao,
                p.abc,
                um.sigla,
                pe.quantidade,
                pri.codimagem,
                coalesce(pe.preco, p.preco * coalesce(pe.quantidade, 1)) as preco,
                coalesce(pv.inativo, p.inativo) as inativo
            from tblprancheta pr
            inner join tblprodutobarra pb on (pb.codprodutobarra = pr.codprodutobarra)
            inner join tblprodutovariacao pv on (pv.codprodutovariacao  = pb.codprodutovariacao)
            inner join tblproduto p on (p.codproduto  = pv.codproduto)
            left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
            inner join tblunidademedida um on (um.codunidademedida = coalesce(pe.codunidademedida, p.codunidademedida))
            left join tblprodutoimagem pri on (pri.codprodutoimagem = pv.codprodutoimagem)
            where pr.codpranchetacategoria = :codpranchetacategoria
            order by pr.ordem
        ';
        $prods = DB::select($sql, [
            'codpranchetacategoria' => $cat->codpranchetacategoria
        ]);
        $cat->produtos = $prods;
    }

    public static function getPrancheta()
    {

        $sincronizado = date('Y-m-d h:i:s');
        $sql = '
            select 
                cat.*,
                :sincronizado as sincronizado
            from tblpranchetacategoria cat
            where cat.codpranchetacategoriapai is null
            order by cat.ordem
        ';
        $cats = DB::select($sql, [
            'sincronizado' => $sincronizado
        ]);

        foreach ($cats as $cat) {
            static::categoriasCategoriaPrancheta($cat);
        }

        return $cats;
    }

    public static function updatePrancheta($data)
    {
        $atualizadas = [];
        foreach ($data as $cat) {
            $atualizadas[] = static::updateCategoria($cat);
        }
        PranchetaCategoria::whereNull('codpranchetacategoriapai')->whereNotIn('codpranchetacategoria', $atualizadas)->delete();
    }

    public static function updateCategoria($cat, $codpranchetacategoriapai = null)
    {
        if (is_int($cat['codpranchetacategoria'])) {
            $model = PranchetaCategoria::firstOrNew([
                'codpranchetacategoria' => $cat['codpranchetacategoria']
            ]);
        } else {
            $model = new PranchetaCategoria();
        }
        $model->codpranchetacategoriapai = $codpranchetacategoriapai;
        $model->ordem = $cat['ordem'];
        $model->categoria = $cat['categoria'];
        $model->observacoes = $cat['observacoes'];
        $model->imagem = $cat['imagem'];
        $model->save();

        // salva sub-categorias
        $atualizadas = [];
        foreach ($cat['categorias'] as $subCat) {
            $atualizadas[] = static::updateCategoria($subCat, $model->codpranchetacategoria);
        }

        // apaga categorias excedentes
        PranchetaCategoria::where('codpranchetacategoriapai', $model->codpranchetacategoria)->whereNotIn('codpranchetacategoria', $atualizadas)->delete();

        // salva produtos
        $atualizadas = [];
        foreach ($cat['produtos'] as $prod) {
            $atualizadas[] = static::updateProduto($prod, $model->codpranchetacategoria);
        }

        // apaga produtos execedentes
        Prancheta::where('codpranchetacategoria', $model->codpranchetacategoria)->whereNotIn('codprancheta', $atualizadas)->delete();
        
        return $model->codpranchetacategoria;
    }

    public static function updateProduto($prod, $codpranchetacategoria = null)
    {
        if (is_int($prod['codprancheta'])) {
            $model = Prancheta::firstOrNew(['codprancheta' => $prod['codprancheta']]);
        } else {
            $model = new Prancheta();
        }
        $model->codprodutobarra = $prod['codprodutobarra'];
        $model->codpranchetacategoria = $codpranchetacategoria;
        $model->ordem = $prod['ordem'];
        $model->descricao = $prod['descricao'] ?? 'SEM DESCRICAO';
        $model->observacoes = $prod['observacoes'];
        $model->save();
        return $model->codprancheta;
    }
}
