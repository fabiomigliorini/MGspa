<?php

namespace Mg\Rh;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Colaborador\Colaborador;
use Mg\Filial\Setor;
use Mg\Usuario\Autorizador;

class PeriodoColaboradorController extends Controller
{
    public function index(int $codperiodo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $periodo = Periodo::findOrFail($codperiodo);

        $colaboradores = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->whereHas('Colaborador', function ($q) use ($periodo) {
                $q->where('contratacao', '<=', $periodo->periodofinal)
                    ->where(function ($q2) use ($periodo) {
                        $q2->whereNull('rescisao')
                            ->orWhere('rescisao', '>=', $periodo->periodoinicial);
                    });
            })
            ->with([
                'Colaborador.Pessoa',
                'Colaborador.ColaboradorCargoS' => function ($q) {
                    $q->whereNull('fim')->with('Cargo');
                },
                'Setor.UnidadeNegocio',
                'Setor.TipoSetor',
                'ColaboradorRubricaS.Rubrica',
                'ColaboradorRubricaS.Indicador.Setor',
                'ColaboradorRubricaS.Indicador.UnidadeNegocio',
                'ColaboradorRubricaS.IndicadorCondicao.Setor',
                'ColaboradorRubricaS.IndicadorCondicao.UnidadeNegocio',
                'PeriodoColaboradorAcertoS' => function ($q) {
                    // Traz também os inativos (aparecem no card marcados).
                    $q->orderBy('data')
                        ->orderBy('codperiodocolaboradoracerto')
                        ->with(['MovimentoTituloS.Titulo', 'UsuarioCriacao']);
                },
            ])
            ->get();

        // Indicadores pessoais (V/C) — busca bulk para evitar N+1
        $indicadores = Indicador::where('codperiodo', $codperiodo)
            ->whereNotNull('codcolaborador')
            ->with(['Setor', 'UnidadeNegocio'])
            ->get()
            ->groupBy('codcolaborador');

        // Indicadores coletivos (S/U) — para dropdown de rubricas
        $coletivos = Indicador::where('codperiodo', $codperiodo)
            ->whereNull('codcolaborador')
            ->with(['Setor', 'UnidadeNegocio'])
            ->get();

        foreach ($colaboradores as $c) {
            $c->indicadores_pessoais = $indicadores->get($c->codcolaborador, collect());
            $c->indicadores_coletivos = $coletivos;
        }

        return PeriodoColaboradorResource::collection($colaboradores);
    }

    public function disponiveis(int $codperiodo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $periodo = Periodo::findOrFail($codperiodo);

        $jaVinculados = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->pluck('codcolaborador');

        $colaboradores = Colaborador::where('contratacao', '<=', $periodo->periodofinal)
            ->where(function ($q) use ($periodo) {
                $q->whereNull('rescisao')
                    ->orWhere('rescisao', '>=', $periodo->periodoinicial);
            })
            ->whereNotIn('codcolaborador', $jaVinculados)
            ->with([
                'Pessoa',
                'ColaboradorCargoS' => function ($q) {
                    $q->whereNull('fim')->with('Cargo');
                },
            ])
            ->get()
            ->map(function ($c) {
                $cargo = $c->ColaboradorCargoS->first();
                return [
                    'codcolaborador' => $c->codcolaborador,
                    'fantasia' => $c->Pessoa->fantasia ?? null,
                    'cargo' => $cargo?->Cargo?->cargo ?? null,
                ];
            })
            ->sortBy('fantasia')
            ->values();

        return response()->json(['data' => $colaboradores]);
    }

    public function store(int $codperiodo, Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $periodo = Periodo::findOrFail($codperiodo);

        $codcolaboradores = $request->input('colaboradores', []);
        if (empty($codcolaboradores)) {
            return response()->json(['erro' => 'Nenhum colaborador informado.'], 422);
        }

        // Setor "casa" opcional, aplicado a todos os colaboradores deste lote.
        $codsetor = $request->input('codsetor') ?: null;

        $jaVinculados = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->pluck('codcolaborador')
            ->toArray();

        $adicionados = 0;
        DB::beginTransaction();
        try {
            foreach ($codcolaboradores as $codcolaborador) {
                if (in_array((int) $codcolaborador, $jaVinculados)) {
                    continue;
                }
                $pc = new PeriodoColaborador([
                    'codperiodo' => $codperiodo,
                    'codcolaborador' => (int) $codcolaborador,
                    'codsetor' => $codsetor,
                    'status' => 'A',
                    'valortotal' => 0,
                ]);
                $pc->save();
                $adicionados++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $adicionados . ' colaborador(es) adicionado(s).',
            'adicionados' => $adicionados,
        ]);
    }

    public function destroy(int $codperiodo, int $codperiodocolaborador)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $pc = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->where('codperiodocolaborador', $codperiodocolaborador)
            ->firstOrFail();

        if ($pc->status === 'E') {
            return response()->json(['erro' => 'Não é possível excluir um colaborador encerrado. Estorne o encerramento primeiro.'], 422);
        }

        DB::beginTransaction();
        try {
            ColaboradorRubrica::where('codperiodocolaborador', $codperiodocolaborador)->delete();
            $pc->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Colaborador removido do período.']);
    }

    public function encerrar(int $codperiodo, int $codperiodocolaborador)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $pc = EncerramentoService::encerrar($codperiodocolaborador);
            DB::commit();
            return new PeriodoColaboradorResource($pc);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function estornar(int $codperiodo, int $codperiodocolaborador)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $pc = EncerramentoService::estornar($codperiodocolaborador);
            DB::commit();
            return new PeriodoColaboradorResource($pc);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function recalcular(int $codperiodo, int $codperiodocolaborador)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            CalculoRubricaService::calcularColaborador($codperiodocolaborador);
            DB::commit();
            $pc = PeriodoColaborador::find($codperiodocolaborador);
            return new PeriodoColaboradorResource($pc);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function toggleGestor(int $codperiodo, int $codperiodocolaborador)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $pc = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->where('codperiodocolaborador', $codperiodocolaborador)
            ->firstOrFail();

        $pc->gestor = !$pc->gestor;
        $pc->save();

        return response()->json(['gestor' => $pc->gestor]);
    }

    // Define/muda o setor "casa" do colaborador no período.
    public function atualizarSetor(int $codperiodo, int $codperiodocolaborador, Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $pc = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->where('codperiodocolaborador', $codperiodocolaborador)
            ->firstOrFail();

        $pc->codsetor = $request->input('codsetor') ?: null;
        $pc->save();

        return new PeriodoColaboradorResource($pc->load('Setor.UnidadeNegocio'));
    }

    // Lista de setores ativos (com unidade) para os selects do RH.
    public function setores()
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $setores = Setor::whereNull('inativo')
            ->with('UnidadeNegocio')
            ->orderBy('codunidadenegocio')
            ->orderBy('setor')
            ->get()
            ->map(fn($s) => [
                'codsetor' => $s->codsetor,
                'setor' => $s->setor,
                'codunidadenegocio' => $s->codunidadenegocio,
                'unidade_negocio_nome' => $s->UnidadeNegocio?->descricao,
                'label' => ($s->UnidadeNegocio?->descricao ? $s->UnidadeNegocio->descricao . ' — ' : '') . $s->setor,
            ])
            ->values();

        return response()->json(['data' => $setores]);
    }
}
