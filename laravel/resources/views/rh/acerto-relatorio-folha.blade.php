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
        th, td {
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

        /* Total geral */
        .total-geral {
            border-top: 2px solid #000;
            margin-top: 14px;
            padding-top: 6px;
            text-align: right;
            font-size: 11pt;
            font-weight: bold;
        }

        /* Quebra de página entre portadores */
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>

    @if (empty($paginas))
        <div class="report-header">
            <div class="report-header-right">
                <div class="page_number"></div>
                <div>{{ $dataGeracao }}</div>
            </div>
            <h1>Relatório de Acertos</h1>
            <p>Período: {{ $periodo }}</p>
        </div>
        <p style="color: #666; font-style: italic;">Nenhum acerto efetivado para este período.</p>
    @else

        @foreach ($paginas as $idx => $pagina)
            <div @if ($idx > 0) class="page-break" @endif>

                {{-- Cabeçalho --}}
                <div class="report-header">
                    <div class="report-header-right">
                        <div class="page_number"></div>
                        <div>{{ $dataGeracao }}</div>
                    </div>
                    <h1>Relatório de {{ $pagina['nome_portador'] }}</h1>
                    <p>Período: {{ $periodo }}</p>
                </div>

                @if (empty($pagina['porFilial']))
                    <p style="color: #666; font-style: italic;">Nenhum lançamento para este portador.</p>
                @else

                    @php $totalGeral = 0; @endphp

                    @foreach ($pagina['porFilial'] as $fil)
                        @php
                            $totalFilial = collect($fil['linhas'])->sum('valor');
                            $totalGeral += $totalFilial;
                        @endphp

                        <div class="empresa-grupo">
                            <div class="empresa-titulo">
                                {{ $fil['filial'] }}
                                @if ($fil['cnpj_filial'])
                                    &mdash; CNPJ: {{ formataCnpj($fil['cnpj_filial']) }}
                                @endif
                            </div>

                            <table>
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>CPF</th>
                                        <th class="text-right">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($fil['linhas'] as $linha)
                                        <tr>
                                            <td>{{ $linha['nome_colaborador'] }}</td>
                                            <td>{{ $linha['fisica'] ? formataCpf($linha['cpf_colaborador']) : '' }}</td>
                                            <td class="text-right">R$ {{ formataNumero($linha['valor']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2">Total {{ $fil['filial'] }}</td>
                                        <td class="text-right">R$ {{ formataNumero($totalFilial) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endforeach

                    <div class="total-geral">
                        TOTAL GERAL: R$ {{ formataNumero($totalGeral) }}
                    </div>

                @endif

            </div>
        @endforeach

    @endif

</body>
</html>
