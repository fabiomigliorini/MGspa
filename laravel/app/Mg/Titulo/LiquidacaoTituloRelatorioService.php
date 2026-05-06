<?php

namespace Mg\Titulo;

use Mpdf\Mpdf;

class LiquidacaoTituloRelatorioService
{
    public static function pdf(array $filtros): string
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

        $linhas = '';
        foreach ($liqs as $l) {
            $valor = (float)$l->debito - (float)$l->credito;
            $op = $valor < 0 ? 'CR' : 'DB';
            $vstr = number_format(abs($valor), 2, ',', '.');
            $cod = '#' . str_pad((string)$l->codliquidacaotitulo, 8, '0', STR_PAD_LEFT);
            $pess = htmlspecialchars(substr(optional($l->Pessoa)->fantasia ?? '', 0, 40), ENT_QUOTES, 'UTF-8');
            $port = htmlspecialchars(optional($l->Portador)->portador ?? '', ENT_QUOTES, 'UTF-8');
            $tran = $l->transacao ? \Carbon\Carbon::parse($l->transacao)->format('d/m/Y') : '';
            $rowEst = $l->estornado ? ' estornada' : '';

            $movs = collect($l->MovimentoTituloS)
                ->filter(fn($m) => !optional($m->TipoMovimentoTitulo)->estorno && $m->Titulo)
                ->values();
            $rs = max($movs->count(), 1);

            $estLabel = $l->estornado ? "<br><span class='estorno'>Estornado</span>" : '';
            $rhLabel = $l->codperiodo ? "<br><span class='rh'>RH #{$l->codperiodo}</span>" : '';
            $opLow = strtolower($op);
            $celulasLiq = "
                <td rowspan='{$rs}' class='cod'>{$cod}</td>
                <td rowspan='{$rs}' class='pess'>{$pess}</td>
                <td rowspan='{$rs}' class='valor {$opLow}'>{$vstr}&nbsp;{$op}{$estLabel}{$rhLabel}</td>
                <td rowspan='{$rs}' class='tran'>{$tran}</td>
                <td rowspan='{$rs}' class='port'>{$port}</td>
            ";

            if ($movs->isEmpty()) {
                $linhas .= "<tr class='liq-row{$rowEst}'>{$celulasLiq}<td></td><td></td><td></td><td></td><td></td></tr>";
                continue;
            }

            $grupos = $movs->groupBy(fn($m) => $m->codtitulo)->values();
            $primeiroLiq = true;
            foreach ($grupos as $g) {
                $tRs = $g->count();
                $titulo = $g->first()->Titulo;
                $fant = htmlspecialchars(optional($titulo->Pessoa)->fantasia ?? '', ENT_QUOTES, 'UTF-8');
                $num  = htmlspecialchars((string)$titulo->numero, ENT_QUOTES, 'UTF-8');
                $primeiroTit = true;
                foreach ($g as $m) {
                    $valM = (float)$m->debito - (float)$m->credito;
                    $opM  = $valM < 0 ? 'CR' : 'DB';
                    $opMLow = strtolower($opM);
                    $vMstr = number_format(abs($valM), 2, ',', '.');
                    $tipoM = htmlspecialchars(optional($m->TipoMovimentoTitulo)->tipomovimentotitulo ?? '', ENT_QUOTES, 'UTF-8');
                    $celulasInicio = $primeiroLiq ? $celulasLiq : '';
                    $celulasTit = $primeiroTit
                        ? "<td rowspan='{$tRs}' class='mov num'>{$num}</td><td rowspan='{$tRs}' class='mov mov-pess ellipsis'>{$fant}</td>"
                        : '';
                    $cls = ($primeiroLiq ? 'liq-row' : 'mov-row') . $rowEst;
                    $linhas .= "<tr class='{$cls}'>
                        {$celulasInicio}
                        {$celulasTit}
                        <td class='mov mov-valor {$opMLow}'>{$vMstr}&nbsp;{$opM}</td>
                        <td class='mov tipo'>{$tipoM}</td>
                    </tr>";
                    $primeiroLiq = false;
                    $primeiroTit = false;
                }
            }
        }

        $totalLiq = $totalDB - $totalCR;
        $opTot = $totalLiq < 0 ? 'CR' : 'DB';
        $totalStr = number_format(abs($totalLiq), 2, ',', '.');

        $html = "
            <style>
                body { font-family: helvetica; font-size: 7pt; }
                table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                th, td { padding: 2px 4px; vertical-align: top; }
                th { background: #eee; text-align: left; border-bottom: 1px solid #bbb; }
                tfoot td { font-weight: bold; border-top: 2px solid #333; }
                tr.liq-row td { border-top: 1px solid #bbb; }
                tr.estornada td { background-color: #fde9e9; }

                td.cod       { color: #888; }
                td.pess      { color: #1976d2; font-weight: bold; }
                td.valor     { text-align: right; font-weight: bold; }
                td.valor.cr  { color: #cc6600; }
                td.valor.db  { color: #009900; }
                td.mov-valor { text-align: right; }
                td.mov-valor.cr { color: #cc6600; }
                td.mov-valor.db { color: #009900; }
                td.mov       { color: #555; }
                td.ellipsis  { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

                span.estorno { color: #c10015; }
                span.rh      { color: #888; }
            </style>
            <table>
                <colgroup>
                    <col style='width: 6.7%'>
                    <col style='width: 20.6%'>
                    <col style='width: 9.3%'>
                    <col style='width: 8.2%'>
                    <col style='width: 15.5%'>
                    <col style='width: 11.3%'>
                    <col style='width: 10.3%'>
                    <col style='width: 8.8%'>
                    <col style='width: 9.3%'>
                </colgroup>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Pessoa</th>
                        <th style='text-align:right'>Valor</th>
                        <th>Transação</th>
                        <th>Portador</th>
                        <th>Título</th>
                        <th>Pessoa</th>
                        <th>Valor</th>
                        <th>Tipo</th>
                    </tr>
                </thead>
                <tbody>{$linhas}</tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td style='text-align:right'>{$totalStr}&nbsp;{$opTot}</td>
                        <td colspan='7'>&nbsp;</td>
                    </tr>
                </tfoot>
            </table>
        ";

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

        $mpdf->SetHTMLHeader(
            '<div style="font-size:14pt;font-weight:bold;border-bottom:2px solid #000;padding-bottom:3px;">'
                . 'Relat&oacute;rio de Liquida&ccedil;&otilde;es de T&iacute;tulos'
                . '</div>'
        );
        $mpdf->SetHTMLFooter(
            '<div style="text-align:right;font-size:6pt;color:#666;border-top:1px solid #000;padding-top:2px;">'
                . 'MGspa &mdash; {DATE j/m/Y H:i} &mdash; P&aacute;gina {PAGENO} de {nbpg}'
                . '</div>'
        );
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }
}
