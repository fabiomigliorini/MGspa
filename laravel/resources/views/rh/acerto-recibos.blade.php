@php
    // Monta lista de recibos para preencher a tabela 2 colunas
    $recibos = [];
    foreach ($liquidacoes as $liq) {
        if ($liq->credito > 0) {
            $recibos[] = ['tipo' => 'recebimento', 'liq' => $liq];
        }
        if ($liq->debito > 0) {
            $recibos[] = ['tipo' => 'pagamento', 'liq' => $liq];
        }
    }
    $pares = array_chunk($recibos, 2);
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7.5pt;
            margin: 0;
            padding: 0;
            color: #000;
        }

        table {
            border-collapse: collapse;
        }

        .layout {
            width: 100%;
        }

        .layout td {
            width: 50%;
            vertical-align: top;
            padding: 4mm;
        }

        .recibo-outer {
            border: 2px solid #000;
            width: 100%;
            page-break-inside: avoid;
        }

        .recibo-header {
            border-bottom: 2px solid #000;
            padding: 6px 12px;
        }

        .recibo-header table {
            width: 100%;
            border: none;
        }

        .recibo-header td {
            border: none;
            padding: 1px 0;
            vertical-align: top;
        }

        .recibo-header .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .recibo-faixa {
            padding: 4px 10px;
            text-align: center;
        }

        .titulo-recibo {
            margin-top: 6px;
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .titulo-valor {
            margin-top: 4px;
            margin-bottom: 4px;
            font-size: 11pt;
            font-weight: bold;
        }

        .recibo-corpo {
            padding: 6px 10px 4px 10px;
            font-size: 7pt;
            line-height: 1.5;
        }

        .recibo-corpo p {
            margin: 0;
        }

        .itens-table {
            width: 100%;
            border: 1.5px solid #000;
            margin-top: 4px;
        }

        .itens-table th {
            background: #e0e0e0;
            padding: 2px 3px;
            text-align: left;
            font-size: 6.5pt;
            font-weight: bold;
            border: 1.5px solid #000;
        }

        .itens-table th.r {
            text-align: right;
        }

        .itens-table td {
            padding: 1px 3px;
            font-size: 6.5pt;
            border: 1px solid #888;
        }

        .itens-table td.r {
            text-align: right;
        }

        .itens-table td.r.bold {
            font-weight: bold;
        }

        .recibo-rodape {
            padding: 4px 10px 6px 10px;
        }

        .rodape-data {
            text-align: right;
            font-size: 6.5pt;
            margin-bottom: 2px;
        }

        .assin-bloco {
            text-align: right;
            margin-top: 30px;
        }

        .assin-linha {
            display: inline-block;
            width: 70%;
            border-top: 1px solid #000;
            padding-top: 2px;
            font-size: 6.5pt;
            text-align: center;
        }

        .assin-cnpj,
        .assin-doc {
            font-size: 6pt;
            color: #333;
        }
    </style>
</head>

<body>
    <table class="layout">
        @foreach ($pares as $par)
            <tr>
                @foreach ($par as $item)
                    <td>
                        @if ($item['tipo'] === 'recebimento')
                            @include('liquidacao-titulo._recibo-recebimento', ['liq' => $item['liq']])
                        @else
                            @include('liquidacao-titulo._recibo-pagamento', ['liq' => $item['liq']])
                        @endif
                    </td>
                @endforeach
                @if (count($par) === 1)
                    <td></td>
                @endif
            </tr>
        @endforeach
    </table>
</body>

</html>
