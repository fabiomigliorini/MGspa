<script setup>
// Resumo do período da maquineta (TASK-188 M9.8) no formato do relatório da maquininha, dentro do
// card do cabeçalho (como o resumo do período do portador): modalidade (débito, crédito à vista,
// crédito parcelado) → bandeira, com quantidade e valor, para bater o olho com o topo do papel.
// Embaixo, o Total: verde se bate com o borderô digitado (o Conferir é o FAB da página), preto se
// não; com diferença, o Informado e a Diferença. Venda cancelada no próprio período fica fora, como
// no papel. Clicar na modalidade, na bandeira ou no cancelamento filtra o detalhe; de novo, ou no
// Total, tira o filtro.
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { formataNumero } from '@components/formatters'
import { useMaquinetaPeriodoStore } from 'src/stores/maquinetaPeriodoStore'

const store = useMaquinetaPeriodoStore()
const { periodo, filtro } = storeToRefs(store)

const sistema = computed(() => periodo.value?.sistema)
const informado = computed(() => periodo.value?.totalinformado != null)

const diferenca = computed(() => ({
  quantidade: (periodo.value.quantidadeinformada ?? 0) - (sistema.value?.quantidade ?? 0),
  total:
    Math.round(((periodo.value.totalinformado ?? 0) - (sistema.value?.total ?? 0)) * 100) / 100,
}))
const zero = (v) => Math.abs(v) < 0.005
const cor = (v) => (zero(v) ? 'text-green-8' : '')
const temDiferenca = computed(
  () => informado.value && !(zero(diferenca.value.quantidade) && zero(diferenca.value.total)),
)

const porBandeira = (m, b) => ({
  chave: `${m.modalidade}|${b.bandeira}`,
  descricao: `${m.descricao} · ${b.descricao}`,
  modalidade: m.modalidade,
  bandeira: b.bandeira,
})
const porModalidade = (m) => ({
  chave: m.modalidade,
  descricao: m.descricao,
  modalidade: m.modalidade,
})
const CANCELAMENTOS = { chave: 'cancelamentos', descricao: 'Cancelamento de outro período' }
const filtrar = (f) => (filtro.value = filtro.value?.chave === f.chave ? null : f)
const marcada = (f) => (filtro.value?.chave === f.chave ? 'bg-blue-1' : '')
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
        <tr
          class="cursor-pointer"
          :class="marcada(porModalidade(m))"
          @click="filtrar(porModalidade(m))"
        >
          <td class="text-weight-medium">{{ m.descricao }}</td>
          <td class="text-right text-weight-medium">{{ m.quantidade }}</td>
          <td class="text-right text-weight-medium">{{ formataNumero(m.valor) }}</td>
        </tr>
        <tr
          v-for="b in m.bandeiras"
          :key="b.descricao"
          class="text-grey-8 cursor-pointer"
          :class="marcada(porBandeira(m, b))"
          @click="filtrar(porBandeira(m, b))"
        >
          <td class="q-pl-lg">{{ b.descricao }}</td>
          <td class="text-right">{{ b.quantidade }}</td>
          <td class="text-right">{{ formataNumero(b.valor) }}</td>
        </tr>
      </template>
      <tr
        v-if="sistema?.cancelamentos?.quantidade"
        class="text-red-8 cursor-pointer"
        :class="marcada(CANCELAMENTOS)"
        @click="filtrar(CANCELAMENTOS)"
      >
        <td>Cancelamento de outro período</td>
        <td></td>
        <td class="text-right">{{ formataNumero(sistema.cancelamentos.valor) }}</td>
      </tr>
      <tr class="bg-grey-2 cursor-pointer" @click="filtro = null">
        <td class="text-weight-bold">Total</td>
        <td class="text-right text-weight-bold" :class="informado && cor(diferenca.quantidade)">
          {{ sistema?.quantidade ?? 0 }}
        </td>
        <td class="text-right text-weight-bold" :class="informado && cor(diferenca.total)">
          {{ formataNumero(sistema?.total ?? 0) }}
        </td>
      </tr>
      <template v-if="temDiferenca">
        <tr class="text-grey-8">
          <td>Informado</td>
          <td class="text-right">{{ periodo.quantidadeinformada }}</td>
          <td class="text-right">{{ formataNumero(periodo.totalinformado) }}</td>
        </tr>
        <tr class="bg-grey-2 text-weight-bold text-red-8">
          <td>Diferença</td>
          <td class="text-right">{{ diferenca.quantidade }}</td>
          <td class="text-right">{{ formataNumero(diferenca.total) }}</td>
        </tr>
      </template>
    </tbody>
  </q-markup-table>
  <div v-if="sistema?.cancelados" class="text-caption text-grey-7 q-px-md q-pb-md">
    {{ sistema.cancelados }}
    {{ sistema.cancelados > 1 ? 'vendas canceladas' : 'venda cancelada' }} no período, fora da conta
    (como no papel).
  </div>
</template>
