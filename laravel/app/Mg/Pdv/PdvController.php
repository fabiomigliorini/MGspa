<?php

namespace Mg\Pdv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\Negocio\NegocioResource;
use Mg\Negocio\Negocio;
use Mg\NotaFiscal\NotaFiscalService;
use Mg\PagarMe\PagarMePedidoResource;
use Mg\Pix\PixService;
use Mg\PagarMe\PagarMeService;
use Mg\PagarMe\PagarMePedido;

class PdvController
{

    public function dispositivo(Request $request)
    {
        $request->validate([
            'uuid' => 'required|uuid',
            'desktop' => 'required|boolean',
            'navegador' => 'required',
            'versaonavegador' => 'required',
            'plataforma' => 'required',
        ]);
        $pdv = PdvService::dispositivo(
            $request->uuid,
            $request->ip(),
            $request->latitude,
            $request->longitude,
            $request->precisao,
            $request->desktop,
            $request->navegador,
            $request->versaonavegador,
            $request->plataforma
        );
        return new PdvResource($pdv);
    }

    public function produtoCount(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::produtoCount();
    }

    public function produto(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        $codprodutobarra = $request->codprodutobarra ?? 0;
        $limite = $request->limite ?? 10000;
        return PdvService::produto($codprodutobarra, $limite);
    }

    public function pessoaCount(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::pessoaCount();
    }

    public function pessoa(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        $codpessoa = $request->codpessoa ?? 0;
        $limite = $request->limite ?? 10000;
        return PdvService::pessoa($codpessoa, $limite);
    }

    public function naturezaOperacao(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::naturezaOperacao();
    }

    public function estoqueLocal(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::estoqueLocal();
    }

    public function formaPagamento(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::formaPagamento();
    }

    public function impressora(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::impressora();
    }

    public function putNegocio(PdvRequest $request)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $negocio = PdvNegocioService::negocio($request->negocio, $pdv);
        return new NegocioResource($negocio);
    }

    public function deleteNegocio(PdvRequest $request, $codnegocio)
    {
        DB::beginTransaction();
        $pdv = PdvService::autoriza($request->pdv);
        $request->validate([
            'justificativa' => [
                'required',
                'string',
                'min:15',
            ]
        ]);        
        $negocio = Negocio::findOrFail($codnegocio);
        $negocio = PdvNegocioService::cancelar($negocio, $pdv, $request->justificativa);
        DB::commit();
        return new NegocioResource($negocio);
    }

    public function getNegocio(PdvRequest $request, $codnegocio)
    {
        PdvService::autoriza($request->pdv);
        if (preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', $codnegocio)) {
            $negocio = Negocio::where(['uuid' => $codnegocio])->firstOrFail();
        } else {
            $negocio = Negocio::findOrFail($codnegocio);
        }
        return new NegocioResource($negocio);
    }

    public function fecharNegocio(PdvRequest $request, $codnegocio)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $negocio = Negocio::findOrFail($codnegocio);
        $negocio = PdvNegocioService::fechar($negocio, $pdv);
        return new NegocioResource($negocio);
    }

    public function romaneio($codnegocio)
    {
        $negocio = Negocio::findOrFail($codnegocio);
        $pdf = RomaneioService::pdf($negocio);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Romaneio' . $codnegocio . '.pdf"'
        ]);
    }

    public function imprimirRomaneio($codnegocio, $impressora)
    {
        RomaneioService::imprimir($codnegocio, $impressora);
    }

    public function criarPixCob(PdvRequest $request)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $negocio = Negocio::findOrFail($request->codnegocio);
        PixService::criarPixCobPdv($request->valor, $pdv, $negocio);
        return new NegocioResource($negocio);
    }

    public function criarPagarMePedido(PdvRequest $request)
    {
        $data = (object) $request->all();
        $pdv = PdvService::autoriza($request->pdv);
        $negocio = Negocio::findOrFail($request->codnegocio);
        PagarMeService::cancelarPedidosAbertosPos($data->codpagarmepos);
        PagarMeService::criarPedido(
            null,
            $data->codpagarmepos,
            $data->tipo,
            $data->valor,
            $data->valorjuros ?? 0,
            ($data->valorjuros ?? 0) + ($data->valor ?? 0),
            $data->valorparcela ?? 0,
            $data->parcelas,
            $data->jurosloja,
            $data->descricao,
            $data->codnegocio,
            $pdv->codpdv,
            $data->codpessoa
        );
        return new NegocioResource($negocio);
    }

    public function consultarPagarMePedido($codpagarmepedido)
    {
        $pedido = PagarMePedido::findOrFail($codpagarmepedido);
        $pedido = PagarMeService::consultarPedido($pedido);
        return new PagarMePedidoResource($pedido);
    }

    public function  notaFiscal(PdvRequest $request, $codnegocio) 
    {
        PdvService::autoriza($request->pdv);
        $modelo = intval($request->modelo??65);
        $negocio = Negocio::findOrFail($request->codnegocio);
        NotaFiscalService::gerarNotaFiscalDoNegocio($negocio, $modelo);
        return new NegocioResource($negocio);
    }

}
