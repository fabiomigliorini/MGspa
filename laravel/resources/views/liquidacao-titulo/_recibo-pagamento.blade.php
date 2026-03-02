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
@endphp

<div class="recibo-outer">

    {{-- Header --}}
    <div class="recibo-header">
        <table>
            <tr>
                <td class="bold">{{ $filialPessoa->fantasia ?? '' }} {{ $filialPessoa->telefone1 ?? '' }}</td>
                <td class="right">Recibo: {{ formataCodigo($liq->codliquidacaotitulo) }}</td>
            </tr>
            <tr>
                <td>Usuario: {{ $liq->UsuarioCriacao->usuario ?? '—' }}</td>
                <td class="right">Data: {{ $liq->criacao?->format('d/m/Y H:i:s') }}</td>
            </tr>
        </table>
    </div>

    {{-- Faixa: RECIBO + VALOR --}}
    <div class="recibo-faixa">
        <div class="titulo-recibo">R E C I B O</div>
        <div class="titulo-valor">Valor R$ {{ formataNumero($totalPago) }}</div>
    </div>

    {{-- Corpo --}}
    <div class="recibo-corpo">
        <p>
            Recebi(emos) de <strong>{{ $filialPessoa->pessoa ?? '—' }}</strong>
            @if ($filialPessoa->cnpj ?? '')
                , CNPJ {{ formataCnpjCpf($filialPessoa->cnpj, $filialPessoa->fisica ?? false) }}
            @endif,
            a importancia de
            <strong>{{ $valorExtenso }}</strong>,
            referente ao pagamento dos titulos abaixo listados:
        </p>

        @foreach ($resumo as $codtitulo => $r)
            @if ($r['total'] > 0)
                @php $rubricas = $r['titulo']->PeriodoColaboradorS->flatMap->ColaboradorRubricaS->where('valorcalculado', '>', 0); @endphp
                @if ($rubricas->isNotEmpty())
                    <table class="itens-table">
                        <thead>
                            <tr>
                                <th>Descricao</th>
                                <th class="r">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rubricas as $rubrica)
                                <tr>
                                    <td>{{ $rubrica->descricao }}</td>
                                    <td class="r">{{ formataNumero($rubrica->valorcalculado) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
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
                        </tbody>
                    </table>
                @endif
            @endif
        @endforeach
    </div>

    {{-- Rodapé --}}
    <div class="recibo-rodape">
        <div class="rodape-data">{{ $dataExtenso }}</div>

        <div class="assin-bloco">
            <div class="assin-linha">
                {{ $pessoa->pessoa ?? '—' }}<br>
                <span class="assin-doc">{{ $pessoa->fisica ?? false ? 'CPF' : 'CNPJ' }}
                    {{ formataCnpjCpf($pessoa->cnpj ?? '', $pessoa->fisica ?? false) }}</span>
            </div>
        </div>
    </div>

</div>
