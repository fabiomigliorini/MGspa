<?php

namespace Mg\Select;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SelectImpressoraController extends Controller
{
    public static function index(Request $request)
    {
        $printers = json_decode(file_get_contents(base_path('printers.json')), true);
        $printers = array_merge(['' => ''], $printers);
        $ret = [];
        foreach ($printers as $value => $label) {
            $ret[] = [
                'label' => $label,
                'value' => $value,
            ];
        }
        return $ret;
    }

    public static function show($id)
    {
        $printers = json_decode(file_get_contents(base_path('printers.json')), true);
        if (!array_key_exists($id, $printers)) {
            abort(404);
        }
        return [
            'label' => $printers[$id],
            'value' => $id,
        ];
    }
}
