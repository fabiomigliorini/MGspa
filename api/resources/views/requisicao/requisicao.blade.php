@php
    // Uma view, duas vias x dois formatos (TASK-55, doc-6):
    //   via requisitante — autorizacao de retirada, com o codigo (entregue ao cliente)
    //   via requisitada  — lista para separar, com a localizacao e o QR; o codigo so'
    //                      mascarado (77XX82), para conferir a caixa com o do cliente;
    //                      na entrega no endereco, quem busca e' o entregador da loja
    //                      que pediu (ela entrega ao cliente), entao vai o aviso no lugar
    //   formato termica (80 mm) ou a5
    $a5 = $formato === 'a5';
    $daRequisitante = $via === 'requisitante';
    $comCodigo = $daRequisitante && in_array($req->destino, ['R', 'E']);
    $entregador = $req->destino === 'E';
    $codigoMascarado = ($daRequisitante || $entregador) ? null : \Mg\Requisicao\RequisicaoEntregaService::codigoRetiradaMascarado($req);
    $lojaRetirada = $req->EstoqueLocalRequisitada;
    $pessoaRetirada = $lojaRetirada?->Filial?->Pessoa;
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: {{ $a5 ? '1cm' : '0.3cm' }};
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: {{ $a5 ? '10pt' : '8pt' }};
        }

        .titulo {
            font-size: {{ $a5 ? '16pt' : '12pt' }};
            font-weight: bold;
        }

        .faixa {
            background-color: black;
            color: white;
            text-align: center;
            padding: 3px;
            font-weight: bold;
        }

        .codigo {
            font-size: {{ $a5 ? '34pt' : '26pt' }};
            font-weight: bold;
            letter-spacing: 4px;
            text-align: center;
            border: 2px solid black;
            padding: 4px;
            margin: 6px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            vertical-align: top;
            padding: 2px 1px;
        }

        .itens td {
            border-bottom: 1px dotted #888;
        }

        /* o item ocupa duas linhas: a divisoria fica so' embaixo da segunda */
        .itens .item td {
            border-bottom: none;
            padding-bottom: 0;
        }

        .itens .marca td {
            padding-top: 6px;
            font-weight: bold;
            border-bottom: 1px solid black;
        }

        .direita {
            text-align: right;
        }

        .cinza {
            color: #555;
        }

        .bloco {
            margin-top: 6px;
        }
    </style>
</head>

<body>
    <table>
        <tr>
            <td style="width: {{ $a5 ? '3cm' : '2cm' }};">
                <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('MGPapelariaLogoSeloPretoBranco.jpeg'))) }}"
                    style="width: 100%;">
            </td>
            <td style="text-align: center;">
                <div class="titulo">Requisição {{ formataCodigo($req->codrequisicao) }}</div>
                {{ $req->criacao->format('d/m/Y H:i') }}
                <br>
                {{ $req->EstoqueLocalRequisitante->sigla }} para {{ $lojaRetirada?->sigla ?? 'Compras' }}
            </td>
        </tr>
    </table>

    <div class="faixa bloco">
        {{ $daRequisitante ? ($comCodigo ? 'AUTORIZAÇÃO DE RETIRADA' : 'VIA DA LOJA QUE PEDE') : 'VIA DE SEPARAÇÃO' }}
    </div>

    <div class="bloco">
        <b>Pede:</b> {{ $req->EstoqueLocalRequisitante->estoquelocal }}<br>
        <b>Atende:</b> {{ $lojaRetirada?->estoquelocal ?? 'Compras' }}<br>
        <b>Destino:</b> {{ \Mg\Requisicao\Requisicao::DESTINOS[$req->destino] }}<br>
        <b>Prioridade:</b> {{ \Mg\Requisicao\Requisicao::PRIORIDADES[$req->prioridade] ?? '' }}
    </div>

    @if ($req->nomes || $req->endereco)
        <div class="bloco">
            @foreach ($req->nomes as $i => $nome)
                <b>Cliente:</b> {{ $nome }}
                @if (!empty($req->telefones[$i]))
                    &middot; {{ formataTelefone($req->telefones[$i]) }}
                @endif
                <br>
            @endforeach
            @if ($req->endereco)
                <b>Endereço:</b> {{ $req->endereco }}
            @endif
        </div>
    @endif

    @if ($comCodigo)
        <div class="bloco">Código de retirada:</div>
        <div class="codigo">{{ $req->codigoretirada }}</div>
        @if ($req->destino === 'R' && $pessoaRetirada)
            <div>
                Retirar em <b>{{ $lojaRetirada->estoquelocal }}</b>:
                {{ $pessoaRetirada->endereco }}, {{ $pessoaRetirada->numero }}
                @if ($pessoaRetirada->complemento)
                    - {{ $pessoaRetirada->complemento }}
                @endif
                - {{ $pessoaRetirada->bairro }}
                @if ($pessoaRetirada->telefone1)
                    &middot; {{ $pessoaRetirada->telefone1 }}
                @endif
            </div>
        @endif
        @if ($entregador)
            <div class="bloco cinza">Informe este código ao entregador na entrega.</div>
        @else
            <div class="bloco cinza">Apresente este comprovante e o código para retirar a mercadoria.</div>
        @endif
    @endif

    @if (!$daRequisitante && $entregador)
        <div class="bloco">O entregador de <b>{{ $req->EstoqueLocalRequisitante->estoquelocal }}</b> vem buscar.</div>
    @endif

    @if ($codigoMascarado)
        <div class="bloco">Código de retirada (confira com o do cliente):</div>
        <div class="codigo">{{ $codigoMascarado }}</div>
    @endif

    {{-- agrupados por marca (o deposito guarda assim); no A5, com a foto --}}
    <table class="itens bloco">
        <tr>
            <td colspan="{{ $a5 ? 2 : 1 }}"><b>Produto</b></td>
            <td class="direita"><b>Qtd</b></td>
        </tr>
        @foreach ($itens as $marca => $daMarca)
            <tr class="marca">
                <td colspan="{{ $a5 ? 3 : 2 }}">{{ $marca }}</td>
            </tr>
            @foreach ($daMarca as $item)
                {{-- 1a linha: descricao e quantidade; 2a: barras (e localizacao) e unidade --}}
                <tr class="item">
                    @if ($a5)
                        <td style="width: 11mm;" rowspan="2">
                            @if (!empty($fotos[$item['imagem']]))
                                <img src="{{ $fotos[$item['imagem']]['src'] }}"
                                    style="width: {{ $fotos[$item['imagem']]['w'] }}mm; height: {{ $fotos[$item['imagem']]['h'] }}mm;">
                            @endif
                        </td>
                    @endif
                    <td>{{ $item['descricao'] }}</td>
                    <td class="direita"><b>{{ $item['quantidade'] }}</b></td>
                </tr>
                <tr>
                    <td class="cinza">
                        {{ $item['barras'] }}
                        @if (!$daRequisitante && $item['localizacao'])
                            &middot; {{ $item['localizacao'] }}
                        @endif
                    </td>
                    <td class="direita" style="white-space: nowrap;">{{ $item['unidade'] }}</td>
                </tr>
            @endforeach
        @endforeach
    </table>

    @if ($req->observacoes)
        <div class="bloco"><b>Observações:</b> {{ $req->observacoes }}</div>
    @endif

    @if (!$daRequisitante && $qrcode)
        <div class="bloco" style="text-align: center;">
            <img src="{{ $qrcode }}" style="width: {{ $a5 ? '4cm' : '3.5cm' }};">
            <br>
            <span class="cinza">Bipe no PDV para ligar o negócio a esta requisição</span>
        </div>
    @endif
</body>

</html>
