<script setup>
import { onMounted } from 'vue'
import { formataNumero, formataData, formataCodigo } from '@components/formatters'
import { usePagamentoStore } from 'src/stores/pagamentoStore'
import { abrirPdf } from 'src/utils/abrirPdf'

const store = usePagamentoStore()

function abrirRelatorio() {
  const params = {}
  for (const [k, v] of Object.entries(store.filters)) {
    if (v !== null && v !== undefined && v !== '') params[k] = v
  }
  abrirPdf('v1/pagamento/relatorio', params, { title: 'Recebimentos e Pagamentos' })
}

// CR recebeu, DB pagou, CP encontro de contas sem dinheiro
const icone = (l) => ({ CR: 'south_west', DB: 'north_east' })[l.operacao] ?? 'sync_alt'

async function carregarMais(index, done) {
  await store.fetchItems(false)
  done(!store.hasMore)
}

onMounted(() => {
  if (store.items.length === 0) store.fetchItems(true)
})
</script>

<template>
  <q-page>
    <q-infinite-scroll @load="carregarMais" :offset="250">
      <div class="q-pa-md" style="max-width: 1086px; margin: auto">
        <q-list v-if="store.items.length > 0" bordered separator class="bg-white rounded-borders">
          <q-item
            v-for="l in store.items"
            :key="l.codpagamento"
            clickable
            :to="{ name: 'pagamento-detalhe', params: { id: l.codpagamento } }"
            :class="{ 'bg-red-1': l.estado === 'C' }"
          >
            <q-item-section avatar class="gt-xs">
              <q-avatar
                :icon="l.estado === 'C' ? 'undo' : icone(l)"
                :color="l.estado === 'C' ? 'grey' : 'primary'"
                text-color="white"
                size="40px"
              />
            </q-item-section>

            <q-item-section style="min-width: 0">
              <q-item-label class="text-primary text-weight-medium ellipsis">
                {{ l.fantasia }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                {{ formataCodigo(l.codpagamento) }}
                <template v-if="l.codliquidacaotituloantigo">
                  (liquidação {{ formataCodigo(l.codliquidacaotituloantigo) }})
                </template>
                · {{ l.usuariocriacao || '' }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                {{ formataData(l.criacao) }}
              </q-item-label>
            </q-item-section>

            <q-item-section class="gt-xs" style="flex: 0 0 130px; min-width: 0">
              <q-item-label class="ellipsis">
                {{ l.portador || l.meiodescricao }}
              </q-item-label>
              <q-item-label caption>
                {{ l.portador ? l.meiodescricao + ' · ' : '' }}{{ formataData(l.lancamento) }}
              </q-item-label>
            </q-item-section>

            <q-item-section style="flex: 0 0 110px; min-width: 0">
              <q-item-label
                class="text-weight-bold text-right"
                :class="l.operacao === 'CR' ? 'text-orange' : 'text-green'"
              >
                {{ formataNumero(l.total) }} {{ l.operacao }}
              </q-item-label>
              <q-item-label v-if="l.estado === 'C'" caption class="text-right text-negative">
                Estornado
              </q-item-label>
              <q-item-label
                v-if="l.codperiodocolaboradoracerto"
                caption
                class="text-right text-grey-7"
              >
                Acerto RH
              </q-item-label>
            </q-item-section>
          </q-item>
        </q-list>

        <div v-else-if="!store.loading" class="text-center text-grey q-pa-xl">
          Nenhum recebimento ou pagamento encontrado
        </div>

        <div v-if="store.items.length" class="text-caption text-grey q-mt-md text-center">
          {{ store.items.length }} de {{ store.total }}
        </div>
      </div>

      <template #loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="32px" />
        </div>
      </template>
    </q-infinite-scroll>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <div class="row q-gutter-sm items-end">
        <q-btn fab-mini color="grey-8" icon="print" @click="abrirRelatorio">
          <q-tooltip anchor="top middle" self="bottom middle">Relatório</q-tooltip>
        </q-btn>
        <q-btn fab icon="add" color="primary" :to="{ name: 'pagamento-novo' }">
          <q-tooltip anchor="top middle" self="bottom middle">Receber ou Pagar Títulos</q-tooltip>
        </q-btn>
      </div>
    </q-page-sticky>
  </q-page>
</template>
