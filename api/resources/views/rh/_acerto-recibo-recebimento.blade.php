@php
    // Recibo "empresa RECEBE do colaborador" (assina a EMPRESA/filial).
    // Fonte: evento de acerto ($ev). Os itens sao os vales/titulos a debito quitados
    // neste acerto (movimentos de credito, tipo 601). Corresponde ao antigo
    // liquidacao-titulo/_recibo-recebimento, agora dirigido pela tabela de eventos.
    $pc      = $ev->PeriodoColaborador;
    $col     = $pc?->Colaborador;
    $pessoa  = $col?->Pessoa; // colaborador (de quem recebemos)
    $filialP = $col?->Filial?->Pessoa; // empresa/filial (assina)

    $cidadeEstado = '';
    if ($filialP?->Cidade) {
        $cidadeEstado = $filialP->Cidade->cidade . '/' . ($filialP->Cidade->Estado->sigla ?? '');
    }

    // Um item por titulo, com o valor liquido baixado (credito - debito). Agrupar por
    // titulo neutraliza eventuais ajustes de toggle (movimentos 930) de acertos reativados.
    $resumo = [];
    foreach ($ev->MovimentoTituloS as $mov) {
        $ct = $mov->codtitulo;
        if (!isset($resumo[$ct])) {
            $resumo[$ct] = ['titulo' => $mov->Titulo, 'valor' => 0];
        }
        $resumo[$ct]['valor'] += ($mov->credito ?? 0) - ($mov->debito ?? 0);
    }
    $linhas = array_values(array_filter($resumo, fn ($r) => round($r['valor'], 2) > 0));
    $qtdeLinhas = count($linhas);
    $totalGeral = round(array_sum(array_column($linhas, 'valor')), 2);

    $dt = $ev->data ?? $ev->criacao ?? now();
    $dataExtenso = $cidadeEstado . ', ' . formataDataPorExtenso($dt) . '.';
    $valorExtenso = formataValorPorExtenso((float) $totalGeral, true);

    // Paginacao feita aqui, e nao pelo Dompdf: ele nao quebra tabela aninhada dentro
    // de celula de tabela e descarta as linhas que sobram (FrameDecorator/Page.php:464).
    $itens = $linhas;
    $itens[] = ['totalizador' => true];
    $paginas = [];
    $resto = $itens;
    while (!empty($resto)) {
        $cabem = empty($paginas) ? 22 : 27;
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

                {{-- Header: empresa, recibo, usuario, data --}}
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
                        <div class="titulo-recibo">R E C I B O</div>
                        <div class="titulo-valor">Valor R$ {{ formataNumero($totalGeral) }}</div>
                    </div>
                @endif

                {{-- Corpo --}}
                <div class="recibo-corpo">
                    @if ($numPagina == 1)
                        <p>
                            <strong>Recebemos de</strong> {{ $pessoa->pessoa ?? '—' }}
                            ({{ formataCodigo($pessoa->codpessoa ?? 0) }}),
                            {{ $pessoa->fisica ?? false ? 'CPF' : 'CNPJ' }}
                            {{ formataCnpjCpf($pessoa->cnpj ?? '', $pessoa->fisica ?? false) }}
                            a importancia de <strong>{{ $valorExtenso }}</strong>,
                            referente ao pagamento dos titulos abaixo listados:
                        </p>
                    @endif

                    <table class="itens-table">
                        <thead>
                            <tr>
                                <th>Numero</th>
                                <th>Emissao</th>
                                <th>Vencimento</th>
                                <th class="r">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($linhasPagina as $r)
                                @if (!empty($r['totalizador']))
                                    <tr class="linha-total">
                                        <td colspan="3">TOTAL ({{ $qtdeLinhas }}
                                            {{ $qtdeLinhas == 1 ? 'titulo' : 'titulos' }})</td>
                                        <td class="r">{{ formataNumero($totalGeral) }}</td>
                                    </tr>
                                @else
                                    <tr>
                                        <td>{{ $r['titulo']?->numero }}</td>
                                        <td>{{ $r['titulo']?->emissao?->format('d/m/Y') }}</td>
                                        <td>{{ $r['titulo']?->vencimento?->format('d/m/Y') }}</td>
                                        <td class="r bold">{{ formataNumero($r['valor']) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @unless ($ultimaPagina)
                    <div class="continua">continua na proxima pagina &gt;&gt;</div>
                @endunless

            </td>
        </tr>
        @if ($ultimaPagina)
            <tr>
                <td class="recibo-rodape-cell">

                    {{-- Rodapé: assina a EMPRESA (recebemos do colaborador) --}}
                    <div class="recibo-rodape">
                        <div class="assin-bloco">
                            <div class="assin-linha">
                                {{ $filialP->pessoa ?? '' }}<br>
                                <span
                                    class="assin-cnpj">{{ formataCnpjCpf($filialP->cnpj ?? '', $filialP->fisica ?? false) }}</span>
                            </div>
                        </div>
                        <div class="rodape-data">{{ $dataExtenso }}</div>
                    </div>

                </td>
            </tr>
        @endif
    </table>
@endforeach
