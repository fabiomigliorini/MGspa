<script setup>
// Listagem única de pagamentos (M6.1 doc-3): venda, títulos, transferência e avulso, em
// qualquer estado (pendente amarelo, efetivado, cancelado vermelho). O app configura o
// pagamentoListaStore (contas: tudo, com a filial do usuário; PDV: travada nele) e decide o que
// a linha abre: `to(pagamento)` (página de detalhe) ou o evento `abrir` (dialog).
import { watch, onMounted } from 'vue'
import { debounce } from 'quasar'
import { pagamentoListaStore } from '@components/stores/pagamentoListaStore'
import { formataData, formataNumero, formataCodigo } from '@components/formatters'
import { visualPagamento } from '@components/cobranca/pagamento.js'
import LogoPagamento from '@components/cobranca/LogoPagamento.vue'
import MgEmptyState from '@components/MgEmptyState.vue'

const props = defineProps({
  // rota da linha (página de detalhe); sem ela a linha emite `abrir`
  to: {
    type: Function,
    default: null,
  },
})

const emit = defineEmits(['abrir'])

const store = pagamentoListaStore()

const recarregar = debounce(() => store.buscar(true), 600)
watch(() => store.filtros, recarregar, { deep: true })

onMounted(() => store.buscar(true))

const carregarMais = async (index, done) => {
  await store.buscar(false)
  done(!store.temMais)
}

const COR_ESTADO = { P: 'amber-8', E: 'green-7', C: 'red-7' }
const CLASSE_LINHA = { P: 'bg-amber-1', C: 'bg-red-1' }
// CR entrou, DB saiu, TR transferência, CP compensação
const COR_TOTAL = { CR: 'text-green-8', DB: 'text-red-8', TR: 'text-blue-8', CP: 'text-grey-7' }

const urlVenda = (l) => `${process.env.NEGOCIOS_URL}/negocio/${l.codnegocio}`

const clicar = (l) => {
  if (!props.to) {
    emit('abrir', l)
  }
}
</script>

<template>
  <q-infinite-scroll @load="carregarMais" :offset="250">
    <q-list v-if="store.itens.length" bordered separator class="bg-white rounded-borders">
      <q-item
        v-for="l in store.itens"
        :key="l.codpagamento"
        clickable
        :to="to ? to(l) : undefined"
        :class="CLASSE_LINHA[l.estado]"
        @click="clicar(l)"
      >
        <q-item-section avatar>
          <logo-pagamento v-bind="visualPagamento(l)" size="40px" />
        </q-item-section>

        <!-- pessoa e documento -->
        <q-item-section style="min-width: 0">
          <q-item-label class="text-weight-medium ellipsis">
            {{ l.fantasia || '—' }}
          </q-item-label>
          <q-item-label caption class="ellipsis">
            {{ l.origemdescricao }} ·
            <a
              v-if="l.origem === 'V'"
              :href="urlVenda(l)"
              target="_blank"
              class="text-primary"
              style="text-decoration: none"
            >
              {{ l.documento }}
            </a>
            <template v-else>{{ l.documento }}</template>
          </q-item-label>
          <q-item-label caption class="ellipsis">
            {{ formataCodigo(l.codpagamento) }}
            <template v-if="l.codliquidacaotituloantigo">
              (liquidação {{ formataCodigo(l.codliquidacaotituloantigo) }})
            </template>
            · {{ l.usuariocriacao }}
          </q-item-label>
        </q-item-section>

        <!-- meio, maquineta, portador, PDV -->
        <q-item-section class="gt-xs" style="flex: 0 0 200px; min-width: 0">
          <q-item-label class="ellipsis">
            {{ l.meiodescricao }}
            <template v-if="l.parcelas > 1"> {{ l.parcelas }}x</template>
          </q-item-label>
          <q-item-label caption class="ellipsis" v-if="l.maquineta || l.portador">
            {{ [l.maquineta, l.portador].filter(Boolean).join(' · ') }}
          </q-item-label>
          <q-item-label caption class="ellipsis" v-if="l.pdv">{{ l.pdv }}</q-item-label>
        </q-item-section>

        <!-- data, total e estado -->
        <q-item-section side style="min-width: 110px">
          <q-item-label class="text-weight-bold" :class="COR_TOTAL[l.operacao]">
            R$ {{ formataNumero(l.total) }}
          </q-item-label>
          <q-item-label caption>{{ formataData(l.lancamento) }}</q-item-label>
          <q-item-label caption>
            <q-badge :color="COR_ESTADO[l.estado]" :label="l.estadodescricao" />
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-list>

    <MgEmptyState v-else-if="!store.carregando" icon="payments">
      Nenhum pagamento no período e filtros escolhidos.
    </MgEmptyState>

    <div v-if="store.itens.length" class="text-caption text-grey q-mt-md text-center">
      {{ store.itens.length }} de {{ store.total }}
    </div>

    <template #loading>
      <div class="row justify-center q-my-md">
        <q-spinner-dots color="primary" size="32px" />
      </div>
    </template>
  </q-infinite-scroll>
</template>
