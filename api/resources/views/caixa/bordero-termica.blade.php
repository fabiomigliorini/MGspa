@php
    // Bordero do caixa (M9; completo desde o M13 doc-3): contagem por cedula e
    // moeda, itens do caixa, avulsos e ajustes. Cartoes so' em quantidade (o
    // gerente fecha o lote da maquineta pelo bordero dela).
    $filial = $sessao->Portador->Filial;
    $contado = (float) $sessao->saldofinal;
    $abertura = $sessao->contagemabertura ?? [];
    $fechamento = $sessao->contagemfechamento ?? [];
    $denominacoes = array_merge(\Mg\Caixa\CaixaService::CEDULAS, \Mg\Caixa\CaixaService::MOEDAS);
    $itens = collect($painel['itens']);
    $estoqueAbertura = $itens->where('modo', 'C')->sum('valorabertura');
    $estoqueFechamento = $itens->where('modo', 'C')->sum('valorfechamento');
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
        <b>BORDERÔ DO CAIXA</b><br>
        {{ $sessao->Portador->portador }} · sessão {{ $sessao->codportadorperiodo }}
    </div>

    <table>
        <tr>
            <td>Abertura</td>
            <td class="r">{{ $sessao->inicio->format('d/m/Y H:i') }}<br>{{ $sessao->UsuarioAbertura->usuario ?? '' }}</td>
        </tr>
        <tr>
            <td>Fechamento</td>
            <td class="r">{{ $sessao->fim?->format('d/m/Y H:i') }}<br>{{ $sessao->UsuarioFechamento->usuario ?? '' }}</td>
        </tr>
    </table>

    <div class="linha"></div>
    <b>CONTAGEM</b> <small>(quantidade)</small>
    <table>
        <tr>
            <td></td>
            <td class="r">Abertura</td>
            <td class="r">Fechamento</td>
        </tr>
        @foreach ($denominacoes as $d)
            @if (!empty($abertura[$d]) || !empty($fechamento[$d]))
                <tr>
                    <td>{{ formataNumero((float) $d) }}</td>
                    <td class="r">{{ $abertura[$d] ?? '' }}</td>
                    <td class="r">{{ $fechamento[$d] ?? '' }}</td>
                </tr>
            @endif
        @endforeach
        <tr>
            <td>Moedas</td>
            <td class="r">{{ formataNumero($sessao->moedasabertura ?? 0) }}</td>
            <td class="r">{{ formataNumero($sessao->moedasfechamento ?? 0) }}</td>
        </tr>
        <tr>
            <td>Cédulas</td>
            <td class="r">{{ formataNumero($sessao->cedulasabertura ?? 0) }}</td>
            <td class="r">{{ formataNumero($sessao->cedulasfechamento ?? 0) }}</td>
        </tr>
        @foreach ($itens->where('modo', 'C') as $i)
            <tr>
                <td>{{ $i['item'] }}</td>
                <td class="r">{{ formataNumero($i['valorabertura'] ?? 0) }}</td>
                <td class="r">{{ formataNumero($i['valorfechamento'] ?? 0) }}</td>
            </tr>
        @endforeach
        <tr>
            <td><b>Total</b></td>
            <td class="r"><b>{{ formataNumero(($sessao->moedasabertura ?? 0) + ($sessao->cedulasabertura ?? 0) + $estoqueAbertura) }}</b></td>
            <td class="r grande">{{ formataNumero($contado) }}</td>
        </tr>
    </table>

    <div class="linha"></div>
    <b>DINHEIRO</b>
    <table>
        <tr>
            <td>Envelope anterior</td>
            <td class="r">{{ formataNumero($sessao->saldoinicial ?? 0) }}</td>
        </tr>
        @if ($painel['ajusteabertura'] != 0)
            <tr>
                <td>Ajuste na abertura</td>
                <td class="r">{{ formataNumero($painel['ajusteabertura']) }}</td>
            </tr>
        @endif
        <tr>
            <td>Sistema</td>
            <td class="r">{{ formataNumero($painel['dinheiro']['sistema'] - $painel['ajustefechamento']) }}</td>
        </tr>
        <tr>
            <td>Contado</td>
            <td class="r">{{ formataNumero($contado) }}</td>
        </tr>
        <tr>
            <td><b>Ajuste no fechamento</b></td>
            <td class="r"><b>{{ formataNumero($painel['ajustefechamento']) }}</b></td>
        </tr>
    </table>

    @if ($itens->isNotEmpty())
        <div class="linha"></div>
        <b>ITENS DO CAIXA</b> <small>(entrada / saída · repasse)</small>
        <table>
            @foreach ($itens as $i)
                @if ($i['valorentrada'] || $i['valorsaida'] || $i['valorvendido'] || $i['liquido'])
                    <tr>
                        <td>
                            {{ $i['item'] }}
                            @if ($i['modo'] == 'M' && $i['valorvendido'] !== null)
                                <br><small>vendido {{ formataNumero($i['valorvendido']) }}</small>
                            @endif
                        </td>
                        <td class="r">
                            {{ formataNumero($i['valorentrada']) }} / {{ formataNumero($i['valorsaida']) }}<br>
                            <b>{{ formataNumero($i['liquido'] ?? 0) }}</b>
                            @if ($i['titulo'])
                                <small>{{ $i['titulo'] }}</small>
                            @endif
                        </td>
                    </tr>
                @endif
            @endforeach
        </table>
    @endif

    @if (!empty($painel['avulsos']))
        <div class="linha"></div>
        <b>LANÇAMENTOS AVULSOS</b>
        <table>
            @foreach ($painel['avulsos'] as $a)
                <tr>
                    <td @if ($a['estado'] == 'C') style="text-decoration: line-through" @endif>
                        {{ $a['motivodescricao'] }} · {{ $a['observacoes'] }}
                    </td>
                    <td class="r" @if ($a['estado'] == 'C') style="text-decoration: line-through" @endif>
                        {{ formataNumero($a['valor']) }}
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    @php
        $outros = collect($informativo['meios'])->whereNotIn('meio', [3, 4]);
    @endphp
    @if ($outros->isNotEmpty() || $informativo['prazo']['quantidade'] > 0 || !empty($informativo['maquinetas']))
        <div class="linha"></div>
        <b>ENTREGO TAMBÉM</b> <small>(quantidade)</small>
        <table>
            @foreach ($informativo['meios'] as $m)
                @if (!in_array($m['meio'], [3, 4]))
                    <tr>
                        <td>{{ $m['descricao'] }}</td>
                        <td class="r">{{ $m['quantidade'] }}</td>
                    </tr>
                @endif
            @endforeach
            @if ($informativo['prazo']['quantidade'] > 0)
                <tr>
                    <td>Duplicatas a prazo (assinadas)</td>
                    <td class="r">{{ $informativo['prazo']['quantidade'] }}</td>
                </tr>
            @endif
            @foreach ($informativo['maquinetas'] as $m)
                <tr>
                    <td>Cartões na maquineta {{ $m['maquineta'] }}</td>
                    <td class="r">{{ $m['quantidade'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($sessao->observacoes)
        <div class="linha"></div>
        {!! nl2br(e($sessao->observacoes)) !!}
    @endif

    <div class="assinatura">{{ $sessao->UsuarioFechamento->usuario ?? 'Caixa' }}</div>
    <div class="assinatura">Gerente</div>
</body>

</html>
