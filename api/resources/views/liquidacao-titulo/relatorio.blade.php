@php
    use Carbon\Carbon;

    $fmtVal = fn($v) => number_format(abs((float) $v), 2, ',', '.');
    $fmtData = fn($d) => $d ? Carbon::parse($d)->format('d/m/Y') : '';
    $fmtCod = fn($c) => '#' . str_pad((string) $c, 8, '0', STR_PAD_LEFT);

    $totalLiq = $totalDB - $totalCR;
    $opTot = $totalLiq < 0 ? 'CR' : 'DB';
@endphp
<style>
    body {
        font-family: helvetica;
        font-size: 8pt;
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

    table.outer {
        width: 194mm;
        border-collapse: collapse;
        table-layout: fixed;
    }

    table.outer th {
        background: #eee;
        border-bottom: 1px solid #bbb;
        padding: 1mm;
        text-align: left;
        font-weight: bold;
    }

    table.outer td {
        padding: 1mm;
        vertical-align: top;
        overflow: hidden;
    }

    table.outer tr.liq-row td {
        border-top: 1px solid #bbb;
    }

    table.outer tr.estornada td {
        background-color: #fde9e9;
    }

    table.outer tr td.num,
    table.outer tr td.mov-pess,
    table.outer tr td.mov-valor,
    table.outer tr td.tipo {
        color: #555;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    td.cod {
        color: #888;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    td.pess {
        color: #1976d2;
        font-weight: bold;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    td.valor {
        text-align: right;
        font-weight: bold;
    }

    td.valor.cr {
        color: #cc6600;
    }

    td.valor.db {
        color: #009900;
    }

    td.tran {
        white-space: nowrap;
    }

    td.port {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    td.mov-valor {
        text-align: right;
    }

    td.mov-valor.cr {
        color: #cc6600;
    }

    td.mov-valor.db {
        color: #009900;
    }

    table.outer th.h-valor {
        text-align: right;
    }

    table.outer th.h-mov-valor {
        text-align: right;
    }

    span.estorno {
        color: #c10015;
    }

    span.rh {
        color: #888;
    }

    table.total {
        width: 194mm;
        border-collapse: collapse;
        margin-top: 1mm;
    }

    table.total td {
        padding: 1mm;
        font-weight: bold;
        border-top: 2px solid #333;
    }

    table.total td.label {
        width: 14mm;
    }

    table.total td.tot-valor {
        width: 39mm;
        text-align: right;
    }

    table.total td.tot-valor.cr {
        color: #cc6600;
    }

    table.total td.tot-valor.db {
        color: #009900;
    }
</style>

<htmlpageheader name="page-header">
    <div class="report-title">Relatório de Liquidações de Títulos</div>
</htmlpageheader>

<htmlpagefooter name="page-footer">
    <div class="footer">MGspa &mdash; {DATE j/m/Y H:i} &mdash; Página {PAGENO} de {nbpg}</div>
</htmlpagefooter>

<sethtmlpageheader name="page-header" value="on" show-this-page="1">
    <sethtmlpagefooter name="page-footer" value="on" show-this-page="1">

        <table class="outer">
            <colgroup>
                <col style="width:14mm">
                <col style="width:39mm">
                <col style="width:18mm">
                <col style="width:16mm">
                <col style="width:30mm">
                <col style="width:22mm">
                <col style="width:20mm">
                <col style="width:17mm">
                <col style="width:18mm">
            </colgroup>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Pessoa</th>
                    <th class="h-valor">Valor</th>
                    <th>Transação</th>
                    <th>Portador</th>
                    <th>Título</th>
                    <th>Pessoa</th>
                    <th class="h-mov-valor">Valor</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($liqs as $l)
                    @php
                        $valor = (float) $l->debito - (float) $l->credito;
                        $op = $valor < 0 ? 'CR' : 'DB';
                        $opLow = strtolower($op);
                        $clsEst = $l->estornado ? ' estornada' : '';
                        $movs = collect($l->MovimentoTituloS)
                            ->filter(fn($m) => !optional($m->TipoMovimentoTitulo)->estorno && $m->Titulo)
                            ->values();
                    @endphp

                    @if ($movs->isEmpty())
                        <tr class="liq-row{{ $clsEst }}">
                            <td class="cod">{{ $fmtCod($l->codliquidacaotitulo) }}</td>
                            <td class="pess">{{ mb_substr(optional($l->Pessoa)->fantasia ?? '', 0, 40) }}</td>
                            <td class="valor {{ $opLow }}">
                                {{ $fmtVal($valor) }}&nbsp;{{ $op }}
                                @if ($l->estornado)
                                    <br><span class="estorno">Estornado</span>
                                @endif
                                @if ($l->codperiodo)
                                    <br><span class="rh">RH #{{ $l->codperiodo }}</span>
                                @endif
                            </td>
                            <td class="tran">{{ $fmtData($l->transacao) }}</td>
                            <td class="port">{{ optional($l->Portador)->portador }}</td>
                            <td class="num"></td>
                            <td class="mov-pess"></td>
                            <td class="mov-valor"></td>
                            <td class="tipo"></td>
                        </tr>
                    @else
                        @php
                            $primeira = true;
                            $lastCodTitulo = null;
                        @endphp
                        @foreach ($movs as $m)
                            @php
                                $valM = (float) $m->debito - (float) $m->credito;
                                $opM = $valM < 0 ? 'CR' : 'DB';
                                $opMLow = strtolower($opM);
                                $sameTitulo = $m->codtitulo === $lastCodTitulo;
                            @endphp
                            <tr class="{{ $primeira ? 'liq-row' : 'liq-cont' }}{{ $clsEst }}">
                                @if ($primeira)
                                    <td class="cod">{{ $fmtCod($l->codliquidacaotitulo) }}</td>
                                    <td class="pess">{{ mb_substr(optional($l->Pessoa)->fantasia ?? '', 0, 40) }}
                                    </td>
                                    <td class="valor {{ $opLow }}">
                                        {{ $fmtVal($valor) }}&nbsp;{{ $op }}
                                        @if ($l->estornado)
                                            <br><span class="estorno">Estornado</span>
                                        @endif
                                        @if ($l->codperiodo)
                                            <br><span class="rh">RH #{{ $l->codperiodo }}</span>
                                        @endif
                                    </td>
                                    <td class="tran">{{ $fmtData($l->transacao) }}</td>
                                    <td class="port">{{ optional($l->Portador)->portador }}</td>
                                @else
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                @endif
                                <td class="num">{{ $sameTitulo ? '' : $m->Titulo->numero }}</td>
                                <td class="mov-pess">{{ $sameTitulo ? '' : optional($m->Titulo->Pessoa)->fantasia }}
                                </td>
                                <td class="mov-valor {{ $opMLow }}">
                                    {{ $fmtVal($valM) }}&nbsp;{{ $opM }}</td>
                                <td class="tipo">{{ optional($m->TipoMovimentoTitulo)->tipomovimentotitulo }}</td>
                            </tr>
                            @php
                                $primeira = false;
                                $lastCodTitulo = $m->codtitulo;
                            @endphp
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>

        <table class="total">
            <tr>
                <td class="label">Total</td>
                <td class="tot-valor {{ strtolower($opTot) }}">{{ $fmtVal($totalLiq) }}&nbsp;{{ $opTot }}</td>
            </tr>
        </table>
