<?php

namespace Mg\Rh;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class IndicadorController extends Controller
{
    public function index(int $codperiodo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $indicadores = Indicador::where('codperiodo', $codperiodo)
            ->with(['Colaborador.Pessoa', 'Setor', 'UnidadeNegocio'])
            ->withCount('IndicadorLancamentoS')
            ->orderBy('codunidadenegocio')
            ->orderBy('codsetor')
            ->orderByRaw("CASE tipo WHEN 'V' THEN 1 WHEN 'C' THEN 2 WHEN 'S' THEN 3 WHEN 'U' THEN 4 END")
            ->orderBy('codindicador')
            ->get();

        return IndicadorResource::collection($indicadores);
    }

    public function store(int $codperiodo, IndicadorStoreRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $dados = $request->validated();

            $indicador = Indicador::firstOrCreate(
                [
                    'codperiodo' => $codperiodo,
                    'tipo' => $dados['tipo'],
                    'codunidadenegocio' => $dados['codunidadenegocio'] ?? null,
                    'codsetor' => $dados['codsetor'] ?? null,
                    'codcolaborador' => $dados['codcolaborador'] ?? null,
                ],
                [
                    'meta' => $dados['meta'] ?? null,
                    'valoracumulado' => 0,
                ]
            );

            if (!$indicador->wasRecentlyCreated && isset($dados['meta'])) {
                $indicador->meta = $dados['meta'];
                $indicador->save();
            }

            DB::commit();

            $indicador->load(['Colaborador.Pessoa', 'Setor', 'UnidadeNegocio']);
            $indicador->loadCount('IndicadorLancamentoS');

            return new IndicadorResource($indicador);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function lancamentos(int $codindicador)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $indicador = Indicador::with(['Setor', 'UnidadeNegocio', 'Colaborador.Pessoa'])
            ->findOrFail($codindicador);

        $paginated = IndicadorLancamento::where('tblindicadorlancamento.codindicador', $codindicador)
            ->with(['Negocio.Pessoa'])
            ->leftJoin('tblnegocio', 'tblnegocio.codnegocio', '=', 'tblindicadorlancamento.codnegocio')
            ->orderByDesc('tblnegocio.lancamento')
            ->orderByDesc('tblindicadorlancamento.criacao')
            ->orderBy('tblindicadorlancamento.valor')
            ->select('tblindicadorlancamento.*')
            ->paginate(50);

        return response()->json([
            'data' => [
                'indicador' => new IndicadorResource($indicador),
                'lancamentos' => IndicadorLancamentoResource::collection($paginated->getCollection()),
            ],
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
            ],
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

    public function atualizarLancamento(int $codindicadorlancamento, IndicadorLancamentoManualRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $lancamento = IndicadorLancamento::findOrFail($codindicadorlancamento);

            if (!$lancamento->manual) {
                return response()->json(['erro' => 'Apenas lançamentos manuais podem ser alterados'], 422);
            }

            $dados = $request->validated();
            $diferenca = $dados['valor'] - $lancamento->valor;

            $lancamento->fill($dados);
            $lancamento->save();

            $indicador = Indicador::findOrFail($lancamento->codindicador);
            $indicador->valoracumulado += $diferenca;
            $indicador->save();

            DB::commit();
            return new IndicadorLancamentoResource($lancamento);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function excluirLancamento(int $codindicadorlancamento)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $lancamento = IndicadorLancamento::findOrFail($codindicadorlancamento);

            if (!$lancamento->manual) {
                return response()->json(['erro' => 'Apenas lançamentos manuais podem ser excluídos'], 422);
            }

            $indicador = Indicador::findOrFail($lancamento->codindicador);
            $indicador->valoracumulado -= $lancamento->valor;
            $indicador->save();

            $lancamento->delete();

            DB::commit();
            return response()->json(['mensagem' => 'Lançamento excluído']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $codindicador)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $indicador = Indicador::findOrFail($codindicador);

            if ($indicador->IndicadorLancamentoS()->count() > 0) {
                return response()->json(['erro' => 'Indicador possui lançamentos e não pode ser excluído'], 422);
            }

            $indicador->delete();

            DB::commit();
            return response()->json(['mensagem' => 'Indicador excluído']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }
}
