<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useTributacaoCadastroStore } from '../stores/tributacaoCadastroStore'

const router = useRouter()
const $q = useQuasar()
const tributacaoStore = useTributacaoCadastroStore()

const loading = computed(() => tributacaoStore.pagination.loading)
const tributacoes = computed(() => tributacaoStore.tributacoes)
const hasActiveFilters = computed(() => tributacaoStore.hasActiveFilters)

// Formata a aliquota com % e 2 casas decimais
const formatAliquota = (value) => {
  if (value === null || value === undefined) return '-'
  return `${parseFloat(value).toFixed(2)}%`
}

const onLoad = async (index, done) => {
  try {
    await tributacaoStore.fetchTributacoes()
    done(!tributacaoStore.pagination.hasMore)
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar Tributacoes',
      caption: error.message,
    })
    done(true)
  }
}

const handleCreateTributacao = () => {
  router.push({ name: 'tributacao-cadastro-create' })
}

const handleEditTributacao = (codtributacao) => {
  router.push({ name: 'tributacao-cadastro-edit', params: { codtributacao } })
}

const handleDeleteTributacao = (tributacao) => {
  $q.dialog({
    title: 'Confirmar exclusao',
    message: `Deseja realmente excluir a tributacao "${tributacao.tributacao}"?`,
    cancel: {
      label: 'Cancelar',
      flat: true,
    },
    ok: {
      label: 'Excluir',
      color: 'negative',
    },
    persistent: true,
  }).onOk(async () => {
    try {
      await tributacaoStore.deleteTributacao(tributacao.codtributacao)
      $q.notify({
        type: 'positive',
        message: 'Tributacao excluida com sucesso',
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir Tributacao',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

onMounted(async () => {
  if (!tributacaoStore.initialLoadDone) {
    try {
      await tributacaoStore.fetchTributacoes(true)
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao carregar Tributacoes',
        caption: error.response?.data?.message || error.message,
      })
    }
  }
})
</script>

<template>
  <q-page class="q-pa-md">
    <!-- Loading inicial -->
    <div v-if="loading && tributacoes.length === 0" class="row justify-center q-mt-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Empty State -->
    <div v-else-if="tributacoes.length === 0" class="row justify-center q-mt-xl">
      <q-card flat bordered class="text-center q-pa-lg">
        <q-icon name="receipt_long" size="4em" color="grey-5" />
        <div class="text-h6 q-mt-md">Nenhuma Tributacao encontrada</div>
        <div class="text-body2 text-grey">
          <template v-if="hasActiveFilters">Tente ajustar os filtros no menu lateral</template>
          <template v-else>Clique em "Nova Tributacao" para criar sua primeira Tributacao</template>
        </div>
      </q-card>
    </div>

    <!-- Lista de Tributacoes com Scroll Infinito -->
    <q-infinite-scroll v-else @load="onLoad" :offset="250">
      <div class="row q-col-gutter-sm">
        <div
          v-for="tributacao in tributacoes"
          :key="tributacao.codtributacao"
          class="col-6 col-sm-4 col-md-3"
        >
          <q-card class="q-pa-none" flat bordered>
            <div class="text-weight-bold text-primary text-body2 q-pa-sm">
              {{ tributacao.tributacao }}
            </div>
            <q-separator />
            <div class="q-pa-sm text-caption ellipsis text-grey-7">
              Código do Tributo:{{ tributacao.codtributacao }}
            </div>
            <div class="q-px-sm q-pb-sm text-caption text-grey-6 ellipsis">
              Aliquota ICMS ECF: {{ formatAliquota(tributacao.aliquotaicmsecf) }}
            </div>

            <q-separator />
            <q-card-section class="q-pa-none" align="right">
              <q-btn
                flat
                dense
                size="sm"
                rounded
                icon="edit"
                color="primary"
                @click="handleEditTributacao(tributacao.codtributacao)"
              />
              <q-btn
                flat
                dense
                size="sm"
                rounded
                icon="delete"
                color="negative"
                class="q-mr-sm"
                @click="handleDeleteTributacao(tributacao)"
              />
            </q-card-section>
          </q-card>
        </div>
      </div>

      <template v-slot:loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="40px" />
        </div>
      </template>
    </q-infinite-scroll>

    <!-- FAB para Nova Tributacao -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="handleCreateTributacao" :disable="loading">
        <q-tooltip>Nova Tributacao</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
