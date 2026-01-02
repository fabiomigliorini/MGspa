<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from 'src/stores/notaFiscalStore'
import { getEnteIcon } from 'src/composables/useTributoIcons'
import NotaFiscalItemNav from 'src/components/NotaFiscalItem/NotaFiscalItemNav.vue'
import NotaFiscalItemTributoDialog from 'src/components/NotaFiscalItem/NotaFiscalItemTributoDialog.vue'
import { storeToRefs } from 'pinia'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const notaFiscalStore = useNotaFiscalStore()

// State
const loading = ref(false)
const codnotafiscal = computed(() => route.params.codnotafiscal)
const codnotafiscalitem = computed(() => route.params.codnotafiscalitem)
const nota = computed(() => notaFiscalStore.currentNota)
const tributos = computed(() => notaFiscalStore.editingItem?.tributos || [])
const notaBloqueada = computed(() => {
  if (!nota.value) return false
  return ['AUT', 'CAN', 'INU'].includes(nota.value.status)
})

// Usa o editingItem do store diretamente (ref reativa)
const { editingItem } = storeToRefs(notaFiscalStore)

// Dialog state
const tributoDialog = ref(false)
const tributoSelecionado = ref(null)

// Columns para a tabela de tributos
const columns = [
  { name: 'tributo', label: 'Tributo', field: 'tributo', align: 'left', sortable: true },
  { name: 'cst', label: 'CST', field: 'cst', align: 'center', sortable: true },
  { name: 'base', label: 'Base', field: 'base', align: 'right', sortable: true, format: (val) => `R$ ${(val || 0).toFixed(2)}` },
  { name: 'aliquota', label: 'Alíquota', field: 'aliquota', align: 'right', sortable: true, format: (val) => `${(val || 0).toFixed(2)}%` },
  { name: 'valor', label: 'Valor', field: 'valor', align: 'right', sortable: true, format: (val) => `R$ ${(val || 0).toFixed(2)}` },
  { name: 'credito', label: 'Gera Crédito', field: 'geracredito', align: 'center', sortable: true },
  { name: 'actions', label: 'Ações', field: 'actions', align: 'center' },
]

// Methods
const loadFormData = async () => {
  try {
    loading.value = true

    // Carrega a nota fiscal se ainda não foi carregada
    if (!nota.value || nota.value.codnotafiscal !== parseInt(codnotafiscal.value)) {
      await notaFiscalStore.fetchNota(codnotafiscal.value)
    }

    // Verifica se já está editando este item
    if (!editingItem.value || editingItem.value.codnotafiscalprodutobarra !== parseInt(codnotafiscalitem.value)) {
      // Inicia a edição do item (cria cópia no store)
      await notaFiscalStore.startEditingItem(codnotafiscalitem.value)
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar dados: ' + error.message,
    })
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  loading.value = true
  try {
    // Salva o item completo, incluindo os tributos que já estão em editingItem
    await notaFiscalStore.updateItemDetalhes(codnotafiscalitem.value, editingItem.value)
    $q.notify({
      type: 'positive',
      message: 'Tributos atualizados com sucesso',
    })
    // Limpa o item em edição após salvar
    notaFiscalStore.clearEditingItem()
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao salvar: ' + error.message,
    })
  } finally {
    loading.value = false
  }
}

const handleCancel = () => {
  // Limpa o item em edição ao cancelar
  notaFiscalStore.clearEditingItem()
  router.push({
    name: 'nota-fiscal-view',
    params: { codnotafiscal: codnotafiscal.value },
  })
}

const abrirDialogNovo = () => {
  tributoSelecionado.value = null
  tributoDialog.value = true
}

const abrirDialogEditar = (tributo) => {
  tributoSelecionado.value = tributo
  tributoDialog.value = true
}

const salvarTributo = async (tributoData) => {
  try {
    loading.value = true

    if (tributoData.codnotafiscalitemtributo) {
      // Editar
      await notaFiscalStore.updateItem(tributoData.codnotafiscalitemtributo, tributoData)
      $q.notify({
        type: 'positive',
        message: 'Tributo atualizado com sucesso',
      })
    } else {
      // Criar
      await notaFiscalStore.createItem(codnotafiscalitem.value, tributoData)
      $q.notify({
        type: 'positive',
        message: 'Tributo adicionado com sucesso',
      })
    }

    tributoDialog.value = false
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao salvar tributo: ' + error.message,
    })
  } finally {
    loading.value = false
  }
}

const excluirTributo = async (tributo) => {
  $q.dialog({
    title: 'Confirmar Exclusão',
    message: `Deseja realmente excluir o tributo ${tributo.tributo?.nome || 'selecionado'}?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      loading.value = true
      await notaFiscalStore.deleteItemTributo(tributo.codnotafiscalitemtributo)
      $q.notify({
        type: 'positive',
        message: 'Tributo excluído com sucesso',
      })
      tributoDialog.value = false
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir tributo: ' + error.message,
      })
    } finally {
      loading.value = false
    }
  })
}

// Lifecycle
onMounted(() => {
  loadFormData()
})
</script>

<template>
  <q-page padding>
    <div style="max-width: 700px; margin: 0 auto">
      <q-form @submit.prevent="handleSubmit">
        <!-- Header -->
        <div class="row items-center q-mb-md">
          <div class="text-h5">
            <q-btn flat dense round icon="arrow_back" @click="handleCancel" class="q-mr-sm" size="0.8em"
              :disable="loading" />
            Reforma Tributária - Item #{{ editingItem?.ordem }} - NFe #{{ nota?.numero }}
          </div>
          <q-space />
          <q-btn flat dense color="grey-7" icon="close" @click="handleCancel" :disable="loading" class="q-mr-sm">
            <q-tooltip>Cancelar</q-tooltip>
          </q-btn>
          <q-btn unelevated color="primary" icon="save" label="Salvar" type="submit" :loading="loading"
            :disable="notaBloqueada" />
        </div>

        <q-banner v-if="notaBloqueada && nota" class="bg-warning text-white q-mb-md" rounded>
          <template v-slot:avatar>
            <q-icon name="lock" />
          </template>
          Esta nota está {{ nota.status }} e não pode ser editada.
        </q-banner>

        <!-- Navegação -->
        <NotaFiscalItemNav :codnotafiscal="codnotafiscal" :codnotafiscalitem="codnotafiscalitem" />

        <q-banner class="bg-info text-white q-mb-md" rounded>
          <template v-slot:avatar>
            <q-icon name="info" />
          </template>
          Tributos da Reforma Tributária (CBS, IBS, etc). Gerencie os tributos aplicáveis a este item.
        </q-banner>

        <!-- Tabela de Tributos -->
        <q-card flat bordered>
          <q-card-section>
            <div class="row items-center q-mb-md">
              <div class="text-subtitle1 text-weight-bold">
                <q-icon name="gavel" size="sm" class="q-mr-xs" />
                TRIBUTOS DA REFORMA TRIBUTÁRIA
              </div>
              <q-space />
              <q-btn unelevated color="primary" icon="add" label="Adicionar Tributo" @click="abrirDialogNovo"
                :disable="notaBloqueada" size="sm" />
            </div>

            <q-table :rows="tributos" :columns="columns" row-key="codnotafiscalitemtributo" :loading="loading" flat
              bordered :rows-per-page-options="[10, 25, 50, 0]" no-data-label="Nenhum tributo cadastrado">
              <template v-slot:body-cell-tributo="props">
                <q-td :props="props">
                  <div class="row items-center q-gutter-xs">
                    <q-icon v-if="props.row.tributo?.ente" :name="getEnteIcon(props.row.tributo.ente)" size="sm"
                      :color="props.row.tributo.ente === 'FEDERAL' ? 'blue' : props.row.tributo.ente === 'ESTADUAL' ? 'green' : 'orange'" />
                    <div>
                      <div class="text-weight-medium">{{ props.row.tributo?.codigo || '-' }}</div>
                      <div class="text-caption text-grey-7">{{ props.row.tributo?.descricao || '' }}</div>
                    </div>
                  </div>
                </q-td>
              </template>

              <template v-slot:body-cell-cst="props">
                <q-td :props="props">
                  <q-badge v-if="props.row.cst" outline color="primary">
                    {{ props.row.cst }}
                  </q-badge>
                  <span v-else class="text-grey-5">-</span>
                </q-td>
              </template>

              <template v-slot:body-cell-credito="props">
                <q-td :props="props">
                  <q-icon v-if="props.row.geracredito" name="check_circle" color="positive" size="sm">
                    <q-tooltip>Gera crédito de R$ {{ (props.row.valorcredito || 0).toFixed(2) }}</q-tooltip>
                  </q-icon>
                  <q-icon v-else name="cancel" color="grey-4" size="sm">
                    <q-tooltip>Não gera crédito</q-tooltip>
                  </q-icon>
                </q-td>
              </template>

              <template v-slot:body-cell-actions="props">
                <q-td :props="props">
                  <q-btn flat dense round icon="edit" color="primary" size="sm" @click="abrirDialogEditar(props.row)"
                    :disable="notaBloqueada">
                    <q-tooltip>Editar</q-tooltip>
                  </q-btn>
                  <q-btn flat dense round icon="delete" color="negative" size="sm" @click="excluirTributo(props.row)"
                    :disable="notaBloqueada">
                    <q-tooltip>Excluir</q-tooltip>
                  </q-btn>
                </q-td>
              </template>

              <template v-slot:no-data>
                <div class="full-width column flex-center q-pa-lg text-grey-6">
                  <q-icon name="info" size="2em" class="q-mb-sm" />
                  <div class="text-subtitle1">Nenhum tributo cadastrado</div>
                  <div class="text-caption">
                    Clique em "Adicionar Tributo" para incluir tributos da reforma tributária
                  </div>
                </div>
              </template>
            </q-table>
          </q-card-section>
        </q-card>

        <!-- Resumo de Valores (se houver tributos) -->
        <q-card v-if="tributos.length > 0" flat bordered class="q-mt-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              <q-icon name="calculate" size="sm" class="q-mr-xs" />
              RESUMO
            </div>

            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-4">
                <q-card flat bordered>
                  <q-card-section class="text-center">
                    <div class="text-caption text-grey-7">Total Base de Cálculo</div>
                    <div class="text-h6 text-primary">
                      R$ {{tributos.reduce((acc, t) => acc + (t.base || 0), 0).toFixed(2)}}
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <div class="col-12 col-sm-4">
                <q-card flat bordered>
                  <q-card-section class="text-center">
                    <div class="text-caption text-grey-7">Total Tributos</div>
                    <div class="text-h6 text-negative">
                      R$ {{tributos.reduce((acc, t) => acc + (t.valor || 0), 0).toFixed(2)}}
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <div class="col-12 col-sm-4">
                <q-card flat bordered>
                  <q-card-section class="text-center">
                    <div class="text-caption text-grey-7">Total Créditos</div>
                    <div class="text-h6 text-positive">
                      R$ {{tributos.filter(t => t.geracredito).reduce((acc, t) => acc + (t.valorcredito || 0),
                        0).toFixed(2)}}
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </q-form>
    </div>

    <!-- Dialog de Tributo -->
    <NotaFiscalItemTributoDialog v-model="tributoDialog" :tributo="tributoSelecionado" :nota-bloqueada="notaBloqueada"
      @save="salvarTributo" @delete="excluirTributo" />
  </q-page>
</template>
