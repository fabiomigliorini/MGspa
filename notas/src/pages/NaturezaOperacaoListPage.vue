<script setup>
import { computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import {
  useNaturezaOperacaoStore,
  FINNFE_OPTIONS,
  OPERACAO_OPTIONS,
} from '../stores/naturezaOperacaoStore'

const $q = useQuasar()
const naturezaOperacaoStore = useNaturezaOperacaoStore()

const loading = computed(() => naturezaOperacaoStore.pagination.loading)
const naturezaOperacoes = computed(() =>
  [...naturezaOperacaoStore.naturezaOperacoes].sort((a, b) =>
    (a.naturezaoperacao || '').localeCompare(b.naturezaoperacao || '', 'pt-BR')
  )
)
const hasActiveFilters = computed(() => naturezaOperacaoStore.hasActiveFilters)

// Retorna label da finalidade NFe
const getFinnfeLabel = (finnfe) => {
  if (finnfe === null || finnfe === undefined) return '-'
  const option = FINNFE_OPTIONS.find((o) => o.value === finnfe)
  return option ? option.label : '-'
}

// Retorna label da operação
const getOperacaoLabel = (codoperacao) => {
  if (codoperacao === null || codoperacao === undefined) return '-'
  const option = OPERACAO_OPTIONS.find((o) => o.value === codoperacao)
  return option ? option.label : '-'
}

// Retorna cor da operação
const getOperacaoColor = (codoperacao) => {
  return codoperacao === 1 ? 'blue' : codoperacao === 2 ? 'green' : 'grey'
}

// Retorna ícone da operação
const getOperacaoIcon = (codoperacao) => {
  return codoperacao === 1 ? 'arrow_downward' : codoperacao === 2 ? 'arrow_upward' : 'swap_horiz'
}

// Formata o código com zeros à esquerda
const formatCodigo = (codigo) => {
  return String(codigo).padStart(8, '0')
}

onMounted(async () => {
  if (!naturezaOperacaoStore.initialLoadDone) {
    try {
      await naturezaOperacaoStore.fetchNaturezaOperacoes(true)
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao carregar Naturezas de Operação',
        caption: error.response?.data?.message || error.message,
      })
    }
  }
})
</script>

<template>
  <q-page>
    <!-- Loading inicial -->
    <div v-if="loading && naturezaOperacoes.length === 0" class="row justify-center q-py-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Empty State -->
    <q-card v-else-if="naturezaOperacoes.length === 0" flat class="q-pa-xl text-center">
      <q-icon name="swap_horiz" size="4em" color="grey-5" />
      <div class="text-h6 text-grey-7 q-mt-md">Nenhuma natureza de operação encontrada</div>
      <div class="text-caption text-grey-7 q-mt-sm">
        <template v-if="hasActiveFilters">Tente ajustar os filtros no menu lateral</template>
        <template v-else>Clique em "Nova" para criar sua primeira natureza de operação</template>
      </div>
    </q-card>

    <!-- Lista de Naturezas de Operação -->
    <q-list v-else separator>
      <q-item
        v-for="naturezaOperacao in naturezaOperacoes"
        :key="naturezaOperacao.codnaturezaoperacao"
        clickable
        :to="{
          name: 'natureza-operacao-view',
          params: { codnaturezaoperacao: naturezaOperacao.codnaturezaoperacao },
        }"
      >
        <q-item-section class="q-pa-sm">
          <div class="row q-col-gutter-sm q-pa-none">
            <div class="col-4 col-sm-2">
              <div class="text-caption text-grey-7">
                <q-icon name="tag" size="xs" class="q-mr-xs" />
                Código
              </div>
              <div class="text-caption">
                #{{ formatCodigo(naturezaOperacao.codnaturezaoperacao) }}
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <div class="text-caption text-grey-7">
                <q-icon name="swap_horiz" size="xs" class="q-mr-xs" />
                Natureza de Operação
              </div>
              <div class="text-weight-bold text-primary ellipsis">
                {{ naturezaOperacao.naturezaoperacao }}
              </div>
            </div>
            <div class="col-4 col-sm-2">
              <div class="text-caption text-grey-7">
                <q-icon name="compare_arrows" size="xs" class="q-mr-xs" />
                Operação
              </div>
              <q-badge :color="getOperacaoColor(naturezaOperacao.codoperacao)">
                <q-icon
                  :name="getOperacaoIcon(naturezaOperacao.codoperacao)"
                  size="xs"
                  class="q-mr-xs"
                />
                {{ getOperacaoLabel(naturezaOperacao.codoperacao) }}
              </q-badge>
            </div>
            <div class="col-4 col-sm-2">
              <div class="text-caption text-grey-7">
                <q-icon name="send" size="xs" class="q-mr-xs" />
                Emissão
              </div>
              <q-badge v-if="naturezaOperacao.emitida" color="orange">Nossa Emissão</q-badge>
              <span v-else class="text-caption text-grey-6">Terceiros</span>
            </div>
            <div class="col-4 col-sm-2">
              <div class="text-caption text-grey-7">
                <q-icon name="description" size="xs" class="q-mr-xs" />
                Finalidade
              </div>
              <div class="text-caption">{{ getFinnfeLabel(naturezaOperacao.finnfe) }}</div>
            </div>
          </div>
        </q-item-section>
      </q-item>
    </q-list>

    <!-- FAB para Nova Natureza de Operação -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn
        fab
        icon="add"
        color="primary"
        :to="{ name: 'natureza-operacao-create' }"
        :disable="loading"
      >
        <q-tooltip>Nova Natureza de Operação</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
