<?php

namespace Mg\Rh;

use Illuminate\Support\Facades\Auth;
use Mg\Colaborador\Colaborador;
use Mg\Filial\Setor;

class MeuPainelService
{
    public static function resolverColaborador(): ?Colaborador
    {
        $user = Auth::user();
        if (!$user || !$user->codpessoa) {
            return null;
        }

        return Colaborador::where('codpessoa', $user->codpessoa)
            ->where(function ($q) {
                $q->whereNull('rescisao')
                    ->orWhere('rescisao', '>=', now()->toDateString());
            })
            ->orderBy('codcolaborador', 'desc')
            ->first();
    }

    public static function periodos(int $codcolaborador): array
    {
        return Periodo::whereHas('PeriodoColaboradorS', function ($q) use ($codcolaborador) {
            $q->where('codcolaborador', $codcolaborador);
        })
            ->orderBy('periodoinicial', 'desc')
            ->get()
            ->map(fn($p) => [
                'codperiodo' => $p->codperiodo,
                'periodoinicial' => $p->periodoinicial,
                'periodofinal' => $p->periodofinal,
                'status' => $p->status,
            ])
            ->toArray();
    }

    public static function dashboard(int $codcolaborador, int $codperiodo): array
    {
        $pc = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->where('codcolaborador', $codcolaborador)
            ->firstOrFail();

        $pcCarregado = static::carregarColaboradorCompleto($pc->codperiodocolaborador, $codperiodo);

        $resultado = [
            'colaborador' => new PeriodoColaboradorResource($pcCarregado),
            'gestor' => (bool) $pc->gestor,
            'equipe' => [],
            'indicadores_setor' => [],
        ];

        if ($pc->gestor) {
            $meusSetores = PeriodoColaboradorSetor::where('codperiodocolaborador', $pc->codperiodocolaborador)
                ->pluck('codsetor')
                ->toArray();

            $minhasUnidades = Setor::whereIn('codsetor', $meusSetores)
                ->pluck('codunidadenegocio')
                ->filter()
                ->unique()
                ->toArray();

            if (!empty($minhasUnidades)) {
                $setoresUnidade = Setor::whereIn('codunidadenegocio', $minhasUnidades)
                    ->pluck('codsetor')
                    ->toArray();

                $equipeCods = PeriodoColaboradorSetor::whereIn('codsetor', $setoresUnidade)
                    ->whereHas('PeriodoColaborador', function ($q) use ($codperiodo, $pc) {
                        $q->where('codperiodo', $codperiodo)
                            ->where('codperiodocolaborador', '!=', $pc->codperiodocolaborador);
                    })
                    ->pluck('codperiodocolaborador')
                    ->unique()
                    ->toArray();

                if (!empty($equipeCods)) {
                    $equipe = static::carregarColaboradoresCompletos($equipeCods, $codperiodo);
                    $resultado['equipe'] = PeriodoColaboradorResource::collection($equipe)->resolve();
                }

                $resultado['indicadores_setor'] = Indicador::where('codperiodo', $codperiodo)
                    ->whereNull('codcolaborador')
                    ->where(function ($q) use ($setoresUnidade, $minhasUnidades) {
                        $q->whereIn('codsetor', $setoresUnidade)
                            ->orWhereIn('codunidadenegocio', $minhasUnidades);
                    })
                    ->with(['Setor', 'UnidadeNegocio'])
                    ->get()
                    ->toArray();
            }
        }

        return $resultado;
    }

    public static function colaboradorEquipe(int $codcolaborador, int $codperiodo, int $codperiodocolaborador): array
    {
        // Verifica se o user é gestor
        $pcGestor = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->where('codcolaborador', $codcolaborador)
            ->where('gestor', true)
            ->firstOrFail();

        $meusSetores = PeriodoColaboradorSetor::where('codperiodocolaborador', $pcGestor->codperiodocolaborador)
            ->pluck('codsetor')
            ->toArray();

        $minhasUnidades = Setor::whereIn('codsetor', $meusSetores)
            ->pluck('codunidadenegocio')
            ->filter()->unique()->toArray();

        $setoresUnidade = Setor::whereIn('codunidadenegocio', $minhasUnidades)
            ->pluck('codsetor')->toArray();

        $compartilha = PeriodoColaboradorSetor::where('codperiodocolaborador', $codperiodocolaborador)
            ->whereIn('codsetor', $setoresUnidade)
            ->exists();

        if (!$compartilha) {
            abort(403, 'Não autorizado.');
        }

        $pcCarregado = static::carregarColaboradorCompleto($codperiodocolaborador, $codperiodo);

        return [
            'colaborador' => new PeriodoColaboradorResource($pcCarregado),
        ];
    }

    private static function carregarColaboradorCompleto(int $codperiodocolaborador, int $codperiodo): PeriodoColaborador
    {
        $pc = PeriodoColaborador::where('codperiodocolaborador', $codperiodocolaborador)
            ->with([
                'Colaborador.Pessoa',
                'Colaborador.ColaboradorCargoS' => function ($q) {
                    $q->whereNull('fim')->with('Cargo');
                },
                'PeriodoColaboradorSetorS.Setor.UnidadeNegocio',
                'PeriodoColaboradorSetorS.Setor.TipoSetor',
                'ColaboradorRubricaS.Indicador.Setor',
                'ColaboradorRubricaS.Indicador.UnidadeNegocio',
                'ColaboradorRubricaS.IndicadorCondicao.Setor',
                'ColaboradorRubricaS.IndicadorCondicao.UnidadeNegocio',
            ])
            ->firstOrFail();

        // Indicadores pessoais (V/C)
        $pessoais = Indicador::where('codperiodo', $codperiodo)
            ->where('codcolaborador', $pc->codcolaborador)
            ->with(['Setor', 'UnidadeNegocio'])
            ->get();

        // Indicadores coletivos (S/U)
        $codsetores = $pc->PeriodoColaboradorSetorS->pluck('codsetor')->toArray();
        $codunidades = $pc->PeriodoColaboradorSetorS
            ->map(fn($pcs) => $pcs->Setor->codunidadenegocio ?? null)
            ->filter()
            ->unique()
            ->toArray();

        $coletivos = Indicador::where('codperiodo', $codperiodo)
            ->whereNull('codcolaborador')
            ->where(function ($q) use ($codsetores, $codunidades) {
                $q->whereIn('codsetor', $codsetores)
                    ->orWhereIn('codunidadenegocio', $codunidades);
            })
            ->with(['Setor', 'UnidadeNegocio'])
            ->get();

        $pc->indicadores_pessoais = $pessoais;
        $pc->indicadores_coletivos = $coletivos;

        return $pc;
    }

    private static function carregarColaboradoresCompletos(array $codperiodocolaboradores, int $codperiodo): \Illuminate\Support\Collection
    {
        $colaboradores = PeriodoColaborador::whereIn('codperiodocolaborador', $codperiodocolaboradores)
            ->with([
                'Colaborador.Pessoa',
                'Colaborador.ColaboradorCargoS' => function ($q) {
                    $q->whereNull('fim')->with('Cargo');
                },
                'PeriodoColaboradorSetorS.Setor.UnidadeNegocio',
                'PeriodoColaboradorSetorS.Setor.TipoSetor',
                'ColaboradorRubricaS.Indicador.Setor',
                'ColaboradorRubricaS.Indicador.UnidadeNegocio',
                'ColaboradorRubricaS.IndicadorCondicao.Setor',
                'ColaboradorRubricaS.IndicadorCondicao.UnidadeNegocio',
            ])
            ->get();

        // Bulk-load indicadores pessoais
        $codcolaboradores = $colaboradores->pluck('codcolaborador')->unique()->toArray();
        $indicadores = Indicador::where('codperiodo', $codperiodo)
            ->whereIn('codcolaborador', $codcolaboradores)
            ->with(['Setor', 'UnidadeNegocio'])
            ->get()
            ->groupBy('codcolaborador');

        // Coletivos
        $coletivos = Indicador::where('codperiodo', $codperiodo)
            ->whereNull('codcolaborador')
            ->with(['Setor', 'UnidadeNegocio'])
            ->get();

        foreach ($colaboradores as $c) {
            $c->indicadores_pessoais = $indicadores->get($c->codcolaborador, collect());
            $c->indicadores_coletivos = $coletivos;
        }

        return $colaboradores;
    }
}
