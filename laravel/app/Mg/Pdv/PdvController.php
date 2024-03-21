<?php

namespace Mg\Pdv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\Negocio\NegocioResource;
use Mg\Negocio\NegocioListagemResource;
use Mg\Negocio\Negocio;
use Mg\NotaFiscal\NotaFiscalService;
use Mg\PagarMe\PagarMePedidoResource;
use Mg\Pix\PixService;
use Mg\PagarMe\PagarMeService;
use Mg\PagarMe\PagarMePedido;

class PdvController
{

    public function getDispositivo(Request $request)
    {
        $pdvs = Pdv::orderBy('criacao', 'desc')->get();
        return PdvResource::collection($pdvs);
    }

    public function putDispositivo(Request $request)
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

    public static function autorizar($codpdv)
    {
        $pdv = Pdv::findOrFail($codpdv);
        $pdv = PdvService::autorizar($pdv);
        return new PdvResource($pdv);
    }

    public static function desautorizar($codpdv)
    {
        $pdv = Pdv::findOrFail($codpdv);
        $pdv = PdvService::desautorizar($pdv);
        return new PdvResource($pdv);
    }

    public static function inativar($codpdv)
    {
        $pdv = Pdv::findOrFail($codpdv);
        $pdv = PdvService::inativar($pdv);
        return new PdvResource($pdv);
    }

    public static function reativar($codpdv)
    {
        $pdv = Pdv::findOrFail($codpdv);
        $pdv = PdvService::reativar($pdv);
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

    public function getNegocios(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);

        $qry = Negocio::query();

        foreach ($request->all() as $filtro => $valor) {
            if (empty($valor)) {
                continue;
            }
            switch ($filtro) {
                case 'valor_de':
                    $qry->where('valortotal', '>=', $valor);
                    break;
                case 'valor_ate':
                    $qry->where('valortotal', '<=', $valor);
                    break;
                case 'lancamento_de':
                    $qry->where('lancamento', '>=', $valor);
                    break;
                case 'lancamento_ate':
                    $qry->where('lancamento', '<=', $valor);
                    break;
                case 'codnegocio':
                    $qry->where('codnegocio', $valor);
                    break;
                case 'codestoquelocal':
                    $qry->where('codestoquelocal', $valor);
                    break;
                case 'codnegociostatus':
                    $qry->where('codnegociostatus', $valor);
                    break;
                case 'codnaturezaoperacao':
                    $qry->where('codnaturezaoperacao', $valor);
                    break;
                case 'codpessoa':
                    $qry->where('codpessoa', $valor);
                    break;
                case 'codpessoavendedor':
                    $qry->where('codpessoavendedor', $valor);
                    break;
                case 'codpessoatransportador':
                    $qry->where('codpessoatransportador', $valor);
                    break;
                case 'codpdv':
                    $qry->where('codpdv', $valor);
                    break;
                case 'codusuario':
                    $qry->where('codusuario', $valor);
                    break;
                case 'integracao':
                    if (sizeof($valor) == 2) {
                        // se todos nao precisa fazer nenhum filtro
                        break;
                    }
                    $integracao = ($valor[0] != 'Manual');
                    $qry->whereIn('codnegocio', function ($query) use ($integracao) {
                        $query->select('codnegocio')
                            ->from('tblnegocioformapagamento')
                            ->whereRaw('tblnegocioformapagamento.codnegocio = tblnegocio.codnegocio')
                            ->where('integracao', $integracao);
                    });
                    break;
                case 'codformapagamento':
                    $qry->whereIn('codnegocio', function ($query) use ($valor) {
                        $query->select('codnegocio')
                            ->from('tblnegocioformapagamento')
                            ->whereRaw('tblnegocioformapagamento.codnegocio = tblnegocio.codnegocio')
                            ->whereIn('codformapagamento', $valor);
                    });
                    break;
                case 'pdv':
                    break;
                default:
                    break;
            }
        }
        $qry->orderBy('lancamento', 'desc')->orderBy('codnegocio', 'desc');
        return NegocioListagemResource::collection($qry->paginate(100));
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
        $modelo = intval($request->modelo ?? 65);
        $negocio = Negocio::findOrFail($request->codnegocio);
        NotaFiscalService::gerarNotaFiscalDoNegocio($negocio, $modelo);
        return new NegocioResource($negocio);
    }


    public function conferencia(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        $request->validate([
            'codpdv' => 'required',
            'dia' => 'required'
        ]);
        $negocios = PdvService::conferencia($request->codpdv, $request->dia);
        return response()->json($negocios, 200);
    }

    public function update(PdvRequest $request, $codpdv)
    {

        PdvService::autoriza($request->pdv);
        $pdv =  Pdv::findOrFail($codpdv);
        $data = $request->all();

        unset($data['pdv']);

        $pdvUpdate = PdvService::update($pdv, $data);

        return new PdvResource($pdvUpdate);
    }
}
