{{-- $recibos: lista de PagamentoReciboService::montar, um recibo por pagina (o lote dos acertos do
     RH; no mais, um so'). O documento do pagamento que alguem assina, no jeito do bloco de papel: o
     VALE do colaborador com a NOTA PROMISSORIA que o garante, numa folha A5 em pe (uma em cada
     metade, com a mesma regua: fonte, tamanhos, caixas); os demais (ADIANTAMENTO, RECIBO, ENCONTRO DE
     CONTAS) sem promissoria, na A5 deitada (mesmos campos, letra e caixas maiores para encher a
     folha). As tabelas dos titulos e os assinantes vem prontos do service. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: {{ $recibos[0]['promissoria'] ? 'A5 portrait' : 'A5 landscape' }};
            margin: 4mm 6mm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #000;
            margin: 0;
        }
        /* metades iguais: o corte cai no meio da folha */
        .meia .form {
            height: 97mm;
        }
        .corte {
            border-top: 0.8pt dashed #777;
            margin: 2.6mm 0;
        }
        table.linha {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.linha td {
            padding: 0.9mm 0 0 0;
            vertical-align: top;
        }
        table.linha td + td {
            padding-left: 1.5mm;
        }
        .campo {
            border: 0.7pt solid #555;
            border-radius: 1.4mm;
            padding: 0.4mm 1.6mm 0.8mm 1.6mm;
        }
        .fundo {
            background: #e4e4e4;
        }
        .rotulo {
            font-size: 6pt;
            color: #444;
        }
        .valor {
            font-size: 9.5pt;
        }
        .grande {
            font-size: 11.5pt;
            font-weight: bold;
        }
        .miudo {
            font-size: 6.5pt;
            color: #333;
        }
        .titulo {
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 0.3pt;
            padding-top: 2.6mm;
            white-space: nowrap;
        }
        .texto {
            font-size: 8pt;
            line-height: 1.35;
        }
        /* na promissoria, espaco para escrever nos brancos */
        .texto.np {
            line-height: 2;
        }
        .branco {
            display: inline-block;
            border-bottom: 0.7pt solid #000;
        }
        .traco {
            border-top: 0.7pt solid #000;
            margin-top: 6mm;
            text-align: center;
            font-size: 7pt;
            padding-top: 0.4mm;
        }
        /* A5 deitada, sem promissoria: os mesmos campos maiores */
        .inteira table.linha td {
            padding-top: 1.2mm;
        }
        .inteira table.linha td + td {
            padding-left: 2mm;
        }
        .inteira .campo {
            padding: 0.6mm 2mm 1.4mm 2mm;
        }
        .inteira .rotulo {
            font-size: 7pt;
        }
        .inteira .valor {
            font-size: 12pt;
        }
        .inteira .miudo {
            font-size: 8pt;
        }
        .inteira .grande {
            font-size: 15pt;
        }
        .inteira .titulo {
            font-size: 22pt;
            padding-top: 2mm;
        }
        .inteira .texto {
            font-size: 10pt;
            line-height: 1.45;
        }
        .inteira .traco {
            margin-top: 8mm;
            font-size: 8.5pt;
        }
        table.titulos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.4mm;
            font-size: 9.5pt;
        }
        table.titulos th {
            font-size: 7pt;
            font-weight: normal;
            color: #444;
            text-align: left;
            border-bottom: 0.6pt solid #555;
            padding: 0.3mm 1mm;
        }
        table.titulos td {
            border-bottom: 0.3pt solid #bbb;
            padding: 0.6mm 1mm;
        }
        table.titulos tr.total td {
            border-bottom: none;
            border-top: 0.8pt solid #000;
            font-weight: bold;
        }
        table.titulos .r {
            text-align: right;
        }
        .inteira table.titulos {
            font-size: 10pt;
        }
        .inteira table.titulos td {
            padding: 0.4mm 1mm;
        }
        /* titulo comprido (ENCONTRO DE CONTAS) */
        .inteira .titulo.longo {
            font-size: 17pt;
            padding-top: 3mm;
        }
        /* as rubricas da folha embaixo do titulo do acerto de RH */
        table.titulos tr.rubrica td {
            font-size: 7.5pt;
            color: #333;
            border-bottom: none;
            padding-top: 0;
            padding-bottom: 0;
        }
        /* o encontro de contas tem duas tabelas e duas assinaturas: um pouco mais justo para caber */
        .inteira .compacta table.linha td {
            padding-top: 0.8mm;
        }
        .inteira .compacta .campo {
            padding: 0.4mm 1.8mm 0.9mm 1.8mm;
        }
        .inteira .compacta .valor {
            font-size: 10.5pt;
        }
        .inteira .compacta table.titulos {
            font-size: 9pt;
        }
        .inteira .compacta table.titulos td {
            padding: 0.2mm 1mm;
        }
        .inteira .compacta .texto {
            font-size: 9pt;
            line-height: 1.3;
        }
        .inteira .compacta .traco {
            margin-top: 7mm;
        }
        .saldo {
            text-align: right;
            font-size: 9pt;
            font-weight: bold;
            margin-top: 0.8mm;
        }
    </style>
</head>
<body class="{{ $recibos[0]['promissoria'] ? 'meia' : 'inteira' }}">
@foreach ($recibos as $r)
    @php
        $np = $r['promissoria'];
        $emitente = $r['assinantes'][0] ?? [];
        $caixas = 17 + ($r['vencimento'] ? 20 : 0) + count($r['valores']) * 22;
        $cols = ['juros' => 'Juros', 'multa' => 'Multa', 'desconto' => 'Desconto'];
    @endphp

    <div class="form{{ $r['modelo'] == 'encontro' ? ' compacta' : '' }}" @if (!$loop->first) style="page-break-before: always" @endif>
        <table class="linha">
            <tr>
                <td style="width: {{ 100 - $caixas }}%">
                    <div class="titulo{{ mb_strlen($r['titulo']) > 14 ? ' longo' : '' }}">{{ $r['titulo'] }}</div>
                </td>
                <td style="width: 17%">
                    <div class="campo">
                        <div class="rotulo">Nº</div>
                        <div class="valor">{{ $r['numero'] }}</div>
                    </div>
                </td>
                {{-- sem prazo (so' o vale e o adiantamento tem), sem a caixa Vencimento --}}
                @if ($r['vencimento'])
                    <td style="width: 20%">
                        <div class="campo">
                            <div class="rotulo">Vencimento</div>
                            <div class="valor">{{ $r['vencimento'] }}</div>
                        </div>
                    </td>
                @endif
                {{-- o valor; no encontro de contas, o compensado e a diferenca paga --}}
                @foreach ($r['valores'] as $v)
                    <td style="width: 22%">
                        <div class="campo">
                            <div class="rotulo">{{ $v['rotulo'] }}</div>
                            <div class="grande">R$ {{ formataNumero($v['valor']) }}</div>
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>

        @if ($r['de'])
            <table class="linha">
                <tr>
                    <td>
                        <div class="campo">
                            <div class="rotulo">{{ $r['rotuloDe'] }}</div>
                            <div class="valor"><b>{{ $r['de']['nome'] }}</b>
                                @if ($r['de']['codigo'])
                                    <span class="miudo">{{ $r['de']['codigo'] }}</span>
                                @endif
                                @if ($r['de']['documento'])
                                    <span class="miudo">· {{ $r['de']['documento'] }}</span>
                                @endif
                            </div>
                            @if (!$r['vale'] && $r['de']['endereco'])
                                <div class="miudo">{{ $r['de']['endereco'] }}</div>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        @endif

        @if ($r['referentes'])
            {{-- a linha em branco do bloco: o que se escreveu no pagamento --}}
            <table class="linha">
                <tr>
                    <td>
                        <div class="campo">
                            <div class="rotulo">Referente a</div>
                            @php $obs = implode(' · ', $r['referentes']); @endphp
                            {{-- comprido (ate 255) com letra menor, para caber na metade do vale --}}
                            <div class="texto" @if (mb_strlen($obs) > 160) style="font-size: 7pt; line-height: 1.2" @endif>{{ $obs }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        @endif

        <table class="linha">
            <tr>
                <td>
                    <div class="campo fundo">
                        <div class="rotulo">Valor por extenso</div>
                        @foreach ($r['extensos'] as $extenso)
                            <div class="valor"><b>{{ $extenso }}</b></div>
                        @endforeach
                    </div>
                </td>
            </tr>
        </table>

        {{-- os titulos, sempre com principal, juros, multa, desconto e total; fora da table.linha:
             tabela dentro de celula o Dompdf nao quebra entre paginas --}}
        @foreach ($r['tabelas'] as $tab)
            @php $l = collect($tab['linhas']); @endphp
            <div class="campo" style="margin-top: {{ $r['modelo'] == 'encontro' ? 0.8 : 1.6 }}mm">
                <div class="rotulo">{{ $tab['rotulo'] }}</div>
                <table class="titulos">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Vencimento</th>
                            <th class="r">Principal</th>
                            @foreach ($cols as $c)
                                <th class="r">{{ $c }}</th>
                            @endforeach
                            <th class="r">Total</th>
                        </tr>
                    </thead>
                    @foreach ($l as $t)
                        <tr>
                            <td>{{ $t['descricao'] }}</td>
                            <td>{{ $t['vencimento'] }}</td>
                            <td class="r">{{ formataNumero($t['principal']) }}</td>
                            @foreach ($cols as $k => $c)
                                <td class="r">{{ formataNumero($t[$k]) }}</td>
                            @endforeach
                            <td class="r">{{ formataNumero($t['valor']) }}</td>
                        </tr>
                        @foreach ($t['rubricas'] as $rubrica)
                            <tr class="rubrica">
                                <td colspan="6">&nbsp;&nbsp;{{ $rubrica->descricao }}</td>
                                <td class="r">{{ formataNumero($rubrica->valorcalculado) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    @if ($tab['livre'] != 0)
                        <tr>
                            <td colspan="6">Não amarrado</td>
                            <td class="r">{{ formataNumero($tab['livre']) }}</td>
                        </tr>
                    @endif
                    @if ($tab['total'] !== null)
                        <tr class="total">
                            <td colspan="2">Total</td>
                            <td class="r">{{ formataNumero($l->sum('principal')) }}</td>
                            @foreach ($cols as $k => $c)
                                <td class="r">{{ formataNumero($l->sum($k)) }}</td>
                            @endforeach
                            <td class="r">{{ formataNumero($tab['total']) }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        @endforeach
        @if ($r['saldo'])
            <div class="saldo">{{ $r['saldo'] }}</div>
        @endif

        <table class="linha">
            <tr>
                <td style="width: 45%">
                    <div class="campo">
                        <div class="rotulo">Pagamento em</div>
                        <div class="valor">{{ implode(' + ', $r['formas']) }}</div>
                        @if ($r['autorizacoes'])
                            <div class="miudo">{{ implode(' · ', $r['autorizacoes']) }}</div>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="campo">
                        <div class="rotulo">Local / Data</div>
                        <div class="valor">{{ $r['localData'] }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="linha">
            <tr>
                <td>
                    <div class="campo">
                        <div class="rotulo">Declaração</div>
                        <div class="texto">
                            {{-- o Dompdf quebra a linha no hifen do "comprometo-me", do "devolve-lo" e do CPF/CNPJ --}}
                            {!! preg_replace('/(comprometo-me|comprometemo-nos|devolvê-lo|\d[\d.\/]*-\d{2})/u', '<span style="white-space: nowrap">$1</span>', e($r['declaracao'])) !!}
                            @if ($np)
                                Em garantia, emito a nota promissória abaixo.
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- quem recebeu assina; no encontro de contas, a loja e a contraparte, lado a lado --}}
        <table class="linha">
            <tr>
                @foreach ($r['assinantes'] as $a)
                    <td>
                        <div class="campo">
                            <div class="rotulo">Assinatura</div>
                            <div class="traco">
                                <b>{{ $a['nome'] ?? '' }}</b>
                                @if ($a['documento'] ?? null)
                                    · {{ $a['documento'] }}
                                @endif
                            </div>
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>

    @if ($np)
        {{-- a garantia do vale: o beneficiario fica em branco, preenchido na cobranca com o CNPJ
             que for cobrar; nenhuma clausula alem das da lei --}}
        <div class="corte"></div>

        <div class="form">
            <table class="linha">
                <tr>
                    <td style="width: 39%"><div class="titulo">NOTA PROMISSÓRIA</div></td>
                    <td style="width: 17%">
                        <div class="campo">
                            <div class="rotulo">Nº</div>
                            <div class="valor">{{ $r['numero'] }}</div>
                        </div>
                    </td>
                    <td style="width: 20%">
                        <div class="campo">
                            <div class="rotulo">Vencimento</div>
                            <div class="valor">{{ $np['vencimento'] }}</div>
                        </div>
                    </td>
                    <td style="width: 24%">
                        <div class="campo">
                            <div class="rotulo">Valor</div>
                            <div class="grande">R$ {{ formataNumero($r['total']) }}</div>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="linha">
                <tr>
                    <td>
                        <div class="campo">
                            <div class="rotulo">Emitente</div>
                            <div class="valor"><b>{{ $emitente['nome'] ?? '' }}</b>
                                @if ($emitente['documento'] ?? null)
                                    <span class="miudo">· {{ $emitente['documento'] }}</span>
                                @endif
                            </div>
                            @if ($emitente['endereco'] ?? null)
                                <div class="miudo">{{ $emitente['endereco'] }}</div>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>

            <table class="linha">
                <tr>
                    <td>
                        <div class="campo fundo">
                            <div class="rotulo">Promessa de pagamento</div>
                            {{-- o branco do beneficiario vai ate' o fim da linha (largura do texto: 132mm =
                                 379pt; letra de 8pt); o CPF/CNPJ embaixo --}}
                            @php
                                $branco = \Mg\Pagamento\PagamentoReciboService::brancoDaLinha(
                                    $np['data'] . ', pagarei por esta única via de', 'NOTA PROMISSÓRIA', 379, 8);
                            @endphp
                            <div class="texto np">
                                {{ $np['data'] }}, pagarei por esta única via de <b>NOTA PROMISSÓRIA</b> a
                                @if ($branco)
                                    <span class="branco" style="width: {{ $branco }}pt">&nbsp;</span>
                                @endif
                            </div>
                            @unless ($branco)
                                <div class="texto np"><span class="branco" style="width: 99%">&nbsp;</span></div>
                            @endunless
                            <div class="texto np">
                                CPF/CNPJ <span class="branco" style="width: 45mm">&nbsp;</span>, ou à sua
                                ordem, a quantia de <b>{{ $np['valor'] }}</b> em moeda corrente deste país.
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="linha">
                <tr>
                    <td style="width: 45%">
                        <div class="campo">
                            <div class="rotulo">Pagável em</div>
                            <div class="valor">{{ $np['pagavel'] }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="campo">
                            <div class="rotulo">Local / Data de emissão</div>
                            <div class="valor">{{ $r['localData'] }}</div>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="linha">
                <tr>
                    <td>
                        <div class="campo">
                            <div class="rotulo">Assinatura do emitente</div>
                            <div class="traco">
                                <b>{{ $emitente['nome'] ?? '' }}</b>
                                @if ($emitente['documento'] ?? null)
                                    · {{ $emitente['documento'] }}
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    @endif
@endforeach
</body>
</html>
