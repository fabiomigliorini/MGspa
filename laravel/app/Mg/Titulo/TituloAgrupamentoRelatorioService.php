<?php

namespace Mg\Titulo;

use Carbon\Carbon;
use Mpdf\Mpdf;

class TituloAgrupamentoRelatorioService
{
    public static function pdfListagem(array $filtros): string
    {
        $q = TituloAgrupamento::query()
            ->select('tbltituloagrupamento.*')
            ->with(['Pessoa:codpessoa,fantasia', 'UsuarioCriacao:codusuario,usuario'])
            ->join('tblpessoa as p', 'p.codpessoa', '=', 'tbltituloagrupamento.codpessoa');

        if (!empty($filtros['codtituloagrupamento'])) {
            $q->where('tbltituloagrupamento.codtituloagrupamento', preg_replace('/[^0-9]/', '', (string)$filtros['codtituloagrupamento']));
        }
        if (!empty($filtros['codpessoa'])) {
            $q->where('tbltituloagrupamento.codpessoa', $filtros['codpessoa']);
        }
        if (!empty($filtros['codgrupoeconomico'])) {
            $q->where('p.codgrupoeconomico', $filtros['codgrupoeconomico']);
        }

        $estornado = $filtros['estornado'] ?? '0';
        if ((string)$estornado === '0') {
            $q->whereNull('tbltituloagrupamento.cancelamento');
        } elseif ((string)$estornado === '1') {
            $q->whereNotNull('tbltituloagrupamento.cancelamento');
        }

        foreach ([
            'emissao_de'  => ['tbltituloagrupamento.emissao', '>='],
            'emissao_ate' => ['tbltituloagrupamento.emissao', '<='],
            'criacao_de'  => ['tbltituloagrupamento.criacao', '>='],
            'criacao_ate' => ['tbltituloagrupamento.criacao', '<='],
        ] as $key => [$col, $op]) {
            if (!empty($filtros[$key])) {
                $bound = str_ends_with($key, '_de') ? 'startOfDay' : 'endOfDay';
                $q->where($col, $op, Carbon::parse($filtros[$key])->{$bound}()->format('Y-m-d H:i:s'));
            }
        }

        $regs = $q->orderBy('tbltituloagrupamento.emissao', 'desc')->get();

        $linhas = '';
        $total = 0.0;
        foreach ($regs as $r) {
            $valor = (float)$r->debito - (float)$r->credito;
            $op = $valor < 0 ? 'CR' : 'DB';
            $cor = $op === 'CR' ? '#cc6600' : '#009900';
            $cod = '#' . str_pad((string)$r->codtituloagrupamento, 8, '0', STR_PAD_LEFT);
            $pess = htmlspecialchars(substr(optional($r->Pessoa)->fantasia ?? '', 0, 50), ENT_QUOTES, 'UTF-8');
            $emi = $r->emissao ? Carbon::parse($r->emissao)->format('d/m/Y') : '';
            $can = $r->cancelamento ? Carbon::parse($r->cancelamento)->format('d/m/Y') : '';
            $obs = htmlspecialchars(substr((string)$r->observacao, 0, 60), ENT_QUOTES, 'UTF-8');
            $vstr = number_format(abs($valor), 2, ',', '.');
            $total += $valor;
            $linhas .= "<tr>
                <td>{$cod}</td><td>{$emi}</td><td>{$pess}</td>
                <td style='text-align:right;color:{$cor}'>{$vstr}</td>
                <td style='color:{$cor}'>{$op}</td>
                <td>{$obs}</td><td>{$can}</td>
            </tr>";
        }

        $opTot = $total < 0 ? 'CR' : 'DB';
        $tstr = number_format(abs($total), 2, ',', '.');
        $html = self::estiloTabela() . "
            <table>
                <thead><tr>
                    <th>#</th><th>Emissão</th><th>Pessoa</th>
                    <th style='text-align:right'>Valor</th><th>OP</th><th>Observação</th><th>Estornado</th>
                </tr></thead>
                <tbody>{$linhas}</tbody>
                <tfoot><tr>
                    <td colspan='3'>Total</td>
                    <td style='text-align:right'>{$tstr}</td><td>{$opTot}</td>
                    <td colspan='2'></td>
                </tr></tfoot>
            </table>";

        return self::renderPdf($html, 'Relat&oacute;rio de Agrupamentos de T&iacute;tulos');
    }

    public static function pdfPendentes(array $filtros): string
    {
        $regs = TituloAgrupamentoService::pendentes($filtros);
        $linhas = '';
        $total = 0.0;
        foreach ($regs as $r) {
            $venc = $r['vencimento'] ? Carbon::parse($r['vencimento'])->format('d/m/Y') : '';
            $sstr = number_format($r['saldo'], 2, ',', '.');
            $total += (float)$r['saldo'];
            $gc = htmlspecialchars((string)$r['grupocliente'], ENT_QUOTES, 'UTF-8');
            $ge = htmlspecialchars((string)$r['grupoeconomico'], ENT_QUOTES, 'UTF-8');
            $pe = htmlspecialchars((string)$r['fantasia'], ENT_QUOTES, 'UTF-8');
            $fp = htmlspecialchars((string)$r['formapagamento'], ENT_QUOTES, 'UTF-8');
            $linhas .= "<tr>
                <td>{$gc}</td><td>{$ge}</td><td>{$pe}</td>
                <td>{$venc}</td>
                <td style='text-align:right'>{$sstr}</td>
                <td>{$fp}</td>
            </tr>";
        }
        $tstr = number_format($total, 2, ',', '.');
        $html = self::estiloTabela() . "
            <table>
                <thead><tr>
                    <th>Grupo Cliente</th><th>Grupo Econômico</th><th>Cliente</th>
                    <th>Vencimento</th><th style='text-align:right'>Saldo</th><th>Forma</th>
                </tr></thead>
                <tbody>{$linhas}</tbody>
                <tfoot><tr>
                    <td colspan='4'>Total</td>
                    <td style='text-align:right'>{$tstr}</td><td></td>
                </tr></tfoot>
            </table>";
        return self::renderPdf($html, 'Fechamentos Pendentes');
    }

    public static function pdfDetalhe(int $id): string
    {
        $ag = TituloAgrupamento::with([
            'Pessoa',
            'Pessoa.Cidade.Estado',
            'TituloS' => function ($q) {
                $q->orderBy('vencimento')->with([
                    'Filial:codfilial,codpessoa,filial',
                    'Filial.Pessoa',
                    'Portador:codportador,portador',
                ]);
            },
            'MovimentoTituloS' => function ($q) {
                $q->orderBy('codmovimentotitulo')->with([
                    'Titulo',
                    'Titulo.Filial:codfilial,filial',
                    'TipoMovimentoTitulo:codtipomovimentotitulo,tipomovimentotitulo,estorno',
                ]);
            },
        ])->findOrFail($id);

        $cod = '#' . str_pad((string)$ag->codtituloagrupamento, 8, '0', STR_PAD_LEFT);
        $html = view('titulo-agrupamento.detalhe', compact('ag'))->render();

        return self::renderPdf($html, 'Agrupamento de T&iacute;tulos ' . $cod);
    }

    private static function estiloTabela(): string
    {
        return "<style>
            body { font-family: helvetica; font-size: 8pt; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            th, td { padding: 2px 4px; border-bottom: 1px solid #ddd; }
            th { background: #eee; text-align: left; }
            tfoot td { font-weight: bold; border-top: 2px solid #333; }
            h3 { margin: 10px 0 4px; font-size: 10pt; }
        </style>";
    }

    private static function renderPdf(string $html, string $titulo): string
    {
        ini_set('memory_limit', '512M');
        set_time_limit(120);

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
            . $titulo
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
