<?php

namespace Mg\Pix;

use Illuminate\Http\Request;

class PixController
{

    public function index (Request $request)
    {
        return response()->json($request->all(), 206);
    }

}
