<?php

namespace Usuario;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ImpressoraController extends Controller
{
    public function index(Request $request) {
        $o = shell_exec("lpstat -d -p");
        $res = explode("\n", $o);
        $printers = [];
        foreach ($res as $r)
        {
            if (strpos($r, "printer") !== FALSE)
            {
                $r = str_replace("printer ", "", $r);
                $r = explode(" ", $r);
                $printers[] = [
                    'label' => $r[0],
                    'value' => $r[0]
                ];
            }
        }

        return response()->json($printers, 200);
    }
}
