<?php

namespace Mg\Boleto;

use Illuminate\Http\Request;
use Mg\MgController;

use App\Http\Requests;

class BoletoController extends MgController
{

    public function retornoPendente(Request $request)
    {
        return ['teste' => 'vamos resolver o problema das revistinhas agora?'];
        return $request->all();
    }

}
