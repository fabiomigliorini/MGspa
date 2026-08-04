<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: A5 landscape;
            margin: 5mm 8mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 0;
            color: #000;
        }

        table {
            border-collapse: collapse;
        }

        /* A5 paisagem: 1 recibo por folha (a caixa preenche a area util ~194x136mm) */
        .recibo-outer {
            border: 2px solid #000;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            height: 136mm;
            page-break-inside: avoid;
            margin-bottom: 0;
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

        /* data + "Pag. x/y" nunca quebram: sobra folga para o nome ao lado */
        .recibo-header .right {
            text-align: right;
            white-space: nowrap;
        }

        /* nome do colaborador, entre usuario e data. Pode quebrar em duas linhas no caso
           extremo (nome longo + usuario longo + "Pag. x/y") em vez de colidir com a data. */
        .recibo-header .center {
            text-align: center;
            padding-left: 8px;
            padding-right: 8px;
        }

        .bold {
            font-weight: bold;
        }

        /* espaco entre a faixa RECIBO/valor e o texto do corpo: enxuto de proposito,
           e o que permite 8 titulos numa folha (ver .itens-table margin-top) */
        .recibo-faixa {
            padding: 2px 6px;
        }

        .faixa-table {
            width: 100%;
            border: none;
        }

        .faixa-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .titulo-recibo,
        .titulo-valor {
            font-size: 20pt;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .titulo-recibo {
            text-align: left;
        }

        .titulo-valor {
            text-align: right;
        }

        .recibo-corpo {
            padding: 2px 10px 0px 6px;
            font-size: 10pt;
            line-height: 1.4;
        }

        .corpo-data {
            text-align: right;
            font-size: 9.5pt;
            margin-top: 5px;
        }

        .recibo-corpo p {
            margin: 0;
        }

        .itens-table {
            width: 100%;
            border: 1.5px solid #000;
            margin-top: 6px;
            margin-bottom: 15px;
        }

        .itens-table th {
            background: #e0e0e0;
            padding: 3px 5px;
            text-align: left;
            font-size: 9.5pt;
            font-weight: bold;
            border: 1.5px solid #000;
        }

        .itens-table th.r {
            text-align: right;
        }

        .itens-table td {
            padding: 3px 5px;
            font-size: 9.5pt;
            border: 1px solid #888;
        }

        .itens-table td.r {
            text-align: right;
        }

        .itens-table td.r.bold {
            font-weight: bold;
        }

        /* linha nunca quebra em duas: mantem a altura da fatia previsivel */
        .itens-table td,
        .itens-table th {
            white-space: nowrap;
        }

        .itens-table td.desc {
            white-space: normal;
        }

        .linha-total td {
            border-top: 1.5px solid #000;
            font-weight: bold;
            background: #f0f0f0;
        }

        .continua {
            text-align: right;
            font-size: 9pt;
            font-style: italic;
            padding: 2px 10px 0 10px;
        }

        .recibo-rodape {
            padding: 3px 10px 3px 10px;
        }

        .rodape-data {
            text-align: right;
            font-size: 8pt;
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
            font-size: 9pt;
            text-align: center;
        }

        .assin-cnpj,
        .assin-doc {
            font-size: 8.5pt;
            color: #333;
        }
    </style>
</head>

<body>
    {{--
        Um recibo por ACERTO (evento tblperiodocolaboradoracerto). Em cada acerto:
        - debitos > 0  -> RECIBO DE RECEBIMENTO: empresa recebe do colaborador (vales
          quitados); assina a EMPRESA.
        - rubricas/creditos > 0 -> RECIBO DE PAGAMENTO: empresa paga o colaborador
          (beneficio/rubricas); assina o COLABORADOR.
        Um encontro de contas (beneficio + vale no mesmo acerto) emite os DOIS.
    --}}
    @php $tipo = $tipo ?? null; @endphp
    @foreach ($acertos as $ev)
        @if (($tipo === null || $tipo === 'recebimento') && $ev->debitos > 0)
            @include('rh._acerto-recibo-recebimento', ['ev' => $ev])
        @endif
        @if (($tipo === null || $tipo === 'pagamento') && ($ev->rubricas > 0 || $ev->creditos > 0))
            @include('rh._acerto-recibo-pagamento', ['ev' => $ev])
        @endif
    @endforeach
</body>

</html>
