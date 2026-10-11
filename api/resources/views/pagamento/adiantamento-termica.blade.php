{{-- $r: PagamentoReciboService::montar. O VALE (com a NOTA PROMISSORIA) e o ADIANTAMENTO a
     fornecedor na bobina de 80mm: os mesmos campos e a mesma ordem do A5 (pagamento/adiantamento),
     um embaixo do outro, com a linha de corte antes da promissoria. --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0.4cm 0.4cm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td {
            vertical-align: top;
            padding: 0;
        }

        td + td {
            padding-left: 3px;
        }

        .titulo {
            font-size: 15pt;
            font-weight: bold;
            letter-spacing: 0.3pt;
        }

        .campo {
            border: 0.8pt solid #000;
            border-radius: 3px;
            padding: 1px 4px 3px 4px;
            margin-top: 4px;
        }

        .fundo {
            background: #ddd;
        }

        .rotulo {
            font-size: 6.5pt;
            color: #333;
        }

        .valor {
            font-size: 9.5pt;
        }

        .grande {
            font-size: 10.5pt;
            font-weight: bold;
        }

        .miudo {
            font-size: 7pt;
        }

        .texto {
            font-size: 8.5pt;
            line-height: 1.35;
        }

        /* na promissoria, espaco para escrever nos brancos */
        .texto.np {
            line-height: 2;
        }

        .branco {
            display: inline-block;
            border-bottom: 0.8pt solid #000;
        }

        .traco {
            margin-top: 1cm;
            border-top: 1px solid #000;
            text-align: center;
            padding-top: 1px;
        }

        .corte {
            border-top: 1px dashed #000;
            margin: 10px 0 8px 0;
        }
    </style>
</head>

<body>
    @php
        $np = $r['promissoria'];
        $assinante = $r['assinante'] ?? [];
    @endphp

    <div class="titulo">{{ $r['titulo'] }}</div>
    <table>
        <tr>
            <td style="width: 32%">
                <div class="campo">
                    <div class="rotulo">Nº</div>
                    <div class="valor">{{ $r['numero'] }}</div>
                </div>
            </td>
            <td style="width: 30%">
                <div class="campo">
                    <div class="rotulo">Vencimento</div>
                    <div class="valor">{{ $r['vencimento'] ?? '—' }}</div>
                </div>
            </td>
            <td style="width: 38%">
                <div class="campo">
                    <div class="rotulo">Valor</div>
                    <div class="grande">R$ {{ formataNumero($r['total']) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="campo">
        <div class="rotulo">{{ $r['rotuloDe'] }}</div>
        <div class="valor"><b>{{ $r['de']['nome'] ?? '' }}</b></div>
        @if ($r['de']['documento'] ?? null)
            <div class="miudo">{{ $r['de']['documento'] }}</div>
        @endif
        @if (!$r['vale'] && ($r['de']['endereco'] ?? null))
            <div class="miudo">{{ $r['de']['endereco'] }}</div>
        @endif
    </div>

    @if ($r['referentes'])
        <div class="campo">
            <div class="rotulo">Referente a</div>
            <div class="texto">{{ implode(' · ', $r['referentes']) }}</div>
        </div>
    @endif

    <div class="campo fundo">
        <div class="rotulo">Valor por extenso</div>
        <div class="valor"><b>{{ $r['extenso'] }}</b></div>
    </div>

    <div class="campo">
        <div class="rotulo">Pagamento em</div>
        <div class="valor">{{ implode(' + ', $r['formas']) }}</div>
    </div>

    <div class="campo">
        <div class="rotulo">Local / Data</div>
        <div class="valor">{{ $r['localData'] }}</div>
    </div>

    <div class="campo">
        <div class="rotulo">Declaração</div>
        <div class="texto">
            {{-- o Dompdf quebra a linha no hifen do "comprometo-me" e do "devolve-lo" --}}
            {!! preg_replace('/(comprometo-me|comprometemo-nos|devolvê-lo)/u', '<span style="white-space: nowrap">$1</span>', e($r['declaracao'])) !!}
            @if ($np)
                Em garantia, emito a nota promissória abaixo.
            @endif
        </div>
    </div>

    <div class="campo">
        <div class="rotulo">Assinatura</div>
        <div class="traco">
            {{ $assinante['nome'] ?? '' }}
            @if ($assinante['documento'] ?? null)
                <br><span class="miudo">{{ $assinante['documento'] }}</span>
            @endif
        </div>
    </div>

    @if ($np)
        {{-- a garantia do vale: o beneficiario fica em branco, preenchido na cobranca com o CNPJ
             que for cobrar; nenhuma clausula alem das da lei --}}
        <div class="corte"></div>

        <div class="titulo">NOTA PROMISSÓRIA</div>
        <table>
            <tr>
                <td style="width: 32%">
                    <div class="campo">
                        <div class="rotulo">Nº</div>
                        <div class="valor">{{ $r['numero'] }}</div>
                    </div>
                </td>
                <td style="width: 30%">
                    <div class="campo">
                        <div class="rotulo">Vencimento</div>
                        <div class="valor">{{ $np['vencimento'] }}</div>
                    </div>
                </td>
                <td style="width: 38%">
                    <div class="campo">
                        <div class="rotulo">Valor</div>
                        <div class="grande">R$ {{ formataNumero($r['total']) }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="campo">
            <div class="rotulo">Emitente</div>
            <div class="valor"><b>{{ $assinante['nome'] ?? '' }}</b></div>
            @if ($assinante['documento'] ?? null)
                <div class="miudo">{{ $assinante['documento'] }}</div>
            @endif
            @if ($assinante['endereco'] ?? null)
                <div class="miudo">{{ $assinante['endereco'] }}</div>
            @endif
        </div>

        <div class="campo fundo">
            <div class="rotulo">Promessa de pagamento</div>
            {{-- o branco do beneficiario vai ate' o fim da linha (largura do texto: 200pt; letra
                 de 8,5pt); o CPF/CNPJ embaixo --}}
            @php
                $branco = \Mg\Pagamento\PagamentoReciboService::brancoDaLinha(
                    $np['data'] . ', pagarei por esta única via de', 'NOTA PROMISSÓRIA', 200, 8.5, 50);
            @endphp
            <div class="texto np">
                {{ $np['data'] }}, pagarei por esta única via de <b>NOTA PROMISSÓRIA</b> a
                @if ($branco)
                    <span class="branco" style="width: {{ $branco }}pt">&nbsp;</span>
                @endif
            </div>
            @unless ($branco)
                <div class="texto np"><span class="branco" style="width: 96%">&nbsp;</span></div>
            @endunless
            <div class="texto np">
                CPF/CNPJ <span class="branco" style="width: 62%">&nbsp;</span>, ou à sua ordem,
                a quantia de <b>{{ $np['valor'] }}</b> em moeda corrente deste país.
            </div>
        </div>

        <div class="campo">
            <div class="rotulo">Pagável em</div>
            <div class="valor">{{ $np['pagavel'] }}</div>
        </div>

        <div class="campo">
            <div class="rotulo">Local / Data de emissão</div>
            <div class="valor">{{ $r['localData'] }}</div>
        </div>

        <div class="campo">
            <div class="rotulo">Assinatura do emitente</div>
            <div class="traco">
                {{ $assinante['nome'] ?? '' }}
                @if ($assinante['documento'] ?? null)
                    <br><span class="miudo">{{ $assinante['documento'] }}</span>
                @endif
            </div>
        </div>
    @endif
</body>

</html>
