<?php

namespace Mg\Stone\Connect;

use Illuminate\Http\Request;

use Mg\Stone\StonePosService;
use Mg\Stone\StonePos;
use Mg\Stone\StoneFilial;
use Mg\Stone\StoneFilialResource;


use Mg\MgController;

class PosController extends MgController
{

    public function store(Request $request)
    {
        $request->validate([
            'codstonefilial' => ['required', 'integer'],
            'serialnumber' => ['required', 'string'],
            'apelido' => ['required', 'string'],
        ]);
        $sf = StoneFilial::findOrFail($request->codstonefilial);
        $sp = StonePosService::create(
            $sf,
            $request->serialnumber,
            $request->apelido
        );
        return new StoneFilialResource($sp->StoneFilial);
    }

    public function destroy(Request $request, $codstonepos)
    {
        $sp = StonePos::findOrFail($codstonepos);
        $sp = StonePosService::destroy($sp);
        return new StoneFilialResource($sp->StoneFilial);
    }

}
