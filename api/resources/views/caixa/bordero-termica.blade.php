@php
    // Bordero do caixa (M9 doc-3): a contagem do caixa e o que mais ele leva
    // ao escritorio. Sem o dinheiro do sistema nem a diferenca (o gerente
    // confere as cegas) e sem o valor dos cartoes (o gerente digita o
    // bordero da maquineta as cegas).
    $filial = $sessao->Portador->Filial;
    $contado = (float) $sessao->saldofinal;
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
    <b>CONTAGEM DO DINHEIRO</b>
    <table>
        <tr>
            <td></td>
            <td class="r">Abertura</td>
            <td class="r">Fechamento</td>
        </tr>
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
        <tr>
            <td><b>Total</b></td>
            <td class="r"><b>{{ formataNumero($sessao->saldoinicial ?? 0) }}</b></td>
            <td class="r grande">{{ formataNumero($contado) }}</td>
        </tr>
    </table>

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

    @if ($sessao->observacoes)
        <div class="linha"></div>
        {!! nl2br(e($sessao->observacoes)) !!}
    @endif

    <div class="assinatura">{{ $sessao->UsuarioFechamento->usuario ?? 'Caixa' }}</div>
    <div class="assinatura">Gerente</div>
</body>

</html>
