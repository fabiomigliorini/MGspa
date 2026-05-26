<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 7pt;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .grupo-titulo {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 2px;
        }

        table.notas {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.notas th,
        table.notas td {
            overflow: hidden;
        }

        table.notas th {
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding: 2px 3px;
            text-align: left;
            background: #f0f0f0;
        }

        table.notas td {
            padding: 2px 3px;
        }

        table.notas tr.zebra td {
            background: #f7f7f7;
        }

        .num {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .status-naoautorizada {
            color: #828282;
        }

        .status-digitacao {
            color: #ff6400;
        }

        .status-cancelada {
            color: #ff0000;
        }

        .status-inutilizada {
            color: #ff0000;
        }

        tr.totais td {
            border-top: 1px solid #000;
            font-weight: bold;
            padding-top: 3px;
        }
    </style>
</head>

<body>

    @php
        function corStatus($status)
        {
            $s = strtolower($status ?? '');
            if (str_contains($s, 'nao') || str_contains($s, 'não')) {
                return 'status-naoautorizada';
            }
            if (str_contains($s, 'digit')) {
                return 'status-digitacao';
            }
            if (str_contains($s, 'cancel')) {
                return 'status-cancelada';
            }
            if (str_contains($s, 'inutil')) {
                return 'status-inutilizada';
            }
            return '';
        }
        function fmtNum($v, $dec = 2)
        {
            return number_format(abs((float) $v), $dec, ',', '.');
        }
        function fmtData($d)
        {
            if (empty($d)) {
                return '';
            }
            try {
                return \Carbon\Carbon::parse($d)->format('d/m/y');
            } catch (\Exception $e) {
                return '';
            }
        }
    @endphp

    @foreach ($grupos as $chave => $notasGrupo)
        @php
            $primeira = $notasGrupo->first();
            $totaisPorStatus = [];
            foreach ($notasGrupo as $n) {
                $st = $n->status ?? '-';
                if (!isset($totaisPorStatus[$st])) {
                    $totaisPorStatus[$st] = [
                        'valorprodutos' => 0,
                        'icmsvalor' => 0,
                        'ipivalor' => 0,
                        'icmsstvalor' => 0,
                        'valorfrete' => 0,
                        'valorseguro' => 0,
                        'valordesconto' => 0,
                        'valoroutras' => 0,
                        'valortotal' => 0,
                    ];
                }
                foreach (
                    [
                        'valorprodutos',
                        'icmsvalor',
                        'ipivalor',
                        'icmsstvalor',
                        'valorfrete',
                        'valorseguro',
                        'valordesconto',
                        'valoroutras',
                        'valortotal',
                    ]
                    as $c
                ) {
                    $totaisPorStatus[$st][$c] += (float) ($n->$c ?? 0);
                }
            }
        @endphp

        <div class="grupo-titulo">
            {{ $primeira->filial ?? '' }} &gt; {{ $primeira->naturezaoperacao ?? '' }}
        </div>

        <table class="notas">
            <colgroup>
                <col style="width:3.2%"><!-- # -->
                <col style="width:1.6%"><!-- S -->
                <col style="width:3.2%"><!-- Numero -->
                <col style="width:3.0%"><!-- Emissao -->
                <col style="width:3.0%"><!-- Saida -->
                <col style="width:7.5%"><!-- Cpf/Cnpj -->
                <col style="width:9.5%"><!-- Fantasia -->
                <col style="width:6.0%"><!-- Cidade -->
                <col style="width:2.2%"><!-- UF -->
                <col style="width:5.5%"><!-- Produtos -->
                <col style="width:5.0%"><!-- Icms -->
                <col style="width:4.5%"><!-- Ipi -->
                <col style="width:5.0%"><!-- ST -->
                <col style="width:4.5%"><!-- Frete -->
                <col style="width:4.5%"><!-- Seguro -->
                <col style="width:4.5%"><!-- Desc -->
                <col style="width:4.5%"><!-- Outras -->
                <col style="width:6.0%"><!-- Total -->
                <col style="width:3.3%"><!-- Autoriz -->
                <col style="width:3.3%"><!-- Cancel -->
                <col style="width:3.3%"><!-- Inutil -->
                <col style="width:1.9%"><!-- M -->
            </colgroup>
            <thead>
                <tr>
                    <th class="num">#</th>
                    <th class="center">S</th>
                    <th class="num">Número</th>
                    <th>Emissão</th>
                    <th>Saída</th>
                    <th>Cpf/Cnpj</th>
                    <th>Fantasia</th>
                    <th>Cidade</th>
                    <th>UF</th>
                    <th class="num">Produtos</th>
                    <th class="num">Icms</th>
                    <th class="num">Ipi</th>
                    <th class="num">ST</th>
                    <th class="num">Frete</th>
                    <th class="num">Seguro</th>
                    <th class="num">Desc</th>
                    <th class="num">Outras</th>
                    <th class="num">Total</th>
                    <th>Autoriz</th>
                    <th>Cancel</th>
                    <th>Inutil</th>
                    <th class="center">M</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($notasGrupo as $i => $nota)
                    <tr class="{{ $i % 2 ? 'zebra' : '' }} {{ corStatus($nota->status) }}">
                        <td class="num">{{ abs($nota->codnotafiscal) }}</td>
                        <td class="center">{{ $nota->serie }}</td>
                        <td class="num">{{ number_format(abs($nota->numero ?? 0), 0, '', '.') }}</td>
                        <td>{{ fmtData($nota->emissao) }}</td>
                        <td>{{ fmtData($nota->saida) }}</td>
                        <td>{{ formataCnpjCpf($nota->cnpj ?? '', (bool) ($nota->fisica ?? false)) }}</td>
                        <td>{{ $nota->fantasia }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($nota->cidade ?? '', 12, '') }}</td>
                        <td>{{ $nota->uf ?? '' }}</td>
                        <td class="num">{{ fmtNum($nota->valorprodutos) }}</td>
                        <td class="num">{{ fmtNum($nota->icmsvalor) }}</td>
                        <td class="num">{{ fmtNum($nota->ipivalor) }}</td>
                        <td class="num">{{ fmtNum($nota->icmsstvalor) }}</td>
                        <td class="num">{{ fmtNum($nota->valorfrete) }}</td>
                        <td class="num">{{ fmtNum($nota->valorseguro) }}</td>
                        <td class="num">{{ fmtNum($nota->valordesconto) }}</td>
                        <td class="num">{{ fmtNum($nota->valoroutras) }}</td>
                        <td class="num">{{ fmtNum($nota->valortotal) }}</td>
                        <td>{{ fmtData($nota->nfedataautorizacao) }}</td>
                        <td>{{ fmtData($nota->nfedatacancelamento) }}</td>
                        <td>{{ fmtData($nota->nfedatainutilizacao) }}</td>
                        <td class="center">{{ $nota->modelo }}</td>
                    </tr>
                @endforeach

                @foreach ($totaisPorStatus as $status => $t)
                    <tr class="totais">
                        <td colspan="9" class="num">{{ $status }}</td>
                        <td class="num">{{ fmtNum($t['valorprodutos']) }}</td>
                        <td class="num">{{ fmtNum($t['icmsvalor']) }}</td>
                        <td class="num">{{ fmtNum($t['ipivalor']) }}</td>
                        <td class="num">{{ fmtNum($t['icmsstvalor']) }}</td>
                        <td class="num">{{ fmtNum($t['valorfrete']) }}</td>
                        <td class="num">{{ fmtNum($t['valorseguro']) }}</td>
                        <td class="num">{{ fmtNum($t['valordesconto']) }}</td>
                        <td class="num">{{ fmtNum($t['valoroutras']) }}</td>
                        <td class="num">{{ fmtNum($t['valortotal']) }}</td>
                        <td colspan="4"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

</body>

</html>
