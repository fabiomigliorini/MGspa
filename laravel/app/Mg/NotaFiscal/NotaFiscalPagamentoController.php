<?php

namespace Mg\NotaFiscal;

use App\Http\Controllers\Controller;
use Mg\NotaFiscal\Requests\NotaFiscalPagamentoRequest;
use Mg\NotaFiscal\Resources\NotaFiscalPagamentoResource;
use Mg\NotaFiscal\NotaFiscalStatusService;
use Illuminate\Http\Request;

class NotaFiscalPagamentoController extends Controller
{
    public function index(Request $request, int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        $query = $nota->NotaFiscalPagamentoS()->with(['Pessoa']);

        return NotaFiscalPagamentoResource::collection($query->get());
    }

    public function show(int $codnotafiscal, int $codnotafiscalpagamento)
    {
        $pagamento = NotaFiscalPagamento::where('codnotafiscal', $codnotafiscal)
            ->where('codnotafiscalpagamento', $codnotafiscalpagamento)
            ->with(['Pessoa'])
            ->firstOrFail();

        return new NotaFiscalPagamentoResource($pagamento);
    }

    public function store(NotaFiscalPagamentoRequest $request, int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($nota);

        $data = array_merge($request->validated(), ['codnotafiscal' => $codnotafiscal]);
        $pagamento = NotaFiscalPagamento::create($data);

        return (new NotaFiscalPagamentoResource($pagamento))
            ->response()
            ->setStatusCode(201);
    }

    public function update(NotaFiscalPagamentoRequest $request, int $codnotafiscal, int $codnotafiscalpagamento)
    {
        $pagamento = NotaFiscalPagamento::where('codnotafiscal', $codnotafiscal)
            ->where('codnotafiscalpagamento', $codnotafiscalpagamento)
            ->firstOrFail();

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($pagamento->NotaFiscal);

        $pagamento->update($request->validated());

        return new NotaFiscalPagamentoResource($pagamento->fresh());
    }

    public function destroy(int $codnotafiscal, int $codnotafiscalpagamento)
    {
        $pagamento = NotaFiscalPagamento::where('codnotafiscal', $codnotafiscal)
            ->where('codnotafiscalpagamento', $codnotafiscalpagamento)
            ->firstOrFail();

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($pagamento->NotaFiscal);

        $pagamento->delete();

        return response()->noContent();
    }

    private function verificarNotaBloqueada(NotaFiscal $nota): void
    {
        if (!NotaFiscalStatusService::isEditable($nota)) {
            abort(422, "Não é possível modificar pagamentos de uma nota com status: {$nota->status}");
        }
    }
}
