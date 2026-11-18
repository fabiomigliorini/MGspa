<?php

namespace Mg\NotaFiscalServico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mg\Filial\Filial;
use Mg\NotaFiscalServico\Resources\DistribuicaoNfseResource;

/**
 * Caixa de DF-e da NFS-e (TASK-279): lista do que o ADN distribuiu, XML, reprocessar e
 * a consulta por novos documentos (um lote por chamada, a tela pagina).
 */
class NfseDistribuicaoController extends Controller
{
    public function index(Request $request)
    {
        return DistribuicaoNfseResource::collection(
            NfseDistribuicaoService::pesquisar($request->all())->paginate($request->get('per_page', 50))
        );
    }

    public function filiaisHabilitadas()
    {
        return response()->json(NfseDistribuicaoService::filiaisHabilitadas());
    }

    public function consultar(int $codfilial, ?int $nsu = null)
    {
        $filial = Filial::findOrFail($codfilial);
        if (!$filial->emitenfse) {
            abort(422, "A filial {$filial->filial} não está configurada para NFS-e.");
        }
        return response()->json(NfseDistribuicaoService::consultar($filial, $nsu));
    }

    // Busca de novo o documento desta linha no ADN, pelo NSU
    public function consultarNsu(int $coddistribuicaonfse)
    {
        $dist = DistribuicaoNfse::findOrFail($coddistribuicaonfse);
        return response()->json(NfseDistribuicaoService::consultarNsu($dist->Filial, $dist->nsu));
    }

    // Eventos de uma NFS-e no ADN, pela chave
    public function consultarEventos(int $codfilial, string $chave)
    {
        $filial = Filial::findOrFail($codfilial);
        if (!$filial->emitenfse) {
            abort(422, "A filial {$filial->filial} não está configurada para NFS-e.");
        }
        return response()->json(NfseDistribuicaoService::consultarEventos($filial, $chave));
    }

    public function xml(int $coddistribuicaonfse)
    {
        $xml = NfseDistribuicaoService::xml(DistribuicaoNfse::findOrFail($coddistribuicaonfse));
        if (empty($xml)) {
            abort(404, 'Arquivo XML não encontrado.');
        }
        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    public function processar(int $coddistribuicaonfse)
    {
        $dist = NfseDistribuicaoService::processar(DistribuicaoNfse::findOrFail($coddistribuicaonfse));
        return new DistribuicaoNfseResource($dist->fresh(['Filial', 'NotaFiscalServico']));
    }
}
