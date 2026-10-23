<?php

namespace Mg\Pix;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

use Mg\Negocio\Negocio;
use Mg\Portador\Portador;
use Mg\Usuario\Autorizador;

class PixController
{

    public function criarPixCobNegocio(Request $request, $codnegocio)
    {
        $negocio = Negocio::findOrFail($codnegocio);
        $cob = PixService::criarPixCobNegocio($negocio);
        return new PixCobResource($cob);
    }

    public function transmitirPixCob(Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        PixService::transmitirPixCob($cob);
        return new PixCobResource($cob);
    }

    public function consultarPixCob(Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        PixService::consultarPixCob($cob);
        $ret = new PixCobResource($cob);
        return $ret;
    }

    public function brCodePixCob(Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        return BrCodeService::montar($cob);
    }

    public function show(Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        return new PixCobResource($cob);
    }

    public function portadores(Request $request)
    {
        Autorizador::autoriza(['Administrador', 'Financeiro', 'Caixa']);
        $qry = Portador::ativo()->whereNotNull('pixdict')->orderBy('codfilial');
        if ($request->codfilial) {
            $qry->where('codfilial', $request->codfilial);
        }
        return response()->json($qry->get(['codportador', 'portador']), 200);
    }

    public function consultarPix(Request $request, $codportador)
    {
        Autorizador::autoriza(['Administrador', 'Financeiro', 'Caixa']);
        if ($inicio = $request->inicio) {
            $inicio = Carbon::parse($inicio);
        } else {
            $inicio = Carbon::today()->addDays(-1);
        }
        if ($fim = $request->fim) {
            $fim = Carbon::parse($fim);
        } else {
            $fim = Carbon::now();
        }
        $portador = Portador::findOrFail($codportador);
        $ret = PixService::consultarPix(
            $portador,
            $inicio,
            $fim,
            $request->pagina ?? 0
        );
        return response()->json([
            'success' => true,
            'pix' => PixResource::collection($ret['processados']),
            'parametros' => $ret['parametros'],
        ], 200);
    }

    public function consultarPixTodos(Request $request)
    {
        Autorizador::autoriza(['Administrador', 'Financeiro', 'Caixa']);
        if ($inicio = $request->inicio) {
            $inicio = Carbon::parse($inicio);
        } else {
            $inicio = Carbon::today()->addDays(-1);
        }
        if ($fim = $request->fim) {
            $fim = Carbon::parse($fim);
        } else {
            $fim = Carbon::now();
        }
        $qry = Portador::ativo()->whereNotNull('pixdict')->orderBy('codfilial');
        if ($request->codfilial) {
            $qry->where('codfilial', $request->codfilial);
        }
        $portadores = $qry->get();
        $resp = [];
        foreach ($portadores as $portador) {
            try {
                $processados = collect([]);
                $paginaAtual = 0;
                do {
                    $ret = PixService::consultarPix(
                        $portador,
                        $inicio,
                        $fim,
                        $paginaAtual
                    );
                    $processados = $processados->merge($ret['processados']);
                    $pag = $ret['parametros']['paginacao'] ?? null;
                    $continuar = $pag && ($pag['paginaAtual'] < ($pag['quantidadeDePaginas'] - 1));
                    if ($continuar) {
                        $paginaAtual = $pag['paginaAtual'] + 1;
                    }
                } while ($continuar);
                $resp[] = [
                    'success' => true,
                    'portador' => $portador->portador,
                    'codportador' => $portador->codportador,
                    'processados' => count($processados),
                ];
            } catch (\Exception $e) {
                Log::warning("Erro ao consultar PIX do Portador {$portador->codportador} ({$portador->portador}): {$e->getMessage()}");
                $resp[] = [
                    'success' => false,
                    'portador' => $portador->portador,
                    'codportador' => $portador->codportador,
                    'message' => $e->getMessage(),
                ];
            }
        }
        return response()->json($resp, 200);
    }

    public function detalhes(Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        return new PixCobResource($cob);
    }

    public function imprimirQrCode(Request $request, $codpixcob)
    {
        $request->validate([
            'impressora' => ['required']
        ]);
        $cob = PixCob::findOrFail($codpixcob);
        $pdf = PixService::imprimirQrCode($cob, $request->impressora);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="PixCob' . $codpixcob . '.pdf"'
        ]);
    }

    public function pdf(Request $request, $codpixcob)
    {
        $cob = PixCob::findOrFail($codpixcob);
        $pdf = PixService::pdf($cob);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="PixCob' . $codpixcob . '.pdf"'
        ]);
    }


    public function webhook(Request $request)
    {
        Log::info('Recebendo Webhook PIX');
        $arquivo = PixJsonService::salvar($request->getContent());
        PixWebhookJob::dispatch($arquivo);
        return response()->json([
            'success' => true,
            'arquivo' => $arquivo
        ], 200);
    }

    public function index(Request $request)
    {
        Autorizador::autoriza(['Administrador', 'Financeiro', 'Caixa']);

        $horarioinicial = empty($request->horarioinicial) ? null : Carbon::parse($request->horarioinicial);
        $horariofinal = empty($request->horariofinal) ? null : Carbon::parse($request->horariofinal);

        $valorinicial = null;
        if (!empty($request->valorinicial)) {
            $valorinicial = floatval($request->valorinicial);
        }

        $valorfinal = null;
        if (!empty($request->valorfinal)) {
            $valorfinal = floatval($request->valorfinal);
        }

        $res = PixService::listagem(
            $request->page ?? 1,
            $request->per_page ?? 50,
            $request->sort ?? 'horario',
            $request->nome ?? null,
            $request->cpf ?? null,
            $request->negocio ?? 'todos',
            $valorinicial,
            $valorfinal,
            $horarioinicial,
            $horariofinal,
            $request->uso ?? 'todos'
        );
        return response()->json($res, 200);
    }

    // o PIX para usar (Receber Titulos, adiantamento): valor, data, conta, a
    // pessoa do CPF/CNPJ do pagador e se ja' foi usado (TASK-219)
    public function pix(Request $request, int $codpix)
    {
        Autorizador::autoriza(['Administrador', 'Financeiro', 'Caixa']);
        return ['data' => static::uso(Pix::findOrFail($codpix))];
    }

    // marcar e desmarcar 'a mao, no PIX sem pagamento ligado
    public function marcar(Request $request, int $codpix)
    {
        Autorizador::autoriza(['Administrador', 'Financeiro', 'Caixa']);
        DB::beginTransaction();
        $pix = PixUsoService::marcar($codpix);
        DB::commit();
        return ['data' => static::uso($pix->fresh())];
    }

    public function desmarcar(Request $request, int $codpix)
    {
        Autorizador::autoriza(['Administrador', 'Financeiro', 'Caixa']);
        DB::beginTransaction();
        $pix = PixUsoService::desmarcar($codpix);
        DB::commit();
        return ['data' => static::uso($pix->fresh())];
    }

    private static function uso(Pix $pix): array
    {
        $codpessoa = PixService::pessoaDoPagador($pix->cpf, $pix->cnpj);
        return [
            'codpix' => $pix->codpix,
            'valor' => (float) $pix->valor,
            'horario' => optional($pix->horario)->format('Y-m-d\TH:i:s'),
            'nome' => $pix->nome,
            'cpf' => $pix->cpf,
            'cnpj' => $pix->cnpj,
            'codportador' => $pix->codportador,
            'portador' => optional($pix->Portador)->portador,
            'codpessoa' => $codpessoa,
            'utilizacao' => optional($pix->utilizacao)->format('Y-m-d\TH:i:s'),
            'usuarioutilizacao' => optional($pix->UsuarioUtilizacao)->usuario,
            'codpagamento' => $pix->codpagamento,
        ];
    }


    public function descobreNome(Request $request)
    {
        Autorizador::autoriza(['Administrador', 'Financeiro', 'Caixa']);
        $busca = PixService::descobreNome($request->cnpjCpf);
        return response()->json($busca, 200);
    }
}
