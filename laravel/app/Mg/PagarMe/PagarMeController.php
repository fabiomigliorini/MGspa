<?php

namespace Mg\PagarMe;

use Illuminate\Http\Request;
use Mg\Stone\StoneTransacaoProcessaJob;
use Illuminate\Support\Facades\Log;
//use Illuminate\Support\Facades\Auth;
//use App\Http\Requests;
//use DB;

use Mg\MgController;

class PagarMeController extends MgController
{

    public function webhook(Request $request)
    {
        Log::info('Recebendo Webhook PagarMe');            
        $file = WebhookJsonService::salvar($request->getContent());
        return response()->json([
            'success'=>true,
            'file'=>$file
        ], 200);
    }

}
