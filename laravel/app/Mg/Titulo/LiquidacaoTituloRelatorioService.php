<?php

namespace Mg\Titulo;

use Mpdf\Mpdf;

class LiquidacaoTituloRelatorioService
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
            'margin_top' => 22,
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
        set_time_limit(120);

        $q = LiquidacaoTitulo::query()
            ->select('tblliquidacaotitulo.*')
            ->with([
                'Pessoa:codpessoa,fantasia',
                'Portador:codportador,portador',
                'UsuarioCriacao:codusuario,usuario',
                'MovimentoTituloS.TipoMovimentoTitulo:codtipomovimentotitulo,tipomovimentotitulo,estorno',
                'MovimentoTituloS.Titulo:codtitulo,codpessoa,numero,vencimento',
                'MovimentoTituloS.Titulo.Pessoa:codpessoa,fantasia',
            ])
            ->join('tblpessoa as p', 'p.codpessoa', '=', 'tblliquidacaotitulo.codpessoa');

        // mesmo filtro do listar(), mas sem paginação
        if (!empty($filtros['codliquidacaotitulo'])) {
            $q->where('tblliquidacaotitulo.codliquidacaotitulo', preg_replace('/[^0-9]/', '', (string)$filtros['codliquidacaotitulo']));
        }
        if (!empty($filtros['codpessoa'])) {
            $q->where('tblliquidacaotitulo.codpessoa', $filtros['codpessoa']);
        }
        if (!empty($filtros['codgrupoeconomico'])) {
            $q->where('p.codgrupoeconomico', $filtros['codgrupoeconomico']);
        }
        if (!empty($filtros['codportador'])) {
            $q->where('tblliquidacaotitulo.codportador', $filtros['codportador']);
        }
        if (!empty($filtros['codusuariocriacao'])) {
            $q->where('tblliquidacaotitulo.codusuariocriacao', $filtros['codusuariocriacao']);
        }

        $estornado = $filtros['estornado'] ?? '0';
        if ((string)$estornado === '0') {
            $q->whereNull('tblliquidacaotitulo.estornado');
        } elseif ((string)$estornado === '1') {
            $q->whereNotNull('tblliquidacaotitulo.estornado');
        }

        foreach (
            [
                'criacao_de'   => ['tblliquidacaotitulo.criacao', '>='],
                'criacao_ate'  => ['tblliquidacaotitulo.criacao', '<='],
                'transacao_de' => ['tblliquidacaotitulo.transacao', '>='],
                'transacao_ate' => ['tblliquidacaotitulo.transacao', '<='],
            ] as $key => [$col, $op]
        ) {
            if (!empty($filtros[$key])) {
                $bound = str_ends_with($key, '_de') ? 'startOfDay' : 'endOfDay';
                $q->where($col, $op, \Carbon\Carbon::parse($filtros[$key])->{$bound}()->format('Y-m-d H:i:s'));
            }
        }

        $q->orderBy('tblliquidacaotitulo.transacao', 'desc')
            ->orderBy('tblliquidacaotitulo.codliquidacaotitulo', 'desc');

        $liqs = $q->get();

        $totalDB = 0.0;
        $totalCR = 0.0;
        foreach ($liqs as $l) {
            $totalDB += (float)$l->debito;
            $totalCR += (float)$l->credito;
        }

        return view('liquidacao-titulo.relatorio', compact('liqs', 'totalDB', 'totalCR'))->render();
    }
}
