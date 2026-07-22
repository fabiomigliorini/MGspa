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

    // Paginacao feita aqui, e nao pelo Dompdf: ele nao quebra tabela aninhada dentro
    // de celula de tabela e descarta as linhas que sobram (FrameDecorator/Page.php:464).
    $linhas = array_values(array_filter($resumo, fn($r) => $r['total'] > 0));
    $qtdeLinhas = count($linhas);
    $totalGeral = array_sum(array_column($linhas, 'total'));
    $linhas[] = ['totalizador' => true];
    // A faixa "RECIBO / Valor / Recebemos de..." so sai na primeira pagina,
    // entao a partir da segunda cabem mais linhas na caixa de 108mm.
    $paginas = [];
    $resto = $linhas;
    while (!empty($resto)) {
        $cabem = empty($paginas) ? $linhasPorPagina ?? 22 : $linhasPorPaginaDemais ?? 27;
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
                        <td class="bold">{{ $filialPessoa->fantasia ?? '' }} {{ $filialPessoa->telefone1 ?? '' }}</td>
                        <td class="right">Recibo: {{ formataCodigo($liq->codliquidacaotitulo) }}</td>
                    </tr>
                    <tr>
                        {{-- usuariocriacao (accessor) e nao UsuarioCriacao: a propriedade
                             com o nome da relacao cai no accessor e devolve string --}}
                        <td>Usuario: {{ $liq->usuariocriacao ?? '—' }}</td>
                        <td class="right">Data: {{ $liq->criacao?->format('d/m/Y H:i:s') }}
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
                    <div class="titulo-valor">Valor R$ {{ formataNumero($liq->credito) }}</div>
                </div>
            @endif

            {{-- Corpo --}}
            <div class="recibo-corpo">
                @if ($numPagina == 1)
                    <p>
                        <strong>Recebemos de</strong> {{ $liq->Pessoa->pessoa ?? '—' }}
                        ({{ formataCodigo($liq->codpessoa) }}),
                        {{ $liq->Pessoa->fisica ?? false ? 'CPF' : 'CNPJ' }}
                        {{ formataCnpjCpf($liq->Pessoa->cnpj ?? '', $liq->Pessoa->fisica ?? false) }}
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
                            <th class="r">Valor Original</th>
                            <th class="r">Pagamento</th>
                            <th class="r">Juros</th>
                            <th class="r">Desconto</th>
                            <th class="r">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($linhasPagina as $r)
                            @if (!empty($r['totalizador']))
                                <tr class="linha-total">
                                    <td colspan="7">TOTAL ({{ $qtdeLinhas }}
                                        {{ $qtdeLinhas == 1 ? 'titulo' : 'titulos' }})</td>
                                    <td class="r">{{ formataNumero($totalGeral) }}</td>
                                </tr>
                            @else
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

            @unless ($ultimaPagina)
                <div class="continua">continua na proxima pagina &gt;&gt;</div>
            @endunless

        </td>
    </tr>
    @if ($ultimaPagina)
        <tr>
            <td class="recibo-rodape-cell">

                {{-- Rodapé --}}
                <div class="recibo-rodape">
                    <div class="assin-bloco">
                        <div class="assin-linha">
                            {{ $filialPessoa->pessoa ?? '' }}<br>
                            <span
                                class="assin-cnpj">{{ formataCnpjCpf($filialPessoa->cnpj ?? '', $filialPessoa->fisica ?? false) }}</span>
                        </div>
                    </div>
                    <div class="rodape-data">{{ $dataExtenso }}</div>
                </div>

            </td>
        </tr>
    @endif
</table>
@endforeach
