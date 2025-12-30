<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from '../stores/notaFiscalStore'

const router = useRouter()
const $q = useQuasar()
const notaFiscalStore = useNotaFiscalStore()

// State
const loading = computed(() => notaFiscalStore.loading.notas)
const notas = computed(() => notaFiscalStore.notas)
const pagination = computed({
  get: () => notaFiscalStore.pagination,
  set: (val) => {
    notaFiscalStore.pagination = val
  }
})

const hasActiveFilters = computed(() => {
  const filters = notaFiscalStore.filters
  return !!(filters.search || filters.modelo || filters.situacao || filters.dataInicial || filters.dataFinal)
})

// Colunas da tabela
const columns = [
  {
    name: 'modelo',
    label: 'Modelo',
    field: 'modelo',
    align: 'left',
    sortable: true
  },
  {
    name: 'numero',
    label: 'Número',
    field: 'numero',
    align: 'left',
    sortable: true
  },
  {
    name: 'serie',
    label: 'Série',
    field: 'serie',
    align: 'left',
    sortable: true
  },
  {
    name: 'emissao',
    label: 'Data Emissão',
    field: 'emissao',
    align: 'left',
    sortable: true
  },
  {
    name: 'destinatario',
    label: 'Destinatário',
    field: row => row.pessoa?.fantasia || row.pessoa?.pessoa || 'Sem destinatário',
    align: 'left',
    sortable: false
  },
  {
    name: 'naturezaoperacao',
    label: 'Natureza',
    field: row => row.naturezaOperacao?.naturezaoperacao || '-',
    align: 'left',
    sortable: false
  },
  {
    name: 'status',
    label: 'Situação',
    field: 'status',
    align: 'center',
    sortable: true
  },
  {
    name: 'valorprodutos',
    label: 'Valor Produtos',
    field: 'valorprodutos',
    align: 'right',
    sortable: true
  },
  {
    name: 'acoes',
    label: 'Ações',
    align: 'center',
    sortable: false
  }
]

// Methods
const fetchNotas = async () => {
  try {
    await notaFiscalStore.fetchNotas()
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar notas fiscais',
      caption: error.response?.data?.message || error.message
    })
  }
}

const handleTableRequest = (props) => {
  const { page, rowsPerPage } = props.pagination
  pagination.value.page = page
  pagination.value.rowsPerPage = rowsPerPage
  fetchNotas()
}

const handlePageChange = () => {
  fetchNotas()
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
      fetchNotas()
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
onMounted(() => {
  fetchNotas()
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

    <!-- Loading -->
    <div v-if="loading" class="row justify-center q-py-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Lista de Notas (Cards para Mobile) -->
    <div v-else-if="notas.length > 0">
      <!-- Mobile: Cards -->
      <div class="lt-md">
        <q-card
          v-for="nota in notas"
          :key="nota.codnotafiscal"
          class="q-mb-md cursor-pointer"
          @click="handleViewNota(nota.codnotafiscal)"
        >
          <q-card-section>
            <div class="row items-center q-mb-sm">
              <div class="col">
                <div class="text-weight-bold">
                  {{ nota.modelo }} Nº {{ nota.numero }}
                  <span v-if="nota.serie"> / Série {{ nota.serie }}</span>
                </div>
              </div>
              <div class="col-auto">
                <q-badge :color="getSituacaoColor(nota.status)">
                  {{ nota.status }}
                </q-badge>
              </div>
            </div>

            <div class="text-caption text-grey-7 q-mb-xs">
              <q-icon name="calendar_today" size="xs" class="q-mr-xs" />
              {{ formatDate(nota.emissao) }}
            </div>

            <div class="text-caption text-grey-7 q-mb-xs">
              <q-icon name="person" size="xs" class="q-mr-xs" />
              {{ nota.pessoa?.fantasia || nota.pessoa?.pessoa || 'Sem destinatário' }}
            </div>

            <div class="text-caption text-grey-7 q-mb-sm">
              <q-icon name="description" size="xs" class="q-mr-xs" />
              {{ nota.naturezaOperacao?.naturezaoperacao || 'Sem natureza' }}
            </div>

            <div class="row items-center">
              <div class="col">
                <div class="text-weight-bold text-primary">
                  R$ {{ formatCurrency(nota.valorprodutos) }}
                </div>
              </div>
              <div class="col-auto">
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
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Desktop: Tabela -->
      <q-table
        class="gt-sm"
        :rows="notas"
        :columns="columns"
        :loading="loading"
        :pagination="pagination"
        @request="handleTableRequest"
        row-key="codnotafiscal"
        flat
        bordered
      >
        <!-- Situação com badge -->
        <template v-slot:body-cell-status="props">
          <q-td :props="props">
            <q-badge :color="getSituacaoColor(props.value)">
              {{ props.value }}
            </q-badge>
          </q-td>
        </template>

        <!-- Data formatada -->
        <template v-slot:body-cell-emissao="props">
          <q-td :props="props">
            {{ formatDate(props.value) }}
          </q-td>
        </template>

        <!-- Valor formatado -->
        <template v-slot:body-cell-valorprodutos="props">
          <q-td :props="props">
            R$ {{ formatCurrency(props.value) }}
          </q-td>
        </template>

        <!-- Ações -->
        <template v-slot:body-cell-acoes="props">
          <q-td :props="props">
            <q-btn
              flat
              round
              dense
              icon="visibility"
              color="primary"
              @click="handleViewNota(props.row.codnotafiscal)"
            >
              <q-tooltip>Visualizar</q-tooltip>
            </q-btn>

            <q-btn
              flat
              round
              dense
              icon="edit"
              color="primary"
              @click="handleEditNota(props.row.codnotafiscal)"
              :disable="isNotaBloqueada(props.row)"
            >
              <q-tooltip>Editar</q-tooltip>
            </q-btn>

            <q-btn
              flat
              round
              dense
              icon="delete"
              color="negative"
              @click="handleDeleteNota(props.row)"
              :disable="isNotaBloqueada(props.row)"
            >
              <q-tooltip>Excluir</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>

      <!-- Paginação Mobile -->
      <div class="lt-md q-mt-md">
        <q-pagination
          v-model="pagination.page"
          :max="Math.ceil(pagination.rowsNumber / pagination.rowsPerPage)"
          :max-pages="5"
          direction-links
          boundary-links
          @update:model-value="handlePageChange"
        />

        <div class="text-center text-caption text-grey-7 q-mt-sm">
          Total: {{ pagination.rowsNumber }} registros
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <q-card v-else flat bordered class="q-pa-xl text-center">
      <q-icon name="description" size="4em" color="grey-5" />
      <div class="text-h6 text-grey-7 q-mt-md">Nenhuma nota fiscal encontrada</div>
      <div class="text-caption text-grey-6 q-mt-sm">
        {{ hasActiveFilters ? 'Tente ajustar os filtros no menu lateral' : 'Clique em "Nova Nota" para criar sua primeira nota fiscal' }}
      </div>
    </q-card>
  </q-page>
</template>
