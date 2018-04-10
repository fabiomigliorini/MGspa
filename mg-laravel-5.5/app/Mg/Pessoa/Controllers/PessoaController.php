<?php

namespace App\Mg\Pessoa\Controllers;

use Illuminate\Http\Request;
use App\Mg\Controllers\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

use App\Mg\Pessoa\Models\Pessoa;
use App\Mg\Pessoa\Repositories\PessoaRepository;

class PessoaController extends MgController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        list($filter, $sort, $fields) = $this->parseSearchRequest($request);
        $qry = Pessoa::search($filter, $sort, $fields);
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
        $model = Pessoa::findOrFail($id, $request->get('fields'));

        return response()->json($model, 200);
    }

    public function autocomplete (Request $request)
    {
        $qry = PessoaRepository::autocomplete($request->all());

        return response()->json($qry, 206);
    }

}
