<?php

namespace App\Mg\Filial\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

use App\Mg\Filial\Models\Filial;

class FilialController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        list($filter, $sort, $fields) = $this->parseSearchRequest($request);
        $qry = Filial::search($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());

        return response()->json($res, 206);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $model = Filial::findOrFail($id, $request->get('fields'));

        return response()->json($model, 200);
    }

    public function parseSearchRequest(Request $request)
    {
        $req = $request->all();

        $sort = $request->sort;
        if (!empty($sort)) {
            $sort = explode(',', $sort);
        }

        $fields = $request->fields;
        if (!empty($fields)) {
            $fields = explode(',', $fields);
        }

        $filter = $request->all();

        unset($filter['fields']);
        unset($filter['sort']);
        unset($filter['page']);

        return [
            $filter,
            $sort,
            $fields,
        ];
    }

}
