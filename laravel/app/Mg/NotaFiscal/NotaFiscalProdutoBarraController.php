<?php

namespace Mg\NotaFiscal;

use App\Http\Controllers\Controller;
use Mg\NotaFiscal\Requests\NotaFiscalProdutoBarraRequest;
use Mg\NotaFiscal\Requests\NotaFiscalProdutoBarraStoreRequest;
use Mg\NotaFiscal\Resources\NotaFiscalProdutoBarraResource;
use Mg\NotaFiscal\Resources\NotaFiscalDetailResource;
use Mg\Tributacao\TributacaoService;

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

    public function store(NotaFiscalProdutoBarraStoreRequest $request, int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($nota);

        $data = array_merge($request->validated(), ['codnotafiscal' => $codnotafiscal]);

        // Calcula tributação se não foi informada
        $item = new NotaFiscalProdutoBarra($data);

        // quantidade
        if (empty($item->quantidade)) {
            $item->quantidade = 1;
        }

        // valorunitario
        if (empty($item->valorunitario)) {
            $item->valorunitario = $item->ProdutoBarra->preco;
        }

        // valortotal
        if (empty($item->valortotal)) {
            $item->valortotal =
                ($item->valorunitario * $item->quantidade)
                - $item->valordesconto
                + $item->valorseguro
                + $item->valorfrete
                + $item->outras;
        }

        //ordem
        if (empty($item->ordem)) {
            $item->ordem = NotaFiscalProdutoBarra::where('codnotafiscal', $codnotafiscal)->max('ordem') + 1;
        }

        if (empty($item->codcfop)) {
            NotaFiscalProdutoBarraService::calcularTributacao($item);
        }
        $item->save();

        TributacaoService::recalcularTributosItem($item);

        // Recalcula os totais da nota
        NotaFiscalItemService::recalcularTotais($nota);

        return (new NotaFiscalDetailResource($nota->fresh()))
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

        // Recalcula tributação
        $item->fill($request->validated());
        $item->save();

        // salva tributos do item
        $data = $request->all();
        $codnotafiscalitemtributo = [];
        foreach ($data['tributos'] as $trib) {
            $it = NotaFiscalItemTributo::firstOrNew([
                'codnotafiscalprodutobarra' => $codnotafiscalprodutobarra,
                'codtributo' => $trib['codtributo']
            ]);
            $it->fill($trib);
            $it->save();
            $codnotafiscalitemtributo[] = $it->codnotafiscalitemtributo;
        }
        NotaFiscalItemTributo::where('codnotafiscalprodutobarra', $codnotafiscalprodutobarra)->whereNotIn('codnotafiscalitemtributo', $codnotafiscalitemtributo)->delete();

        // Recalcula os totais da nota
        $nota = $item->NotaFiscal;
        NotaFiscalItemService::recalcularTotais($nota);

        return (new NotaFiscalDetailResource($nota->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(int $codnotafiscal, int $codnotafiscalprodutobarra)
    {
        $item = NotaFiscalProdutoBarra::where('codnotafiscal', $codnotafiscal)
            ->where('codnotafiscalprodutobarra', $codnotafiscalprodutobarra)
            ->firstOrFail();

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($item->NotaFiscal);

        $nota = $item->NotaFiscal;
        $item->delete();

        // Recalcula os totais da nota
        NotaFiscalItemService::recalcularTotais($nota);

        return (new NotaFiscalDetailResource($nota->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    private function verificarNotaBloqueada(NotaFiscal $nota): void
    {
        if (!NotaFiscalStatusService::isEditable($nota)) {
            abort(422, "Não é possível modificar itens de uma nota com status: {$nota->status}");
        }
    }
}
