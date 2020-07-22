<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SelectImpressoraController extends Controller
{
    public static function index(Request $request)
    {
        $o = shell_exec("lpstat -d -p");
        $res = explode("\n", $o);
        foreach ($res as $r) {
            if (strpos($r, "printer") !== false) {
                $r = str_replace("printer ", "", $r);
                $r = explode(" ", $r);
                $printers[] = [
                    'label' => $r[0],
                    'value' => $r[0]
                ];
            }
        }
        return $printers;
    }
}
