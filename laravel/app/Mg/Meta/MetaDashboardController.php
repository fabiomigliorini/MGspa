<?php

namespace Mg\Meta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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

        return new JsonResource($projecao);
    }

    public function dashboardPessoa(Request $request, $codmeta, $codpessoa)
    {
        $meta = Meta::findOrFail($codmeta);
        $resumo = MetaProjecaoService::resumoPessoa($meta, (int) $codpessoa);

        return new JsonResource($resumo);
    }

}
