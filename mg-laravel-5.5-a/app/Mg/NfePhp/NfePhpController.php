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
    public function criarXml(Request $request, $id)
    {
        $ret = NfePhpRepository::criarXml($id);
        return $ret;
    }

    public function assinarXml(Request $request, $id)
    {
        $ret = NfePhpRepository::assinarXml($id);
        return $ret;
    }

    public function enviarXml(Request $request, $id)
    {
        $ret = NfePhpRepository::enviarXml($id);
        return $ret;
    }

}
