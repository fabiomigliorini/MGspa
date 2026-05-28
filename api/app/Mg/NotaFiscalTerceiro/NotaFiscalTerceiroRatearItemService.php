<?php

namespace Mg\NotaFiscalTerceiro;

use Mg\MgService;

use Mg\Filial\Filial;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NotaFiscalTerceiroRatearItemService extends MgService
{

    public static function armazenaItem($request)
    {

        DB::beginTransaction();

        foreach ($request->item as $key => $item) {

            $NFeItem = NotaFiscalTerceiroItem::firstOrNew([
                'referencia' => $request->referencia,
                'codnotafiscalterceirogrupo' => $request->codnotafiscalterceirogrupo
            ]);
            $NFeItem->codnotafiscalterceirogrupo = null;
            $NFeItem->numero = null;
            $NFeItem->referencia = null;
            $NFeItem->produto = null;
            $NFeItem->ncm = null;
            $NFeItem->cfop = null;
            $NFeItem->barras = null;
            $NFeItem->barrastributavel =  null;
            $NFeItem->quantidadetributavel = null;
            $NFeItem->valorunitariotributavel = null;
            $NFeItem->unidademedida = null;
            $NFeItem->quantidade = null;
            $NFeItem->valorunitario = null;
            $NFeItem->valorproduto = null;
            $NFeItem->valorfrete = null;
            $NFeItem->valorseguro = null;
            $NFeItem->valordesconto = null;
            $NFeItem->valoroutras = null;
            $NFeItem->valortotal = null;
            $NFeItem->compoetotal = null;
            $NFeItem->csosn = null; // rever este campo
            $NFeItem->origem = null;
            $NFeItem->icmsbasemodalidade = null;
            $NFeItem->icmsbase = null;
            $NFeItem->icmspercentual = null;
            $NFeItem->icmsvalor = null;
            $NFeItem->icmscst = null;
            $NFeItem->icmsstbasemodalidade = null;
            $NFeItem->icmsstbase = null;
            $NFeItem->icmsstpercentual = null;
            $NFeItem->icmsstvalor = null;
            $NFeItem->ipicst = null;
            $NFeItem->ipibase = null;
            $NFeItem->ipipercentual = null;
            $NFeItem->ipivalor = null;
            $NFeItem->piscst = null;
            $NFeItem->pisbase = null;
            $NFeItem->pispercentual = null;
            $NFeItem->pisvalor = null;
            $NFeItem->cofinscst = null;
            $NFeItem->cofinsbase = null;
            $NFeItem->cofinspercentual = null;
            $NFeItem->cofinsvalor = null;
            // dd($NFeItem);
            $NFeItem->save();

            // SALVA OS DADOS NA tblnotafiscalterceiroprodutobarra
            $produtobarra = NotaFiscalTerceiroProdutoBarra::firstOrNew([
                'codnotafiscalterceirogrupo' => $request->codnotafiscalterceirogrupo
            ]);
            $produtobarra->codnotafiscalterceirogrupo = $request->codnotafiscalterceirogrupo;;
            $produtobarra->codprodutobarra = null;
            $produtobarra->margem = null; // rever este campo
            $produtobarra->complemento = null; // rever este campo
            $produtobarra->quantidade = null;
            $produtobarra->valorproduto = null;
            // dd($produtobarra);
            $produtobarra->save();
        }

        DB::commit();

        return true;
    } // FIM DO armazenaItem


}
