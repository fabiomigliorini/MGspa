<script setup>
// Resumo do período da maquineta (TASK-188 M9.8) no formato do relatório da maquininha, dentro do
// card do cabeçalho (como o resumo do período do portador): modalidade (débito, crédito à vista,
// crédito parcelado) → bandeira, com quantidade e valor, para bater o olho com o topo do papel.
// Embaixo do total, o borderô digitado (o Conferir é o FAB da página) e a diferença. Venda
// cancelada no próprio período fica fora, como no papel.
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { formataNumero } from '@components/formatters'
import { useMaquinetaPeriodoStore } from 'src/stores/maquinetaPeriodoStore'

const store = useMaquinetaPeriodoStore()
const { periodo } = storeToRefs(store)

const sistema = computed(() => periodo.value?.sistema)
const informado = computed(() => periodo.value?.totalinformado != null)

const diferenca = computed(() => ({
  quantidade: (periodo.value.quantidadeinformada ?? 0) - (sistema.value?.quantidade ?? 0),
  total:
    Math.round(((periodo.value.totalinformado ?? 0) - (sistema.value?.total ?? 0)) * 100) / 100,
}))
const cor = (v) => (Math.abs(v) < 0.005 ? 'text-green-8' : 'text-red-8')
</script>

<template>
  <q-separator />
  <q-markup-table flat separator="horizontal" wrap-cells>
    <thead>
      <tr>
        <th class="text-left">Resumo</th>
        <th class="text-right" style="width: 64px">Qtd</th>
        <th class="text-right" style="width: 112px">Valor</th>
      </tr>
    </thead>
    <tbody>
      <template v-for="m in sistema?.modalidades ?? []" :key="m.modalidade">
        <tr>
          <td class="text-weight-medium">{{ m.descricao }}</td>
          <td class="text-right text-weight-medium">{{ m.quantidade }}</td>
          <td class="text-right text-weight-medium">{{ formataNumero(m.valor) }}</td>
        </tr>
        <tr v-for="b in m.bandeiras" :key="b.descricao" class="text-grey-8">
          <td class="q-pl-lg">{{ b.descricao }}</td>
          <td class="text-right">{{ b.quantidade }}</td>
          <td class="text-right">{{ formataNumero(b.valor) }}</td>
        </tr>
      </template>
      <tr v-if="sistema?.cancelamentos?.quantidade" class="text-red-8">
        <td>Cancelamento de outro período</td>
        <td></td>
        <td class="text-right">{{ formataNumero(sistema.cancelamentos.valor) }}</td>
      </tr>
      <tr class="bg-grey-2">
        <td class="text-weight-bold">Total do sistema</td>
        <td class="text-right text-weight-bold">{{ sistema?.quantidade ?? 0 }}</td>
        <td class="text-right text-weight-bold">{{ formataNumero(sistema?.total ?? 0) }}</td>
      </tr>
      <tr>
        <td>Borderô</td>
        <td class="text-right">{{ informado ? periodo.quantidadeinformada : '—' }}</td>
        <td class="text-right">{{ informado ? formataNumero(periodo.totalinformado) : '—' }}</td>
      </tr>
      <tr v-if="informado">
        <td>Diferença</td>
        <td class="text-right text-weight-bold" :class="cor(diferenca.quantidade)">
          {{ diferenca.quantidade }}
        </td>
        <td class="text-right text-weight-bold" :class="cor(diferenca.total)">
          {{ formataNumero(diferenca.total) }}
        </td>
      </tr>
    </tbody>
  </q-markup-table>
  <div v-if="sistema?.cancelados" class="text-caption text-grey-7 q-px-md q-pb-md">
    {{ sistema.cancelados }}
    {{ sistema.cancelados > 1 ? 'vendas canceladas' : 'venda cancelada' }} no período, fora da conta
    (como no papel).
  </div>
</template>
