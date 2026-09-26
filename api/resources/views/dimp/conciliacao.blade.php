@php
    $num = fn($v) => number_format((float) $v, 2, ',', '.');

    $TPAG = [
        1 => 'Dinheiro', 2 => 'Cheque', 3 => 'Cartão de Crédito', 4 => 'Cartão de Débito',
        5 => 'Crédito Loja (crediário)', 10 => 'Vale Alimentação', 11 => 'Vale Refeição',
        12 => 'Vale Presente (resgate de vale compras)', 13 => 'Vale Combustível',
        15 => 'Boleto Bancário', 16 => 'Depósito Bancário', 17 => 'PIX',
        18 => 'Transferência / Carteira Digital', 19 => 'Fidelidade / Cashback',
        90 => 'Sem pagamento', 99 => 'Outros',
    ];
    $rotulo = fn($t) => $TPAG[(int) $t] ?? ('tPag ' . $t);
    $eletronico = fn($t) => in_array((int) $t, [3, 4, 17], true);

    $mesNome = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'][$mes];

    $naoExplicado = round($divergencia - $explicadoVale, 2);
@endphp
<style>
    body { font-family: helvetica; font-size: 9pt; }
    .doc-title { font-size: 14pt; font-weight: bold; }
    .doc-sub { font-size: 8pt; color: #555; }
    .regua { border-bottom: 2px solid #000; height: 1px; margin-bottom: 4mm; }
    h2 { font-size: 10pt; margin: 6mm 0 1.5mm 0; }
    table.q { width: 186mm; border-collapse: collapse; }
    table.q td { padding: 1mm 2mm; border-bottom: 0.2mm solid #ddd; vertical-align: top; }
    table.q td.v { text-align: right; white-space: nowrap; }
    table.q td.c { text-align: right; color: #666; font-size: 8pt; white-space: nowrap; }
    tr.total td { border-top: 0.5mm solid #000; border-bottom: none; font-weight: bold; }
    tr.destaque td { background-color: #eee; font-weight: bold; }
    .nota { font-size: 8pt; color: #444; margin-top: 1.5mm; }
    .ok { color: #1b5e20; font-weight: bold; }
    .erro { color: #b71c1c; font-weight: bold; }
    .footer { text-align: right; font-size: 6pt; color: #666; border-top: 1px solid #000; padding-top: 2px; }
</style>

<htmlpagefooter name="rodape">
    <div class="footer">
        Conciliação DIMP · {{ $mesNome }}/{{ $ano }} · emitido em {{ now()->format('d/m/Y H:i') }}
        · página {PAGENO} de {nbpg}
    </div>
</htmlpagefooter>
<sethtmlpagefooter name="rodape" value="on" />

<div class="doc-title">CONCILIAÇÃO DIMP</div>
<div class="doc-sub">
    {{ $mesNome }} de {{ $ano }} ({{ $inicio->format('d/m/Y') }} a {{ $fim->format('d/m/Y') }})
    @if($codfilial) · Filial {{ $codfilial }} @else · Todas as filiais @endif
    · somente operações de saída
</div>
<div class="regua"></div>

<h2>1 · COMO O CLIENTE PAGOU OS NEGÓCIOS DO MÊS</h2>
<table class="q">
    @foreach($pagamentos as $p)
    <tr>
        <td>{{ $rotulo($p->tipo) }}</td>
        <td class="c">{{ $p->quantidade }}@if($p->troco > 0) · troco {{ $num($p->troco) }}@endif</td>
        <td class="v">{{ $num($p->liquido) }}</td>
    </tr>
    @endforeach
    <tr class="destaque">
        <td>Cartão + PIX — é isto que a adquirente informa na DIMP</td>
        <td class="c"></td>
        <td class="v">{{ $num($eletronicoNegocio) }}</td>
    </tr>
    <tr class="total">
        <td>{{ $negocios['quantidade'] }} negócios fechados no mês</td>
        <td class="c"></td>
        <td class="v">{{ $num($negocios['valortotal']) }}</td>
    </tr>
</table>

<h2>2 · O QUE AS NOTAS EMITIDAS NO MÊS DECLARARAM</h2>
<table class="q">
    @foreach($notasDoMes as $p)
    <tr>
        <td>{{ $rotulo($p->tipo) }}</td>
        <td class="c">{{ $p->quantidade }}@if($p->troco > 0) · troco {{ $num($p->troco) }}@endif</td>
        <td class="v">{{ $num($p->liquido) }}</td>
    </tr>
    @endforeach
    <tr class="destaque">
        <td>Cartão + PIX declarado nas notas</td>
        <td class="c"></td>
        <td class="v">{{ $num($eletronicoNota) }}</td>
    </tr>
</table>

<h2>3 · A DIVERGÊNCIA QUE O FISCO VAI VER</h2>
<table class="q">
    <tr>
        <td>Cartão + PIX recebido nos negócios do mês</td>
        <td class="v">{{ $num($eletronicoNegocio) }}</td>
    </tr>
    <tr>
        <td>(−) Cartão + PIX declarado nas notas do mês</td>
        <td class="v">{{ $num($eletronicoNota) }}</td>
    </tr>
    <tr class="total">
        <td>Diferença</td>
        <td class="v">{{ $num($divergencia) }}</td>
    </tr>
    <tr>
        <td>Explicada por <b>vale compras vendido</b><br>
            <span class="nota">Recebimento antecipado, sem fato gerador de ICMS. O cartão passou
            com o <i>cAut</i> de verdade e o documento fiscal sai na retirada do material, em
            outro mês, com tPag 12 — que aparece na seção 2.</span></td>
        <td class="v">{{ $num($explicadoVale) }}</td>
    </tr>
    <tr class="total">
        <td>Resto — venda a prazo, crediário e nota emitida em mês diferente do negócio</td>
        <td class="v">{{ $num($naoExplicado) }}</td>
    </tr>
</table>

<h2>4 · VALE COMPRAS VENDIDO NO MÊS</h2>
<table class="q">
    <tr>
        <td>Dentro do negócio<br>
            <span class="nota">"Cobrado" é o que o cliente pagou pelo vale, já com o rateio de
            desconto e a fatia de juros. "Face" é o crédito que a escola recebeu — a face não se
            mexe com desconto.</span></td>
        <td class="c">{{ $valeNegocio['quantidade'] }} vales</td>
        <td class="v">
            cobrado {{ $num($valeNegocio['cobrado']) }}<br>
            face {{ $num($valeNegocio['face']) }}<br>
            cartão/PIX {{ $num($valeNegocio['eletronico']) }}
        </td>
    </tr>
    <tr>
        <td>Negócios do mês sem nota fiscal nenhuma<br>
            <span class="nota">Venda 100% vale, natureza que não emite, nota ainda em digitação
            ou nota cancelada.</span></td>
        <td class="c">{{ $semNota['quantidade'] }} negócios</td>
        <td class="v">{{ $num($semNota['valortotal']) }}</td>
    </tr>
</table>

<h2>5 · CONFERÊNCIAS — TODAS TÊM QUE DAR ZERO</h2>
<table class="q">
    @foreach($conferencias as $c)
    <tr>
        <td>{{ $c['titulo'] }}<br><span class="nota">{{ $c['nota'] }}</span></td>
        <td class="c">{{ $c['quantidade'] }}</td>
        <td class="v {{ $c['quantidade'] == 0 ? 'ok' : 'erro' }}">{{ $num($c['valor']) }}</td>
    </tr>
    @endforeach
</table>
<div class="nota">
    @if($fecha)
        <span class="ok">O mês fecha.</span> As três conferências deram zero.
    @else
        <span class="erro">O mês NÃO fecha.</span> Nenhuma dessas linhas é arredondamento —
        cada uma aponta um caso concreto, com valor. Resolva antes de entregar a conciliação.
    @endif
</div>
