<?php

namespace Mg\Contrato;

use App\Http\Requests\Mg\Contrato\ContratoNotaRequest;
use Illuminate\Http\Request;
use Mg\MgController;
use Mg\MgService;

/**
 * Plano de emissao de NF aninhado no contrato: contrato/{codcontrato}/nota.
 * Cada linha = uma nota a emitir (natureza/pessoa/observacao); a hierarquia
 * triangular (uma nota referencia a chave de outra) sai do codcontratonotapai.
 */
class ContratoNotaController extends MgController
{
    const WITH = ['NaturezaOperacao', 'PessoaNf'];

    public function index(Request $request, $codcontrato)
    {
        $res = ContratoNota::with(static::WITH)
            ->where('codcontrato', $codcontrato)
            ->orderBy('ordem')
            ->orderBy('codcontratonota')
            ->get();
        return ContratoNotaResource::collection($res);
    }

    public function store(ContratoNotaRequest $request, $codcontrato)
    {
        Contrato::findOrFail($codcontrato);

        $model = new ContratoNota();
        $model->fill($request->validated());
        $model->codcontrato = $codcontrato;
        static::validarPai($model);
        $model->save();

        return new ContratoNotaResource($model->load(static::WITH));
    }

    public function update(ContratoNotaRequest $request, $codcontrato, $codnota)
    {
        $model = ContratoNota::where('codcontrato', $codcontrato)->findOrFail($codnota);
        $model->fill($request->validated());
        $model->codcontrato = $codcontrato;
        static::validarPai($model);
        $model->update();

        return new ContratoNotaResource($model->load(static::WITH));
    }

    public function destroy($codcontrato, $codnota)
    {
        ContratoNota::where('codcontrato', $codcontrato)->findOrFail($codnota)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $codcontrato, $codnota)
    {
        $m = ContratoNota::where('codcontrato', $codcontrato)->findOrFail($codnota);
        MgService::inativar($m);
        return new ContratoNotaResource($m->fresh(static::WITH));
    }

    public function ativar(Request $request, $codcontrato, $codnota)
    {
        $m = ContratoNota::where('codcontrato', $codcontrato)->findOrFail($codnota);
        MgService::ativar($m);
        return new ContratoNotaResource($m->fresh(static::WITH));
    }

    // A nota-pai (referenciada) tem que ser do MESMO contrato e nao ela mesma.
    protected static function validarPai(ContratoNota $model): void
    {
        $pai = $model->codcontratonotapai;
        if (!$pai) {
            return;
        }
        if ((int) $pai === (int) $model->codcontratonota) {
            abort(422, 'A nota não pode referenciar a si mesma.');
        }
        $ok = ContratoNota::where('codcontrato', $model->codcontrato)
            ->where('codcontratonota', $pai)
            ->exists();
        if (!$ok) {
            abort(422, 'A nota referenciada precisa ser do mesmo contrato.');
        }
    }
}
