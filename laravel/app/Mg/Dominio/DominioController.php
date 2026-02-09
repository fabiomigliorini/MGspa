<?php

namespace Mg\Dominio;

use Illuminate\Http\Request;
use Mg\MgController;

use Mg\Empresa\Empresa;

use Carbon\Carbon;
use Mg\NaturezaOperacao\DominioAcumulador;

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
                    'acumuladores' => DominioAcumuladorResource::collection($filial->DominioAcumuladorS),
                ];
            }
            $ret[] = [
                'codempresa' => $empresa->codempresa,
                'empresa' => $empresa->empresa,
                'filiais' => $retFilial,
            ];
        }
        return $ret;
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

    public function salvarAcumulador (Request $request)
    {
        $acum = DominioAcumuladorService::salvar($request->all());
        return new DominioAcumuladorResource($acum);
    }

    public function excluirAcumulador (Request $request, $coddominioacumulador)
    {
        return DominioAcumuladorService::excluir($coddominioacumulador);
    }
}
