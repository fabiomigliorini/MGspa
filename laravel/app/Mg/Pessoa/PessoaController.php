<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class PessoaController extends MgController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        list($filter, $sort, $fields) = $this->filtros($request);
        $qry = PessoaService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());
        return response()->json($res, 200);
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
        $qry = PessoaService::autocomplete($request->all());
        return response()->json($qry, 200);
    }

    public function novaPessoa (Request $request)
    {
        return PessoaService::novaPessoa();
        //return response()->json($qry, 206);
    }

    public function comandaVendedor (Request $request, $codpessoa)
    {
        $pessoa = Pessoa::findOrFail($codpessoa);
        if (!$pessoa->vendedor) {
            throw new \Exception("\"{$pessoa->fantasia}\" não é vendedor!", 1);
        }
        $pdf = PessoaComandaVendedorService::pdf($pessoa);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Comanda'.$codpessoa.'.pdf"'
        ]);
    }

    public function comandaVendedorImprimir (Request $request, $codpessoa)
    {
        $request->validate([
            'impressora' => ['required', 'string'],
            'copias' => ['required', 'integer']
        ]);
        $pessoa = Pessoa::findOrFail($codpessoa);
        if (!$pessoa->vendedor) {
            throw new \Exception("\"{$pessoa->fantasia}\" não é vendedor!", 1);
        }
        $pdf = PessoaComandaVendedorService::imprimir($pessoa, $request->impressora, $request->copias);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Comanda'.$codpessoa.'.pdf"'
        ]);
    }

    public function detalhes (Request $request, $codpessoa)
    {
        $pessoa = Pessoa::findOrFail($codpessoa);
        return new PessoaResource($pessoa);
    }


}
