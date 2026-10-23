@php
    use Carbon\Carbon;

    $fmtVal = fn($v) => number_format((float) $v, 2, ',', '.');
    $fmtOpc = fn($v) => abs((float) $v) < 0.005 ? '' : number_format((float) $v, 2, ',', '.');
    $fmtData = fn($d) => $d ? Carbon::parse($d)->format('d/m/Y') : '';
    $fmtCod = fn($c) => '#' . str_pad((string) $c, 8, '0', STR_PAD_LEFT);
    $cls = fn($v) => (float) $v < 0 ? 'cr' : '';
    // cada titulo tem no maximo duas linhas: o que nao cabe termina em reticencias
    $corta = fn($t, $n) => mb_strimwidth(trim((string) $t), 0, $n, '…', 'UTF-8');
    $valores = ['principal', 'juros', 'multa', 'desconto', 'total'];
    // as colunas de valor do cabecalho de cada grupo
    $celulas = fn($tot) => collect($valores)
        ->map(fn($v) => '<td class="num ' . $cls($tot[$v]) . '">'
            . (in_array($v, ['principal', 'total']) ? $fmtVal($tot[$v]) : $fmtOpc($tot[$v])) . '</td>')
        ->implode('');
    $nFiliais = collect($grupos)->sum(fn($g) => count($g['filiais']));
    // rotulo => largura (mm, somando 194); o mPDF mede a coluna pelas celulas com largura
    $colunas = [
        'Número' => 28,
        'Pessoa' => 35,
        'Conta contábil' => 38,
        'Venc.' => 14,
        'Principal' => 19,
        'Juros' => 14,
        'Multa' => 12,
        'Desconto' => 14,
        'Total' => 20,
    ];
@endphp
<style>
    body {
        font-family: dejavusanscondensed;
        font-size: 7.5pt;
        color: #263238;
    }

    div.cabecalho {
        border-bottom: 0.4mm solid #263238;
        padding-bottom: 1.5mm;
    }

    table.cabecalho {
        width: 100%;
        border-collapse: collapse;
    }

    table.cabecalho td {
        padding: 0;
        vertical-align: bottom;
    }

    .titulo {
        font-size: 14pt;
        font-weight: bold;
    }

    .periodo {
        text-align: right;
        font-size: 9pt;
        font-weight: bold;
    }

    .filtro {
        font-size: 7.5pt;
        font-weight: normal;
        color: #78909c;
    }

    .rodape {
        text-align: right;
        font-size: 6.5pt;
        color: #90a4ae;
        border-top: 0.2mm solid #cfd8dc;
        padding-top: 1mm;
    }

    table.rel {
        width: 194mm;
        border-collapse: collapse;
    }

    table.rel td {
        padding: 0.5mm 1mm;
        vertical-align: top;
    }

    td.num {
        text-align: right;
        white-space: nowrap;
    }

    td.cr,
    span.cr {
        color: #c25e00;
    }

    span.sec {
        font-size: 6.5pt;
        color: #78909c;
    }

    span.tag {
        font-size: 6.5pt;
        font-weight: bold;
        color: #78909c;
        letter-spacing: 0.3mm;
    }

    tr.espaco td {
        height: 3mm;
        padding: 0;
    }

    tr.espaco-grupo td {
        height: 7mm;
        padding: 0;
    }

    /* total geral: faixa escura no topo */
    tr.geral td {
        background: #263238;
        color: #fff;
        font-weight: bold;
        padding-top: 2mm;
        padding-bottom: 2mm;
        vertical-align: middle;
    }

    tr.geral td.rotulo {
        font-size: 10pt;
    }

    tr.geral td.cr {
        color: #ffb74d;
    }

    tr.gerencial td {
        font-weight: bold;
        border-bottom: 0.6mm solid #263238;
        padding-bottom: 1.2mm;
        vertical-align: bottom;
    }

    tr.gerencial td.rotulo {
        font-size: 12pt;
        letter-spacing: 0.4mm;
    }

    tr.filial td {
        font-weight: bold;
        border-bottom: 0.4mm solid #263238;
        padding-bottom: 1mm;
        vertical-align: bottom;
    }

    tr.filial td.rotulo {
        font-size: 12pt;
    }

    tr.portador td {
        background: #eef3f8;
        color: #1a4b7a;
        font-weight: bold;
        padding-top: 1.5mm;
        padding-bottom: 1.5mm;
        vertical-align: middle;
    }

    tr.portador td.rotulo {
        font-size: 9pt;
        border-left: 1mm solid #1a4b7a;
    }

    tr.portador span.sec {
        font-weight: normal;
    }

    tr.pag td {
        font-size: 8pt;
        padding-top: 1mm;
        padding-bottom: 0.8mm;
        border-bottom: 0.2mm solid #b0bec5;
        vertical-align: bottom;
    }

    tr.pag td.data,
    tr.pag td.pessoa,
    tr.pag td.num {
        font-weight: bold;
    }

    tr.pag span.sec {
        font-weight: normal;
    }

    tr.pag td.info {
        text-align: right;
        font-size: 6.5pt;
        color: #78909c;
    }

    tr.colunas td {
        font-size: 6pt;
        color: #90a4ae;
        text-transform: uppercase;
        padding-top: 0.8mm;
        padding-bottom: 0.2mm;
    }
</style>

<htmlpageheader name="page-header">
    <div class="cabecalho">
    <table class="cabecalho">
        <tr>
            <td class="titulo">Títulos pagos</td>
            <td class="periodo">
                {{ $periodo }}
                @if ($filtro)
                    <br><span class="filtro">{{ $filtro }}</span>
                @endif
            </td>
        </tr>
    </table>
    </div>
</htmlpageheader>

<htmlpagefooter name="page-footer">
    <div class="rodape">MGspa &middot; emitido em {DATE j/m/Y H:i} &middot; página {PAGENO} de {nbpg}</div>
</htmlpagefooter>

<sethtmlpageheader name="page-header" value="on" show-this-page="1">
    <sethtmlpagefooter name="page-footer" value="on" show-this-page="1">

        <table class="rel">
            <tbody>
                @if (empty($grupos))
                    <tr>
                        <td colspan="9">Nenhum título pago no período.</td>
                    </tr>
                @endif
                {{-- todo grupo mostra o total no proprio cabecalho, no comeco --}}
                @if ($nFiliais > 1)
                    <tr class="geral">
                        <td colspan="4" class="rotulo">Total geral</td>
                        {!! $celulas($totais) !!}
                    </tr>
                @endif
                @foreach ($grupos as $g)
                    @if (count($grupos) > 1)
                        <tr class="espaco-grupo">
                            <td colspan="9"></td>
                        </tr>
                        <tr class="gerencial">
                            <td colspan="4" class="rotulo">{{ mb_strtoupper($g['rotulo']) }}</td>
                            {!! $celulas($g['totais']) !!}
                        </tr>
                    @endif
                    @foreach ($g['filiais'] as $f)
                        <tr class="espaco-grupo">
                            <td colspan="9"></td>
                        </tr>
                        <tr class="filial">
                            <td colspan="4" class="rotulo">
                                {{ $f['rotulo'] }}&nbsp;&nbsp;<span class="tag">{{ mb_strtoupper($g['rotulo']) }}</span>
                            </td>
                            {!! $celulas($f['totais']) !!}
                        </tr>
                        @foreach ($f['portadores'] as $p)
                            <tr class="espaco">
                                <td colspan="9"></td>
                            </tr>
                            <tr class="portador">
                                <td colspan="4" class="rotulo">
                                    {{ $p['rotulo'] }}
                                    <span class="sec">&nbsp;&middot;&nbsp;{{ count($p['pagamentos']) }}
                                        {{ count($p['pagamentos']) == 1 ? 'pagamento' : 'pagamentos' }}</span>
                                </td>
                                {!! $celulas($p['totais']) !!}
                            </tr>
                            @foreach ($p['pagamentos'] as $pag)
                                @php
                                    // o valor com o sinal das linhas: saiu positivo, entrou negativo
                                    $valorPag = $pag['operacao'] == 'R' ? -$pag['valor'] : $pag['valor'];
                                    // pessoa e observacao dividem uma linha (a observacao e' menor)
                                    $obs = trim((string) $pag['observacoes']);
                                    $pessoaPag = $corta($pag['pessoa'], $obs === '' ? 56 : 40);
                                    if ($obs !== '') {
                                        $obs = $corta($obs, max(6, (int) ((425 - 7.8 * mb_strlen($pessoaPag)) / 6.2) - 4));
                                    }
                                    $info = $fmtCod($pag['codpagamento']);
                                    if ($pag['parcial']) {
                                        $info = count($pag['linhas']) . ' de ' . $pag['linhaspagamento']
                                            . ' títulos no filtro · ' . $info;
                                    }
                                @endphp
                                <tr class="espaco">
                                    <td colspan="9"></td>
                                </tr>
                                <tr class="pag">
                                    <td class="data">{{ $fmtData($pag['transacao']) }}</td>
                                    <td colspan="3" class="pessoa">
                                        {{ $pessoaPag }}
                                        @if ($obs !== '')
                                            <span class="sec">&nbsp;&middot;&nbsp;{{ $obs }}</span>
                                        @endif
                                    </td>
                                    <td colspan="4" class="info">{{ $info }}</td>
                                    <td class="num {{ $cls($valorPag) }}">{{ $fmtVal($valorPag) }}</td>
                                </tr>
                                {{-- o cabecalho da tabela em cima dos titulos de cada pagamento --}}
                                <tr class="colunas">
                                    @foreach (array_keys($colunas) as $i => $c)
                                        <td class="{{ $i >= 4 ? 'num' : '' }}" style="width:{{ $colunas[$c] }}mm">{{ $c }}</td>
                                    @endforeach
                                </tr>
                                @foreach ($pag['linhas'] as $l)
                                    <tr>
                                        <td>
                                            {{ $corta($l->numero, 17) }}
                                            @if ($l->fatura)
                                                <br><span class="sec">{{ $corta($l->fatura, 22) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $corta($l->pessoa, 44) }}</td>
                                        <td>
                                            {{ $corta($l->contacontabil, 26) }}
                                            <br><span class="sec">{{ $corta($l->tipotitulo, 31) }}</span>
                                        </td>
                                        <td>{{ $fmtData($l->vencimento) }}</td>
                                        <td class="num {{ $cls($l->principal) }}">{{ $fmtVal($l->principal) }}</td>
                                        <td class="num">{{ $fmtOpc($l->juros) }}</td>
                                        <td class="num">{{ $fmtOpc($l->multa) }}</td>
                                        <td class="num">{{ $fmtOpc($l->desconto) }}</td>
                                        <td class="num {{ $cls($l->total) }}">{{ $fmtVal($l->total) }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endforeach
                    @endforeach
                @endforeach
            </tbody>
        </table>
