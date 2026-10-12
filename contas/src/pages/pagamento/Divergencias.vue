<script setup>
// Divergências (TASK-218): o dinheiro que não bate com o que ele pagou. A venda fechada cujos
// pagamentos + parcelas não somam o total (abre a Venda com diferença, que corrige) e o pagamento
// cuja soma das baixas não bate com o valor, sem venda nem título, ou numa venda aberta ou
// cancelada (abre o detalhe, que cancela). O histórico todo; os filtros ficam na gaveta.
import { ref, computed, watch, onMounted } from 'vue'
import { formataNumero, formataData, formataCodigo } from '@components/formatters'
import { useDivergenciaStore } from 'src/stores/divergenciaStore'
import MgEmptyState from '@components/MgEmptyState.vue'

const store = useDivergenciaStore()

// a lista chega inteira (até o limite); a tela mostra aos poucos
const PASSO = 100
const quantos = ref(PASSO)
const visiveis = computed(() => store.items.slice(0, quantos.value))
watch(
  () => store.items,
  () => (quantos.value = PASSO),
)
const carregarMais = (index, done) => {
  quantos.value += PASSO
  done(quantos.value >= store.items.length)
}

const titulo = (d) =>
  d.tipo === 'venda'
    ? `Venda ${formataCodigo(d.id)}`
    : `Pagamento ${formataCodigo(d.id)} · ${d.meio ?? ''} ${formataNumero(d.valor)}`

// o que não bate, em palavras
const motivo = (d) => {
  const valor = formataNumero(Math.abs(d.diferenca))
  switch (d.caso) {
    case 'venda':
      return d.diferenca > 0 ? `Faltou ${valor}` : `Sobrou ${valor}`
    case 'baixas':
      return d.diferenca > 0 ? `Sobrou ${valor} sem baixa` : `Baixas passaram ${valor}`
    case 'sem':
      return 'Sem venda nem título'
    case 'aberta':
      return `Venda ${formataCodigo(d.codnegocio)} aberta`
    case 'cancelada':
      return `Venda ${formataCodigo(d.codnegocio)} cancelada`
  }
  return ''
}

const destino = (d) =>
  d.tipo === 'venda'
    ? { name: 'fechamento-venda', params: { id: d.id } }
    : { name: 'pagamento-detalhe', params: { id: d.id } }

onMounted(() => store.fetchItems())
</script>

<template>
  <q-page>
    <q-infinite-scroll @load="carregarMais" :offset="250" :disable="store.loading">
      <div class="q-pa-md" style="max-width: 1086px; margin: auto">
        <div class="row items-center q-mb-sm">
          <q-btn flat round icon="arrow_back" :to="{ name: 'pagamento' }" aria-label="Voltar" />
          <div class="col q-ml-sm">
            <div class="text-h5 text-grey-9">Divergências</div>
            <div v-if="!store.loading" class="text-caption text-grey-7">
              <template v-if="store.total > store.items.length">
                As {{ formataNumero(store.items.length, 0) }} mais recentes de
                {{ formataNumero(store.total, 0) }}: use o filtro de data
              </template>
              <template v-else>{{ formataNumero(store.total, 0) }} divergência(s)</template>
            </div>
          </div>
        </div>

        <q-card flat bordered>
          <q-inner-loading :showing="store.loading" color="primary" />
          <div v-if="store.loading && !store.items.length" class="q-pa-xl" />
          <MgEmptyState v-else-if="!store.loading && !store.items.length" icon="rule" plain>
            Nenhuma divergência com esse filtro.
          </MgEmptyState>
          <q-list v-else separator>
            <q-item v-for="d in visiveis" :key="`${d.tipo}-${d.id}`" :to="destino(d)">
              <q-item-section avatar class="gt-xs">
                <q-avatar
                  :icon="d.tipo === 'venda' ? 'shopping_cart' : 'payments'"
                  :color="d.tipo === 'venda' ? 'indigo-7' : 'teal-7'"
                  text-color="white"
                />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-medium text-primary">
                  {{ titulo(d) }}
                </q-item-label>
                <q-item-label caption class="ellipsis">
                  {{ d.fantasia ?? '—' }}
                </q-item-label>
                <q-item-label caption>{{ formataData(d.data) }} · {{ d.filial }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-item-label class="text-right text-weight-bold text-red-8">
                  {{ motivo(d) }}
                </q-item-label>
                <q-item-label v-if="d.tipo === 'venda'" caption class="text-right">
                  Total {{ formataNumero(d.valor) }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </div>
    </q-infinite-scroll>
  </q-page>
</template>
