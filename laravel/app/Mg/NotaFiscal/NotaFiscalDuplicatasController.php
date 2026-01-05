<?php

namespace Mg\NotaFiscal;

use App\Http\Controllers\Controller;
use Mg\NotaFiscal\Requests\NotaFiscalDuplicatasRequest;
use Mg\NotaFiscal\Resources\NotaFiscalDuplicatasResource;
use Illuminate\Http\Request;

class NotaFiscalDuplicatasController extends Controller
{
    public function index(Request $request, int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        $query = $nota->NotaFiscalDuplicatasS();

        // Ordenação por vencimento
        $query->orderBy('vencimento', 'asc');

        return NotaFiscalDuplicatasResource::collection($query->get());
    }

    public function show(int $codnotafiscal, int $codnotafiscalduplicatas)
    {
        $duplicata = NotaFiscalDuplicatas::where('codnotafiscal', $codnotafiscal)
            ->where('codnotafiscalduplicatas', $codnotafiscalduplicatas)
            ->firstOrFail();

        return new NotaFiscalDuplicatasResource($duplicata);
    }

    public function store(NotaFiscalDuplicatasRequest $request, int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($nota);

        $data = array_merge($request->validated(), ['codnotafiscal' => $codnotafiscal]);
        $duplicata = NotaFiscalDuplicatas::create($data);

        return (new NotaFiscalDuplicatasResource($duplicata))
            ->response()
            ->setStatusCode(201);
    }

    public function update(NotaFiscalDuplicatasRequest $request, int $codnotafiscal, int $codnotafiscalduplicatas)
    {
        $duplicata = NotaFiscalDuplicatas::where('codnotafiscal', $codnotafiscal)
            ->where('codnotafiscalduplicatas', $codnotafiscalduplicatas)
            ->firstOrFail();

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($duplicata->NotaFiscal);

        $duplicata->update($request->validated());

        return new NotaFiscalDuplicatasResource($duplicata->fresh());
    }

    public function destroy(int $codnotafiscal, int $codnotafiscalduplicatas)
    {
        $duplicata = NotaFiscalDuplicatas::where('codnotafiscal', $codnotafiscal)
            ->where('codnotafiscalduplicatas', $codnotafiscalduplicatas)
            ->firstOrFail();

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($duplicata->NotaFiscal);

        $duplicata->delete();

        return response()->noContent();
    }

    private function verificarNotaBloqueada(NotaFiscal $nota): void
    {
        if (!NotaFiscalService::isEditable($nota)) {
            abort(422, "Não é possível modificar duplicatas de uma nota com status: {$nota->status}");
        }
    }
}
