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

        // Uma linha por vínculo, com Recarga Bee e Dinheiro lado a lado, agrupada
        // pela EMPRESA da filial do vínculo (tblcolaborador.codfilial ->
        // tblfilial.codempresa) — o mesmo corte do Gerar Recarga — na ordem do
        // código: Migliorini (1), FDF (2), Fazenda (4). Desconto Folha não entra.
        // Vários eventos da mesma forma no vínculo saem somados.
        $rows = DB::select("
            SELECT
                e.codempresa,
                e.empresa,
                pc.codperiodocolaborador,
                p.pessoa AS nome,
                p.cnpj   AS cpf,
                p.fisica,
                COALESCE(SUM(a.saldo) FILTER (WHERE a.forma = :bee), 0)      AS bee,
                COALESCE(SUM(a.saldo) FILTER (WHERE a.forma = :dinheiro), 0) AS dinheiro
            FROM tblperiodocolaboradoracerto a
            JOIN tblperiodocolaborador pc ON pc.codperiodocolaborador = a.codperiodocolaborador
            JOIN tblcolaborador col       ON col.codcolaborador = pc.codcolaborador
            JOIN tblpessoa p              ON p.codpessoa = col.codpessoa
            JOIN tblfilial f              ON f.codfilial = col.codfilial
            JOIN tblempresa e             ON e.codempresa = f.codempresa
            WHERE pc.codperiodo = :codperiodo
              AND a.inativo IS NULL
              AND a.saldo <> 0
              AND a.forma IN (:bee2, :dinheiro2)
            GROUP BY e.codempresa, e.empresa, pc.codperiodocolaborador, p.pessoa, p.cnpj, p.fisica
            ORDER BY e.codempresa, p.pessoa
        ", [
            'codperiodo' => $codperiodo,
            'bee'        => PeriodoColaboradorAcerto::FORMA_BEE,
            'dinheiro'   => PeriodoColaboradorAcerto::FORMA_DINHEIRO,
            'bee2'       => PeriodoColaboradorAcerto::FORMA_BEE,
            'dinheiro2'  => PeriodoColaboradorAcerto::FORMA_DINHEIRO,
        ]);

        $empresas = [];
        $totalBee = 0;
        $totalDinheiro = 0;
        foreach ($rows as $row) {
            if (!isset($empresas[$row->codempresa])) {
                $empresas[$row->codempresa] = [
                    'empresa'        => $row->empresa,
                    'linhas'         => [],
                    'total_bee'      => 0,
                    'total_dinheiro' => 0,
                ];
            }
            $bee = (float) $row->bee;
            $dinheiro = (float) $row->dinheiro;
            $empresas[$row->codempresa]['linhas'][] = [
                'nome'     => $row->nome,
                'cpf'      => $row->cpf,
                'fisica'   => (bool) $row->fisica,
                'bee'      => $bee,
                'dinheiro' => $dinheiro,
            ];
            $empresas[$row->codempresa]['total_bee'] += $bee;
            $empresas[$row->codempresa]['total_dinheiro'] += $dinheiro;
            $totalBee += $bee;
            $totalDinheiro += $dinheiro;
        }

        $periodo_label = $periodo->periodoinicial->format('d/m/Y')
            . ' a '
            . $periodo->periodofinal->format('d/m/Y');
        $dataGeracao = Carbon::now()->format('d/m/Y H:i');

        $html = view('rh.acerto-relatorio-folha', [
            'empresas'      => $empresas,
            'totalBee'      => $totalBee,
            'totalDinheiro' => $totalDinheiro,
            'periodo'       => $periodo_label,
            'dataGeracao'   => $dataGeracao,
        ])->render();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

}
