<?php

namespace Mg\Dominio;

use Illuminate\Http\Request;
use Mg\MgController;

use Mg\Filial\Empresa;

use Carbon\Carbon;


class DominioController extends MgController
{

    public function empresas(Request $request)
    {
        $empresas = Empresa::orderBy('codempresa')->get();
        $ret = [];
        foreach ($empresas as $empresa) {
            $filiais = $empresa->FilialS()
                ->whereNull('inativo')
                ->whereNotNull('empresadominio')
                ->orderBy('codfilial')
                ->get();
            if ($filiais->count() <= 0) {
                continue;
            }
            $retFilial = [];
            foreach ($filiais as $filial) {
                $retFilial[] = [
                    'codfilial' => $filial->codfilial,
                    'filial' => $filial->filial,
                    'empresadominio' => $filial->empresadominio,
                ];
            }
            $ret[] = [
                'codempresa' => $empresa->codempresa,
                'empresa' => $empresa->empresa,
                'filiais' => $retFilial,
            ];
        }
        return $ret;
        // $filiais = Filial::where('');
        // $mes = Carbon::parse($request->mes);
        // return DominioService::estoque($codfilial, $mes);
    }

    // public function estoque(Request $request)
    public function estoque(DominioRequest $request)
    {
        $codfilial = (int) $request->codfilial;
        $mes = Carbon::parse($request->mes);
        return DominioService::estoque($codfilial, $mes);
    }

    public function produto(DominioRequest $request)
    {
        $codfilial = (int) $request->codfilial;
        $mes = Carbon::parse($request->mes);
        return DominioService::produto($codfilial, $mes);
    }

    public function pessoa(DominioRequest $request)
    {
        $codfilial = (int) $request->codfilial;
        $mes = Carbon::parse($request->mes);
        return DominioService::pessoa($codfilial, $mes);
    }

    public function entrada(DominioRequest $request)
    {
        $codfilial = (int) $request->codfilial;
        $mes = Carbon::parse($request->mes);
        return DominioService::entrada($codfilial, $mes);
    }

    public function nfeSaida(DominioXMLRequest $request)
    {
        $codfilial = (int) $request->codfilial;
        $modelo = (int) $request->modelo;
        $mes = Carbon::parse($request->mes);
        ini_set('max_execution_time', '120');
        return DominioXMLService::nfeSaida($codfilial, $mes, $modelo);
    }

    public function nfeEntrada(DominioRequest $request)
    {
        $codfilial = (int) $request->codfilial;
        $mes = Carbon::parse($request->mes);
        return DominioXMLService::nfeEntrada($codfilial, $mes);
    }

}
