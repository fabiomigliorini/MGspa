<?php

namespace Mg\CieloLio;

use Storage;
use Illuminate\Support\Str;

use Illuminate\Http\Request;
// use Mg\MgController;
// use Carbon\Carbon;
// use Illuminate\Validation\Rule;

class CieloLioController
{
    public function callback(Request $request)
    {
        $file = 'recebidos/' . date('Y-m-d H-i-s') . ' ' . uniqid() . '.json';
        Storage::disk('cielo-lio')->put($file, $request->getContent());
        return response()->json($request->all(), 200);
    }
}
