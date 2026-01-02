<?php

namespace Mg\NotaFiscal;

use App\Http\Controllers\Controller;
use Mg\NotaFiscal\Requests\NotaFiscalProdutoBarraRequest;
use Mg\NotaFiscal\Resources\NotaFiscalProdutoBarraResource;
use Illuminate\Http\Request;

class NotaFiscalProdutoBarraController extends Controller
{
    public function index(Request $request, int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        // TODO: Fazer o backend entender que se tiver embalagem, 
        // precisa buscar a Unidade da Embalagem
        $query = $nota->NotaFiscalProdutoBarraS()
            ->with([
                'ProdutoBarra.Produto',
                // 'ProdutoBarra.Produto.Unidade',
                'Cfop',
                'NotaFiscalItemTributoS.Tributo'
            ]);

        // Ordenação
        $query->orderBy('ordem', 'asc');

        return NotaFiscalProdutoBarraResource::collection($query->get());
    }

    public function show(int $codnotafiscal, int $codnotafiscalprodutobarra)
    {
        $item = NotaFiscalProdutoBarra::where('codnotafiscal', $codnotafiscal)
            ->where('codnotafiscalprodutobarra', $codnotafiscalprodutobarra)
            ->with([
                'ProdutoBarra.Produto',
                // 'ProdutoBarra.Produto.Unidade',
                'Cfop',
                'NotaFiscalItemTributoS.Tributo'
            ])
            ->firstOrFail();

        return new NotaFiscalProdutoBarraResource($item);
    }

    public function store(NotaFiscalProdutoBarraRequest $request, int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($nota);

        $data = array_merge($request->validated(), ['codnotafiscal' => $codnotafiscal]);

        // Calcula tributação se não foi informada
        $item = new NotaFiscalProdutoBarra($data);
        if (empty($item->icmsvalor)) {
            NotaFiscalProdutoBarraService::calcularTributacao($item);
        }

        $item->save();

        return (new NotaFiscalProdutoBarraResource($item))
            ->response()
            ->setStatusCode(201);
    }

    public function update(NotaFiscalProdutoBarraRequest $request, int $codnotafiscal, int $codnotafiscalprodutobarra)
    {
        $item = NotaFiscalProdutoBarra::where('codnotafiscal', $codnotafiscal)
            ->where('codnotafiscalprodutobarra', $codnotafiscalprodutobarra)
            ->firstOrFail();

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($item->NotaFiscal);

        $item->fill($request->validated());

        // Recalcula tributação
        NotaFiscalProdutoBarraService::calcularTributacao($item);

        $item->save();

        return new NotaFiscalProdutoBarraResource($item->fresh());
    }

    public function destroy(int $codnotafiscal, int $codnotafiscalprodutobarra)
    {
        $item = NotaFiscalProdutoBarra::where('codnotafiscal', $codnotafiscal)
            ->where('codnotafiscalprodutobarra', $codnotafiscalprodutobarra)
            ->firstOrFail();

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($item->NotaFiscal);

        $item->delete();

        return response()->noContent();
    }

    private function verificarNotaBloqueada(NotaFiscal $nota): void
    {
        if (in_array($nota->status, [NotaFiscalService::STATUS_AUTORIZADA, NotaFiscalService::STATUS_CANCELADA, NotaFiscalService::STATUS_INUTILIZADA])) {
            abort(422, "Não é possível modificar itens de uma nota com status: {$nota->status}");
        }
    }
}
