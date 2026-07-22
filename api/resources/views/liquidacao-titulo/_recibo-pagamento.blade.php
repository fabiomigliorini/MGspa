@php
    use Mg\Titulo\MovimentoTituloService;

    $filialPessoa = $liq->MovimentoTituloS->first()?->Titulo?->Filial?->Pessoa;
    $cidadeEstado = '';
    if ($filialPessoa?->Cidade) {
        $cidadeEstado = $filialPessoa->Cidade->cidade . '/' . ($filialPessoa->Cidade->Estado->sigla ?? '');
    }

    // Resumo por título (lado débito — empresa pagando)
    $resumo = [];
    foreach ($liq->MovimentoTituloS as $mov) {
        if ($mov->debito <= 0) {
            continue;
        }
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
                $resumo[$mov->codtitulo]['juros'] += $mov->debito;
                break;
            case MovimentoTituloService::TIPO_MULTA:
                $resumo[$mov->codtitulo]['multa'] += $mov->debito;
                break;
            case MovimentoTituloService::TIPO_DESCONTO:
                $resumo[$mov->codtitulo]['desconto'] += $mov->credito;
                break;
            default:
                $resumo[$mov->codtitulo]['total'] += $mov->debito;
                break;
        }
    }
    foreach ($resumo as &$d) {
        $d['principal'] = $d['total'] - $d['juros'] - $d['multa'] + $d['desconto'];
    }
    unset($d);

    $totalPago = collect($resumo)->sum('total');

    $dt = $liq->transacao ?? now();
    $dataExtenso = $cidadeEstado . ', ' . formataDataPorExtenso($dt) . '.';
    $valorExtenso = formataValorPorExtenso($totalPago, true);

    $pessoa = $liq->Pessoa;

    // Titulos de folha sao detalhados pelas rubricas do colaborador; os demais, pelo titulo.
    $linhas = [];
    foreach ($resumo as $r) {
        if ($r['total'] <= 0) {
            continue;
        }
        $rubricas = $r['titulo']->PeriodoColaboradorS->flatMap->ColaboradorRubricaS->where('valorcalculado', '>', 0);
        if ($rubricas->isNotEmpty()) {
            foreach ($rubricas as $rubrica) {
                $linhas[] = ['tipo' => 'rubrica', 'rubrica' => $rubrica];
            }
        } else {
            $linhas[] = ['tipo' => 'titulo', 'resumo' => $r];
        }
    }

    // Paginacao feita aqui, e nao pelo Dompdf: ele nao quebra tabela aninhada dentro
    // de celula de tabela e descarta as linhas que sobram (FrameDecorator/Page.php:464).
    $qtdeLinhas = count($linhas);
    $linhas[] = ['tipo' => 'totalizador'];
    // A faixa "RECIBO / Valor / Recebi(emos) de..." so sai na primeira pagina,
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
        $rubricasPagina = array_filter($linhasPagina, fn($l) => $l['tipo'] == 'rubrica');
        $titulosPagina = array_filter($linhasPagina, fn($l) => $l['tipo'] == 'titulo');
        $temTotal = collect($linhasPagina)->contains('tipo', 'totalizador');
    @endphp
    <table class="recibo-outer">
    <tr>
        <td class="recibo-inner">

            {{-- Header --}}
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
                    <div class="titulo-valor">Valor R$ {{ formataNumero($totalPago) }}</div>
                </div>
            @endif

            {{-- Corpo --}}
            <div class="recibo-corpo">
                @if ($numPagina == 1)
                    <p>
                        Recebi(emos) de <strong>{{ $filialPessoa->pessoa ?? '—' }}</strong>
                        @if ($filialPessoa->cnpj ?? '')
                            , CNPJ {{ formataCnpjCpf($filialPessoa->cnpj, $filialPessoa->fisica ?? false) }}
                        @endif,
                        a importancia de
                        <strong>{{ $valorExtenso }}</strong>,
                        referente ao pagamento dos titulos abaixo listados:
                    </p>
                @endif

                @if (!empty($rubricasPagina))
                    <table class="itens-table">
                        <thead>
                            <tr>
                                <th>Descricao</th>
                                <th class="r">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rubricasPagina as $l)
                                <tr>
                                    <td class="desc">{{ $l['rubrica']->descricao }}</td>
                                    <td class="r">{{ formataNumero($l['rubrica']->valorcalculado) }}</td>
                                </tr>
                            @endforeach
                            @if ($temTotal && empty($titulosPagina))
                                <tr class="linha-total">
                                    <td>TOTAL ({{ $qtdeLinhas }} {{ $qtdeLinhas == 1 ? 'item' : 'itens' }})</td>
                                    <td class="r">{{ formataNumero($totalPago) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                @endif
                @if (!empty($titulosPagina) || ($temTotal && empty($rubricasPagina)))
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
                            @foreach ($titulosPagina as $l)
                                @php $r = $l['resumo']; @endphp
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
                            @endforeach
                            @if ($temTotal)
                                <tr class="linha-total">
                                    <td colspan="7">TOTAL ({{ $qtdeLinhas }}
                                        {{ $qtdeLinhas == 1 ? 'item' : 'itens' }})</td>
                                    <td class="r">{{ formataNumero($totalPago) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
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

                {{-- Rodapé --}}
                <div class="recibo-rodape">
                    <div class="assin-bloco">
                        <div class="assin-linha">
                            {{ $pessoa->pessoa ?? '—' }}<br>
                            <span class="assin-doc">{{ $pessoa->fisica ?? false ? 'CPF' : 'CNPJ' }}
                                {{ formataCnpjCpf($pessoa->cnpj ?? '', $pessoa->fisica ?? false) }}</span>
                        </div>
                    </div>
                    <div class="rodape-data">{{ $dataExtenso }}</div>
                </div>

            </td>
        </tr>
    @endif
</table>
@endforeach
