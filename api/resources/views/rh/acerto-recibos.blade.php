<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: A4 portrait;
            margin: 6mm 8mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7pt;
            margin: 0;
            padding: 0;
            color: #000;
        }

        table {
            border-collapse: collapse;
        }

        .recibo-outer {
            border: 2px solid #000;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            height: 136mm;
            page-break-inside: avoid;
            margin-bottom: 3mm;
        }

        .recibo-inner {
            height: 110mm;
            vertical-align: top;
            padding: 0;
            border: none;
        }

        .recibo-rodape-cell {
            height: 26mm;
            vertical-align: bottom;
            padding: 0;
            border: none;
        }

        .recibo-header {
            border-bottom: 2px solid #000;
            padding: 4px 10px;
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
            padding: 2px 10px;
            text-align: center;
        }

        .titulo-recibo {
            margin-top: 3px;
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .titulo-valor {
            margin-top: 2px;
            margin-bottom: 2px;
            font-size: 9pt;
            font-weight: bold;
        }

        .recibo-corpo {
            padding: 4px 10px 3px 10px;
            font-size: 6.5pt;
            line-height: 1.4;
        }

        .recibo-corpo p {
            margin: 0;
        }

        .itens-table {
            width: 100%;
            border: 1.5px solid #000;
            margin-top: 3px;
        }

        .itens-table th {
            background: #e0e0e0;
            padding: 1px 3px;
            text-align: left;
            font-size: 6pt;
            font-weight: bold;
            border: 1.5px solid #000;
        }

        .itens-table th.r {
            text-align: right;
        }

        .itens-table td {
            padding: 1px 3px;
            font-size: 6pt;
            border: 1px solid #888;
        }

        .itens-table td.r {
            text-align: right;
        }

        .itens-table td.r.bold {
            font-weight: bold;
        }

        .recibo-rodape {
            padding: 3px 10px 4px 10px;
        }

        .rodape-data {
            text-align: right;
            font-size: 6pt;
            margin-bottom: 1px;
        }

        .assin-bloco {
            text-align: right;
        }

        .assin-linha {
            display: inline-block;
            width: 70%;
            border-top: 1px solid #000;
            padding-top: 2px;
            font-size: 6pt;
            text-align: center;
        }

        .assin-cnpj,
        .assin-doc {
            font-size: 5.5pt;
            color: #333;
        }
    </style>
</head>

<body>
    @foreach ($liquidacoes as $liq)
        @if ($liq->credito > 0)
            @include('liquidacao-titulo._recibo-recebimento', ['liq' => $liq])
        @endif
        @if ($liq->debito > 0)
            @include('liquidacao-titulo._recibo-pagamento', ['liq' => $liq])
        @endif
    @endforeach
</body>

</html>
