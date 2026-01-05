<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from '../stores/notaFiscalStore'
import { getStatusLabel, getStatusColor, getStatusIcon, getModeloLabel } from '../constants/notaFiscal'
import { formatDateTime, formatDate, formatCurrency, formatNumero } from 'src/utils/formatters'

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

// const handleEditNota = (codnotafiscal) => {
//   router.push({ name: 'nota-fiscal-edit', params: { codnotafiscal } })
// }

// const handleDeleteNota = (nota) => {
//   $q.dialog({
//     title: 'Confirmar exclusão',
//     message: `Deseja realmente excluir a nota fiscal ${nota.modelo} nº ${nota.numero}?`,
//     cancel: {
//       label: 'Cancelar',
//       flat: true
//     },
//     ok: {
//       label: 'Excluir',
//       color: 'negative'
//     },
//     persistent: true
//   }).onOk(async () => {
//     try {
//       await notaFiscalStore.deleteNota(nota.codnotafiscal)
//       $q.notify({
//         type: 'positive',
//         message: 'Nota fiscal excluída com sucesso'
//       })
//     } catch (error) {
//       $q.notify({
//         type: 'negative',
//         message: 'Erro ao excluir nota fiscal',
//         caption: error.response?.data?.message || error.message
//       })
//     }
//   })
// }

// const handleShowMenu = () => {
//   // Evento é capturado pelo @click.stop no botão
// }

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
  <q-page>
    <!-- Header com título -->
    <div class="text-h5 q-mb-md q-pa-md">Notas Fiscais</div>

    <!-- Loading inicial -->
    <div v-if="loading && notas.length === 0" class="row justify-center q-py-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Empty State -->
    <q-card v-else-if="notas.length === 0" flat bordered class="q-pa-xl text-center">
      <q-icon name="description" size="4em" color="grey-5" />
      <div class="text-h6 text-grey-7 q-mt-md">Nenhuma nota fiscal encontrada</div>
      <div class="text-caption text-grey-7 q-mt-sm">
        <template v-if="hasActiveFilters">
          Tente ajustar os filtros no menu lateral
        </template>
        <template v-else>
          Clique em "Nova Nota" para criar sua primeira nota fiscal
        </template>
      </div>
    </q-card>

    <!-- Lista de Notas com Scroll Infinito -->
    <q-infinite-scroll v-else @load="onLoad" :offset="250">
      <q-list separator>
        <q-item hoverable v-for="nota in notas" :key="nota.codnotafiscal" clickable :to="'/nota/' + nota.codnotafiscal">
          <q-item-section>

            <!-- Cabeçalho: Modelo, Número, Série, Filial e Status -->
            <div class="row q-col-gutter-sm ">
              <!-- Número  -->
              <div class="col-12 col-sm-3 col-md-3">
                <div class="text-caption text-grey-7">
                  <q-icon name="event" size="xs" class="q-mr-xs" />
                  Número
                </div>
                <div class="text-caption ellipsis">
                  {{ getModeloLabel(nota.modelo) }}
                  {{ formatNumero(nota.numero) }}
                  - Série
                  {{ nota.serie }}
                </div>
              </div>


              <!-- Valor Total -->
              <div class="col-12 col-sm-3 col-md-2">
                <div class="text-caption text-grey-7">Valor</div>
                <div class="text-weight-bold text-primary">
                  R$ {{ formatCurrency(nota.valortotal) }}
                </div>
              </div>

              <!-- Pessoa (Cliente/Fornecedor) -->
              <div class="col-12 col-sm-4 col-md-4">
                <div class="text-caption text-grey-7">
                  <q-icon name="person" size="xs" class="q-mr-xs" />
                  Pessoa
                </div>
                <div class=" text-weight-bold text-secondary ellipsis">
                  {{ nota.pessoa?.pessoa || 'Sem pessoa' }}
                  <!-- <span v-if="nota.pessoa?.cidade" class="text-caption text-grey-7">
                    {{ nota.pessoa.cidade }}/{{ nota.pessoa.uf }}
                  </span> -->
                </div>
              </div>

              <!-- Cidade -->
              <div class="col-12 col-sm-2 col-md-3">
                <div class="text-caption text-grey-7">
                  <q-icon name="person" size="xs" class="q-mr-xs" />
                  Cidade
                </div>
                <div class="ellipsis">
                  {{ nota.pessoa.cidade }}/{{ nota.pessoa.uf }}
                </div>
              </div>
            </div>

            <!-- Informações principais -->
            <div class="row q-col-gutter-sm ">

              <!-- Status -->
              <div class="col-12 col-sm-3 col-md-3">
                <div class="text-caption text-grey-7">
                  <q-icon name="business" size="xs" class="q-mr-xs" />
                  Status
                </div>
                <div class=" ellipsis">
                  <q-badge :color="getStatusColor(nota.status)">
                    <q-icon :name="getStatusIcon(nota.status)" size="xs" class="q-mr-xs" />
                    {{ getStatusLabel(nota.status) }}
                  </q-badge>
                </div>
              </div>


              <!-- Filial -->
              <div class="col-12 col-sm-3 col-md-2">
                <div class="text-caption text-grey-7">
                  <q-icon name="business" size="xs" class="q-mr-xs" />
                  Filial
                </div>
                <div class=" ellipsis">
                  {{ nota.filial.filial }}
                </div>
              </div>

              <!-- Natureza de Operação -->
              <div class="col-12 col-sm-4 col-md-4">
                <div class="text-caption text-grey-7">
                  <q-icon name="description" size="xs" class="q-mr-xs" />
                  Natureza
                </div>
                <div class=" ellipsis">
                  {{ nota.naturezaOperacao?.naturezaoperacao || '-' }}
                </div>
              </div>


              <!-- Data/Hora de Emissão -->
              <div class="col-12 col-sm-2 col-md-3">
                <div class="text-caption text-grey-7 ellipsis">
                  <q-icon name="event" size="xs" class="q-mr-xs" />
                  Emissão e Saída
                </div>
                <div class=" ellipsis">
                  {{ formatDate(nota.emissao) }}
                  <span class="text-caption text-grey-7">
                    |
                    {{ formatDateTime(nota.saida) }}
                  </span>
                </div>
              </div>


            </div>
          </q-item-section>

          <!-- Ações -->
          <!-- <q-item-section side>
            <q-btn flat round dense icon="more_vert" @click.stop="handleShowMenu()">
              <q-menu>
                <q-list style="min-width: 150px">
                  <q-item clickable v-close-popup @click="handleViewNota(nota.codnotafiscal)">
                    <q-item-section avatar>
                      <q-icon name="visibility" />
                    </q-item-section>
                    <q-item-section>Visualizar</q-item-section>
                  </q-item>

                  <q-item clickable v-close-popup @click="handleEditNota(nota.codnotafiscal)"
                    :disable="isNotaBloqueada(nota.status)">
                    <q-item-section avatar>
                      <q-icon name="edit" />
                    </q-item-section>
                    <q-item-section>Editar</q-item-section>
                  </q-item>

                  <q-separator />

                  <q-item clickable v-close-popup @click="handleDeleteNota(nota)"
                    :disable="isNotaBloqueada(nota.status)">
                    <q-item-section avatar>
                      <q-icon name="delete" color="negative" />
                    </q-item-section>
                    <q-item-section class="text-negative">Excluir</q-item-section>
                  </q-item>
                </q-list>
              </q-menu>
            </q-btn>
          </q-item-section> -->
        </q-item>
      </q-list>

      <template v-slot:loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="40px" />
        </div>
      </template>
    </q-infinite-scroll>

    <!-- FAB para Nova Nota -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="handleCreateNota" :disable="loading">
        <q-tooltip>Nova Nota</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
