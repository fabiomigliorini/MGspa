<?php

namespace Mg\Meta;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Meta\Services\MetaProjecaoService;

class MetaDashboardController extends MgController
{
    public function dashboard(Request $request, $codmeta)
    {
        $meta = Meta::findOrFail($codmeta);
        $projecao = MetaProjecaoService::projecao($meta);

        $projecao['periodoinicial'] = $meta->periodoinicial->toDateString();
        $projecao['periodofinal'] = $meta->periodofinal->toDateString();
        $projecao['status'] = $meta->status;

        return response()->json($projecao);
    }

    public function dashboardPessoa(Request $request, $codmeta, $codpessoa)
    {
        $meta = Meta::findOrFail($codmeta);
        $resumo = MetaProjecaoService::resumoPessoa($meta, (int) $codpessoa);

        return response()->json($resumo);
    }

    public function vendasFilial(Request $request, $codmeta)
    {
        $meta = Meta::findOrFail($codmeta);
        $vendas = MetaService::vendasFilial($meta);

        return response()->json($vendas);
    }

    public function vendasVendedor(Request $request, $codmeta)
    {
        $meta = Meta::findOrFail($codmeta);
        $vendas = MetaService::vendasVendedor($meta);

        return response()->json($vendas);
    }
}
