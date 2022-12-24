<?php

namespace Mg\Pix;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

use Mg\Negocio\Negocio;
use Mg\Portador\Portador;
use Mg\Pix\GerenciaNet\GerenciaNetService;

class PixController
{

    public function criarPixCobNegocio (Request $request, $codnegocio)
    {
        $negocio = Negocio::findOrFail($codnegocio);
        $cob = PixService::criarPixCobNegocio($negocio);
        return new PixCobResource($cob);
    }

    public function transmitirPixCob (Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        PixService::transmitirPixCob($cob);
        return new PixCobResource($cob);
    }

    public function consultarPixCob (Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        PixService::consultarPixCob($cob);
        $ret = new PixCobResource($cob);
        return $ret;
    }

    public function brCodePixCob (Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        return BrCodeService::montar($cob);
    }

    public function show (Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        return new PixCobResource($cob);
    }

    public function consultarPix (Request $request, $codportador)
    {
        $portador = Portador::findOrFail($codportador);
        if ($inicio = $request->inicio) {
            $inicio = Carbon::parse($inicio);
        }
        if ($fim = $request->fim) {
            $fim = Carbon::parse($fim);
        }
        $ret = PixService::consultarPix(
            $portador,
            $inicio,
            $fim,
            $request->pagina??0
        );
        return response()->json([
            'success'=>true,
            'pix'=>PixResource::collection($ret['processados']),
            'parametros'=>$ret['parametros'],
        ], 200);
    }

    public function consultarPixTodos (Request $request)
    {
        $portadores = Portador::ativo()->whereNotNull('pixdict')->get();
        foreach ($portadores as $portador) {
            if ($inicio = $request->inicio) {
                $inicio = Carbon::parse($inicio);
            }
            if ($fim = $request->fim) {
                $fim = Carbon::parse($fim);
            }
            $ret = PixService::consultarPix(
                $portador,
                $inicio,
                $fim,
                $request->pagina??0
            );
            $resp[] = [
                'success'=>true,
                'pix'=>PixResource::collection($ret['processados']),
                'parametros'=>$ret['parametros'],
            ];
        }
        return response()->json($resp, 200);
    }

    public function detalhes (Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        return new PixCobResource($cob);
    }

    public function imprimirQrCode (Request $request, $codpixcob)
    {
        $request->validate([
            'impressora' => ['required', 'string']
        ]);
        $cob = PixCob::findOrFail($codpixcob);
        $pdf = PixService::imprimirQrCode($cob, $request->impressora);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="PixCob'.$codpixcob.'.pdf"'
        ]);
    }

    public function pdf (Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        $pdf = PixService::pdf($cob);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="PixCob'.$codpixcob.'.pdf"'
        ]);
    }


    public function webhook(Request $request)
    {
        Log::info('Recebendo Webhook PIX');
        $arquivo = PixJsonService::salvar($request->getContent());
        PixWebhookJob::dispatch($arquivo);
        return response()->json([
            'success'=>true,
            'arquivo'=>$arquivo
        ], 200);
    }

    public function index(Request $request)
    {

        $horarioinicial = null;
        if (!empty($request->horarioinicial)) {
            $horarioinicial = Carbon::createFromFormat('d/m/Y H:i', $request->horarioinicial);
        }

        $horariofinal = null;
        if (!empty($request->horariofinal)) {
            $horariofinal = Carbon::createFromFormat('d/m/Y H:i', $request->horariofinal);
        }

        $valorinicial = null;
        if (!empty($request->valorinicial)) {
            $valorinicial = floatval($request->valorinicial);
        }

        $valorfinal = null;
        if (!empty($request->valorfinal)) {
            $valorfinal = floatval($request->valorfinal);
        }

        $res = PixService::listagem(
            $request->page??1,
            $request->per_page??50,
            $request->sort??'horario',
            $request->nome??null,
            $request->cpf??null,
            $request->negocio??todos,
            $valorinicial,
            $valorfinal,
            $horarioinicial,
            $horariofinal
        );
        return response()->json($res, 200);
    }



}
