@php
    use Carbon\Carbon;
    use Mg\Grao\CargaRelatorioService as R;

    $kg = fn($v) => number_format((float) $v, 0, ',', '.');
    $sc = fn($v) => number_format((float) $v, 1, ',', '.');
    $dt = fn($d) => $d ? Carbon::parse($d)->format('d/m/y H:i') : '';

    // A4 paisagem: 297 - 8 - 8 = 281mm úteis. O layout fica em 276mm; os 5mm de
    // folga são porque o mPDF arredonda pra cima na borda de cada célula e, no
    // limite, joga a última coluna pra fora da página.
    $cols = [15, 21, 11, 26, 32, 46, 46, 20, 19, 22, 18]; // = 276mm
    $agrupado = $agrupar !== 'nenhum';
@endphp
<style>
    body {
        font-family: helvetica;
        font-size: 7pt;
    }

    .report-title {
        font-size: 13pt;
        font-weight: bold;
        border-bottom: 2px solid #000;
        padding-bottom: 2px;
    }

    .legenda {
        font-size: 6.5pt;
        color: #555;
        padding-top: 1mm;
    }

    .footer {
        text-align: right;
        font-size: 6pt;
        color: #666;
        border-top: 1px solid #000;
        padding-top: 2px;
    }

    table.cargas {
        width: 276mm;
        border-collapse: collapse;
        table-layout: fixed;
    }

    table.cargas th {
        background: #eee;
        border-bottom: 1px solid #999;
        padding: 1mm;
        text-align: left;
        font-weight: bold;
        font-size: 7.5pt;
    }

    table.cargas td {
        padding: 0.8mm 1mm;
        vertical-align: top;
        overflow: hidden;
        border-top: 1px solid #ddd;
    }

    .r {
        text-align: right;
    }

    .c {
        text-align: center;
    }

    .nowrap {
        white-space: nowrap;
        overflow: hidden;
    }

    td.rom {
        color: #555;
        white-space: nowrap;
    }

    span.etapa {
        color: #b26500;
        font-size: 6pt;
    }

    span.carreta {
        color: #888;
        font-size: 6pt;
    }

    td.local {
        color: #1976d2;
        overflow: hidden;
    }

    td.liq {
        font-weight: bold;
    }

    td.desc {
        color: #c05600;
    }

    /* Cancelada: some o número e escurece a linha — ela conta na listagem mas
       não entra no que foi movimentado. */
    tr.cancelada td {
        background-color: #fde9e9;
    }

    tr.cancelada td.rom {
        text-decoration: line-through;
    }

    tr.grupo td {
        background: #e4e4e4;
        font-weight: bold;
        font-size: 8pt;
        border-top: 1px solid #999;
        padding: 1.2mm 1mm;
    }

    tr.subtotal td {
        font-weight: bold;
        border-top: 1px solid #999;
        background: #f6f6f6;
    }

    tr.totalgeral td {
        font-weight: bold;
        font-size: 8pt;
        border-top: 2px solid #333;
        background: #ddd;
    }

    .vazio {
        padding: 6mm 0;
        color: #777;
        font-style: italic;
    }
</style>

<htmlpageheader name="page-header">
    <div class="report-title">Romaneios de Grãos</div>
    @if ($legenda)
        <div class="legenda">{{ $legenda }}</div>
    @endif
</htmlpageheader>

<htmlpagefooter name="page-footer">
    <div class="footer">MGspa &mdash; {DATE j/m/Y H:i} &mdash; Página {PAGENO} de {nbpg}</div>
</htmlpagefooter>

<sethtmlpageheader name="page-header" value="on" show-this-page="1">
<sethtmlpagefooter name="page-footer" value="on" show-this-page="1">

@if ($total['qtd'] === 0)
    <div class="vazio">Nenhum romaneio encontrado para o recorte informado.</div>
@else
    {{-- Uma tabela só (e não uma por grupo): mantém as colunas alinhadas de
         ponta a ponta e deixa o mPDF repetir o <thead> sozinho a cada página. --}}
    <table class="cargas">
        <colgroup>
            @foreach ($cols as $w)
                <col style="width:{{ $w }}mm">
            @endforeach
        </colgroup>
        <thead>
            <tr>
                <th>Romaneio</th>
                <th>Chegada</th>
                <th>Tipo</th>
                <th>Placa</th>
                <th>Motorista</th>
                <th>Origem</th>
                <th>Destino</th>
                <th class="r">Bruto kg</th>
                <th class="r">Desc. kg</th>
                <th class="r">Líquido kg</th>
                <th class="r">Sacas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($grupos as $g)
                @if ($agrupado)
                    <tr class="grupo">
                        <td colspan="11">{{ $g['rotulo'] }} &mdash; {{ $g['total']['qtd'] }}
                            {{ $g['total']['qtd'] == 1 ? 'romaneio' : 'romaneios' }}</td>
                    </tr>
                @endif

                @foreach ($g['cargas'] as $c)
                    <tr class="{{ $c->inativo ? 'cancelada' : '' }}">
                        <td class="rom">
                            #{{ $c->codcarga }}
                            @if ($c->etapa !== 'FINALIZADO')
                                <br><span class="etapa">{{ R::ETAPA_LABEL[$c->etapa] ?? $c->etapa }}</span>
                            @elseif ($c->inativo)
                                <br><span class="etapa">Cancelado</span>
                            @endif
                        </td>
                        <td class="nowrap">{{ $dt($c->data) }}</td>
                        <td class="nowrap">{{ R::SENTIDO_CURTO[$c->sentido] ?? $c->sentido }}</td>
                        <td class="nowrap">
                            {{ $c->placa }}
                            @if ($c->placacarreta)
                                <br><span class="carreta">{{ $c->placacarreta }}</span>
                            @endif
                        </td>
                        <td>{{ $c->motorista }}</td>
                        <td class="local">{{ R::rotulosDoPapel($c, 'ORIGEM') }}</td>
                        <td class="local">{{ R::rotulosDoPapel($c, 'DESTINO') }}</td>
                        <td class="r">{{ $kg($c->bruto) }}</td>
                        <td class="r desc">{{ $c->desconto ? $kg($c->desconto) : '' }}</td>
                        <td class="r liq">{{ $kg($c->liquido) }}</td>
                        <td class="r">{{ $sc(R::sacas($c)) }}</td>
                    </tr>
                @endforeach

                @if ($agrupado)
                    <tr class="subtotal">
                        <td colspan="7">Subtotal &mdash; {{ $g['rotulo'] }}</td>
                        <td class="r">{{ $kg($g['total']['bruto']) }}</td>
                        <td class="r">{{ $kg($g['total']['desconto']) }}</td>
                        <td class="r">{{ $kg($g['total']['liquido']) }}</td>
                        <td class="r">{{ $sc($g['total']['sacas']) }}</td>
                    </tr>
                @endif
            @endforeach

            <tr class="totalgeral">
                <td colspan="7">TOTAL GERAL &mdash; {{ $total['qtd'] }}
                    {{ $total['qtd'] == 1 ? 'romaneio' : 'romaneios' }}</td>
                <td class="r">{{ $kg($total['bruto']) }}</td>
                <td class="r">{{ $kg($total['desconto']) }}</td>
                <td class="r">{{ $kg($total['liquido']) }}</td>
                <td class="r">{{ $sc($total['sacas']) }}</td>
            </tr>
        </tbody>
    </table>
@endif
