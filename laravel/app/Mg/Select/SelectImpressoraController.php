<?php

namespace Mg\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SelectImpressoraController extends Controller
{
    public static function index(Request $request)
    {
        $printers = json_decode(file_get_contents(base_path('printers.json')), true);
        $printers = array_merge([''=>''], $printers);
        $ret = [];
        foreach ($printers as $value => $label) {
            $ret[] = [
                'label' => $label,
                'value' => $value
            ];
        }
        return $ret;
    }
}
