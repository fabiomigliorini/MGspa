<?php

namespace Mg\Contrato;

use App\Http\Requests\Mg\Contrato\ContratoFixacaoRequest;
use Illuminate\Http\Request;
use Mg\MgController;
use Mg\MgService;

/**
 * Fixacoes de preco aninhadas no contrato: contrato/{codcontrato}/fixacao.
 * Permite fixacao parcial (varias fixacoes) e USD com trava de R$ (precoreal).
 */
class ContratoFixacaoController extends MgController
{
    public function index(Request $request, $codcontrato)
    {
        $res = ContratoFixacao::with('Contrato.Filial', 'ContratoPagamentoS') // ledger sem N+1
            ->where('codcontrato', $codcontrato)
            ->orderBy('data')
            ->get();
        return ContratoFixacaoResource::collection($res);
    }

    public function store(ContratoFixacaoRequest $request, $codcontrato)
    {
        // Precificação vive na fixação: um contrato "FIXO" é só um que recebe a
        // fixação cheia na assinatura. Qualquer contrato pode receber fixações.
        $contrato = Contrato::findOrFail($codcontrato);

        $model = new ContratoFixacao();
        $model->fill($request->validated());
        $model->codcontrato = $codcontrato;
        $model->precoreal = ContratoService::precoReal($request->validated());
        static::aplicarSnapshotImpostos($model, $contrato, $request->validated());
        $model->save();

        return new ContratoFixacaoResource($model->load('Contrato.Filial'));
    }

    public function update(ContratoFixacaoRequest $request, $codcontrato, $codfixacao)
    {
        $model = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);

        $model->fill($request->validated());
        $model->codcontrato = $codcontrato;
        $model->precoreal = ContratoService::precoReal($request->validated());
        static::aplicarSnapshotImpostos($model, $model->Contrato, $request->validated());
        $model->update();

        return new ContratoFixacaoResource($model->load('Contrato.Filial'));
    }

    /**
     * Trava o snapshot dos impostos na fixação. Quando o modal manda as linhas
     * (`tributos`), o líquido é RECALCULADO server-side a partir delas — não
     * confia no total do cliente — e gravado (precoliquido/totaldeducao/tributos).
     * Sem linhas, zera o snapshot e o líquido volta a ser calculado na leitura.
     */
    protected static function aplicarSnapshotImpostos(
        ContratoFixacao $model,
        Contrato $contrato,
        array $dados
    ): void {
        // Fixação dolarizada pura (US$ sem cotação): precoreal null = sem R$ ainda.
        // Não há bruto p/ calcular tributos; zera o snapshot. Guarda de defesa —
        // sem ela, tributos UNIDADE (FETHAB/SENAR, que independem do bruto) gerariam
        // dedução sobre bruto 0 e o líquido negativo dispararia o abort abaixo.
        $tributos = $dados['tributos'] ?? null;
        if ($model->precoreal === null || empty($tributos) || !is_array($tributos)) {
            $model->tributos = null;
            $model->totaldeducao = null;
            $model->precoliquido = null;
            return;
        }
        $pesosaca = (float) ($contrato->Cultura->pesosaca ?? 60) ?: 60;
        $calc = ContratoCalculoService::calcular([
            'codcultura' => (int) $contrato->codcultura,
            'bruto' => (float) $model->precoreal,
            'pesosaca' => $pesosaca,
            'tributos' => $tributos,
        ]);
        $model->tributos = $calc['itens'];
        $model->totaldeducao = $calc['totaldeducao'];
        $model->precoliquido = $calc['liquido'];

        // Deduções não podem superar o bruto: líquido negativo é incoerente
        // (alíquotas somando > 100% ou UPF irreal). Aborta antes de gravar.
        if ((float) $model->precoliquido < 0) {
            abort(422, 'As deduções de impostos superam o preço bruto (líquido ficaria negativo).');
        }

        // Isenção de FETHAB virou IMPLÍCITA (sem checkbox): é isento quando a
        // cultura tem linha(s) do grupo FETHAB mas o operador deixou todas sem
        // valor (UPF zerada). Deriva o flag p/ o badge da listagem e o agregado.
        $fethab = array_filter($calc['itens'], fn($i) => !empty($i['grupofethab']));
        $model->isentofethab = count($fethab) > 0
            && count(array_filter($fethab, fn($i) => (float) ($i['valor'] ?? 0) > 0)) === 0;
    }

    public function destroy($codcontrato, $codfixacao)
    {
        ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $codcontrato, $codfixacao)
    {
        $m = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);
        MgService::inativar($m);
        return new ContratoFixacaoResource($m->fresh('Contrato.Filial'));
    }

    public function ativar(Request $request, $codcontrato, $codfixacao)
    {
        $m = ContratoFixacao::where('codcontrato', $codcontrato)->findOrFail($codfixacao);
        MgService::ativar($m);
        return new ContratoFixacaoResource($m->fresh('Contrato.Filial'));
    }
}
