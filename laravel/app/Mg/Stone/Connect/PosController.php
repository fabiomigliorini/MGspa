<?php

namespace Mg\Stone\Connect;

use Illuminate\Http\Request;

use Mg\Stone\StonePosService;
use Mg\Stone\StonePos;
use Mg\Stone\StoneFilial;

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
        return $sp;
    }

    public function destroy(Request $request, $codstonepos)
    {
        $sp = StonePos::findOrFail($codstonepos);
        $sp = StonePosService::destroy($sp);
        return $sp;
    }

}
