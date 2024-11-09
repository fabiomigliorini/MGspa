<h3>
    Outros Títulos em Aberto
</h3>
<table>
    <thead>
        <tr>
            <th>Tipo</th>
            <th>Conta</th>
            <th>Número</th>
            <th>Observações</th>
            <th class="text-center">Vencimento</th>
            <th class="text-right">Valor</th>
            <th class="text-right">Saldo</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($titulos as $t)
            <tr>
                <td>
                    {{ $t->TipoTitulo->tipotitulo }}
                </td>
                <td>
                    {{ $t->ContaContabil->contacontabil }}
                </td>
                <td>
                    {{ $t->numero }}
                </td>
                <td>
                    {{ $t->observacoes }}
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
                <th colspan=6>Total</th>
                <th>{{ formataNumero($titulos->sum('saldo')) }}</th>
            </tr>
        </tfoot>
    @endif
</table>
