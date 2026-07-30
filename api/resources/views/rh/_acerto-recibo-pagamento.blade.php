@php
    // Recibo "empresa PAGA o colaborador" (assina o COLABORADOR).
    // Fonte: evento de acerto ($ev). Os itens sao o beneficio (rubricas) entregue neste
    // acerto e, se houver, titulos a credito pagos. Corresponde ao antigo
    // liquidacao-titulo/_recibo-pagamento, agora dirigido pela tabela de eventos.
    $pc = $ev->PeriodoColaborador;
    $col = $pc?->Colaborador;
    $pessoa = $col?->Pessoa; // colaborador (assina, recebeu da empresa)
    $filialP = $col?->Filial?->Pessoa; // empresa/filial (quem pagou)

    $cidadeEstado = '';
    if ($filialP?->Cidade) {
        $cidadeEstado = $filialP->Cidade->cidade . '/' . ($filialP->Cidade->Estado->sigla ?? '');
    }

    $totalPago = round((float) $ev->rubricas + (float) $ev->creditos, 2);

    // Beneficio: detalhar as rubricas do colaborador quando este acerto entrega o
    // beneficio inteiro (caso comum). Se a entrega foi parcial (beneficio dividido em
    // varios acertos), a quebra completa enganaria o total -> linha unica.
    $rubricas = $pc?->ColaboradorRubricaS?->where('valorcalculado', '>', 0) ?? collect();
    $beneficioTotal = round((float) $rubricas->sum('valorcalculado'), 2);
    $entregaInteira = $beneficioTotal > 0 && round((float) $ev->rubricas, 2) == $beneficioTotal;

    $linhas = [];
    if ((float) $ev->rubricas > 0) {
        if ($entregaInteira) {
            foreach ($rubricas as $rubrica) {
                $linhas[] = ['descricao' => $rubrica->descricao, 'valor' => (float) $rubrica->valorcalculado];
            }
        } else {
            $linhas[] = ['descricao' => 'Benefício (' . $ev->forma_descricao . ')', 'valor' => (float) $ev->rubricas];
        }
    }

    // Titulos a credito pagos neste acerto (movimentos de debito). Raro no RH; agrupar
    // por titulo com o liquido debito - credito neutraliza ajustes de toggle (930).
    if ((float) $ev->creditos > 0) {
        $resumoCred = [];
        foreach ($ev->MovimentoTituloS as $mov) {
            $ct = $mov->codtitulo;
            if (!isset($resumoCred[$ct])) {
                $resumoCred[$ct] = ['titulo' => $mov->Titulo, 'valor' => 0];
            }
            $resumoCred[$ct]['valor'] += ($mov->debito ?? 0) - ($mov->credito ?? 0);
        }
        foreach ($resumoCred as $r) {
            if (round($r['valor'], 2) > 0) {
                $linhas[] = ['descricao' => 'Título ' . ($r['titulo']?->numero ?? ''), 'valor' => (float) $r['valor']];
            }
        }
    }

    $qtdeLinhas = count($linhas);

    $dt = $ev->data ?? ($ev->criacao ?? now());
    $dataExtenso = $cidadeEstado . ', ' . formataDataPorExtenso($dt) . '.';
    $valorExtenso = formataValorPorExtenso((float) $totalPago, true);

    // Paginacao feita aqui, e nao pelo Dompdf: ele nao quebra tabela aninhada dentro
    // de celula de tabela e descarta as linhas que sobram (FrameDecorator/Page.php:464).
    $itens = $linhas;
    $itens[] = ['totalizador' => true];
    $paginas = [];
    $resto = $itens;
    while (!empty($resto)) {
        $cabem = empty($paginas) ? 11 : 15;
        $paginas[] = array_slice($resto, 0, $cabem);
        $resto = array_slice($resto, $cabem);
    }
    // nunca deixar a ultima pagina so com o TOTAL, sem esvaziar a anterior
    while (
        count($paginas) > 1 &&
        count($paginas[count($paginas) - 1]) < 3 &&
        count($paginas[count($paginas) - 2]) > 1
    ) {
        $ultima = array_pop($paginas);
        array_unshift($ultima, array_pop($paginas[count($paginas) - 1]));
        $paginas[] = $ultima;
    }
@endphp

@foreach ($paginas as $linhasPagina)
    @php
        // $loop e sombreado pelos foreach internos
        $ultimaPagina = $loop->last;
        $numPagina = $loop->iteration;
    @endphp
    <table class="recibo-outer">
        <tr>
            <td class="recibo-inner">

                {{-- Header --}}
                <div class="recibo-header">
                    <table>
                        <tr>
                            <td class="bold">{{ $filialP->fantasia ?? '' }} {{ $filialP->telefone1 ?? '' }}</td>
                            <td class="right">Recibo: {{ formataCodigo($ev->codperiodocolaboradoracerto) }}</td>
                        </tr>
                        <tr>
                            <td>Usuario: {{ $ev->usuariocriacao ?? '—' }}</td>
                            <td class="right">Data: {{ $ev->criacao?->format('d/m/Y H:i:s') }}
                                @if (count($paginas) > 1)
                                    &nbsp;&nbsp;Pag. {{ $numPagina }}/{{ count($paginas) }}
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- Faixa e texto do recibo: so na primeira pagina --}}
                @if ($numPagina == 1)
                    <div class="recibo-faixa">
                        <table class="faixa-table">
                            <tr>
                                <td class="titulo-recibo">RECIBO</td>
                                <td class="titulo-valor">R$ {{ formataNumero($totalPago) }}</td>
                            </tr>
                        </table>
                    </div>
                @endif

                {{-- Corpo --}}
                <div class="recibo-corpo">
                    @if ($numPagina == 1)
                        <p>
                            Recebi(emos) de <strong>{{ $filialP->pessoa ?? '—' }}</strong>
                            @if ($filialP->cnpj ?? '')
                                , CNPJ {{ formataCnpjCpf($filialP->cnpj, $filialP->fisica ?? false) }}
                            @endif,
                            a importancia de <strong>{{ $valorExtenso }}</strong>,
                            referente a' bonificacao abaixo discriminada:
                        </p>
                    @endif

                    <table class="itens-table">
                        <thead>
                            <tr>
                                <th>Descricao</th>
                                <th class="r">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($linhasPagina as $l)
                                @if (!empty($l['totalizador']))
                                    <tr class="linha-total">
                                        <td>TOTAL ({{ $qtdeLinhas }} {{ $qtdeLinhas == 1 ? 'item' : 'itens' }})</td>
                                        <td class="r">{{ formataNumero($totalPago) }}</td>
                                    </tr>
                                @else
                                    <tr>
                                        <td class="desc">{{ $l['descricao'] }}</td>
                                        <td class="r">{{ formataNumero($l['valor']) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>

                    @if ($ultimaPagina)
                        <div class="corpo-data">{{ $dataExtenso }}</div>
                    @endif
                </div>

                @unless ($ultimaPagina)
                    <div class="continua">continua na proxima pagina &gt;&gt;</div>
                @endunless

            </td>
        </tr>
        @if ($ultimaPagina)
            <tr>
                <td class="recibo-rodape-cell">

                    {{-- Rodapé: assina o COLABORADOR (recebeu da empresa) --}}
                    <div class="recibo-rodape">
                        <div class="assin-bloco">
                            <div class="assin-linha">
                                {{ $pessoa->pessoa ?? '—' }}<br>
                                <span class="assin-doc">{{ $pessoa->fisica ?? false ? 'CPF' : 'CNPJ' }}
                                    {{ formataCnpjCpf($pessoa->cnpj ?? '', $pessoa->fisica ?? false) }}</span>
                            </div>
                        </div>
                    </div>

                </td>
            </tr>
        @endif
    </table>
@endforeach
