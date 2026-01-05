<?php

namespace Mg\NotaFiscal;

use App\Http\Controllers\Controller;
use Mg\NotaFiscal\Requests\NotaFiscalCartaCorrecaoRequest;
use Mg\NotaFiscal\Resources\NotaFiscalCartaCorrecaoResource;
use Illuminate\Http\Request;

class NotaFiscalCartaCorrecaoController extends Controller
{
    public function index(Request $request, int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        $query = $nota->NotaFiscalCartaCorrecaoS();

        // Ordenação por sequência
        $query->orderBy('sequencia', 'desc');

        return NotaFiscalCartaCorrecaoResource::collection($query->get());
    }

    public function show(int $codnotafiscal, int $codnotafiscalcartacorrecao)
    {
        $carta = NotaFiscalCartaCorrecao::where('codnotafiscal', $codnotafiscal)
            ->where('codnotafiscalcartacorrecao', $codnotafiscalcartacorrecao)
            ->firstOrFail();

        return new NotaFiscalCartaCorrecaoResource($carta);
    }

    public function store(NotaFiscalCartaCorrecaoRequest $request, int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        // Verifica se a nota está autorizada
        if (!NotaFiscalService::isAutorizada($nota)) {
            abort(422, "Só é possível criar carta de correção para notas autorizadas.");
        }

        // Busca a última sequência
        $ultimaSequencia = $nota->NotaFiscalCartaCorrecaoS()->max('sequencia') ?? 0;

        $data = array_merge($request->validated(), [
            'codnotafiscal' => $codnotafiscal,
            'sequencia' => $ultimaSequencia + 1,
        ]);

        $carta = NotaFiscalCartaCorrecao::create($data);

        return (new NotaFiscalCartaCorrecaoResource($carta))
            ->response()
            ->setStatusCode(201);
    }

    public function update(NotaFiscalCartaCorrecaoRequest $request, int $codnotafiscal, int $codnotafiscalcartacorrecao)
    {
        $carta = NotaFiscalCartaCorrecao::where('codnotafiscal', $codnotafiscal)
            ->where('codnotafiscalcartacorrecao', $codnotafiscalcartacorrecao)
            ->firstOrFail();

        // Não permite alterar carta já protocolada
        if (!empty($carta->protocolo)) {
            abort(422, "Não é possível alterar uma carta de correção já protocolada.");
        }

        $carta->update($request->validated());

        return new NotaFiscalCartaCorrecaoResource($carta->fresh());
    }

    public function destroy(int $codnotafiscal, int $codnotafiscalcartacorrecao)
    {
        $carta = NotaFiscalCartaCorrecao::where('codnotafiscal', $codnotafiscal)
            ->where('codnotafiscalcartacorrecao', $codnotafiscalcartacorrecao)
            ->firstOrFail();

        // Não permite excluir carta já protocolada
        if (!empty($carta->protocolo)) {
            abort(422, "Não é possível excluir uma carta de correção já protocolada.");
        }

        $carta->delete();

        return response()->noContent();
    }

    public function pdf(int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        // Busca a última carta de correção (maior sequência)
        $carta = $nota->NotaFiscalCartaCorrecaoS()
            ->orderBy('sequencia', 'desc')
            ->first();

        if (!$carta) {
            abort(404, "Nenhuma carta de correção encontrada para esta nota fiscal.");
        }

        $pdf = NotaFiscalCartaCorrecaoPdfService::pdf($nota, $carta);

        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="CCe-' . $nota->numero . '-' . $carta->sequencia . '.pdf"'
        ]);
    }
}
