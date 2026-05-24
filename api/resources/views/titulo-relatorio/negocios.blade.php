@php
    $saldos = [];
    $total = 0;
@endphp
<h3>
    Negócios em Aberto
</h3>
@foreach ($negocios as $n)
    <table>
        <thead>
            <tr>
                <td style="width: 130mm; border: none !important; vertical-align: top; padding: 0mm;">
                    <h4 style="margin-top: 0mm">
                        {{ $n->NaturezaOperacao->naturezaoperacao }}
                        {{ formataCodigo($n->codnegocio) }}
                        |
                        {{ formataData($n->lancamento) }}
                    </h4>
                    <table>
                        <thead>
                            <tr>
                                <th class="text-right">Barras</th>
                                <th>Produto</th>
                                <th class="text-right">Quantidade</th>
                                <th class="text-right">Preço</th>
                                <th class="text-right">Total</th>
                                @if (!empty($n->valordesconto))
                                    <th class="text-right">Desconto</th>
                                    <th class="text-right">Líquido</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($n->NegocioProdutoBarraS()->whereNull('inativo')->orderBy('alteracao', 'desc')->get() as $npb)
                                <tr>
                                    <td class="text-right">
                                        {{ $npb->ProdutoBarra->barras }}
                                    </td>
                                    <td>
                                        {{ $npb->ProdutoBarra->Produto->produto }}
                                        {{ $npb->ProdutoBarra->ProdutoVariacao->variacao }}
                                        @if (!empty($npb->ProdutoBarra->codprodutoembalagem))
                                            C/{{ $npb->ProdutoBarra->ProdutoEmbalagem->quantidade }}
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        {{ formataNumero($npb->quantidade) }}
                                    </td>
                                    <td class="text-right">
                                        {{ formataNumero($npb->valorunitario) }}
                                    </td>
                                    <td class="text-right">
                                        {{ formataNumero($npb->valorprodutos) }}
                                    </td>
                                    @if (!empty($n->valordesconto))
                                        <td class="text-right">
                                            {{ formataNumero($npb->valordesconto) }}
                                        </td>
                                        <td class="text-right">
                                            {{ formataNumero($npb->valorprodutos - $npb->valordesconto) }}
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-right">Total</th>
                                <th class="text-right">{{ formataNumero($n->valorprodutos) }}</th>
                                @if (!empty($n->valordesconto))
                                    <th class="text-right">{{ formataNumero($n->valordesconto) }}</th>
                                    <th class="text-right">
                                        {{ formataNumero($n->valorprodutos - $n->valordesconto) }}</th>
                                @endif
                            </tr>
                            @if (!empty($n->valorseguro))
                                <tr>
                                    <th colspan="4" class="text-right">Seguro</th>
                                    @if (!empty($n->valordesconto))
                                        <th colspan="2">&nbsp;</th>
                                    @endif
                                    <th class="text-right">{{ formataNumero($n->valorseguro) }}</th>
                                </tr>
                            @endif
                            @if (!empty($n->valorfrete))
                                <tr>
                                    <th colspan="4" class="text-right">Frete</th>
                                    @if (!empty($n->valordesconto))
                                        <th colspan="2">&nbsp;</th>
                                    @endif
                                    <th class="text-right">{{ formataNumero($n->valorfrete) }}</th>
                                </tr>
                            @endif
                            @if (!empty($n->valoroutras))
                                <tr>
                                    <th colspan="4" class="text-right">Outras</th>
                                    @if (!empty($n->valordesconto))
                                        <th colspan="2">&nbsp;</th>
                                    @endif
                                    <th class="text-right">{{ formataNumero($n->valoroutras) }}</th>
                                </tr>
                            @endif
                            @if ($n->valortotal != $n->valorprodutos - $n->valordesconto)
                                <tr>
                                    <th colspan="4" class="text-right">Líquido</th>
                                    @if (!empty($n->valordesconto))
                                        <th colspan="2">&nbsp;</th>
                                    @endif
                                    <th class="text-right">{{ formataNumero($n->valortotal) }}</th>
                                </tr>
                            @endif
                            @foreach ($n->NegocioFormaPagamentoS as $nfp)
                                @foreach ($nfp->TituloS()->orderBy('vencimento')->get() as $t)
                                    <tr>
                                        <th colspan="4" class="text-right">
                                            Saldo Vencimento {{ formataData($t->vencimento) }}
                                        </th>
                                        @if (!empty($n->valordesconto))
                                            <th colspan="2">&nbsp;</th>
                                        @endif
                                        <th class="text-right">
                                            {{ formataNumero($t->saldo) }}
                                        </th>
                                    </tr>
                                    @php
                                        $v = $t->vencimento->format('Y-m-d');
                                        if (!isset($saldos[$v])) {
                                            $saldos[$v] = $t->saldo;
                                        } else {
                                            $saldos[$v] += $t->saldo;
                                        }
                                        $total += $t->saldo;
                                    @endphp
                                @endforeach
                            @endforeach
                        </tfoot>
                    </table>
                    @if ($n->observacoes)
                        <div class="observacoes">{{ $n->observacoes }}</div>
                    @endif
                </td>
                <td style="width: 50mm; border: none !important; vertical-align: top; padding: 0mm; padding-left: 3mm;">
                    @if (isset($n->anexos['confissao']))
                        @foreach ($n->anexos['confissao'] as $c)
                            {{-- @php
                                    dd($c);
                                @endphp --}}
                            <img src="{{ $c }}" style="max-width: 100%">
                        @endforeach
                    @endif
                </td>
            </tr>
        </thead>
    </table>
    <br>
@endforeach

@php
    // dd($total);
    // dd($saldos);
@endphp

@if (count($negocios) > 1)
    <table style="width: 40mm;">
        <thead>
            <tr>
                <th colspan="2">Resumo Negócios</th>
            </tr>
        </thead>
        <tbody>
            @php
                ksort($saldos);
            @endphp
            @foreach ($saldos as $data => $saldo)
                <tr>
                    <td class="text-center">
                        {{ formataData($data) }}
                    </td>
                    <td class="text-right">
                        {{ formataNumero($saldo) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        @if (count($saldos) > 1)
            <tfoot>
                <tr>
                    <th class="text-center">
                        Total
                    </th>
                    <th class="text-right">
                        {{ formataNumero($total) }}
                    </th>
                </tr>
            </tfoot>
        @endif
    </table>
@endif
