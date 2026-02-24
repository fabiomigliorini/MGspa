<?php

namespace Mg\Rh;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class IndicadorController extends Controller
{
    public function lancamentos(int $codindicador)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $indicador = Indicador::with(['Setor', 'UnidadeNegocio', 'Colaborador.Pessoa'])
            ->findOrFail($codindicador);

        $lancamentos = IndicadorLancamento::where('tblindicadorlancamento.codindicador', $codindicador)
            ->with(['Negocio.Pessoa'])
            ->leftJoin('tblnegocio', 'tblnegocio.codnegocio', '=', 'tblindicadorlancamento.codnegocio')
            ->orderByDesc('tblnegocio.lancamento')
            ->orderByDesc('tblindicadorlancamento.criacao')
            ->orderBy('tblindicadorlancamento.valor')
            ->select('tblindicadorlancamento.*')
            ->get();

        return response()->json([
            'data' => [
                'indicador' => new IndicadorResource($indicador),
                'lancamentos' => IndicadorLancamentoResource::collection($lancamentos),
            ]
        ]);
    }

    public function atualizarMeta(int $codindicador, IndicadorMetaRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $indicador = Indicador::findOrFail($codindicador);
            $indicador->fill($request->validated());
            $indicador->save();
            DB::commit();
            return new IndicadorResource($indicador);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function lancamentoManual(int $codindicador, IndicadorLancamentoManualRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $indicador = Indicador::findOrFail($codindicador);

            $dados = $request->validated();

            $lancamento = IndicadorLancamento::create(array_merge(
                $dados,
                ['codindicador' => $codindicador, 'manual' => true]
            ));

            $indicador->valoracumulado += $dados['valor'];
            $indicador->save();

            DB::commit();
            return new IndicadorLancamentoResource($lancamento);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }
}
