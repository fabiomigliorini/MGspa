<?php

namespace Mg\Portador;

use App\Mg\Portador\ExtratoBbService;
use App\Mg\Portador\SomatorioSaldoResource;
use Carbon\Carbon;
use Illuminate\Http\Request;

use Mg\MgController;

class PortadorController extends MgController
{

    public function index(Request $request)
    {
        $portadores = Portador::ativo()->orderBy('portador')->get();
        return PortadorResource::collection($portadores);
    }

    public function show(Request $request, $codportador)
    {
        $portador = Portador::findOrFail($codportador);
        return new PortadorResource($portador);
    }

    public function info(Request $request, $codportador)
    {
        $portador = Portador::findOrFail($codportador);

        return response()->json([
            'codportador'=>  $portador->codportador,
            'portador'=>  $portador->portador,
            'codfilial' => $portador->codfilial,
            'filial' => optional($portador->Filial)->filial,
            'codbanco' => $portador->codbanco,
            'banco' => optional($portador->Banco)->banco,
        ]);
    }

    public function importarOfx(Request $request)
    {
        $request->validate([
            'arquivos' => 'required',
            'arquivos.*' => 'required|mimes:txt,ofx'
        ],[
            'arquivos.required' => 'Nenhum arquivo enviado!',
            'arquivos.*.required' => 'Envie um arquivo!',
            'arquivos.*.mimes' => 'Somente arquivos OFX aceitos!',
        ]);

        $ret = [];
        foreach ($request->arquivos as $key => $arquivo) {
            $ofx = file_get_contents($arquivo->getRealPath());
            $ret[$arquivo->getClientOriginalName()] = PortadorService::importarOfx($ofx);
        }
        return $ret;
    }

    public function consultaExtrato(Request $request, $codportador)
    {

        $homologacao = true;
        $mes = $request->mes;
        $ano = $request->ano;

        $portador = Portador::findOrFail($codportador);

        if($homologacao){
            $portador->bbdevappkey = 'd1fa18b4902e4deab7107b2450e21995';
            $portador->bbclientid = 'eyJpZCI6ImY5MzRhZTktNDdhZi0iLCJjb2RpZ29QdWJsaWNhZG9yIjowLCJjb2RpZ29Tb2Z0d2FyZSI6MTM0MDM5LCJzZXF1ZW5jaWFsSW5zdGFsYWNhbyI6MX0';
            $portador->bbclientsecret = 'eyJpZCI6IiIsImNvZGlnb1B1YmxpY2Fkb3IiOjAsImNvZGlnb1NvZnR3YXJlIjoxMzQwMzksInNlcXVlbmNpYWxJbnN0YWxhY2FvIjoxLCJzZXF1ZW5jaWFsQ3JlZGVuY2lhbCI6MSwiYW1iaWVudGUiOiJob21vbG9nYWNhbyIsImlhdCI6MTc0NTY3NDY0MzE0OH0';
            $portador->agencia = '1505';
            $portador->conta = '1348';
        }

        $dataInicial = Carbon::create($ano, $mes, 1)->startOfDay();
        $dataFinal   = Carbon::create($ano, $mes, 1)->endOfMonth()->endOfDay();

        return ExtratoBbService::consultarExtrato($portador, $dataInicial, $dataFinal);
    }

    public function listaExtratos(Request $request, $codportador){
        //$per_page = $request->limit??50;
        $mes = $request->mes;
        $ano = $request->ano;

        $dataInicial = Carbon::create($ano, $mes, 1)->startOfDay();
        $dataFinal   = Carbon::create($ano, $mes, 1)->endOfMonth()->endOfDay();

        $extratosPage = PortadorService::listaMovimentacoes($codportador, $dataInicial, $dataFinal);

        return $extratosPage;
    }

    public function getIntervaloSaldos(){
        return PortadorService::getIntervaloTotalExtratos();
    }

    public function listaSaldos(Request $request){
        $dia = Carbon::createFromFormat('d-m-Y', $request->dia);

        $dados = PortadorService::listaSaldos($dia);

        return new SomatorioSaldoResource($dados);
    }

    public function listaSaldosPortador(Request $request, $codportador){
        $mes = $request->mes;
        $ano = $request->ano;

        $dataInicial = Carbon::create($ano, $mes, 1)->startOfDay();
        $dataFinal   = Carbon::create($ano, $mes, 1)->endOfMonth()->endOfDay();

        $dados = PortadorService::listaSaldosPortador($codportador, $dataInicial, $dataFinal);

        return $dados;
    }
}
