{{-- $r: PagamentoReciboService::montar. O VALE do colaborador e a NOTA PROMISSORIA que o garante
     dividem uma folha A5 em pe, uma em cada metade, com a mesma regua (fonte, tamanhos, caixas). --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: A5 portrait;
            margin: 4mm 6mm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #000;
            margin: 0;
        }
        .form {
            /* metades iguais: o corte cai no meio da folha */
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
    </style>
</head>
<body>
    @php
        $np = $r['promissoria'];
        $assinante = $r['assinante'] ?? [];
    @endphp

    <div class="form">
        <table class="linha">
            <tr>
                <td style="width: 39%"><div class="titulo">VALE</div></td>
                <td style="width: 17%">
                    <div class="campo">
                        <div class="rotulo">Nº</div>
                        <div class="valor">{{ $r['numero'] }}</div>
                    </div>
                </td>
                <td style="width: 20%">
                    <div class="campo">
                        <div class="rotulo">Vencimento</div>
                        <div class="valor">{{ $np['vencimento'] ?? '—' }}</div>
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
                        <div class="rotulo">Nome</div>
                        <div class="valor"><b>{{ $r['de']['nome'] ?? '' }}</b>
                            @if ($r['de']['documento'] ?? null)
                                <span class="miudo">· {{ $r['de']['documento'] }}</span>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        @if ($r['referentes'])
            {{-- a linha em branco do bloco: o que se escreveu no pagamento --}}
            <table class="linha">
                <tr>
                    <td>
                        <div class="campo">
                            <div class="rotulo">Referente a</div>
                            @php $obs = implode(' · ', $r['referentes']); @endphp
                            {{-- observacao comprida (ate 255) com letra menor, para caber na metade --}}
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
                        <div class="valor"><b>{{ $r['extenso'] }}</b></div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="linha">
            <tr>
                <td style="width: 45%">
                    <div class="campo">
                        <div class="rotulo">Pagamento em</div>
                        <div class="valor">{{ implode(' + ', $r['formas']) }}</div>
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
                            {{-- o Dompdf quebra a linha no hifen do "comprometo-me" e do "devolve-lo" --}}
                            {!! preg_replace('/(comprometo-me|devolvê-lo)/u', '<span style="white-space: nowrap">$1</span>', e($r['declaracao'])) !!}
                            @if ($np)
                                Em garantia, emito a nota promissória abaixo.
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="linha">
            <tr>
                <td>
                    <div class="campo">
                        <div class="rotulo">Assinatura</div>
                        <div class="traco">
                            <b>{{ $assinante['nome'] ?? '' }}</b>
                            @if ($assinante['documento'] ?? null)
                                · {{ $assinante['documento'] }}
                            @endif
                        </div>
                    </div>
                </td>
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
                            <div class="valor"><b>{{ $assinante['nome'] ?? '' }}</b>
                                @if ($assinante['documento'] ?? null)
                                    <span class="miudo">· {{ $assinante['documento'] }}</span>
                                @endif
                            </div>
                            @if ($assinante['endereco'] ?? null)
                                <div class="miudo">{{ $assinante['endereco'] }}</div>
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
                                <b>{{ $assinante['nome'] ?? '' }}</b>
                                @if ($assinante['documento'] ?? null)
                                    · {{ $assinante['documento'] }}
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    @endif
</body>
</html>
