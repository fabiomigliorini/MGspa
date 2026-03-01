@php
    use Mg\Titulo\MovimentoTituloService;

    $filialPessoa = $liq->MovimentoTituloS->first()?->Titulo?->Filial?->Pessoa;
    $cidadeEstado = '';
    if ($filialPessoa?->Cidade) {
        $cidadeEstado = $filialPessoa->Cidade->cidade . '/' . ($filialPessoa->Cidade->Estado->sigla ?? '');
    }

    // Resumo por título
    $resumo = [];
    foreach ($liq->MovimentoTituloS as $mov) {
        if (!isset($resumo[$mov->codtitulo])) {
            $resumo[$mov->codtitulo] = [
                'titulo' => $mov->Titulo,
                'principal' => 0,
                'juros' => 0,
                'multa' => 0,
                'desconto' => 0,
                'total' => 0,
            ];
        }
        switch ((int) $mov->codtipomovimentotitulo) {
            case MovimentoTituloService::TIPO_JUROS:
                $resumo[$mov->codtitulo]['juros'] += $mov->debito - $mov->credito;
                break;
            case MovimentoTituloService::TIPO_MULTA:
                $resumo[$mov->codtitulo]['multa'] += $mov->debito - $mov->credito;
                break;
            case MovimentoTituloService::TIPO_DESCONTO:
                $resumo[$mov->codtitulo]['desconto'] += $mov->credito - $mov->debito;
                break;
            default:
                $resumo[$mov->codtitulo]['total'] += $mov->credito - $mov->debito;
                break;
        }
    }
    foreach ($resumo as &$d) {
        $d['principal'] = $d['total'] - $d['juros'] - $d['multa'] + $d['desconto'];
    }
    unset($d);

    $dt = $liq->transacao ?? now();
    $dataExtenso = $cidadeEstado . ', ' . formataDataPorExtenso($dt) . '.';
    $valorExtenso = formataValorPorExtenso((float) $liq->credito, true);
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: A5 landscape;
            margin: 8mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            margin: 0;
            padding: 0;
            color: #000;
        }

        table {
            border-collapse: collapse;
        }

        .recibo-outer {
            border: 2px solid #000;
            width: 100%;
        }

        /* Header */
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

        /* Faixa RECIBO */
        .recibo-faixa {
            /* border-bottom: 2px solid #000; */
            padding: 6px 12px;
            text-align: center;
        }

        .titulo-recibo {
            margin-top: 10px;
            font-size: 18pt;
            font-weight: bold;
            letter-spacing: 4px;
        }

        .titulo-valor {
            margin-top: 10px;
            margin-bottom: 10px;
            font-size: 14pt;
            font-weight: bold;
        }

        /* Corpo */
        .recibo-corpo {
            padding: 8px 12px 6px 12px;
            font-size: 8.5pt;
            line-height: 1.6;
        }

        .recibo-corpo p {
            margin: 0;
        }

        /* Tabela itens */
        .itens-table {
            width: 100%;
            border: 1.5px solid #000;
            margin-top: 6px;
        }

        .itens-table th {
            background: #e0e0e0;
            padding: 3px 5px;
            text-align: left;
            font-size: 7.5pt;
            font-weight: bold;
            border: 1.5px solid #000;
        }

        .itens-table th.r {
            text-align: right;
        }

        .itens-table td {
            padding: 2px 5px;
            font-size: 7.5pt;
            border: 1px solid #888;
        }

        .itens-table td.r {
            text-align: right;
        }

        .itens-table td.r.bold {
            font-weight: bold;
        }

        /* Rodapé */
        .recibo-rodape {
            /*border-top: 2px solid #000;*/
            padding: 6px 12px 8px 12px;
        }

        .rodape-data {
            text-align: right;
            font-size: 8pt;
            margin-bottom: 4px;
        }

        .assin-bloco {
            text-align: right;
            margin-top: 50px;
        }

        .assin-linha {
            display: inline-block;
            width: 55%;
            border-top: 1px solid #000;
            padding-top: 4px;
            font-size: 8pt;
            text-align: center;
        }

        .assin-cnpj {
            font-size: 7pt;
            color: #333;
        }
    </style>
</head>

<body>

    <div class="recibo-outer">

        {{-- Header: empresa, recibo, usuario, data --}}
        <div class="recibo-header">
            <table>
                <tr>
                    <td class="bold">{{ $filialPessoa->fantasia ?? '' }} {{ $filialPessoa->telefone1 ?? '' }}</td>
                    <td class="right">Recibo: {{ formataCodigo($liq->codliquidacaotitulo) }}</td>
                </tr>
                <tr>
                    <td>Usuario: {{ $liq->UsuarioCriacao->usuario ?? '—' }}</td>
                    <td class="right">Data: {{ $liq->criacao?->format('d/m/Y H:i:s') }}</td>
                </tr>
            </table>
        </div>

        {{-- Faixa: RECIBO + VALOR --}}
        <div class="recibo-faixa">
            <div class="titulo-recibo">R E C I B O</div>
            <div class="titulo-valor">Valor R$ {{ formataNumero($liq->credito) }}</div>
        </div>

        {{-- Corpo --}}
        <div class="recibo-corpo">
            <p>
                <strong>Recebemos de</strong> {{ $liq->Pessoa->pessoa ?? '—' }} ({{ formataCodigo($liq->codpessoa) }}),
                {{ $liq->Pessoa->fisica ?? false ? 'CPF' : 'CNPJ' }}
                {{ formataCnpjCpf($liq->Pessoa->cnpj ?? '', $liq->Pessoa->fisica ?? false) }}
                a importancia de <strong>{{ $valorExtenso }}</strong>,
                referente ao pagamento dos titulos abaixo listados:
            </p>

            <table class="itens-table">
                <thead>
                    <tr>
                        <th>Numero</th>
                        <th>Emissao</th>
                        <th>Vencimento</th>
                        <th class="r">Valor Original</th>
                        <th class="r">Pagamento</th>
                        <th class="r">Juros</th>
                        <th class="r">Desconto</th>
                        <th class="r">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($resumo as $codtitulo => $r)
                        @if ($r['total'] > 0)
                            <tr>
                                <td>{{ $r['titulo']->numero }}</td>
                                <td>{{ $r['titulo']->emissao?->format('d/m/Y') }}</td>
                                <td>{{ $r['titulo']->vencimento?->format('d/m/Y') }}</td>
                                <td class="r">
                                    {{ formataNumero(abs($r['titulo']->debito - $r['titulo']->credito)) }}</td>
                                <td class="r">{{ formataNumero($r['principal']) }}</td>
                                <td class="r">{{ formataNumero($r['juros'] + $r['multa']) }}</td>
                                <td class="r">{{ formataNumero($r['desconto']) }}</td>
                                <td class="r bold">{{ formataNumero($r['total']) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Rodapé --}}
        <div class="recibo-rodape">
            <div class="rodape-data">{{ $dataExtenso }}</div>

            <div class="assin-bloco">
                <div class="assin-linha">
                    {{ $filialPessoa->pessoa ?? '' }}<br>
                    <span
                        class="assin-cnpj">{{ formataCnpjCpf($filialPessoa->cnpj ?? '', $filialPessoa->fisica ?? false) }}</span>
                </div>
            </div>
        </div>

    </div>

</body>

</html>
