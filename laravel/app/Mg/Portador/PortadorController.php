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

    public function consultaExtrato(Request $request)
    {

        //$portador = new Portador();
        $portador = Portador::findOrFail(1);

        $portador->bbdevappkey = 'd1fa18b4902e4deab7107b2450e21995';
        $portador->bbclientid = 'eyJpZCI6ImY5MzRhZTktNDdhZi0iLCJjb2RpZ29QdWJsaWNhZG9yIjowLCJjb2RpZ29Tb2Z0d2FyZSI6MTM0MDM5LCJzZXF1ZW5jaWFsSW5zdGFsYWNhbyI6MX0';
        $portador->bbclientsecret = 'eyJpZCI6IiIsImNvZGlnb1B1YmxpY2Fkb3IiOjAsImNvZGlnb1NvZnR3YXJlIjoxMzQwMzksInNlcXVlbmNpYWxJbnN0YWxhY2FvIjoxLCJzZXF1ZW5jaWFsQ3JlZGVuY2lhbCI6MSwiYW1iaWVudGUiOiJob21vbG9nYWNhbyIsImlhdCI6MTc0NTY3NDY0MzE0OH0';
        $portador->agencia = '1505';
        $portador->conta = '1348';

        $dataInicioMovimento = Carbon::now()->subDays(16);
        //$dataInicioMovimento = null;
        $dataFimMovimento = Carbon::now()->subDays(0);
        //$dataFimMovimento = null;
        return ExtratoBbService::consultarExtrato($portador, $dataInicioMovimento, $dataFimMovimento);


    }

    public function listaExtratos(Request $request, $codportador){
        $per_page = $request->limit??50;
        $dataInicial = Carbon::parse($request->data_inicial);
        $dataFinal = Carbon::parse($request->data_final);

        $extratosPage = ExtratoBbService::listaExtratos($codportador, $dataInicial, $dataFinal, $per_page);

        return response()->json([
            'data' => $extratosPage->items(),
            'current_page' => $extratosPage->currentPage(),
            'last_page' => $extratosPage->lastPage(),
            'total' => $extratosPage->total()
        ]);
    }

    public function getIntervaloSaldos(){
        return PortadorService::getIntervaloTotalExtratos();
    }

    public function listaSaldos(Request $request){
        //Todo Tratar se vier vazio
        $dataInicial = Carbon::parse($request->data_inicial);
        $dataFinal = Carbon::parse($request->data_final);

        $dados = PortadorService::listaSaldos($dataInicial, $dataFinal);

        return new SomatorioSaldoResource($dados);
    }
}
