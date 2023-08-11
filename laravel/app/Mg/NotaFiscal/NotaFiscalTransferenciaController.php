<?php
namespace Mg\NotaFiscal;

use Mg\MgController;
use Illuminate\Http\Request;

class NotaFiscalTransferenciaController extends MgController 
{
    public function index(Request $request)
    {
        $data = $request->data;

        if(!$data){
        $data = 'now()';
        $ListarNotas =  NotaFiscalTransferenciaService::ListarNotasTransf($data);
            
        }else{
        $ListarNotas =  NotaFiscalTransferenciaService::ListarNotasTransf($data);
        }

        if ($ListarNotas == null){
            return response()->json('Erro! nada encontrado', 200);
        }else{
            return response()->json($ListarNotas, 200);
        }
    }

    public function GerarNovaTransferencia($codfilial)
    {
        if (!is_numeric($codfilial)){
            return response()->json('Apenas números são aceitos', 200);
        }
    
        $NovasTransf =  NotaFiscalTransferenciaService::geraTransferencias($codfilial);

        if (!$NovasTransf){
            return response()->json('Sem dados', 200);
        }
        
        return response()->json($NovasTransf, 200);
    }

    public function NotasPorEmitir()
    {

        $notas = NotaFiscalTransferenciaService::ListarNotasPorEmitir();

        if($notas){
            return response()->json($notas, 200);
        }else{
           return response()->json('Erro! nada encontrado', 200); 
        }
    }

    public function NotasNaoAutorizadas()
    {

        $naoautorizadas = NotaFiscalTransferenciaService::ListarNotasNaoAutorizadas();

        if($naoautorizadas){
            return response()->json($naoautorizadas, 200);
        }else{
           return response()->json('Erro! nada encontrado', 200); 
        }
    }


    public function NotasEmitidas(Request $request)
    {
        $data = $request->data;

        $Emitidas = NotaFiscalTransferenciaService::ListarNotasEmitidas($data);

        if($Emitidas){
            return response()->json($Emitidas, 200);
        }else{
           return response()->json('Erro! nada encontrado', 200); 
        }
    }

    public function NotasLancadas(Request $request)
    {
        $data = $request->data;
        $lancadas = NotaFiscalTransferenciaService::ListarNotasLancadas($data);

        if($lancadas){
            return response()->json($lancadas, 200);
        }else{
           return response()->json('Erro! nada encontrado', 200); 
        }
    }
}