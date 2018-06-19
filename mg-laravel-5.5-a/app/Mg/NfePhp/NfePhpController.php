<?php

namespace Mg\NfePhp;

use Illuminate\Http\Request;
use Mg\MgController;

use App\Http\Requests;
use Illuminate\Validation\Rule;

class NfePhpController extends MgController
{

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function criaXml(Request $request, $id)
    {

        $ret = NfePhpRepository::criaXml($id);

        return $ret;

        /*
        $model = NfePhp::findOrFail($id, $request->get('fields'));

        return response()->json($model, 200);
        */
    }

}
