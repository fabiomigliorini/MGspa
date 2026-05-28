<h3>
    Agrupamentos em Aberto
</h3>
@foreach ($agrupamentos as $a)
    <h5>
        {{ formataCodigo($a->codtituloagrupamento) }}
        |
        {{ formataData($a->emissao) }}
    </h5>
    <table>
        <thead>
            <tr>
                <th>Número</th>
                <th class="text-center">Vencimento</th>
                <th class="text-right">Valor</th>
                <th class="text-right">Saldo</th>
            </tr>
        </thead>
        @php
            $titulos = $a->TituloS()->orderBy('vencimento')->get();
        @endphp
        <tbody>
            @foreach ($titulos as $t)
                <tr>
                    <td>
                        {{ $t->numero }}
                    </td>
                    <td class="text-center">
                        {{ formataData($t->vencimento) }}
                    </td>
                    <td class="text-right">
                        {{ formataNumero($t->debito + $t->credito) }}
                    </td>
                    <td class="text-right">
                        {{ formataNumero($t->saldo) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        @if ($titulos->count() > 1)
            <tfoot>
                <tr>
                    <th class="text-right" colspan=3>Total</th>
                    <th class="text-right">{{ formataNumero($titulos->sum('saldo')) }}</th>
                </tr>
            </tfoot>
        @endif
    </table>
@endforeach
