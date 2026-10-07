@php
    use Carbon\Carbon;
    use Mg\Grao\CargaRelatorioService as R;

    // Espelha a listagem da tela (agro/src/pages/CargasPage.vue) em A4 retrato:
    // as 13 colunas viram 11, com dois campos empilhados por célula (tipo/data,
    // placa/motorista). Safra, origem e destino têm coluna própria. "—" cinza onde não tem
    // valor, líquido em negrito.
    $nulo = '<span class="nulo">—</span>';
    $kg = fn($v) => $v === null || $v === '' ? $nulo : number_format((float) $v, 0, ',', '.');
    $sc = fn($v) => $v === null ? $nulo : number_format((float) $v, 1, ',', '.');
    $dt = fn($d) => $d ? Carbon::parse($d)->format('d/m/Y') : '';
    $romaneios = fn($n) => number_format($n, 0, ',', '.') . ($n == 1 ? ' romaneio' : ' romaneios');

    // Texto longo é cortado com reticências, como na tela: cada linha do
    // romaneio fica com exatamente 2 linhas de altura. O mPDF não faz
    // text-overflow, então o corte é aqui, por número de caracteres.
    $texto = fn($v, $max) => $v !== null && $v !== '' ? e(mb_strimwidth($v, 0, $max, '…')) : $nulo;

    // A4 retrato: 210 - 8 - 8 = 194mm úteis. O layout fica em 190mm; a folga é
    // porque o mPDF arredonda pra cima na borda de cada célula e, no limite,
    // joga a última coluna pra fora da página.
    $cols = [20, 18, 22, 27, 22, 22, 12, 11, 12, 12, 12]; // = 190mm
    // Colunas antes do Bruto: o rótulo das linhas de total ocupa todas elas.
    $colspanRotulo = 6;
    $agrupado = $agrupar !== 'nenhum';
@endphp
<style>
    body {
        font-family: helvetica;
        font-size: 7.5pt;
        color: #222;
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
        width: 190mm;
        border-collapse: collapse;
        table-layout: fixed;
        border: 1px solid #ddd;
    }

    table.cargas th {
        padding: 1.5mm 1.5mm;
        text-align: left;
        font-weight: bold;
        border-bottom: 1px solid #e0e0e0;
    }

    table.cargas td {
        padding: 1.5mm 1.5mm;
        vertical-align: top;
        white-space: nowrap;
        overflow: hidden;
        border-bottom: 1px solid #e0e0e0;
    }

    /* Números: respiro lateral menor — "4.244.797" do total cabe em 13mm. */
    table.cargas th.r,
    table.cargas td.r {
        text-align: right;
        padding-left: 0.8mm;
        padding-right: 1.2mm;
    }

    /* Segundo campo da célula: secundário, como a 2ª linha de um q-item. */
    .sub {
        color: #757575;
        font-size: 6.5pt;
    }

    .nulo {
        color: #9e9e9e;
    }

    td.liq {
        font-weight: bold;
    }

    @foreach (R::ETAPA_COR as $etapa => $cor)
        .etapa-{{ $etapa }} {
            color: {{ $cor }};
        }
    @endforeach

    /* Como na tela: a cancelada não muda a linha, só troca a etapa por "Cancelado". */
    .cancelado {
        color: #f57c00;
        font-weight: bold;
    }

    tr.grupo td {
        background: #f5f5f5;
        font-weight: bold;
    }

    /* Linhas de total no estilo do bottom-row da tela (bg-grey-1). */
    tr.total td {
        background: #fafafa;
        font-weight: bold;
    }

    /* Quadro de totais: fonte maior e respiro largo — só os pesos importam. */
    table.resumo {
        width: 190mm;
        margin-top: 6mm;
        border-collapse: collapse;
        border: 1px solid #ddd;
        font-size: 9pt;
        page-break-inside: avoid;
    }

    table.resumo th {
        padding: 2.5mm 4mm;
        text-align: left;
        font-weight: bold;
        background: #f5f5f5;
        border-bottom: 2px solid #999;
    }

    table.resumo td {
        padding: 3mm 4mm;
        border-bottom: 1px solid #e0e0e0;
    }

    table.resumo th.r,
    table.resumo td.r {
        text-align: right;
    }

    table.resumo td.rotulo,
    table.resumo td.liq {
        font-weight: bold;
    }

    span.qtd {
        color: #757575;
        font-weight: normal;
    }

    .vazio {
        padding: 6mm 0;
        color: #777;
        font-style: italic;
    }
</style>

<htmlpageheader name="page-header">
    <div class="report-title">Relatório de cargas</div>
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
                        <th style="width:{{ $cols[0] }}mm">Tipo<br><span class="sub">Data</span></th>
                        <th style="width:{{ $cols[1] }}mm">Etapa</th>
                        <th style="width:{{ $cols[2] }}mm">Safra</th>
                        <th style="width:{{ $cols[3] }}mm">Placa<br><span class="sub">Motorista</span></th>
                        <th style="width:{{ $cols[4] }}mm">Origem</th>
                        <th style="width:{{ $cols[5] }}mm">Destino</th>
                        <th class="r" style="width:{{ $cols[6] }}mm">Bruto</th>
                        <th class="r" style="width:{{ $cols[7] }}mm">Tara</th>
                        <th class="r" style="width:{{ $cols[8] }}mm">Desc.</th>
                        <th class="r" style="width:{{ $cols[9] }}mm">Líquido</th>
                        <th class="r" style="width:{{ $cols[10] }}mm">Sacas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grupos as $g)
                        @if ($agrupado)
                            <tr class="grupo">
                                <td colspan="11">{{ $g['rotulo'] }} &mdash; {{ $romaneios($g['total']['qtd']) }}
                                </td>
                            </tr>
                        @endif

                        @foreach ($g['cargas'] as $c)
                            <tr>
                                <td>
                                    {{ R::SENTIDO_LABEL[$c->sentido] ?? $c->sentido }}<br>
                                    <span class="sub">{{ $dt($c->data) }}</span>
                                </td>
                                <td>
                                    @if ($c->inativo)
                                        <span class="cancelado">Cancelado</span>
                                    @else
                                        <span
                                            class="etapa-{{ $c->etapa }}">{{ R::ETAPA_LABEL[$c->etapa] ?? $c->etapa }}</span>
                                    @endif
                                </td>
                                <td>{!! $texto($c->Safra->safra ?? null, 16) !!}</td>
                                <td>
                                    {!! $texto($c->placa, 12) !!}<br>
                                    <span class="sub">{!! $texto($c->motorista, 20) !!}</span>
                                </td>
                                <td>{!! $texto(R::rotulosDoPapel($c, 'ORIGEM'), 14) !!}</td>
                                <td>{!! $texto(R::rotulosDoPapel($c, 'DESTINO'), 14) !!}</td>
                                {{-- Coluna "Bruto" mostra o PBT (caminhão cheio), a pedido. --}}
                                <td class="r">{!! $kg($c->pbt) !!}</td>
                                <td class="r">{!! $kg($c->tara) !!}</td>
                                <td class="r">{!! $kg($c->desconto) !!}</td>
                                <td class="r liq">{!! $kg($c->liquido) !!}</td>
                                <td class="r">{!! $sc($c->liquido === null ? null : R::sacas($c)) !!}</td>
                            </tr>
                        @endforeach

                        @if ($agrupado)
                            @foreach ($g['total']['sentidos'] as $sentido => $t)
                                <tr class="total">
                                    <td colspan="{{ $colspanRotulo }}">
                                        Subtotal {{ $g['rotulo'] }} &mdash;
                                        {{ R::TOTAL_LABEL[$sentido] ?? $sentido }}
                                        <span class="qtd">· {{ $romaneios($t['qtd']) }}</span>
                                    </td>
                                    <td class="r">{!! $kg($t['pbt']) !!}</td>
                                    <td></td>
                                    <td class="r">{!! $kg($t['desconto']) !!}</td>
                                    <td class="r">{!! $kg($t['liquido']) !!}</td>
                                    <td class="r">{!! $sc($t['sacas']) !!}</td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach

                </tbody>
            </table>

            {{-- Totais num quadro à parte, com títulos e colunas largas: no fim só
                 interessam os pesos. Uma linha por tipo e sem canceladas. --}}
            @if (count($total['sentidos']))
                <table class="resumo">
                    <thead>
                        <tr>
                            <th>Totais</th>
                            <th class="r">Romaneios</th>
                            <th class="r">Bruto (kg)</th>
                            <th class="r">Desconto (kg)</th>
                            <th class="r">Líquido (kg)</th>
                            <th class="r">Sacas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($total['sentidos'] as $sentido => $t)
                            <tr>
                                <td class="rotulo">{{ R::TOTAL_LABEL[$sentido] ?? $sentido }}</td>
                                <td class="r">{{ number_format($t['qtd'], 0, ',', '.') }}</td>
                                <td class="r">{!! $kg($t['pbt']) !!}</td>
                                <td class="r">{!! $kg($t['desconto']) !!}</td>
                                <td class="r liq">{!! $kg($t['liquido']) !!}</td>
                                <td class="r">{!! $sc($t['sacas']) !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif
