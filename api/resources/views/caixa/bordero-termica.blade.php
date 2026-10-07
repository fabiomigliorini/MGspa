@php
    // Bordero do periodo em especie (doc-4, redefinicao do dinheiro e "Itens
    // do caixa"): contagem por cedula e moeda e os itens do caixa
    // (contam como cedula: preco x quantidade), o resumo com a contagem final
    // e a diferenca e os ajustes. Cartoes so' em quantidade (o gerente fecha o
    // lote pelo bordero dele).
    $filial = $sessao->Portador->Filial;
    $abertura = $sessao->contageminicial ?? [];
    $fechamento = $sessao->contagemfinal ?? [];
    $denominacoes = array_merge(\Mg\Caixa\CaixaService::CEDULAS, \Mg\Caixa\CaixaService::MOEDAS);
    $final = $periodo['contagem']['final'] ?? null;
    // as linhas dos itens: {chave: [rotulo, preco, qtd abertura, qtd fechamento]}
    $nomes = \Mg\Caixa\CaixaItem::pluck('item', 'codcaixaitem');
    $itens = [];
    foreach ([$sessao->contagemitensinicial ?? [], $sessao->contagemitensfinal ?? []] as $n => $cont) {
        foreach ($cont as $cod => $linhas) {
            foreach ($linhas as $l) {
                $chave = $cod . '|' . number_format($l['preco'], 2, '.', '') . '|' . ($l['descricao'] ?? '');
                $itens[$chave] = $itens[$chave] ?? [
                    trim(($nomes[$cod] ?? 'Item') . ' ' . ($l['descricao'] ?? '')),
                    (float) $l['preco'],
                    0,
                    0,
                ];
                $itens[$chave][2 + $n] = (int) $l['quantidade'];
            }
        }
    }
    ksort($itens);
    $totalAbertura = \Mg\Caixa\CaixaService::totalContagem($abertura)
        + \Mg\Caixa\CaixaItemService::totalContagem($sessao->contagemitensinicial);
    $totalFechamento = \Mg\Caixa\CaixaService::totalContagem($fechamento)
        + \Mg\Caixa\CaixaItemService::totalContagem($sessao->contagemitensfinal);
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

        .c {
            text-align: center;
        }

        .contagem td {
            font-size: 7pt;
            padding: 1px 1px;
        }

        .contagem .lado {
            padding-left: 8px;
        }

        .contagem .total td {
            border-top: 1px solid #000;
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
            <td class="r">{{ $sessao->UsuarioAbertura->usuario ?? '' }} {{ $sessao->inicio->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td>Fechamento</td>
            <td class="r">{{ $sessao->UsuarioFechamento->usuario ?? '' }} {{ $sessao->fim?->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <div class="linha"></div>
    <b>CONTAGEM</b>
    <table class="contagem">
        <tr>
            <td colspan="5" class="c"><b>ABERTURA</b></td>
            <td colspan="5" class="c lado"><b>FECHAMENTO</b></td>
        </tr>
        @foreach ($denominacoes as $d)
            @if (!empty($abertura[$d]) || !empty($fechamento[$d]))
                <tr>
                    @foreach ([$abertura, $fechamento] as $n => $cont)
                        @if (!empty($cont[$d]))
                            <td class="r {{ $n ? 'lado' : '' }}">{{ $cont[$d] }}</td>
                            <td class="c">x</td>
                            <td class="r">{{ formataNumero((float) $d) }}</td>
                            <td class="c">=</td>
                            <td class="r">{{ formataNumero($cont[$d] * (float) $d) }}</td>
                        @else
                            <td class="{{ $n ? 'lado' : '' }}"></td><td></td><td></td><td></td><td></td>
                        @endif
                    @endforeach
                </tr>
            @endif
        @endforeach
        @foreach ($itens as $c)
            <tr>
                <td colspan="10"><small>{{ $c[0] }}</small></td>
            </tr>
            <tr>
                @foreach ([2, 3] as $n)
                    @if ($c[$n])
                        <td class="r {{ $n == 3 ? 'lado' : '' }}">{{ $c[$n] }}</td>
                        <td class="c">x</td>
                        <td class="r">{{ formataNumero($c[1]) }}</td>
                        <td class="c">=</td>
                        <td class="r">{{ formataNumero($c[$n] * $c[1]) }}</td>
                    @else
                        <td class="{{ $n == 3 ? 'lado' : '' }}"></td><td></td><td></td><td></td><td></td>
                    @endif
                @endforeach
            </tr>
        @endforeach
        <tr class="total">
            <td colspan="4"><b>Total</b></td>
            <td class="r"><b>{{ formataNumero($totalAbertura) }}</b></td>
            <td colspan="4" class="lado"><b>Total</b></td>
            <td class="r"><b>{{ formataNumero($totalFechamento) }}</b></td>
        </tr>
    </table>

    <div class="linha"></div>
    <b>RESUMO</b>
    <table>
        <tr>
            <td></td>
            <td class="r">Entradas</td>
            <td class="r">Saídas</td>
        </tr>
        <tr>
            <td>Saldo inicial</td>
            <td class="r">{{ $periodo['saldoinicial'] >= 0 ? formataNumero($periodo['saldoinicial']) : '' }}</td>
            <td class="r">{{ $periodo['saldoinicial'] < 0 ? formataNumero(-$periodo['saldoinicial']) : '' }}</td>
        </tr>
        @foreach ($periodo['resumo'] as $r)
            <tr>
                <td>{{ $r['descricao'] }} ({{ $r['quantidade'] }})</td>
                <td class="r">{{ formataNumero($r['entrada']) }}</td>
                <td class="r">{{ formataNumero($r['saida']) }}</td>
            </tr>
        @endforeach
        <tr>
            <td><b>Saldo final</b></td>
            <td colspan="2" class="r grande">{{ formataNumero($periodo['saldofinal']) }}</td>
        </tr>
        @if ($final && $final['contado'] !== null)
            <tr>
                <td>Contagem final</td>
                <td colspan="2" class="r">{{ formataNumero($final['contado']) }}</td>
            </tr>
            <tr>
                <td><b>Diferença</b> <small>(tolerância {{ formataNumero($periodo['tolerancia']) }})</small></td>
                <td colspan="2" class="r"><b>{{ formataNumero($final['diferenca']) }}</b></td>
            </tr>
        @endif
    </table>

    @if (!empty($painel['avulsos']))
        <div class="linha"></div>
        <b>AJUSTES</b>
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
