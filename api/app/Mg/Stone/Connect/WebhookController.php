<?php

namespace Mg\Stone\Connect;

use Illuminate\Http\Request;
use Mg\Stone\StoneTransacaoProcessaJob;
//use Illuminate\Support\Facades\Auth;
//use App\Http\Requests;
//use Illuminate\Support\Facades\DB;

use Mg\MgController;

class WebhookController extends MgController
{

    public function posApplication(Request $request)
    {
        $file = WebhookJsonService::salvarPosApplication($request->getContent());
        return response()->json(['success' => true], 200);
    }

    public function preTransactionStatus(Request $request)
    {
        $file = WebhookJsonService::salvarPreTransactionStatus($request->getContent());
        return response()->json(['success' => true], 200);
    }

    public function processedTransaction(Request $request)
    {
        $file = WebhookJsonService::salvarProcessedTransaction($request->getContent());
        if (isset($request->establishment_id) && isset($request->stone_transaction_id)) {
            StoneTransacaoProcessaJob::dispatch($request->establishment_id, $request->stone_transaction_id);
        }
        return response()->json(['success' => true], 200);
    }

    public function printNoteStatus(Request $request)
    {
        $file = WebhookJsonService::salvarPrintNoteStatus($request->getContent());
        return response()->json(['success' => true], 200);
    }
}
