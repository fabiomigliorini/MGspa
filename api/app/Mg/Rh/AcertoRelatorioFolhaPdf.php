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

        // Uma "página" por FORMA de acerto (Recarga Bee / Dinheiro / Desconto Folha),
        // cada uma agrupada por filial e unidade de negócio — mesma estrutura que a
        // view espera ($paginas[].nome_portador / porFilial / porUnidade / linhas).
        $rows = DB::select("
            SELECT
                a.forma,
                p.pessoa   AS nome_colaborador,
                p.cnpj     AS cpf_colaborador,
                p.fisica,
                f.codfilial,
                f.filial,
                fp.cnpj    AS cnpj_filial,
                un.codunidadenegocio,
                un.descricao AS unidade,
                ABS(a.saldo) AS valor
            FROM tblperiodocolaboradoracerto a
            JOIN tblperiodocolaborador pc ON pc.codperiodocolaborador = a.codperiodocolaborador
            JOIN tblcolaborador col       ON col.codcolaborador = pc.codcolaborador
            JOIN tblpessoa p              ON p.codpessoa = col.codpessoa
            JOIN tblfilial f              ON f.codfilial = col.codfilial
            JOIN tblpessoa fp             ON fp.codpessoa = f.codpessoa
            LEFT JOIN tblsetor s          ON s.codsetor = pc.codsetor
            LEFT JOIN tblunidadenegocio un ON un.codunidadenegocio = s.codunidadenegocio
            WHERE pc.codperiodo = :codperiodo
              AND a.inativo IS NULL
              AND a.saldo <> 0
            ORDER BY a.forma, f.filial, p.pessoa
        ", ['codperiodo' => $codperiodo]);

        // Agrupa forma -> filial -> unidade
        $porForma = [];
        foreach ($rows as $row) {
            $forma = $row->forma;
            if (!isset($porForma[$forma])) {
                $porForma[$forma] = [];
            }

            $filialKey = $row->codfilial;
            if (!isset($porForma[$forma][$filialKey])) {
                $porForma[$forma][$filialKey] = [
                    'filial'      => $row->filial,
                    'cnpj_filial' => $row->cnpj_filial,
                    'porUnidade'  => [],
                ];
            }

            $unidadeKey = $row->codunidadenegocio ?? '__sem_unidade__';
            if (!isset($porForma[$forma][$filialKey]['porUnidade'][$unidadeKey])) {
                $porForma[$forma][$filialKey]['porUnidade'][$unidadeKey] = [
                    'unidade' => $row->unidade,
                    'linhas'  => [],
                ];
            }

            $porForma[$forma][$filialKey]['porUnidade'][$unidadeKey]['linhas'][] = [
                'nome_colaborador' => $row->nome_colaborador,
                'cpf_colaborador'  => $row->cpf_colaborador,
                'fisica'           => (bool) $row->fisica,
                'valor'            => (float) $row->valor,
            ];
        }

        // Ordena as unidades alfabeticamente (sem unidade por último)
        foreach ($porForma as &$filiais) {
            foreach ($filiais as &$fil) {
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
        }
        unset($filiais);

        // Monta as páginas na ordem B, D, F
        $ordem   = [
            PeriodoColaboradorAcerto::FORMA_BEE,
            PeriodoColaboradorAcerto::FORMA_DINHEIRO,
            PeriodoColaboradorAcerto::FORMA_FOLHA,
        ];
        $paginas = [];
        foreach ($ordem as $forma) {
            if (empty($porForma[$forma])) {
                continue;
            }
            $paginas[] = [
                'nome_portador' => PeriodoColaboradorAcerto::FORMA_DESCRICAO[$forma] ?? $forma,
                'porFilial'     => $porForma[$forma],
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
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

}
