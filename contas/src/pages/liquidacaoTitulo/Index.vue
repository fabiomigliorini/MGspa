<script setup>
import { onMounted } from 'vue'
import { date } from 'quasar'
import { formataNumero } from 'src/utils/formatters.js'
import { useLiquidacaoTituloStore } from 'src/stores/liquidacaoTituloStore'
import { abrirPdf } from 'src/utils/abrirPdf'

const store = useLiquidacaoTituloStore()

const formatData = (v) => (v ? date.formatDate(v, 'DD/MM/YYYY') : '')
const formatCodigo = (v) => '#' + String(v).padStart(8, '0')

function abrirRelatorio() {
  const params = {}
  for (const [k, v] of Object.entries(store.filters)) {
    if (v !== null && v !== undefined && v !== '') params[k] = v
  }
  abrirPdf('v1/liquidacao-titulo/relatorio', params)
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
            v-for="l in store.items"
            :key="l.codliquidacaotitulo"
            clickable
            :to="{ name: 'liquidacao-titulo-detalhe', params: { id: l.codliquidacaotitulo } }"
            :class="{ 'bg-red-1': !!l.estornado }"
          >
            <q-item-section avatar class="gt-xs">
              <q-avatar
                :icon="l.estornado ? 'undo' : 'paid'"
                :color="l.estornado ? 'grey' : 'primary'"
                text-color="white"
                size="40px"
              />
            </q-item-section>

            <q-item-section style="min-width: 0">
              <q-item-label class="text-primary text-weight-medium ellipsis">
                {{ l.fantasia }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                {{ formatCodigo(l.codliquidacaotitulo) }}
                · {{ l.usuariocriacao || '' }}
              </q-item-label>
              <q-item-label caption class="ellipsis">
                {{ formatData(l.criacao) }}
              </q-item-label>
            </q-item-section>

            <q-item-section class="gt-xs" style="flex: 0 0 130px; min-width: 0">
              <q-item-label class="ellipsis">
                {{ l.portador }}
              </q-item-label>
              <q-item-label caption>
                {{ formatData(l.transacao) }}
              </q-item-label>
            </q-item-section>

            <q-item-section style="flex: 0 0 110px; min-width: 0">
              <q-item-label
                class="text-weight-bold text-right"
                :class="l.operacao === 'CR' ? 'text-orange' : 'text-green'"
              >
                {{ formataNumero(l.valor) }} {{ l.operacao }}
              </q-item-label>
              <q-item-label v-if="l.estornado" caption class="text-right text-negative">
                Estornado
              </q-item-label>
              <q-item-label v-if="l.codperiodo" caption class="text-right text-grey-7">
                RH #{{ l.codperiodo }}
              </q-item-label>
            </q-item-section>
          </q-item>
        </q-list>

        <div v-else-if="!store.loading" class="text-center text-grey q-pa-xl">
          Nenhuma liquidação encontrada
        </div>

        <div v-if="store.items.length" class="text-caption text-grey q-mt-md text-center">
          {{ store.items.length }} de {{ store.total }} liquidação(ões)
        </div>
      </div>

      <template #loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="32px" />
        </div>
      </template>
    </q-infinite-scroll>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <div class="column q-gutter-sm items-end">
        <q-btn fab-mini color="grey-8" icon="picture_as_pdf" @click="abrirRelatorio">
          <q-tooltip anchor="center left" self="center right">Relatório (PDF)</q-tooltip>
        </q-btn>
        <q-btn fab icon="add" color="primary" :to="{ name: 'liquidacao-titulo-nova' }">
          <q-tooltip anchor="center left" self="center right">Nova Liquidação</q-tooltip>
        </q-btn>
      </div>
    </q-page-sticky>
  </q-page>
</template>
