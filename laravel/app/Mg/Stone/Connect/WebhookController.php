<?php

namespace Mg\Stone\Connect;

use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Auth;
//use App\Http\Requests;
//use DB;

use Mg\MgController;

class WebhookController extends MgController
{

    public function posApplication(Request $request)
    {
        $file = WebhookJsonService::salvarPosApplication($request->getContent());
        return response()->json(['success'=>true], 200);
    }

    public function preTransactionStatus(Request $request)
    {
        $file = WebhookJsonService::salvarPreTransactionStatus($request->getContent());
        return response()->json(['success'=>true], 200);
    }

    public function processedTransaction(Request $request)
    {
        $file = WebhookJsonService::salvarProcessedTransaction($request->getContent());
        return response()->json(['success'=>true], 200);
    }

    public function printNoteStatus(Request $request)
    {
        $file = WebhookJsonService::salvarPrintNoteStatus($request->getContent());
        return response()->json(['success'=>true], 200);
    }

}
