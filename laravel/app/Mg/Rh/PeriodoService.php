<?php

namespace Mg\Rh;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PeriodoService
{
    const STATUS_ABERTO = 'A';
    const STATUS_FECHADO = 'F';
    const STATUS_COLABORADOR_ABERTO = 'A';
    const STATUS_COLABORADOR_ENCERRADO = 'E';

    const STATUS_DESCRICAO = [
        self::STATUS_ABERTO => 'Aberto',
        self::STATUS_FECHADO => 'Fechado',
    ];

    const STATUS_COLABORADOR_DESCRICAO = [
        self::STATUS_COLABORADOR_ABERTO => 'Aberto',
        self::STATUS_COLABORADOR_ENCERRADO => 'Encerrado',
    ];

    public static function criar(array $data): Periodo
    {
        $periodoinicial = Carbon::parse($data['periodoinicial']);
        $periodofinal = Carbon::parse($data['periodofinal']);

        if ($periodofinal->lte($periodoinicial)) {
            throw new \Exception('periodofinal deve ser maior que periodoinicial.');
        }

        $sobreposicao = Periodo::where(function ($q) use ($periodoinicial, $periodofinal) {
            $q->where('periodoinicial', '<=', $periodofinal)
                ->where('periodofinal', '>=', $periodoinicial);
        })->exists();

        if ($sobreposicao) {
            throw new \Exception('Já existe um período que se sobrepõe às datas informadas.');
        }

        $ultimo = Periodo::orderBy('periodofinal', 'desc')->first();

        if ($ultimo) {
            $esperado = $ultimo->periodofinal->copy()->addDay();
            if (!$periodoinicial->eq($esperado)) {
                throw new \Exception("periodoinicial deve ser {$esperado->format('Y-m-d')} (dia seguinte ao último período).");
            }
        }

        $periodo = new Periodo([
            'periodoinicial' => $periodoinicial,
            'periodofinal' => $periodofinal,
            'diasuteis' => $data['diasuteis'],
            'observacoes' => $data['observacoes'] ?? null,
            'status' => 'A',
        ]);
        $periodo->save();

        return $periodo->refresh();
    }

    public static function criarProximoPeriodo(Periodo $anterior): Periodo
    {
        $periodo = new Periodo([
            'periodoinicial' => $anterior->periodoinicial->copy()->addMonth(),
            'periodofinal' => $anterior->periodofinal->copy()->addMonth(),
            'diasuteis' => $anterior->diasuteis,
            'observacoes' => null,
            'status' => self::STATUS_ABERTO,
        ]);
        $periodo->save();

        return $periodo->refresh();
    }

    public static function duplicarDoAnterior(int $codperiodo): Periodo
    {
        $origem = Periodo::findOrFail($codperiodo);

        $novoPeriodo = static::criarProximoPeriodo($origem);

        // Duplicar indicadores e montar mapa antigo => novo
        $mapaIndicadores = [];
        $indicadoresOrigem = Indicador::where('codperiodo', $origem->codperiodo)->get();

        foreach ($indicadoresOrigem as $ind) {
            $novoInd = new Indicador([
                'codperiodo' => $novoPeriodo->codperiodo,
                'tipo' => $ind->tipo,
                'codunidadenegocio' => $ind->codunidadenegocio,
                'codsetor' => $ind->codsetor,
                'codcolaborador' => $ind->codcolaborador,
                'meta' => $ind->meta,
                'valoracumulado' => 0,
            ]);
            $novoInd->save();
            $mapaIndicadores[$ind->codindicador] = $novoInd->codindicador;
        }

        // Duplicar PeriodoColaborador (somente colaboradores ativos)
        $periodosColaborador = PeriodoColaborador::where('codperiodo', $origem->codperiodo)->get();

        foreach ($periodosColaborador as $pc) {
            $colaboradorRescindido = DB::table('tblcolaborador')
                ->where('codcolaborador', $pc->codcolaborador)
                ->whereNotNull('rescisao')
                ->exists();

            if ($colaboradorRescindido) {
                continue;
            }

            $novoPC = new PeriodoColaborador([
                'codperiodo' => $novoPeriodo->codperiodo,
                'codcolaborador' => $pc->codcolaborador,
                'status' => 'A',
                'valortotal' => 0,
            ]);
            $novoPC->save();

            // Duplicar PeriodoColaboradorSetor
            $setores = PeriodoColaboradorSetor::where('codperiodocolaborador', $pc->codperiodocolaborador)->get();
            $mapaSetores = [];

            foreach ($setores as $setor) {
                $novoSetor = new PeriodoColaboradorSetor([
                    'codperiodocolaborador' => $novoPC->codperiodocolaborador,
                    'codsetor' => $setor->codsetor,
                    'diastrabalhados' => 0,
                    'percentualrateio' => $setor->percentualrateio,
                ]);
                $novoSetor->save();
                $mapaSetores[$setor->codperiodocolaboradorsetor] = $novoSetor->codperiodocolaboradorsetor;
            }

            // Duplicar ColaboradorRubrica recorrentes
            $rubricas = ColaboradorRubrica::where('codperiodocolaborador', $pc->codperiodocolaborador)
                ->where('recorrente', true)
                ->get();

            foreach ($rubricas as $rubrica) {
                $novaRubrica = new ColaboradorRubrica([
                    'codperiodocolaborador' => $novoPC->codperiodocolaborador,
                    'codperiodocolaboradorsetor' => isset($mapaSetores[$rubrica->codperiodocolaboradorsetor])
                        ? $mapaSetores[$rubrica->codperiodocolaboradorsetor]
                        : null,
                    'codindicador' => isset($mapaIndicadores[$rubrica->codindicador])
                        ? $mapaIndicadores[$rubrica->codindicador]
                        : null,
                    'codindicadorcondicao' => isset($mapaIndicadores[$rubrica->codindicadorcondicao])
                        ? $mapaIndicadores[$rubrica->codindicadorcondicao]
                        : null,
                    'descricao' => $rubrica->descricao,
                    'tipovalor' => $rubrica->tipovalor,
                    'percentual' => $rubrica->percentual,
                    'valorfixo' => $rubrica->valorfixo,
                    'valorcalculado' => 0,
                    'tipocondicao' => $rubrica->tipocondicao,
                    'concedido' => true,
                    'descontaabsenteismo' => $rubrica->descontaabsenteismo,
                    'recorrente' => true,
                ]);
                $novaRubrica->save();
            }
        }

        return $novoPeriodo->refresh();
    }

    public static function fechar(int $codperiodo): Periodo
    {
        $periodo = Periodo::findOrFail($codperiodo);

        if ($periodo->status !== 'A') {
            throw new \Exception('Somente períodos com status A (aberto) podem ser fechados.');
        }

        $periodo->status = 'F';
        $periodo->save();

        return $periodo;
    }

    public static function reabrir(int $codperiodo): Periodo
    {
        $periodo = Periodo::findOrFail($codperiodo);

        if ($periodo->status !== 'F') {
            throw new \Exception('Somente períodos com status F (fechado) podem ser reabertos.');
        }

        $periodo->status = 'A';
        $periodo->save();

        return $periodo;
    }
}
