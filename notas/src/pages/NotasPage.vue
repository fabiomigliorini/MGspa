<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from '../stores/notaFiscalStore'

const router = useRouter()
const $q = useQuasar()
const notaFiscalStore = useNotaFiscalStore()

// State
const loading = computed(() => notaFiscalStore.pagination.loading)
const notas = computed(() => notaFiscalStore.notas)
const hasActiveFilters = computed(() => notaFiscalStore.hasActiveFilters)

// Methods
const onLoad = async (index, done) => {
  try {
    await notaFiscalStore.fetchNotas()
    done(!notaFiscalStore.pagination.hasMore)
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar notas',
      caption: error.message
    })
    done(true)
  }
}

const handleCreateNota = () => {
  router.push({ name: 'nota-fiscal-create' })
}

const handleViewNota = (codnotafiscal) => {
  router.push({ name: 'nota-fiscal-view', params: { codnotafiscal } })
}

const handleEditNota = (codnotafiscal) => {
  router.push({ name: 'nota-fiscal-edit', params: { codnotafiscal } })
}

const handleDeleteNota = (nota) => {
  $q.dialog({
    title: 'Confirmar exclusão',
    message: `Deseja realmente excluir a nota fiscal ${nota.modelo} nº ${nota.numero}?`,
    cancel: {
      label: 'Cancelar',
      flat: true
    },
    ok: {
      label: 'Excluir',
      color: 'negative'
    },
    persistent: true
  }).onOk(async () => {
    try {
      await notaFiscalStore.deleteNota(nota.codnotafiscal)
      $q.notify({
        type: 'positive',
        message: 'Nota fiscal excluída com sucesso'
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir nota fiscal',
        caption: error.response?.data?.message || error.message
      })
    }
  })
}

const handleShowMenu = () => {
  // Evento é capturado pelo @click.stop no botão
}

const isNotaBloqueada = (nota) => {
  return ['Autorizada', 'Cancelada', 'Inutilizada'].includes(nota.status)
}

const getSituacaoColor = (situacao) => {
  const colors = {
    'Digitacao': 'blue-grey',
    'Autorizada': 'positive',
    'Cancelada': 'negative',
    'Inutilizada': 'warning',
    'Denegada': 'deep-orange'
  }
  return colors[situacao] || 'grey'
}

const formatDate = (value) => {
  if (!value) return '-'
  // Converte ISO string ou timestamp para DD/MM/YYYY
  const dateObj = new Date(value)
  return dateObj.toLocaleDateString('pt-BR')
}

const formatCurrency = (value) => {
  if (!value) return '0,00'
  return parseFloat(value).toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

// Lifecycle
onMounted(async () => {
  if (!notaFiscalStore.initialLoadDone) {
    try {
      await notaFiscalStore.fetchNotas(true)
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao carregar notas fiscais',
        caption: error.response?.data?.message || error.message
      })
    }
  }
})
</script>

<template>
  <q-page padding>
    <!-- Header com título e botão adicionar -->
    <div class="row items-center q-mb-md">
      <div class="col">
        <div class="text-h5">Notas Fiscais</div>
      </div>
      <div class="col-auto">
        <q-btn
          color="primary"
          icon="add"
          label="Nova Nota"
          @click="handleCreateNota"
          :disable="loading"
        />
      </div>
    </div>

    <!-- Loading inicial -->
    <div v-if="loading && notas.length === 0" class="row justify-center q-py-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Empty State -->
    <q-card v-else-if="notas.length === 0" flat bordered class="q-pa-xl text-center">
      <q-icon name="description" size="4em" color="grey-5" />
      <div class="text-h6 text-grey-7 q-mt-md">Nenhuma nota fiscal encontrada</div>
      <div class="text-caption text-grey-6 q-mt-sm">
        {{ hasActiveFilters ? 'Tente ajustar os filtros no menu lateral' : 'Clique em "Nova Nota" para criar sua primeira nota fiscal' }}
      </div>
    </q-card>

    <!-- Lista de Notas com Scroll Infinito -->
    <q-infinite-scroll v-else @load="onLoad" :offset="250">
      <q-list separator>
        <q-item
          v-for="nota in notas"
          :key="nota.codnotafiscal"
          clickable
          @click="handleViewNota(nota.codnotafiscal)"
          class="q-pa-md"
        >
          <q-item-section>
            <!-- Cabeçalho: Modelo, Número, Série e Status -->
            <div class="row items-center q-mb-sm">
              <div class="col">
                <div class="text-subtitle1 text-weight-medium">
                  {{ nota.modelo }} Nº {{ nota.numero }}
                  <span v-if="nota.serie" class="text-grey-7"> / Série {{ nota.serie }}</span>
                </div>
              </div>
              <div class="col-auto">
                <q-badge :color="getSituacaoColor(nota.status)">
                  {{ nota.status }}
                </q-badge>
              </div>
            </div>

            <!-- Informações principais -->
            <div class="row q-col-gutter-sm q-mb-sm">
              <!-- Data de Emissão -->
              <div class="col-12 col-sm-6 col-md-3">
                <div class="text-caption text-grey-7">
                  <q-icon name="calendar_today" size="xs" class="q-mr-xs" />
                  Data Emissão
                </div>
                <div class="text-body2">{{ formatDate(nota.emissao) }}</div>
              </div>

              <!-- Destinatário -->
              <div class="col-12 col-sm-6 col-md-4">
                <div class="text-caption text-grey-7">
                  <q-icon name="person" size="xs" class="q-mr-xs" />
                  Destinatário
                </div>
                <div class="text-body2 ellipsis">
                  {{ nota.pessoa?.fantasia || nota.pessoa?.pessoa || 'Sem destinatário' }}
                </div>
              </div>

              <!-- Natureza de Operação -->
              <div class="col-12 col-sm-6 col-md-3">
                <div class="text-caption text-grey-7">
                  <q-icon name="description" size="xs" class="q-mr-xs" />
                  Natureza
                </div>
                <div class="text-body2 ellipsis">
                  {{ nota.naturezaOperacao?.naturezaoperacao || 'Sem natureza' }}
                </div>
              </div>

              <!-- Valor -->
              <div class="col-12 col-sm-6 col-md-2">
                <div class="text-caption text-grey-7">Valor Produtos</div>
                <div class="text-subtitle1 text-weight-bold text-primary">
                  R$ {{ formatCurrency(nota.valorprodutos) }}
                </div>
              </div>
            </div>
          </q-item-section>

          <!-- Ações -->
          <q-item-section side>
            <q-btn
              flat
              round
              dense
              icon="more_vert"
              @click.stop="handleShowMenu()"
            >
              <q-menu>
                <q-list style="min-width: 150px">
                  <q-item clickable v-close-popup @click="handleViewNota(nota.codnotafiscal)">
                    <q-item-section avatar>
                      <q-icon name="visibility" />
                    </q-item-section>
                    <q-item-section>Visualizar</q-item-section>
                  </q-item>

                  <q-item
                    clickable
                    v-close-popup
                    @click="handleEditNota(nota.codnotafiscal)"
                    :disable="isNotaBloqueada(nota)"
                  >
                    <q-item-section avatar>
                      <q-icon name="edit" />
                    </q-item-section>
                    <q-item-section>Editar</q-item-section>
                  </q-item>

                  <q-separator />

                  <q-item
                    clickable
                    v-close-popup
                    @click="handleDeleteNota(nota)"
                    :disable="isNotaBloqueada(nota)"
                  >
                    <q-item-section avatar>
                      <q-icon name="delete" color="negative" />
                    </q-item-section>
                    <q-item-section class="text-negative">Excluir</q-item-section>
                  </q-item>
                </q-list>
              </q-menu>
            </q-btn>
          </q-item-section>
        </q-item>
      </q-list>

      <template v-slot:loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="40px" />
        </div>
      </template>
    </q-infinite-scroll>
  </q-page>
</template>
