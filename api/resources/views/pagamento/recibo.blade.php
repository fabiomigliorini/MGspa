{{-- $r: PagamentoReciboService::montar. O RECIBO no jeito do bloco de papel, A5 deitado (vale,
     adiantamento a fornecedor e pagamento de titulos tem o deles, pagamento/documento).
     Baixa de titulos: os titulos (principal, juros, multa, desconto, total) entram no formulario;
     muitos titulos continuam na pagina seguinte. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: A5 landscape;
            margin: 6mm 8mm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #000;
            margin: 0;
        }
        .form {
            border: 1.6pt solid #000;
            border-radius: 3mm;
            padding: 1.5mm 3mm 2.5mm 3mm;
        }
        table.linha {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.linha td {
            padding: 1.2mm 0 0 0;
            vertical-align: top;
        }
        table.linha td + td {
            padding-left: 2mm;
        }
        .campo {
            border: 0.8pt solid #555;
            border-radius: 1.6mm;
            padding: 0.5mm 2mm 1.2mm 2mm;
        }
        .fundo {
            background: #e4e4e4;
        }
        .rotulo {
            font-size: 7pt;
            color: #444;
        }
        .valor {
            font-size: 11.5pt;
        }
        .miudo {
            font-size: 7.5pt;
            color: #333;
        }
        .titulo {
            font-size: 32pt;
            font-weight: bold;
            letter-spacing: 1pt;
            line-height: 1;
            padding-top: 1mm;
        }
        .grande {
            font-size: 16pt;
            font-weight: bold;
        }
        .traco {
            border-top: 0.8pt solid #000;
            margin-top: 8mm;
            text-align: center;
            font-size: 8pt;
            padding-top: 0.5mm;
        }
        table.titulos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5mm;
            font-size: 8.5pt;
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
            padding: 0.5mm 1mm;
        }
        table.titulos tr.total td {
            border-bottom: none;
            border-top: 0.8pt solid #000;
            font-weight: bold;
        }
        table.titulos .r {
            text-align: right;
        }
        .rodape {
            margin-top: 1.5mm;
            font-size: 6.5pt;
            color: #444;
        }
    </style>
</head>
<body>
    <div class="form">
        <table class="linha">
            <tr>
                <td style="width: 42%">
                    <div class="titulo">{{ $r['titulo'] }}</div>
                </td>
                <td style="width: 22%">
                    <div class="campo">
                        <div class="rotulo">Nº</div>
                        <div class="valor">{{ $r['numero'] }}</div>
                    </div>
                </td>
                <td style="width: 36%">
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
                        <div class="rotulo">{{ $r['rotuloDe'] }}</div>
                        <div class="valor">
                            <b>{{ $r['de']['nome'] ?? '' }}</b>
                            @if ($r['de']['codigo'] ?? null)
                                <span class="miudo">{{ $r['de']['codigo'] }}</span>
                            @endif
                            @if ($r['de']['documento'] ?? null)
                                <span class="miudo">· {{ $r['de']['documento'] }}</span>
                            @endif
                        </div>
                        @if ($r['de']['endereco'] ?? null)
                            <div class="miudo">{{ $r['de']['endereco'] }}</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <table class="linha">
            <tr>
                <td>
                    <div class="campo fundo">
                        <div class="rotulo">A importância de</div>
                        <div class="valor"><b>{{ $r['extenso'] }}</b></div>
                    </div>
                </td>
            </tr>
        </table>

        @if ($r['referentes'])
            <table class="linha">
                <tr>
                    <td>
                        <div class="campo">
                            <div class="rotulo">Referente a</div>
                            <div class="valor">{{ implode(' · ', $r['referentes']) }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        @endif

        @if ($r['baixa'])
            @php $soma = collect($r['linhas']); @endphp
            {{-- fora da table.linha: tabela dentro de celula o Dompdf nao quebra entre paginas --}}
                        <div class="campo" style="margin-top: 1.2mm">
                            <div class="rotulo">Títulos</div>
                            <table class="titulos">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Vencimento</th>
                                        <th class="r">Principal</th>
                                        <th class="r">Juros</th>
                                        <th class="r">Multa</th>
                                        <th class="r">Desconto</th>
                                        <th class="r">Total</th>
                                    </tr>
                                </thead>
                                @foreach ($r['linhas'] as $l)
                                    @if ($l['rubricas']->isNotEmpty())
                                        @foreach ($l['rubricas'] as $rubrica)
                                            <tr>
                                                <td colspan="6">{{ $l['titulo']->numero }} · {{ $rubrica->descricao }}</td>
                                                <td class="r">{{ formataNumero($rubrica->valorcalculado) }}</td>
                                            </tr>
                                        @endforeach
                                        @continue
                                    @endif
                                    <tr>
                                        <td>{{ $l['titulo']->numero }}{{ $l['lancado'] ? ' (' . $l['lancado'] . ')' : '' }}</td>
                                        <td>{{ $l['titulo']->vencimento?->format('d/m/Y') }}</td>
                                        <td class="r">{{ formataNumero($l['principal']) }}</td>
                                        <td class="r">{{ formataNumero($l['juros']) }}</td>
                                        <td class="r">{{ formataNumero($l['multa']) }}</td>
                                        <td class="r">{{ formataNumero($l['desconto']) }}</td>
                                        <td class="r">{{ formataNumero($l['total']) }}</td>
                                    </tr>
                                @endforeach
                                @if ($r['livre'] != 0)
                                    <tr>
                                        <td colspan="6">Não amarrado</td>
                                        <td class="r">{{ formataNumero($r['livre']) }}</td>
                                    </tr>
                                @endif
                                <tr class="total">
                                    <td colspan="2">Total ({{ $soma->count() }} {{ $soma->count() == 1 ? 'título' : 'títulos' }})</td>
                                    <td class="r">{{ formataNumero($soma->sum('principal')) }}</td>
                                    <td class="r">{{ formataNumero($soma->sum('juros')) }}</td>
                                    <td class="r">{{ formataNumero($soma->sum('multa')) }}</td>
                                    <td class="r">{{ formataNumero($soma->sum('desconto')) }}</td>
                                    <td class="r">{{ formataNumero($r['total']) }}</td>
                                </tr>
                            </table>
                        </div>
        @endif

        <table class="linha">
            <tr>
                <td style="width: 55%">
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

        {{-- quem recebeu o dinheiro: o emitente do recibo --}}
        <table class="linha">
            <tr>
                <td>
                    <div class="campo">
                        <div class="rotulo">Assinatura</div>
                        <div class="traco">
                            <b>{{ $r['assinante']['nome'] ?? '' }}</b>
                            @if ($r['assinante']['documento'] ?? null)
                                · {{ $r['assinante']['documento'] }}
                            @endif
                            @if ($r['assinante']['endereco'] ?? null)
                                <br><span class="miudo">{{ $r['assinante']['endereco'] }}</span>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="rodape">
        @unless ($r['baixa'])
            @foreach ($r['linhas'] as $l)
                Título {{ $l['titulo']->numero }}, vence {{ $l['titulo']->vencimento?->format('d/m/Y') }} ·
            @endforeach
        @endunless
        Lançado por {{ $r['operador'] ?: '—' }} em {{ $r['transacao']->format('d/m/Y H:i') }}
    </div>
</body>
</html>
