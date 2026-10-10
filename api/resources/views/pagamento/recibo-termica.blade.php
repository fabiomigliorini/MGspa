@php
    use Mg\Pagamento\PagamentoListaService;
    use Mg\Pagamento\PagamentoService;

    // um recebimento pode ter varios pagamentos (uma forma cada): um recibo so'
    $primeiro = $pags->first();
    $filial = $primeiro->Filial;
    $pessoa = $primeiro->Pessoa;
    $entrada = PagamentoListaService::operacao($primeiro) != 'DB';
    $total = $pags->where('estado', '!=', PagamentoService::ESTADO_CANCELADO)->sum('total');
    $titulos = [];
    // vale colaborador / adiantamento lancado no PDV (M8): o pagamento nasceu com o titulo
    $lancados = [];
    foreach ($pags as $pag) {
        // a baixa desamarrada (estornada) nao entra no recibo
        $estornadas = $pag->MovimentoTituloS->pluck('codmovimentotituloestorno')->filter()->all();
        foreach ($pag->MovimentoTituloS as $mov) {
            if ($mov->ehEstorno() || !$mov->Titulo || in_array($mov->codmovimentotitulo, $estornadas)) {
                continue;
            }
            if ($mov->codtipomovimentotitulo == \Mg\Titulo\MovimentoTituloService::TIPO_IMPLANTACAO) {
                $lancados[$mov->Titulo->TipoTitulo->tipotitulo ?? ''] = $mov->Titulo->observacao;
            }
            $cod = $mov->codtitulo;
            $titulos[$cod] = $titulos[$cod] ?? ['titulo' => $mov->Titulo, 'total' => 0, 'juros' => 0, 'desconto' => 0];
            $titulos[$cod]['total'] += abs((float) $mov->total);
            $titulos[$cod]['juros'] += (float) $mov->juros + (float) $mov->multa;
            $titulos[$cod]['desconto'] += (float) $mov->desconto;
        }
    }
@endphp
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

        .cabecalho {
            background-color: black;
            color: white;
            text-align: center;
            padding: 4px;
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

        .linha {
            border-top: 1px dashed #000;
            margin: 4px 0;
        }

        .grande {
            font-size: 12pt;
            font-weight: bold;
        }

        .assinatura {
            margin-top: 1.2cm;
            border-top: 1px solid #000;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="cabecalho">
        <b style="font-size: 12pt">{{ $filial->filial ?? '' }}</b><br>
        {{ $filial->Pessoa->telefone1 ?? '' }}<br>
        <b>{{ $lancados ? mb_strtoupper(implode(' / ', array_keys($lancados))) : ($entrada ? 'RECIBO DE RECEBIMENTO' : 'RECIBO DE PAGAMENTO') }}</b><br>
        {{ $primeiro->transacao->format('d/m/Y H:i:s') }}
    </div>

    <p>
        {{ $entrada ? 'Recebemos de' : 'Pagamos a' }}
        <b>{{ $pessoa->pessoa ?? '' }}</b> ({{ formataCodigo($pessoa->codpessoa ?? 0) }})
        a importância de <b>{{ formataValorPorExtenso((float) $total, true) }}</b>
        @if ($lancados)
            referente a {{ implode(' / ', array_keys($lancados)) }}.
            @foreach (array_filter($lancados) as $obs)
                <br>{{ $obs }}
            @endforeach
        @else
            referente aos títulos abaixo.
        @endif
    </p>

    <div class="linha"></div>
    <table>
        @foreach ($titulos as $t)
            <tr>
                <td>
                    {{ $t['titulo']->numero }}<br>
                    <small>vence {{ $t['titulo']->vencimento?->format('d/m/Y') }}
                        @if ($t['juros'] > 0)
                            · juros/multa {{ formataNumero($t['juros']) }}
                        @endif
                        @if ($t['desconto'] > 0)
                            · desc {{ formataNumero($t['desconto']) }}
                        @endif
                    </small>
                </td>
                <td class="r">{{ formataNumero($t['total']) }}</td>
            </tr>
        @endforeach
    </table>

    <div class="linha"></div>
    <table>
        @foreach ($pags as $pag)
            <tr>
                <td>
                    {{ PagamentoService::descricao($pag) }}
                    @if ($pag->Maquineta)
                        · {{ $pag->Maquineta->apelido }}
                    @endif
                    @if ($pag->parcelas > 1)
                        · {{ $pag->parcelas }}x
                    @endif
                    @if ($pag->autorizacao)
                        <br><small>aut. {{ $pag->autorizacao }}</small>
                    @endif
                    @if ($pag->estado == PagamentoService::ESTADO_CANCELADO)
                        <br><b>ESTORNADO</b>
                    @endif
                    <br><small>{{ formataCodigo($pag->codpagamento) }}</small>
                </td>
                <td class="r">{{ formataNumero($pag->total) }}</td>
            </tr>
        @endforeach
        <tr>
            <td class="grande">Total</td>
            <td class="r grande">{{ formataNumero($total) }}</td>
        </tr>
    </table>

    <p><small>Operador: {{ $primeiro->usuariocriacao ?? '' }}</small></p>

    <div class="assinatura">
        {{ $entrada ? ($filial->Pessoa->pessoa ?? '') : ($pessoa->pessoa ?? '') }}
    </div>
</body>

</html>
