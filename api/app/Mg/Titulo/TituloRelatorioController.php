<?php

namespace Mg\Titulo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
// use Illuminate\Support\Facades\DB;


class TituloRelatorioController extends Controller
{
    public function relatorio(Request $request)
    {
        $pessoas = TituloRelatorioService::listagem($request->all());
        return view('titulo-relatorio.relatorio', ['pessoas' => $pessoas]);
    }

    public function relatorioPdf(Request $request)
    {
        $pdf = TituloRelatorioService::pdf($request->all());
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="RelatorioAberto' . date('YmdHis') . '.pdf"'
        ]);
    }
}
