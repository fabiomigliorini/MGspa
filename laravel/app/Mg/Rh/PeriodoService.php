<?php

namespace Mg\Rh;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mg\Feriado\Feriado;

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

    public static function calcularDiasUteis(Carbon $inicio, Carbon $fim): int
    {
        $feriados = Feriado::whereNull('inativo')
            ->whereBetween('data', [$inicio->format('Y-m-d'), $fim->format('Y-m-d')])
            ->pluck('data')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        $feriadoSet = array_flip($feriados);
        $count = 0;
        $d = $inicio->copy();

        while ($d->lte($fim)) {
            // Seg a Sáb (0=dom) — exclui domingos e feriados
            if ($d->dayOfWeek !== Carbon::SUNDAY && !isset($feriadoSet[$d->format('Y-m-d')])) {
                $count++;
            }
            $d->addDay();
        }

        return $count;
    }

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

        $diasuteis = $data['diasuteis'] ?? static::calcularDiasUteis($periodoinicial, $periodofinal);

        $periodo = new Periodo([
            'periodoinicial' => $periodoinicial,
            'periodofinal' => $periodofinal,
            'diasuteis' => $diasuteis,
            'observacoes' => $data['observacoes'] ?? null,
            'status' => 'A',
        ]);
        $periodo->save();

        // Duplica colaboradores/setores/rubricas do último período
        $ultimo = Periodo::where('codperiodo', '!=', $periodo->codperiodo)
            ->orderBy('periodofinal', 'desc')
            ->first();

        if ($ultimo) {
            static::duplicarConteudo($ultimo, $periodo);
        }

        return $periodo->refresh();
    }

    public static function criarProximoPeriodo(Periodo $anterior): Periodo
    {
        $novoInicial = $anterior->periodoinicial->copy()->addMonth();
        $novoFinal = $anterior->periodofinal->copy()->addMonth();

        $periodo = new Periodo([
            'periodoinicial'         => $novoInicial,
            'periodofinal'           => $novoFinal,
            'diasuteis'              => static::calcularDiasUteis($novoInicial, $novoFinal),
            'observacoes'            => null,
            'status'                 => self::STATUS_ABERTO,
            'percentualmaxdesconto'  => $anterior->percentualmaxdesconto ?? 30,
        ]);
        $periodo->save();

        return $periodo->refresh();
    }

    public static function duplicarDoAnterior(int $codperiodo): Periodo
    {
        $origem = Periodo::findOrFail($codperiodo);

        $novoPeriodo = static::criarProximoPeriodo($origem);
        static::duplicarConteudo($origem, $novoPeriodo);

        return $novoPeriodo->refresh();
    }

    protected static function duplicarConteudo(Periodo $origem, Periodo $destino): void
    {
        // Duplicar indicadores e montar mapa antigo => novo
        $mapaIndicadores = [];
        $indicadoresOrigem = Indicador::where('codperiodo', $origem->codperiodo)->get();

        foreach ($indicadoresOrigem as $ind) {
            $novoInd = new Indicador([
                'codperiodo' => $destino->codperiodo,
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
                'codperiodo' => $destino->codperiodo,
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
                    'diastrabalhados' => $destino->diasuteis,
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

        // Recalcula rubricas do período destino
        CalculoRubricaService::calcular($destino->codperiodo);
    }

    public static function atualizar(int $codperiodo, array $data): Periodo
    {
        $periodo = Periodo::findOrFail($codperiodo);

        // Validar datas se alteradas
        if (isset($data['periodoinicial']) || isset($data['periodofinal'])) {
            $periodoinicial = Carbon::parse($data['periodoinicial'] ?? $periodo->periodoinicial);
            $periodofinal = Carbon::parse($data['periodofinal'] ?? $periodo->periodofinal);

            if ($periodofinal->lte($periodoinicial)) {
                throw new \Exception('periodofinal deve ser maior que periodoinicial.');
            }

            $sobreposicao = Periodo::where('codperiodo', '!=', $codperiodo)
                ->where(function ($q) use ($periodoinicial, $periodofinal) {
                    $q->where('periodoinicial', '<=', $periodofinal)
                        ->where('periodofinal', '>=', $periodoinicial);
                })->exists();

            if ($sobreposicao) {
                throw new \Exception('Já existe um período que se sobrepõe às datas informadas.');
            }

            // Recalcular diasuteis se não informado explicitamente
            if (!isset($data['diasuteis'])) {
                $data['diasuteis'] = static::calcularDiasUteis($periodoinicial, $periodofinal);
            }
        }

        // Cascade diasuteis → diastrabalhados
        if (isset($data['diasuteis']) && $data['diasuteis'] != $periodo->diasuteis) {
            $diasUteisAntigo = $periodo->diasuteis;
            $diasUteisNovo = $data['diasuteis'];

            $codperiodocolaboradores = PeriodoColaborador::where('codperiodo', $codperiodo)
                ->pluck('codperiodocolaborador');

            PeriodoColaboradorSetor::whereIn('codperiodocolaborador', $codperiodocolaboradores)
                ->where('diastrabalhados', $diasUteisAntigo)
                ->update(['diastrabalhados' => $diasUteisNovo]);
        }

        $periodo->fill($data);
        $periodo->save();

        CalculoRubricaService::calcular($codperiodo);

        return $periodo->refresh();
    }

    public static function excluir(int $codperiodo): void
    {
        $periodo = Periodo::findOrFail($codperiodo);

        // Apagar rubricas, setores, colaboradores e indicadores do período
        $pcs = PeriodoColaborador::where('codperiodo', $codperiodo)->get();
        foreach ($pcs as $pc) {
            ColaboradorRubrica::where('codperiodocolaborador', $pc->codperiodocolaborador)->delete();
            PeriodoColaboradorSetor::where('codperiodocolaborador', $pc->codperiodocolaborador)->delete();
        }
        PeriodoColaborador::where('codperiodo', $codperiodo)->delete();
        Indicador::where('codperiodo', $codperiodo)->delete();
        $periodo->delete();
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
