<?php

namespace Mg\Stone\Connect;

use Illuminate\Http\Request;

use Mg\Stone\StoneFilialService;
use Mg\Stone\StoneFilial;

use Mg\MgController;

class FilialController extends MgController
{

    public function store(Request $request)
    {
        $request->validate([
          'codfilial' => ['required', 'integer'],
          'stonecode' => ['required', 'integer'],
          'chaveprivada' => ['required', 'string'],
        ]);
        $stoneFilial = StoneFilialService::create($request->codfilial, $request->stonecode, $request->chaveprivada);
        return $stoneFilial;
    }

    public function show(Request $request, $codstonefilial)
    {
        $stoneFilial = StoneFilial::findOrFail($codstonefilial);
        StoneFilialService::sincroniza($stoneFilial);
        $stoneFilial->refresh();
        return $stoneFilial;
    }

    public function showWebhook(Request $request, $codstonefilial)
    {
        $stoneFilial = StoneFilial::findOrFail($codstonefilial);
        $weebhooks = StoneFilialService::consultaWebhook($stoneFilial);
        return $weebhooks;
    }

}
