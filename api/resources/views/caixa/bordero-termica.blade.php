@php
    // Bordero do periodo em especie (doc-4, redefinicao do dinheiro e "Itens
    // do caixa"): no alto o quadro da tela do periodo (o "Movimento do Caixa"
    // de papel: moedas, cedulas, cada item com a movimentacao, as origens com
    // cada maquineta de parceiro, o total e a diferenca) e os ajustes. Cartoes so' em quantidade (o gerente
    // fecha o lote pelo bordero dele).
    $filial = $sessao->Portador->Filial;
    $quadro = $periodo['quadro'];
    $contagem = $periodo['contagem'];
    $final = $contagem['final']['contado'] !== null ? $contagem['final'] : null;
    $naTolerancia = $final && abs($final['diferenca']) <= $periodo['tolerancia'];
    $valor = fn ($v) => $v === null ? '' : formataNumero($v);
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

        .linha {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .quadro td {
            padding: 2px 0;
            border-bottom: 1px solid #ccc;
        }

        .quadro .recuo {
            padding-left: 8px;
        }

        .cabecalho-dados td {
            padding: 6px 0;
        }

        .quadro .total td {
            border-top: 1px solid #000;
            border-bottom: none;
            font-size: 10pt;
            font-weight: bold;
            padding: 4px 0;
        }

        .quadro .diferenca td {
            border-bottom: none;
            font-size: 10pt;
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

    <table class="cabecalho-dados">
        <tr>
            <td>Situação</td>
            <td class="r"><b>{{ ['aberto' => 'Aberto', 'pendente' => 'Pendente', 'fechado' => 'Fechado'][$periodo['situacao']] }}</b></td>
        </tr>
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
    <table class="quadro">
        <tr>
            <td><b>Resumo</b></td>
            <td class="r"><b>Entrada</b></td>
            <td class="r"><b>Saída</b></td>
        </tr>
        @if (!$contagem['abertura'])
            <tr>
                <td>Saldo inicial</td>
                <td class="r">{{ $periodo['saldoinicial'] >= 0 ? formataNumero($periodo['saldoinicial']) : '' }}</td>
                <td class="r">{{ $periodo['saldoinicial'] < 0 ? formataNumero(-$periodo['saldoinicial']) : '' }}</td>
            </tr>
        @endif
        @foreach ($quadro['linhas'] as $l)
            @if ($l['tipo'] == 'titulo')
                <tr>
                    <td colspan="3"><b>{{ $l['descricao'] }}</b></td>
                </tr>
            @elseif ($l['tipo'] == 'contagem')
                <tr>
                    <td class="{{ $l['recuo'] ? 'recuo' : '' }}">{{ $l['descricao'] }}</td>
                    <td class="r">
                        {{ $valor($l['entrada']) }}
                        @if ($l['confere'] === false)
                            <br><small>Divergente</small>
                        @endif
                    </td>
                    <td class="r">{{ $valor($l['saida']) }}</td>
                </tr>
            @else
                <tr>
                    <td class="{{ $l['recuo'] ? 'recuo' : '' }}">{{ $l['descricao'] }} ({{ $l['quantidade'] }})</td>
                    <td class="r">{{ formataNumero($l['entrada']) }}</td>
                    <td class="r">{{ formataNumero($l['saida']) }}</td>
                </tr>
            @endif
        @endforeach
        <tr class="total">
            <td>Total</td>
            <td class="r">{{ formataNumero($quadro['totalentrada']) }}</td>
            <td class="r">{{ formataNumero($quadro['totalsaida']) }}</td>
        </tr>
        @if ($final)
            <tr class="diferenca">
                <td>Diferença</td>
                <td colspan="2" class="r">
                    {{ ($final['diferenca'] > 0 ? '+' : '') . formataNumero($final['diferenca']) }}
                    @if (!$naTolerancia)
                        <br><span style="font-size: 7pt; font-weight: normal">Acima Tolerância {{ formataNumero($periodo['tolerancia']) }}</span>
                    @endif
                </td>
            </tr>
        @else
            <tr>
                <td>Saldo final <small>(a contar)</small></td>
                <td colspan="2" class="r">{{ formataNumero($periodo['saldofinal']) }}</td>
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
