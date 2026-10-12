<?php

namespace Mg\Pdv;

use Mg\Negocio\Negocio;
use Mpdf\Mpdf;

// Relatorio de negocios (TASK-189): o MGRelatorioNegocios do MGsis, com os
// filtros da listagem do PDV
class PdvNegocioRelatorioService
{
    // ~4ms e ~0,15MB por linha no mPDF: 2000 cabem nos 15s do axios do app
    // negocios e nos 512MB
    const LIMITE = 2000;

    public static function pdf(array $filtros): string
    {
        $html = self::html($filtros);
        // milhares de linhas passam do limite padrao do pcre no WriteHTML
        ini_set('pcre.backtrack_limit', '100000000');

        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 22,
            'margin_bottom' => 14,
            'margin_header' => 5,
            'margin_footer' => 5,
            // condensada: as larguras do MGsis eram para a helvetica
            'default_font' => 'dejavusanscondensed',
            'tempDir' => $tempDir,
            // tabela de milhares de linhas: menos memoria
            'packTableData' => true,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public static function html(array $filtros): string
    {
        ini_set('memory_limit', '512M');
        set_time_limit(120);

        $q = Negocio::query()
            ->select([
                'codnegocio', 'codfilial', 'codusuario', 'codoperacao', 'codnegociostatus',
                'codpessoa', 'codpessoavendedor', 'lancamento',
                'valoraprazo', 'valoravista', 'valortotal',
            ])
            ->with([
                'Filial:codfilial,filial',
                'Usuario:codusuario,usuario',
                'Operacao:codoperacao,operacao',
                'NegocioStatus:codnegociostatus,negociostatus',
                'Pessoa:codpessoa,fantasia',
                'PessoaVendedor:codpessoa,fantasia',
            ]);
        PdvNegocioListagemService::filtrar($q, $filtros);
        if ((clone $q)->count() > static::LIMITE) {
            abort(422, 'Mais de ' . static::LIMITE . ' negócios: refine os filtros do relatório.');
        }
        $q->orderBy('codnegociostatus')
            ->orderBy('lancamento', 'desc')
            ->orderBy('codnegocio', 'desc');

        // agrupado por status, com total de cada um e o geral
        $grupos = [];
        $geral = ['valoraprazo' => 0.0, 'valoravista' => 0.0, 'valortotal' => 0.0];
        foreach ($q->get() as $negocio) {
            $st = $negocio->codnegociostatus;
            if (!isset($grupos[$st])) {
                $grupos[$st] = [
                    'negocios' => [],
                    'totais' => ['valoraprazo' => 0.0, 'valoravista' => 0.0, 'valortotal' => 0.0],
                ];
            }
            $grupos[$st]['negocios'][] = $negocio;
            foreach ($geral as $col => $v) {
                $grupos[$st]['totais'][$col] += (float) $negocio->$col;
                $geral[$col] += (float) $negocio->$col;
            }
        }

        return view('negocio.relatorio', compact('grupos', 'geral'))->render();
    }
}
