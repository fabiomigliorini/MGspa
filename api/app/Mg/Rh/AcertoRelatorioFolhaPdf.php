<?php

namespace Mg\Rh;

use Dompdf\Dompdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AcertoRelatorioFolhaPdf
{
    public static function gerar(int $codperiodo): string
    {
        $periodo = Periodo::findOrFail($codperiodo);

        // Mapa codpessoa => descrição da Unidade de Negócio (mesma lógica da tela de Acertos)
        $pcs = PeriodoColaborador::where('codperiodo', $codperiodo)
            ->with(['Colaborador', 'PeriodoColaboradorSetorS.Setor.UnidadeNegocio'])
            ->get();

        $unidadePorPessoa = [];
        foreach ($pcs as $pc) {
            $codpessoa = $pc->Colaborador->codpessoa ?? null;
            if ($codpessoa === null) {
                continue;
            }
            // Convenção do app (AcertoService/CalculoRubricaService): primeiro setor do rateio
            $unidade = $pc->PeriodoColaboradorSetorS->first()?->Setor?->UnidadeNegocio;
            $unidadePorPessoa[$codpessoa] = [
                'cod'  => $unidade?->codunidadenegocio,
                'nome' => $unidade?->descricao,
            ];
        }

        // Portadores distintos com liquidações ativas no período
        $portadores = DB::select("
            SELECT DISTINCT lt.codportador, po.portador AS nome_portador
            FROM tblliquidacaotitulo lt
            JOIN tblportador po ON po.codportador = lt.codportador
            WHERE lt.codperiodo = :codperiodo
              AND lt.estornado IS NULL
            ORDER BY po.portador
        ", ['codperiodo' => $codperiodo]);

        $paginas = [];
        foreach ($portadores as $port) {
            $rows = DB::select("
                SELECT
                    p.pessoa   AS nome_colaborador,
                    p.cnpj     AS cpf_colaborador,
                    p.fisica,
                    lt.codpessoa AS codpessoa,
                    f.codfilial,
                    f.filial,
                    fp.pessoa  AS nome_filial,
                    fp.cnpj    AS cnpj_filial,
                    ABS(lt.debito - lt.credito) AS valor
                FROM tblliquidacaotitulo lt
                JOIN tblpessoa p ON p.codpessoa = lt.codpessoa
                JOIN LATERAL (
                    SELECT col.codfilial
                    FROM tblcolaborador col
                    WHERE col.codpessoa = lt.codpessoa
                    ORDER BY col.codcolaborador DESC
                    LIMIT 1
                ) uc ON true
                JOIN tblfilial f   ON f.codfilial = uc.codfilial
                JOIN tblpessoa fp  ON fp.codpessoa = f.codpessoa
                WHERE lt.codperiodo = :codperiodo
                  AND lt.codportador = :portador
                  AND lt.estornado IS NULL
                  AND ABS(lt.debito - lt.credito) > 0
                ORDER BY f.filial, p.pessoa
            ", [
                'codperiodo' => $codperiodo,
                'portador'   => $port->codportador,
            ]);

            $porFilial = [];
            foreach ($rows as $row) {
                $filialKey = $row->codfilial;
                if (!isset($porFilial[$filialKey])) {
                    $porFilial[$filialKey] = [
                        'filial'      => $row->filial,
                        'nome_filial' => $row->nome_filial,
                        'cnpj_filial' => $row->cnpj_filial,
                        'porUnidade'  => [],
                    ];
                }

                // Sub-agrupa por Unidade de Negócio (mesma da tela de Acertos)
                $un = $unidadePorPessoa[$row->codpessoa] ?? ['cod' => null, 'nome' => null];
                $unidadeKey = $un['cod'] ?? '__sem_unidade__';
                if (!isset($porFilial[$filialKey]['porUnidade'][$unidadeKey])) {
                    $porFilial[$filialKey]['porUnidade'][$unidadeKey] = [
                        'unidade' => $un['nome'],
                        'linhas'  => [],
                    ];
                }

                $porFilial[$filialKey]['porUnidade'][$unidadeKey]['linhas'][] = [
                    'nome_colaborador' => $row->nome_colaborador,
                    'cpf_colaborador'  => $row->cpf_colaborador,
                    'fisica'           => (bool) $row->fisica,
                    'valor'            => (float) $row->valor,
                ];
            }

            // Ordena as Unidades de Negócio alfabeticamente (sem unidade por último)
            foreach ($porFilial as &$fil) {
                uasort($fil['porUnidade'], function ($a, $b) {
                    if ($a['unidade'] === null) {
                        return 1;
                    }
                    if ($b['unidade'] === null) {
                        return -1;
                    }
                    return strcasecmp($a['unidade'], $b['unidade']);
                });
            }
            unset($fil);

            $paginas[] = [
                'nome_portador' => $port->nome_portador,
                'porFilial'     => $porFilial,
            ];
        }

        $periodo_label = $periodo->periodoinicial->format('d/m/Y')
            . ' a '
            . $periodo->periodofinal->format('d/m/Y');
        $dataGeracao = Carbon::now()->format('d/m/Y H:i');

        $html = view('rh.acerto-relatorio-folha', [
            'paginas'     => $paginas,
            'periodo'     => $periodo_label,
            'dataGeracao' => $dataGeracao,
        ])->render();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
