{{-- $r: PagamentoReciboService::montar. O documento do pagamento que alguem assina na bobina de 80mm
     (o VALE com a NOTA PROMISSORIA, o ADIANTAMENTO a fornecedor e o RECIBO do pagamento de titulos):
     os mesmos campos e a mesma ordem do A5 (pagamento/documento), um embaixo do outro, com a linha
     de corte antes da promissoria. --}}
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
        table.titulos {
            margin-top: 1px;
        }

        table.titulos th {
            font-weight: normal;
            font-size: 6.5pt;
            color: #333;
            text-align: right;
            border-bottom: 0.6pt solid #000;
        }

        table.titulos td {
            font-size: 7.5pt;
            text-align: right;
            white-space: nowrap;
        }

        table.titulos td.numero {
            font-size: 9pt;
            text-align: left;
            padding-top: 3px;
            white-space: normal;
        }

        table.titulos tr.total td {
            border-top: 0.8pt solid #000;
            font-weight: bold;
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
            {{-- sem prazo (pagamento de titulos), sem a caixa Vencimento --}}
            <td style="width: {{ $r['vencimento'] ? 32 : 45 }}%">
                <div class="campo">
                    <div class="rotulo">Nº</div>
                    <div class="valor">{{ $r['numero'] }}</div>
                </div>
            </td>
            @if ($r['vencimento'])
                <td style="width: 30%">
                    <div class="campo">
                        <div class="rotulo">Vencimento</div>
                        <div class="valor">{{ $r['vencimento'] }}</div>
                    </div>
                </td>
            @endif
            <td style="width: {{ $r['vencimento'] ? 38 : 55 }}%">
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

    @if ($r['baixa'])
        {{-- os titulos pagos, sempre com principal, juros, multa, desconto e total (o numero e o
             vencimento numa linha, os valores na de baixo); o total so' com mais de uma linha --}}
        @php
            $l = collect($r['linhas']);
            $cols = ['principal', 'juros', 'multa', 'desconto'];
        @endphp
        <div class="campo">
            <div class="rotulo">{{ $l->count() > 1 ? 'Títulos' : 'Título' }}</div>
            <table class="titulos">
                <tr>
                    <th>Principal</th>
                    <th>Juros</th>
                    <th>Multa</th>
                    <th>Desc.</th>
                    <th>Total</th>
                </tr>
                @foreach ($l as $t)
                    <tr>
                        <td colspan="5" class="numero">
                            {{ $t['titulo']->numero }} · vence {{ $t['titulo']->vencimento?->format('d/m/Y') }}
                        </td>
                    </tr>
                    <tr>
                        @foreach ($cols as $k)
                            <td>{{ formataNumero($t[$k]) }}</td>
                        @endforeach
                        <td><b>{{ formataNumero($t['total']) }}</b></td>
                    </tr>
                @endforeach
                @if ($r['livre'] != 0)
                    <tr>
                        <td colspan="4" class="numero">Não amarrado</td>
                        <td>{{ formataNumero($r['livre']) }}</td>
                    </tr>
                @endif
                @if ($l->count() > 1 || $r['livre'] != 0)
                    <tr class="total">
                        @foreach ($cols as $k)
                            <td>{{ formataNumero($l->sum($k)) }}</td>
                        @endforeach
                        <td>{{ formataNumero($r['total']) }}</td>
                    </tr>
                @endif
            </table>
        </div>
    @endif

    <div class="campo">
        <div class="rotulo">Pagamento em</div>
        <div class="valor">{{ implode(' + ', $r['formas']) }}</div>
        @if ($r['autorizacoes'])
            <div class="miudo">{{ implode(' · ', $r['autorizacoes']) }}</div>
        @endif
    </div>

    <div class="campo">
        <div class="rotulo">Local / Data</div>
        <div class="valor">{{ $r['localData'] }}</div>
    </div>

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
