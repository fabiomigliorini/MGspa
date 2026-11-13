<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 1.5cm;
        }

        .page_number:before {
            content: "Página " counter(page);
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #000;
        }

        /* Cabeçalho */
        .report-header {
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }

        .report-header h1 {
            font-size: 14pt;
            margin: 0 0 4px 0;
        }

        .report-header p {
            margin: 2px 0;
            font-size: 8.5pt;
        }

        .report-header-right {
            float: right;
            text-align: right;
            font-size: 8pt;
        }

        /* Grupos de empresa */
        .empresa-grupo {
            margin-bottom: 18px;
        }

        .empresa-titulo {
            font-size: 10pt;
            font-weight: bold;
            border-bottom: 1px solid #aaa;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }

        /* Tabela */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }

        th,
        td {
            border: 0.3mm solid #666;
            padding: 3px 5px;
        }

        th {
            background: #e0e0e0;
            text-align: left;
        }

        tbody tr:nth-child(even) {
            background: #f5f5f5;
        }

        tfoot td {
            background: #d8d8d8;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .col-cpf {
            width: 22mm;
        }

        .col-valor {
            width: 25mm;
        }

        /* Total geral */
        table.total-geral {
            margin-top: 14px;
            font-size: 10pt;
        }

        table.total-geral td {
            border: none;
            border-top: 2px solid #000;
            background: none;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="report-header">
        <div class="report-header-right">
            <div class="page_number"></div>
            <div>{{ $dataGeracao }}</div>
        </div>
        <h1>Relatório Folha</h1>
        <p>Recarga Bee e Dinheiro</p>
        <p>Período: {{ $periodo }}</p>
    </div>

    @if (empty($empresas))
        <p style="color: #666; font-style: italic;">Nenhum acerto em Recarga Bee ou Dinheiro neste período.</p>
    @else
        @foreach ($empresas as $emp)
            <div class="empresa-grupo">
                <div class="empresa-titulo">{{ $emp['empresa'] }}</div>

                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th class="col-cpf">CPF</th>
                            <th class="col-valor text-right">Recarga Bee</th>
                            <th class="col-valor text-right">Dinheiro</th>
                            <th class="col-valor text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($emp['linhas'] as $linha)
                            <tr>
                                <td>{{ $linha['nome'] }}</td>
                                <td>{{ $linha['fisica'] ? formataCpf($linha['cpf']) : '' }}</td>
                                <td class="text-right">{{ $linha['bee'] != 0 ? formataNumero($linha['bee']) : '' }}</td>
                                <td class="text-right">{{ $linha['dinheiro'] != 0 ? formataNumero($linha['dinheiro']) : '' }}</td>
                                <td class="text-right">{{ formataNumero($linha['bee'] + $linha['dinheiro']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">Total {{ $emp['empresa'] }}</td>
                            <td class="text-right">{{ formataNumero($emp['total_bee']) }}</td>
                            <td class="text-right">{{ formataNumero($emp['total_dinheiro']) }}</td>
                            <td class="text-right">{{ formataNumero($emp['total_bee'] + $emp['total_dinheiro']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endforeach

        <table class="total-geral">
            <tr>
                <td>TOTAL GERAL</td>
                <td class="col-valor text-right">{{ formataNumero($totalBee) }}</td>
                <td class="col-valor text-right">{{ formataNumero($totalDinheiro) }}</td>
                <td class="col-valor text-right">{{ formataNumero($totalBee + $totalDinheiro) }}</td>
            </tr>
        </table>
    @endif

</body>

</html>
