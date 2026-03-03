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
@endphp

<table class="recibo-outer">
<tr><td class="recibo-inner">

    {{-- Header: empresa, recibo, usuario, data --}}
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
        <div class="titulo-valor">Valor R$ {{ formataNumero($liq->credito) }}</div>
    </div>

    {{-- Corpo --}}
    <div class="recibo-corpo">
        <p>
            <strong>Recebemos de</strong> {{ $liq->Pessoa->pessoa ?? '—' }} ({{ formataCodigo($liq->codpessoa) }}),
            {{ $liq->Pessoa->fisica ?? false ? 'CPF' : 'CNPJ' }}
            {{ formataCnpjCpf($liq->Pessoa->cnpj ?? '', $liq->Pessoa->fisica ?? false) }}
            a importancia de <strong>{{ $valorExtenso }}</strong>,
            referente ao pagamento dos titulos abaixo listados:
        </p>

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
                @foreach ($resumo as $codtitulo => $r)
                    @if ($r['total'] > 0)
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

</td></tr>
<tr><td class="recibo-rodape-cell">

    {{-- Rodapé --}}
    <div class="recibo-rodape">
        <div class="rodape-data">{{ $dataExtenso }}</div>

        <div class="assin-bloco">
            <div class="assin-linha">
                {{ $filialPessoa->pessoa ?? '' }}<br>
                <span
                    class="assin-cnpj">{{ formataCnpjCpf($filialPessoa->cnpj ?? '', $filialPessoa->fisica ?? false) }}</span>
            </div>
        </div>
    </div>

</td></tr>
</table>
