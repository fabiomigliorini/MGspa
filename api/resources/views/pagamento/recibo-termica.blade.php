{{-- $r: PagamentoReciboService::montar. O mesmo RECIBO do A5, na bobina de 80mm (o vale tem o
     dele, pagamento/vale-termica):
     os campos do bloco um embaixo do outro; titulos e forma miudos no fim. --}}
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
        }

        td {
            vertical-align: top;
            padding: 1px 0;
        }

        .r {
            text-align: right;
        }

        .titulo {
            font-size: 22pt;
            font-weight: bold;
            line-height: 1;
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
            font-size: 13pt;
            font-weight: bold;
        }

        .miudo {
            font-size: 7pt;
        }

        .linha {
            border-top: 1px dashed #000;
            margin: 6px 0 3px 0;
        }

        table.titulos {
            font-size: 7pt;
            margin-top: 1px;
        }

        table.titulos th {
            font-weight: normal;
            font-size: 6pt;
            color: #333;
            text-align: right;
            border-bottom: 0.6pt solid #000;
        }

        table.titulos td {
            text-align: right;
        }

        table.titulos td.numero {
            text-align: left;
            padding-top: 3px;
        }

        table.titulos tr.total td {
            border-top: 0.8pt solid #000;
            font-weight: bold;
        }

        .traco {
            margin-top: 1cm;
            border-top: 1px solid #000;
            text-align: center;
            padding-top: 1px;
        }
    </style>
</head>

<body>
    <table>
        <tr>
            <td class="titulo">{{ $r['titulo'] }}</td>
            <td class="r">
                <span class="rotulo">Nº</span> {{ $r['numero'] }}<br>
                <span class="grande">R$ {{ formataNumero($r['total']) }}</span>
            </td>
        </tr>
    </table>

    <div class="campo">
        <div class="rotulo">{{ $r['rotuloDe'] }}</div>
        <div class="valor"><b>{{ $r['de']['nome'] ?? '' }}</b>
            @if ($r['de']['codigo'] ?? null)
                <span class="miudo">{{ $r['de']['codigo'] }}</span>
            @endif
        </div>
        @if ($r['de']['documento'] ?? null)
            <div class="miudo">{{ $r['de']['documento'] }}</div>
        @endif
    </div>

    <div class="campo fundo">
        <div class="rotulo">A importância de</div>
        <div class="valor"><b>{{ $r['extenso'] }}</b></div>
    </div>

    <div class="campo">
        <div class="rotulo">Referente a</div>
        <div class="valor">{{ implode(' · ', array_filter([$r['referente'], ...$r['referentes']])) ?: '—' }}</div>
    </div>

    @if ($r['baixa'])
        @php $soma = collect($r['linhas']); @endphp
        <div class="campo">
            <div class="rotulo">Títulos</div>
            <table class="titulos">
                <tr>
                    <th>Principal</th>
                    <th>Juros</th>
                    <th>Multa</th>
                    <th>Desc.</th>
                    <th>Total</th>
                </tr>
                @foreach ($r['linhas'] as $l)
                    <tr>
                        <td colspan="5" class="numero">
                            {{ $l['titulo']->numero }}{{ $l['lancado'] ? ' (' . $l['lancado'] . ')' : '' }} · vence {{ $l['titulo']->vencimento?->format('d/m/Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td>{{ formataNumero($l['principal']) }}</td>
                        <td>{{ formataNumero($l['juros']) }}</td>
                        <td>{{ formataNumero($l['multa']) }}</td>
                        <td>{{ formataNumero($l['desconto']) }}</td>
                        <td><b>{{ formataNumero($l['total']) }}</b></td>
                    </tr>
                @endforeach
                @if ($r['livre'] != 0)
                    <tr>
                        <td colspan="4" class="numero">Não amarrado</td>
                        <td>{{ formataNumero($r['livre']) }}</td>
                    </tr>
                @endif
                <tr class="total">
                    <td>{{ formataNumero($soma->sum('principal')) }}</td>
                    <td>{{ formataNumero($soma->sum('juros')) }}</td>
                    <td>{{ formataNumero($soma->sum('multa')) }}</td>
                    <td>{{ formataNumero($soma->sum('desconto')) }}</td>
                    <td>{{ formataNumero($r['total']) }}</td>
                </tr>
            </table>
        </div>
    @endif

    <div class="campo">
        <div class="rotulo">Pagamento em</div>
        <div class="valor">{{ implode(' + ', $r['formas']) }}</div>
    </div>

    <div class="campo">
        <div class="rotulo">Local / Data</div>
        <div class="valor">{{ $r['localData'] }}</div>
    </div>

    <div class="campo">
        <div class="rotulo">Assinatura</div>
        <div class="traco">
            {{ $r['assinante']['nome'] ?? '' }}
            @if ($r['assinante']['documento'] ?? null)
                <br><span class="miudo">{{ $r['assinante']['documento'] }}</span>
            @endif
        </div>
    </div>

    <div class="linha"></div>
    <div class="miudo">
        Lançado por {{ $r['operador'] ?: '—' }} em {{ $r['transacao']->format('d/m/Y H:i') }}
        @unless ($r['baixa'])
            @foreach ($r['linhas'] as $l)
                <br>Título {{ $l['titulo']->numero }}, vence {{ $l['titulo']->vencimento?->format('d/m/Y') }}
            @endforeach
        @endunless
    </div>
</body>

</html>
