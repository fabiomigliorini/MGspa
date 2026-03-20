<?php

namespace Mg\Rh;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mg\Colaborador\Colaborador;
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
        $this->autorizarLancamentos($codindicador);

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

    public function reprocessar(int $codperiodo, Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $periodo = Periodo::findOrFail($codperiodo);

        if ($periodo->status !== PeriodoService::STATUS_ABERTO) {
            return response()->json(['erro' => 'Período não está aberto'], 422);
        }

        $cacheKey = "rh:reprocessar:{$codperiodo}";
        $cache = Cache::get($cacheKey);
        if ($cache && $cache['status'] === 'processando') {
            return response()->json(['erro' => 'Reprocessamento já em andamento'], 422);
        }

        ReprocessarPeriodoJob::dispatch($codperiodo, $request->boolean('limpar', false))
            ->onQueue('high');

        return response()->json(['mensagem' => 'Reprocessamento iniciado']);
    }

    public function progressoReprocessamento(int $codperiodo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $cache = Cache::get("rh:reprocessar:{$codperiodo}");

        if (!$cache) {
            return response()->json(['status' => null]);
        }

        return response()->json($cache);
    }

    public function cancelarReprocessamento(int $codperiodo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $cacheKey = "rh:reprocessar:{$codperiodo}";
        $cache = Cache::get($cacheKey);

        if (!$cache || $cache['status'] !== 'processando') {
            return response()->json(['erro' => 'Nenhum reprocessamento em andamento'], 422);
        }

        Cache::put($cacheKey, array_merge($cache, ['status' => 'cancelado']), 3600);

        return response()->json(['mensagem' => 'Cancelamento solicitado']);
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

    private function autorizarLancamentos(int $codindicador): void
    {
        // Admin tem acesso total
        if (Autorizador::pode(['Recursos Humanos'])) {
            return;
        }

        // Resolve colaborador do usuário logado
        $user = Auth::user();
        $colaborador = Colaborador::where('codpessoa', $user->codpessoa)
            ->where(function ($q) {
                $q->whereNull('rescisao')
                    ->orWhere('rescisao', '>=', now()->toDateString());
            })
            ->first();

        if (!$colaborador) {
            abort(403, 'Não autorizado.');
        }

        $indicador = Indicador::findOrFail($codindicador);

        // Indicador pessoal do próprio colaborador
        if ($indicador->codcolaborador === $colaborador->codcolaborador) {
            return;
        }

        // Indicador coletivo (S/U) de um setor/unidade onde o colaborador participa
        $pc = PeriodoColaborador::where('codperiodo', $indicador->codperiodo)
            ->where('codcolaborador', $colaborador->codcolaborador)
            ->first();

        if (!$pc) {
            abort(403, 'Não autorizado.');
        }

        $meusSetores = PeriodoColaboradorSetor::where('codperiodocolaborador', $pc->codperiodocolaborador)
            ->pluck('codsetor')
            ->toArray();

        $minhasUnidades = PeriodoColaboradorSetor::where('codperiodocolaborador', $pc->codperiodocolaborador)
            ->join('tblsetor', 'tblsetor.codsetor', '=', 'tblperiodocolaboradorsetor.codsetor')
            ->pluck('tblsetor.codunidadenegocio')
            ->unique()
            ->toArray();

        // Indicador coletivo do meu setor/unidade
        if (in_array($indicador->codsetor, $meusSetores) || in_array($indicador->codunidadenegocio, $minhasUnidades)) {
            return;
        }

        // Gestor pode ver indicadores de colaboradores do mesmo setor
        if ($pc->gestor && $indicador->codcolaborador) {
            $alvoCompartilhaSetor = PeriodoColaboradorSetor::whereHas('PeriodoColaborador', function ($q) use ($indicador) {
                $q->where('codcolaborador', $indicador->codcolaborador)
                    ->where('codperiodo', $indicador->codperiodo);
            })
                ->whereIn('codsetor', $meusSetores)
                ->exists();

            if ($alvoCompartilhaSetor) {
                return;
            }
        }

        abort(403, 'Não autorizado.');
    }
}
