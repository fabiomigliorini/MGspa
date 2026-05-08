<script setup>
import { onMounted } from 'vue'
import { date } from 'quasar'
import { formataNumero } from 'src/utils/formatters.js'
import { useTituloAgrupamentoStore } from 'src/stores/tituloAgrupamentoStore'
import { abrirPdf } from 'src/utils/abrirPdf'

const store = useTituloAgrupamentoStore()

const formatData = (v) => (v ? date.formatDate(v, 'DD/MM/YYYY') : '')
const formatCodigo = (v) => '#' + String(v).padStart(8, '0')

function abrirRelatorio() {
  const params = {}
  for (const [k, v] of Object.entries(store.filters)) {
    if (v !== null && v !== undefined && v !== '') params[k] = v
  }
  abrirPdf('v1/titulo-agrupamento/relatorio', params, { title: 'Agrupamentos' })
}

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
            v-for="ag in store.items"
            :key="ag.codtituloagrupamento"
            clickable
            :to="{ name: 'agrupamento-detalhe', params: { id: ag.codtituloagrupamento } }"
            :class="{ 'bg-red-1': !!ag.cancelamento }"
          >
            <q-item-section avatar class="gt-xs">
              <q-avatar
                :icon="ag.cancelamento ? 'undo' : 'receipt_long'"
                :color="ag.cancelamento ? 'grey' : 'indigo-7'"
                text-color="white"
                size="40px"
              />
            </q-item-section>
            <q-item-section style="min-width: 0">
              <q-item-label class="text-primary text-weight-medium ellipsis">
                {{ ag.fantasia }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                {{ formatCodigo(ag.codtituloagrupamento) }}
                · Emissão {{ formatData(ag.emissao) }}
              </q-item-label>
              <q-item-label caption class="ellipsis text-grey-7">
                {{ ag.observacao }}
              </q-item-label>
            </q-item-section>
            <q-item-section style="flex: 0 0 120px; min-width: 0">
              <q-item-label
                class="text-weight-bold text-right"
                :class="ag.operacao === 'CR' ? 'text-orange' : 'text-green'"
              >
                {{ formataNumero(ag.valor) }} {{ ag.operacao }}
              </q-item-label>
              <q-item-label v-if="ag.cancelamento" caption class="text-right text-negative">
                Estornado
              </q-item-label>
            </q-item-section>
          </q-item>
        </q-list>

        <div v-else-if="!store.loading" class="text-center text-grey q-pa-xl">
          Nenhum agrupamento encontrado
        </div>

        <div v-if="store.items.length" class="text-caption text-grey q-mt-md text-center">
          {{ store.items.length }} de {{ store.total }} agrupamento(s)
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
        <q-btn
          fab-mini
          color="indigo-7"
          icon="pending_actions"
          :to="{ name: 'agrupamento-pendentes' }"
        >
          <q-tooltip anchor="top middle" self="bottom middle">Fechamentos pendentes</q-tooltip>
        </q-btn>
        <q-btn fab icon="add" color="primary" :to="{ name: 'agrupamento-novo' }">
          <q-tooltip anchor="top middle" self="bottom middle">Novo Agrupamento</q-tooltip>
        </q-btn>
      </div>
    </q-page-sticky>
  </q-page>
</template>
