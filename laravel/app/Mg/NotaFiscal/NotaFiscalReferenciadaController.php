<?php

namespace Mg\NotaFiscal;

use App\Http\Controllers\Controller;
use Mg\NotaFiscal\Requests\NotaFiscalReferenciadaRequest;
use Mg\NotaFiscal\Resources\NotaFiscalReferenciadaResource;
use Illuminate\Http\Request;

class NotaFiscalReferenciadaController extends Controller
{
    public function index(Request $request, int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        $query = $nota->NotaFiscalReferenciadaS();

        return NotaFiscalReferenciadaResource::collection($query->get());
    }

    public function show(int $codnotafiscal, int $codnotafiscalreferenciada)
    {
        $referenciada = NotaFiscalReferenciada::where('codnotafiscal', $codnotafiscal)
            ->where('codnotafiscalreferenciada', $codnotafiscalreferenciada)
            ->firstOrFail();

        return new NotaFiscalReferenciadaResource($referenciada);
    }

    public function store(NotaFiscalReferenciadaRequest $request, int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($nota);

        $data = array_merge($request->validated(), ['codnotafiscal' => $codnotafiscal]);
        $referenciada = NotaFiscalReferenciada::create($data);

        return (new NotaFiscalReferenciadaResource($referenciada))
            ->response()
            ->setStatusCode(201);
    }

    public function update(NotaFiscalReferenciadaRequest $request, int $codnotafiscal, int $codnotafiscalreferenciada)
    {
        $referenciada = NotaFiscalReferenciada::where('codnotafiscal', $codnotafiscal)
            ->where('codnotafiscalreferenciada', $codnotafiscalreferenciada)
            ->firstOrFail();

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($referenciada->NotaFiscal);

        $referenciada->update($request->validated());

        return new NotaFiscalReferenciadaResource($referenciada->fresh());
    }

    public function destroy(int $codnotafiscal, int $codnotafiscalreferenciada)
    {
        $referenciada = NotaFiscalReferenciada::where('codnotafiscal', $codnotafiscal)
            ->where('codnotafiscalreferenciada', $codnotafiscalreferenciada)
            ->firstOrFail();

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($referenciada->NotaFiscal);

        $referenciada->delete();

        return response()->noContent();
    }

    private function verificarNotaBloqueada(NotaFiscal $nota): void
    {
        if (!NotaFiscalService::isEditable($nota)) {
            abort(422, "Não é possível modificar notas referenciadas de uma nota com status: {$nota->status}");
        }
    }
}
