<?php

namespace Mg\Titulo;

use Mpdf\Mpdf;

class TituloListagemRelatorioService
{
    public static function pdf(array $filtros): string
    {
        $html = self::html($filtros);

        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 16,
            'margin_bottom' => 14,
            'margin_header' => 5,
            'margin_footer' => 5,
            'default_font' => 'helvetica',
            'tempDir' => $tempDir,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public static function html(array $filtros): string
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $detalhado = !empty($filtros['detalhado']) && (string)$filtros['detalhado'] !== '0';

        $titulos = TituloListagemService::query($filtros, [
            'Pessoa:codpessoa,fantasia',
            'Filial:codfilial,filial',
            'Portador:codportador,portador',
            'TipoTitulo:codtipotitulo,tipotitulo',
            'ContaContabil:codcontacontabil,contacontabil',
        ])->get();

        $grupos = [];
        $totGeral = [
            'original' => 0.0,
            'saldo' => 0.0,
            'multa' => 0.0,
            'juros' => 0.0,
            'total' => 0.0,
        ];

        foreach ($titulos as $t) {
            $cod = (int)$t->codpessoa;
            if (!isset($grupos[$cod])) {
                $grupos[$cod] = [
                    'codpessoa' => $cod,
                    'fantasia' => optional($t->Pessoa)->fantasia ?? '',
                    'linhas' => [],
                    'totais' => ['original' => 0.0, 'saldo' => 0.0, 'multa' => 0.0, 'juros' => 0.0, 'total' => 0.0],
                ];
            }

            $original = (float)$t->debito - (float)$t->credito;
            $saldo = (float)$t->saldo;
            $atualizacao = TituloService::calcularAtualizacao($saldo, $t->vencimento);
            $multa = $atualizacao['multa'];
            $juros = $atualizacao['juros'];
            $total = $atualizacao['valoratualizado'];

            $grupos[$cod]['linhas'][] = [
                'titulo' => $t,
                'original' => $original,
                'saldo' => $saldo,
                'diasAtraso' => $atualizacao['diasatraso'],
                'multa' => $multa,
                'juros' => $juros,
                'total' => $total,
            ];

            $grupos[$cod]['totais']['original'] += $original;
            $grupos[$cod]['totais']['saldo']    += $saldo;
            $grupos[$cod]['totais']['multa']    += $multa;
            $grupos[$cod]['totais']['juros']    += $juros;
            $grupos[$cod]['totais']['total']    += $total;

            $totGeral['original'] += $original;
            $totGeral['saldo']    += $saldo;
            $totGeral['multa']    += $multa;
            $totGeral['juros']    += $juros;
            $totGeral['total']    += $total;
        }

        return view('titulo-listagem-relatorio.relatorio', [
            'grupos' => array_values($grupos),
            'totGeral' => $totGeral,
            'detalhado' => $detalhado,
            'diasTolerancia' => TituloService::DIAS_TOLERANCIA_ATRASO,
        ])->render();
    }
}
