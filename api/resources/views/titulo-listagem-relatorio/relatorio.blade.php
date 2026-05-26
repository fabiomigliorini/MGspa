@php
    use Carbon\Carbon;

    $fmtVal = fn($v) => number_format(abs((float) $v), 2, ',', '.');
    $fmtCod = fn($c) => '#' . str_pad((string) $c, 8, '0', STR_PAD_LEFT);
    $fmtData = fn($d) => $d ? Carbon::parse($d)->format('d/m/y') : '';
    $sinal = fn($v) => ((float) $v) < 0 ? 'CR' : 'DB';

    // larguras em mm (somando 194mm = A4 196mm - 2mm de folga)
    $colWidths = [
        'filial'  => 12,
        'portador'=> 17,
        'bol'     => 6,
        'numero'  => 27,
        'fatura'  => 24,
        'emissao' => 11,
        'original'=> 18,
        'vcto'    => 10,
        'saldo'   => 18,
        'multa'   => 12,
        'juros'   => 12,
        'total'   => 18,
        'op'      => 9,
    ];
@endphp
<style>
    body {
        font-family: helvetica;
        font-size: 7pt;
    }

    .report-title {
        font-size: 14pt;
        font-weight: bold;
        border-bottom: 2px solid #000;
        padding-bottom: 3px;
        margin-bottom: 2mm;
    }

    .footer {
        text-align: right;
        font-size: 6pt;
        color: #666;
        border-top: 1px solid #000;
        padding-top: 2px;
    }

    table.pessoa {
        width: 194mm;
        border-collapse: collapse;
        table-layout: fixed;
        margin-bottom: 3mm;
    }

    table.pessoa td,
    table.pessoa th {
        padding: 0.4mm 1mm;
        overflow: hidden;
    }

    /* cabeçalho da pessoa */
    tr.head-pessoa td {
        border-bottom: 2px solid #000;
        padding-top: 1.2mm;
        padding-bottom: 1mm;
    }
    td.codpessoa {
        font-size: 7pt;
        color: #666;
        width: 18mm;
    }
    td.fantasia {
        font-size: 11pt;
        font-weight: bold;
        color: #000;
    }

    /* cabeçalho da tabela */
    tr.head-cols th {
        font-weight: bold;
        border-bottom: 1px solid #999;
        font-size: 7pt;
        text-align: left;
    }
    th.r { text-align: right; }
    th.c { text-align: center; }

    /* linhas */
    tr.alt td { background: #ebebeb; }
    td.r { text-align: right; }
    td.c { text-align: center; }
    td.gerencial { color: #ff6400; }
    td.fiscal { color: #009600; }
    td.cr { color: #ff6400; }
    td.db { color: #009600; }
    td.atrasado { color: #cc0000; font-weight: bold; }
    td.tolerancia { color: #ff6400; font-weight: bold; }
    td.adiantado { color: #009600; font-weight: bold; }
    td.liquidado { color: #888; font-weight: bold; }
    td.b { font-weight: bold; }

    /* detalhado */
    tr.det td {
        font-size: 6pt;
        color: #333;
        padding-top: 0;
        padding-bottom: 0.6mm;
    }

    /* totais */
    tr.tot-pessoa td {
        border-top: 1px solid #999;
        font-weight: bold;
        padding-top: 0.8mm;
    }
    tr.tot-geral td {
        border-top: 2px solid #000;
        font-weight: bold;
        padding-top: 1.5mm;
        font-size: 8pt;
    }
</style>

<htmlpageheader name="page-header">
    <div class="report-title">Relatório de Títulos</div>
</htmlpageheader>

<htmlpagefooter name="page-footer">
    <div class="footer">MGspa &mdash; {DATE j/m/Y H:i} &mdash; Página {PAGENO} de {nbpg}</div>
</htmlpagefooter>

<sethtmlpageheader name="page-header" value="on" show-this-page="1">
<sethtmlpagefooter name="page-footer" value="on" show-this-page="1">

@if (empty($grupos))
    <p style="text-align:center; color:#888; margin-top:20mm;">Nenhum título encontrado.</p>
@endif

@foreach ($grupos as $g)
    <table class="pessoa">
        <colgroup>
            <col style="width:{{ $colWidths['filial'] }}mm">
            <col style="width:{{ $colWidths['portador'] }}mm">
            <col style="width:{{ $colWidths['bol'] }}mm">
            <col style="width:{{ $colWidths['numero'] }}mm">
            <col style="width:{{ $colWidths['fatura'] }}mm">
            <col style="width:{{ $colWidths['emissao'] }}mm">
            <col style="width:{{ $colWidths['original'] }}mm">
            <col style="width:{{ $colWidths['vcto'] }}mm">
            <col style="width:{{ $colWidths['saldo'] }}mm">
            <col style="width:{{ $colWidths['multa'] }}mm">
            <col style="width:{{ $colWidths['juros'] }}mm">
            <col style="width:{{ $colWidths['total'] }}mm">
            <col style="width:{{ $colWidths['op'] }}mm">
        </colgroup>

        <tr class="head-pessoa">
            <td class="codpessoa">{{ $fmtCod($g['codpessoa']) }}</td>
            <td class="fantasia" colspan="12">{{ $g['fantasia'] }}</td>
        </tr>

        <tr class="head-cols">
            <th>Filial</th>
            <th>Portador</th>
            <th>BOL</th>
            <th>Título</th>
            <th>Fatura</th>
            <th class="c">Emissão</th>
            <th class="r">Original</th>
            <th class="c">Venc</th>
            <th class="r">Saldo</th>
            <th class="r">Multa</th>
            <th class="r">Juros</th>
            <th class="r">Total</th>
            <th class="c">OP</th>
        </tr>

        @foreach ($g['linhas'] as $i => $l)
            @php
                $t = $l['titulo'];
                $altCls = $i % 2 === 1 ? ' alt' : '';
                $opSaldo = $sinal($l['saldo']);
                $opOriginal = $sinal($l['original']);

                if ((float)$l['saldo'] == 0) {
                    $vctoCls = 'liquidado';
                } elseif ($l['diasAtraso'] > $diasTolerancia) {
                    $vctoCls = 'atrasado';
                } elseif ($l['diasAtraso'] > 0) {
                    $vctoCls = 'tolerancia';
                } else {
                    $vctoCls = 'adiantado';
                }
            @endphp
            <tr class="line{{ $altCls }}">
                <td class="{{ $t->gerencial ? 'gerencial' : 'fiscal' }}">{{ optional($t->Filial)->filial }}</td>
                <td>{{ optional($t->Portador)->portador }}</td>
                <td>{{ $t->boleto ? 'BOL' : '' }}</td>
                <td>{{ $t->numero }}</td>
                <td>{{ $t->fatura }}</td>
                <td class="c">{{ $fmtData($t->emissao) }}</td>
                <td class="r">{{ $fmtVal($l['original']) }}</td>
                <td class="c {{ $vctoCls }}">{{ $fmtData($t->vencimento) }}</td>
                <td class="r {{ strtolower($opSaldo) }}">{{ $fmtVal($l['saldo']) }}</td>
                <td class="r">{{ $fmtVal($l['multa']) }}</td>
                <td class="r">{{ $fmtVal($l['juros']) }}</td>
                <td class="r b">{{ $fmtVal($l['total']) }}</td>
                <td class="c">{{ $opSaldo }}</td>
            </tr>
            @if ($detalhado)
                <tr class="det{{ $altCls }}">
                    <td colspan="3">{{ optional($t->TipoTitulo)->tipotitulo }}</td>
                    <td colspan="3">{{ optional($t->ContaContabil)->contacontabil }}</td>
                    <td colspan="7">{{ $t->observacao }}</td>
                </tr>
            @endif
        @endforeach

        <tr class="tot-pessoa">
            <td colspan="6" class="r">Total</td>
            <td class="r">{{ $fmtVal($g['totais']['original']) }}</td>
            <td></td>
            <td class="r {{ strtolower($sinal($g['totais']['saldo'])) }}">{{ $fmtVal($g['totais']['saldo']) }}</td>
            <td class="r">{{ $fmtVal($g['totais']['multa']) }}</td>
            <td class="r">{{ $fmtVal($g['totais']['juros']) }}</td>
            <td class="r">{{ $fmtVal($g['totais']['total']) }}</td>
            <td class="c">{{ $sinal($g['totais']['total']) }}</td>
        </tr>
    </table>
@endforeach

@if (!empty($grupos))
    <table class="pessoa">
        <colgroup>
            <col style="width:{{ $colWidths['filial'] }}mm">
            <col style="width:{{ $colWidths['portador'] }}mm">
            <col style="width:{{ $colWidths['bol'] }}mm">
            <col style="width:{{ $colWidths['numero'] }}mm">
            <col style="width:{{ $colWidths['fatura'] }}mm">
            <col style="width:{{ $colWidths['emissao'] }}mm">
            <col style="width:{{ $colWidths['original'] }}mm">
            <col style="width:{{ $colWidths['vcto'] }}mm">
            <col style="width:{{ $colWidths['saldo'] }}mm">
            <col style="width:{{ $colWidths['multa'] }}mm">
            <col style="width:{{ $colWidths['juros'] }}mm">
            <col style="width:{{ $colWidths['total'] }}mm">
            <col style="width:{{ $colWidths['op'] }}mm">
        </colgroup>
        <tr class="tot-geral">
            <td colspan="6" class="r">Total Geral</td>
            <td class="r">{{ $fmtVal($totGeral['original']) }}</td>
            <td></td>
            <td class="r {{ strtolower($sinal($totGeral['saldo'])) }}">{{ $fmtVal($totGeral['saldo']) }}</td>
            <td class="r">{{ $fmtVal($totGeral['multa']) }}</td>
            <td class="r">{{ $fmtVal($totGeral['juros']) }}</td>
            <td class="r">{{ $fmtVal($totGeral['total']) }}</td>
            <td class="c">{{ $sinal($totGeral['total']) }}</td>
        </tr>
    </table>
@endif
