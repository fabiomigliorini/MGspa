@php
    use Carbon\Carbon;
    use Mg\Negocio\NegocioService;

    $fmtVal = fn($v) => number_format((float) $v, 2, ',', '.');
    $fmtCod = fn($c) => $c ? '#' . str_pad((string) $c, 8, '0', STR_PAD_LEFT) : '';
    $fmtData = fn($d) => $d ? Carbon::parse($d)->format('d/m/y') : '';
    $corte = fn($s, $n) => mb_substr((string) $s, 0, $n);

    // larguras do MGRelatorioNegocios do MGsis (190mm)
    $colWidths = [
        'filial' => 11,
        'usuario' => 12,
        'oper' => 9,
        'codnegocio' => 15,
        'data' => 11,
        'aprazo' => 18,
        'avista' => 18,
        'total' => 18,
        'status' => 15,
        'codpessoa' => 14,
        'fantasia' => 33,
        'vendedor' => 16,
    ];
    // Total Geral numa tabela so' dele: rotulo ate a Data, valores, resto
    $larguraRotulo = $colWidths['filial'] + $colWidths['usuario'] + $colWidths['oper'] + $colWidths['codnegocio'] + $colWidths['data'];
    $larguraResto = $colWidths['status'] + $colWidths['codpessoa'] + $colWidths['fantasia'] + $colWidths['vendedor'];

    $corStatus = [
        NegocioService::STATUS_ABERTO => 'aberto',
        NegocioService::STATUS_CANCELADO => 'cancelado',
    ];
@endphp
<style>
    body {
        font-family: dejavusanscondensed;
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

    table.status {
        width: 190mm;
        border-collapse: collapse;
        margin-bottom: 4mm;
    }

    table.status td,
    table.status th {
        padding: 0.4mm 0.5mm;
        overflow: hidden;
        white-space: nowrap;
    }

    th {
        font-weight: bold;
        border-bottom: 1px solid #000;
        text-align: left;
    }

    th.r, td.r { text-align: right; }
    th.c, td.c { text-align: center; }

    tr.alt td { background: #f0f0f0; }
    tr.aberto td { color: #009600; }
    tr.cancelado td { color: #ff6400; }

    tr.tot td {
        border-top: 1px solid #000;
        font-weight: bold;
        font-size: 8pt;
        padding-top: 0.8mm;
    }

    tr.tot-geral td {
        border-top: 2px solid #000;
        font-weight: bold;
        font-size: 8pt;
        padding-top: 1.5mm;
    }
</style>

<htmlpageheader name="page-header">
    <div class="report-title">Relatório de Negócios</div>
</htmlpageheader>

<htmlpagefooter name="page-footer">
    <div class="footer">MGspa &mdash; {DATE j/m/Y H:i} &mdash; Página {PAGENO} de {nbpg}</div>
</htmlpagefooter>

<sethtmlpageheader name="page-header" value="on" show-this-page="1">
<sethtmlpagefooter name="page-footer" value="on" show-this-page="1">

@if (empty($grupos))
    <p style="text-align:center; color:#888; margin-top:20mm;">Nenhum negócio encontrado.</p>
@endif

@foreach ($grupos as $codnegociostatus => $g)
    <table class="status">
        <thead>
            <tr>
                <th style="width:{{ $colWidths['filial'] }}mm">Filial</th>
                <th style="width:{{ $colWidths['usuario'] }}mm">Usuário</th>
                <th style="width:{{ $colWidths['oper'] }}mm">Oper</th>
                <th style="width:{{ $colWidths['codnegocio'] }}mm">#</th>
                <th style="width:{{ $colWidths['data'] }}mm">Data</th>
                <th style="width:{{ $colWidths['aprazo'] }}mm" class="r">À Prazo</th>
                <th style="width:{{ $colWidths['avista'] }}mm" class="r">À Vista</th>
                <th style="width:{{ $colWidths['total'] }}mm" class="r">Total</th>
                <th style="width:{{ $colWidths['status'] }}mm" class="c">Status</th>
                <th style="width:{{ $colWidths['codpessoa'] }}mm" class="r"># Pessoa</th>
                <th style="width:{{ $colWidths['fantasia'] }}mm">Fantasia</th>
                <th style="width:{{ $colWidths['vendedor'] }}mm">Vendedor</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($g['negocios'] as $i => $n)
                <tr class="{{ $i % 2 === 1 ? 'alt' : '' }} {{ $corStatus[$codnegociostatus] ?? '' }}">
                    <td>{{ $corte(optional($n->Filial)->filial, 8) }}</td>
                    <td>{{ $corte(optional($n->Usuario)->usuario, 9) }}</td>
                    <td>{{ $corte(optional($n->Operacao)->operacao, 7) }}</td>
                    <td>{{ $fmtCod($n->codnegocio) }}</td>
                    <td>{{ $fmtData($n->lancamento) }}</td>
                    <td class="r">{{ $fmtVal($n->valoraprazo) }}</td>
                    <td class="r">{{ $fmtVal($n->valoravista) }}</td>
                    <td class="r">{{ $fmtVal($n->valortotal) }}</td>
                    <td class="c">{{ optional($n->NegocioStatus)->negociostatus }}</td>
                    <td class="r">{{ $fmtCod($n->codpessoa) }}</td>
                    <td>{{ $corte(optional($n->Pessoa)->fantasia, 27) }}</td>
                    <td>{{ $corte(optional($n->PessoaVendedor)->fantasia, 12) }}</td>
                </tr>
            @endforeach
            {{-- sem colspan: o packTableData do mPDF quebra com celula mesclada --}}
            <tr class="tot">
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="r">Total</td>
                <td class="r">{{ $fmtVal($g['totais']['valoraprazo']) }}</td>
                <td class="r">{{ $fmtVal($g['totais']['valoravista']) }}</td>
                <td class="r">{{ $fmtVal($g['totais']['valortotal']) }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>
@endforeach

@if (!empty($grupos))
    <table class="status">
        <tr class="tot-geral">
            <td style="width:{{ $larguraRotulo }}mm" class="r">Total Geral</td>
            <td style="width:{{ $colWidths['aprazo'] }}mm" class="r">{{ $fmtVal($geral['valoraprazo']) }}</td>
            <td style="width:{{ $colWidths['avista'] }}mm" class="r">{{ $fmtVal($geral['valoravista']) }}</td>
            <td style="width:{{ $colWidths['total'] }}mm" class="r">{{ $fmtVal($geral['valortotal']) }}</td>
            <td style="width:{{ $larguraResto }}mm"></td>
        </tr>
    </table>
@endif
