<template>
  <q-page class="bg-grey-1">
    <!-- Loading inicial dos tributos -->
    <q-inner-loading :showing="store.tributosLoading">
      <q-spinner-gears size="50px" color="primary" />
    </q-inner-loading>

    <template v-if="!store.tributosLoading && store.tributos.length > 0">
      <!-- Tabs dos Tributos -->
      <q-card flat square class="bg-white">
        <q-tabs
          v-model="store.activeTab"
          class="text-grey-7"
          active-color="primary"
          indicator-color="primary"
          align="left"
          @update:model-value="onTabChange"
        >
          <!-- Tabs dinâmicas dos tributos -->
          <q-tab
            v-for="tributo in store.tributos"
            :key="tributo.codtributo"
            :name="tributo.codtributo"
          >
            <span>{{ tributo.codigo }} {{ tributo.ente }}</span>
            <q-tooltip>{{ tributo.descricao }} - {{ tributo.ente }}</q-tooltip>
          </q-tab>

          <!-- Tab para adicionar novo tributo -->
          <q-btn flat color="primary" @click="novoTributo">Adicionar</q-btn>
        </q-tabs>
      </q-card>

      <!-- Conteúdo da Tab Ativa -->
      <div class="q-pa-md">
        <q-card flat bordered>
          <!-- Header da tabela -->
          <q-card-section class="row items-center justify-between">
            <div class="row items-center q-gutter-sm">
              <div>
                <div class="text-h6">
                  Regras de {{ store.currentTributo?.descricao || store.activeTab }}
                </div>
                <div class="text-caption text-grey-7">
                  {{ store.currentTributo?.ente }}
                </div>
              </div>
              <q-btn
                flat
                dense
                round
                size="sm"
                icon="edit"
                color="grey-7"
                @click="editarTributo(store.currentTributo)"
              >
                <q-tooltip>Editar tributo</q-tooltip>
              </q-btn>
            </div>
            <q-btn color="primary" icon="add" label="Nova Regra" unelevated @click="novaRegra" />
          </q-card-section>

          <q-separator />

          <!-- Tabela com Scroll Infinito -->
          <q-scroll-area :style="`height: ${tableHeight}px`" @scroll="onScroll">
            <q-table
              :rows="store.currentRegras"
              :columns="columns"
              row-key="codtributacaoregra"
              flat
              hide-pagination
              :loading="store.currentPagination?.loading"
              virtual-scroll
              :rows-per-page-options="[0]"
            >
              <!-- Coluna NCM -->
              <template v-slot:body-cell-ncm="props">
                <q-td :props="props">
                  <span class="text-mono">{{ props.value || '-' }}</span>
                </q-td>
              </template>

              <!-- Coluna Estado Destino -->
              <template v-slot:body-cell-codestadodestino="props">
                <q-td :props="props">
                  <q-badge v-if="props.row.EstadoDestino" color="blue-grey-5">
                    {{ props.row.EstadoDestino.sigla }}
                  </q-badge>
                  <span v-else class="text-grey-5">-</span>
                </q-td>
              </template>

              <!-- Coluna Cidade Destino -->
              <template v-slot:body-cell-codcidadedestino="props">
                <q-td :props="props">
                  <q-badge v-if="props.row.CidadeDestino" color="blue-grey-5">
                    {{ props.row.CidadeDestino?.cidade }} /
                    {{ props.row.CidadeDestino?.uf }}
                  </q-badge>
                  <span v-else class="text-grey-5">-</span>
                </q-td>
              </template>

              <!-- Coluna CST -->
              <template v-slot:body-cell-cst="props">
                <q-td :props="props">
                  <q-badge v-if="props.value" color="primary" outline>
                    {{ props.value }}
                  </q-badge>
                  <span v-else class="text-grey-5">-</span>
                </q-td>
              </template>

              <!-- Coluna Alíquota -->
              <template v-slot:body-cell-aliquota="props">
                <q-td :props="props" class="text-weight-medium text-mono">
                  {{ formatPercent(props.value) }}
                </q-td>
              </template>

              <!-- Coluna Base Percentual -->
              <template v-slot:body-cell-basepercentual="props">
                <q-td :props="props" class="text-mono">
                  {{ formatPercent(props.value) }}
                </q-td>
              </template>

              <!-- Coluna Gera Crédito -->
              <template v-slot:body-cell-geracredito="props">
                <q-td :props="props">
                  <q-icon
                    :name="props.value ? 'check_circle' : 'cancel'"
                    :color="props.value ? 'positive' : 'grey-5'"
                    size="sm"
                  />
                </q-td>
              </template>

              <!-- Coluna Benefício -->
              <template v-slot:body-cell-beneficiocodigo="props">
                <q-td :props="props">
                  <q-badge v-if="props.value" color="orange" outline>
                    {{ props.value }}
                  </q-badge>
                  <span v-else class="text-grey-5">-</span>
                </q-td>
              </template>

              <!-- Coluna Ações -->
              <template v-slot:body-cell-actions="props">
                <q-td :props="props">
                  <div class="row no-wrap q-gutter-xs">
                    <q-btn
                      flat
                      dense
                      round
                      icon="edit"
                      color="primary"
                      size="sm"
                      @click="editarRegra(props.row)"
                    >
                      <q-tooltip>Editar</q-tooltip>
                    </q-btn>
                    <q-btn
                      flat
                      dense
                      round
                      icon="content_copy"
                      color="blue-grey"
                      size="sm"
                      @click="duplicarRegra(props.row)"
                    >
                      <q-tooltip>Duplicar</q-tooltip>
                    </q-btn>
                    <q-btn
                      flat
                      dense
                      round
                      icon="delete"
                      color="negative"
                      size="sm"
                      @click="confirmarExclusao(props.row)"
                    >
                      <q-tooltip>Excluir</q-tooltip>
                    </q-btn>
                  </div>
                </q-td>
              </template>

              <!-- Loading -->
              <template v-slot:loading>
                <q-inner-loading showing color="primary" />
              </template>
            </q-table>

            <!-- Loading do scroll infinito -->
            <div v-if="store.currentPagination?.loading" class="q-pa-md text-center">
              <q-spinner color="primary" size="40px" />
              <div class="text-caption text-grey-6 q-mt-sm">Carregando...</div>
            </div>

            <!-- Indicador de fim dos dados -->
            <div
              v-else-if="!store.currentPagination?.hasMore && store.currentRegras.length > 0"
              class="q-pa-md text-center text-grey-6"
            >
              <q-icon name="check_circle" size="sm" class="q-mr-xs" />
              Todos os registros carregados
            </div>

            <!-- Sem dados -->
            <div
              v-else-if="!store.currentPagination?.loading && store.currentRegras.length === 0"
              class="q-pa-xl text-center"
            >
              <q-icon name="inbox" size="64px" color="grey-5" />
              <div class="text-h6 text-grey-6 q-mt-md">Nenhuma regra cadastrada</div>
              <div class="text-caption text-grey-6">Clique em "Nova Regra" para começar</div>
            </div>
          </q-scroll-area>
        </q-card>
      </div>
    </template>

    <!-- Estado vazio (sem tributos) -->
    <div
      v-else-if="!store.tributosLoading && store.tributos.length === 0"
      class="q-pa-xl text-center"
    >
      <q-icon name="warning" size="64px" color="grey-5" />
      <div class="text-h6 text-grey-6 q-mt-md">Nenhum tributo configurado</div>
      <div class="text-caption text-grey-6 q-mb-md">Configure os tributos para começar</div>
      <q-btn color="primary" icon="add" label="Adicionar Tributo" unelevated @click="novoTributo" />
    </div>

    <!-- Dialog para Tributo (Novo/Editar) -->
    <q-dialog v-model="tributoDialog" persistent>
      <q-card style="width: 400px">
        <q-card-section>
          <div class="text-h6">
            {{ tributoDialogMode === 'create' ? 'Novo Tributo' : 'Editar Tributo' }}
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input
            v-model="tributoForm.codigo"
            label="Sigla *"
            outlined
            maxlength="10"
            counter
            :rules="[(val) => !!val || 'Campo obrigatório']"
          />
          <q-input
            v-model="tributoForm.descricao"
            label="Descrição *"
            outlined
            class="q-mt-md"
            :rules="[(val) => !!val || 'Campo obrigatório']"
          />
          <q-select
            v-model="tributoForm.ente"
            label="Ente *"
            :options="['FEDERAL', 'ESTADUAL', 'MUNICIPAL']"
            outlined
            class="q-mt-md"
            :rules="[(val) => !!val || 'Campo obrigatório']"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            v-if="tributoDialogMode === 'edit'"
            flat
            label="Excluir"
            color="negative"
            @click="confirmarExclusaoTributo"
          />
          <q-space />
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn
            unelevated
            label="Salvar"
            color="primary"
            @click="salvarTributo"
            :loading="store.isLoading"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialog para Regra (Nova/Editar) -->
    <q-dialog v-model="regraDialog" persistent>
      <q-card style="width: 600px">
        <q-card-section class="bg-primary text-white">
          <div class="text-h6">
            {{ regraDialogMode === 'create' ? 'Nova Regra' : 'Editar Regra' }}
          </div>
          <div class="text-caption">
            {{ store.currentTributo?.descricao || '' }}
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section class="q-pt-md">
          <!-- Chave de busca da regra -->
          <div class="text-caption text-grey-7 q-mb-sm">Incide sobre</div>
          <div class="row q-col-gutter-md">
            <!-- NCM -->
            <q-input
              v-model="regraForm.ncm"
              label="NCM"
              outlined
              clearable
              placeholder="12345678"
              maxlength="8"
              mask="########"
              :rules="[
                (val) =>
                  !val || (val.length >= 2 && val.length <= 8) || 'Deve ter entre 2 e 8 dígitos',
              ]"
              lazy-rules
              bottom-slots
              class="q-mb-sm col-4"
              input-class="text-center"
            />

            <!-- ESTADO -->
            <SelectEstado
              v-model="regraForm.codestadodestino"
              label="UF"
              placeholder="Selecione UF"
              custom-class="q-mb-sm col-3"
              @clear="regraForm.codcidadedestino = null"
            />

            <!-- CIDADE -->
            <SelectCidade
              v-model="regraForm.codcidadedestino"
              label="Cidade"
              placeholder="Digite para buscar"
              custom-class="q-mb-sm col-5"
            />
          </div>

          <div class="text-caption text-grey-7 q-mb-sm">Classificação, Alíquotas e Benefício</div>
          <div class="row q-col-gutter-md">
            <!-- CST -->
            <q-input
              v-model="regraForm.cst"
              label="CST *"
              outlined
              maxlength="3"
              placeholder="000"
              mask="###"
              :rules="[
                (val) => !!val || 'Campo obrigatório',
                (val) => val?.length === 3 || 'Deve ter 3 dígitos',
              ]"
              lazy-rules
              bottom-slots
              class="q-mb-sm col-3"
              input-class="text-center"
            />

            <!-- Classificação Tributária -->
            <q-input
              v-model="regraForm.cclasstrib"
              label="Classificação Tributária *"
              outlined
              maxlength="6"
              placeholder="000000"
              mask="######"
              :rules="[
                (val) => !!val || 'Campo obrigatório',
                (val) => val?.length === 6 || 'Deve ter 6 dígitos',
              ]"
              lazy-rules
              bottom-slots
              class="q-mb-sm col-5"
              input-class="text-center"
            />

            <!-- SE GERA CREDITO -->
            <div class="q-mb-sm col-4" style="height: 56px; display: flex; align-items: center">
              <q-toggle v-model="regraForm.geracredito" label="Gera crédito" color="primary" />
            </div>
          </div>

          <div class="row q-col-gutter-md">
            <!-- Base Percentual -->
            <q-input
              v-model.number="regraForm.basepercentual"
              label="Base (%)"
              outlined
              type="number"
              min="0"
              max="100"
              step="0.01"
              placeholder="Ex: 100%"
              bottom-slots
              class="q-mb-sm col-3"
              input-class="text-right"
            />
            <!-- Alíquota -->
            <q-input
              v-model.number="regraForm.aliquota"
              label="Alíquota (%) "
              outlined
              type="number"
              min="0"
              max="100"
              step="0.01"
              placeholder="Ex: 8.5"
              bottom-slots
              class="q-mb-sm col-3"
              input-class="text-right"
            />
            <!-- Benefício -->
            <q-input
              v-model="regraForm.beneficiocodigo"
              label="Código Benefício"
              outlined
              clearable
              placeholder="Ex: BE001"
              bottom-slots
              class="q-mb-sm col-6"
            />
          </div>

          <!-- VIGENCIA -->
          <div class="text-caption text-grey-7 q-mb-sm">Vigência</div>
          <div class="row q-col-gutter-md">
            <!-- Vigência Início -->
            <q-input
              v-model="regraForm.vigenciainicio"
              label="Do dia"
              outlined
              clearable
              placeholder="DD/MM/AAAA"
              mask="##/##/####"
              bottom-slots
              class="q-mb-sm col-6"
              input-class="text-center"
            >
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                    <q-date v-model="regraForm.vigenciainicio" mask="DD/MM/YYYY">
                      <div class="row items-center justify-end">
                        <q-btn v-close-popup label="Fechar" color="primary" flat />
                      </div>
                    </q-date>
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>
            <!-- Vigência Fim -->
            <q-input
              v-model="regraForm.vigenciafim"
              label="Até o dia"
              outlined
              clearable
              placeholder="DD/MM/AAAA"
              mask="##/##/####"
              bottom-slots
              class="q-mb-sm col-6"
              input-class="text-center"
            >
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                    <q-date v-model="regraForm.vigenciafim" mask="DD/MM/YYYY">
                      <div class="row items-center justify-end">
                        <q-btn v-close-popup label="Fechar" color="primary" flat />
                      </div>
                    </q-date>
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>
          </div>

          <div class="row q-col-gutter-md">
            <!-- Observações -->
            <q-input
              v-model="regraForm.observacoes"
              label="Observações"
              outlined
              type="textarea"
              rows="4"
              clearable
              placeholder="Observações sobre esta regra"
              bottom-slots
              class="col-12"
            />
          </div>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn
            unelevated
            label="Salvar"
            color="primary"
            icon="save"
            @click="salvarRegra"
            :loading="store.isLoading"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useTributacaoStore } from 'stores/tributacao'
import { useQuasar } from 'quasar'
import SelectEstado from 'src/components/selects/SelectEstado.vue'
import SelectCidade from 'src/components/selects/SelectCidade.vue'

const $q = useQuasar()
const store = useTributacaoStore()

// Colunas da tabela
const columns = [
  {
    name: 'ncm',
    label: 'NCM',
    field: 'ncm',
    align: 'left',
    sortable: true,
  },
  {
    name: 'codestadodestino',
    label: 'Estado',
    field: 'codestadodestino',
    align: 'center',
    sortable: true,
  },
  {
    name: 'codcidadedestino',
    label: 'Cidade',
    field: 'codcidadedestino',
    align: 'left',
    sortable: true,
  },
  {
    name: 'cst',
    label: 'CST',
    field: 'cst',
    align: 'center',
    sortable: true,
  },
  {
    name: 'aliquota',
    label: 'Alíquota',
    field: 'aliquota',
    align: 'right',
    sortable: true,
  },
  {
    name: 'basepercentual',
    label: 'Base %',
    field: 'basepercentual',
    align: 'right',
    sortable: true,
  },
  {
    name: 'geracredito',
    label: 'Crédito',
    field: 'geracredito',
    align: 'center',
    sortable: true,
  },
  {
    name: 'beneficiocodigo',
    label: 'Benefício',
    field: 'beneficiocodigo',
    align: 'center',
    sortable: true,
  },
  {
    name: 'actions',
    label: 'Ações',
    align: 'center',
  },
]

// Altura da tabela (dinâmica)
const tableHeight = ref(600)

const updateTableHeight = () => {
  // 280px = header + toolbar + tabs + padding
  tableHeight.value = window.innerHeight - 280
}

onMounted(() => {
  updateTableHeight()
  window.addEventListener('resize', updateTableHeight)
  inicializar()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateTableHeight)
})

// Inicialização
const inicializar = async () => {
  try {
    await store.fetchTributos()
    if (store.activeTab) {
      await store.fetchRegras(true)
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar dados',
      caption: error.message,
    })
  }
}

// Scroll infinito
const onScroll = (info) => {
  const { verticalPercentage } = info

  if (verticalPercentage > 0.8 && !store.currentPagination?.loading) {
    if (store.currentPagination?.hasMore) {
      store.loadMore()
    }
  }
}

// Mudança de tab
const onTabChange = (newTab) => {
  // Ignora a mudança se for a tab __new__
  if (newTab === '__new__') {
    return
  }
  store.setActiveTab(newTab)
}

// Formatação
const formatPercent = (value) => {
  if (value === null || value === undefined) return '-'
  return `${parseFloat(value).toFixed(2)}%`
}

// ========== REGRAS ==========
const regraDialog = ref(false)
const regraDialogMode = ref('create') // 'create' | 'edit'
const regraForm = ref({
  codtributacaoregra: null,
  codtributo: null,
  codnaturezaoperacao: null,
  ncm: null,
  codestadodestino: null,
  codcidadedestino: null,
  basepercentual: null,
  aliquota: null,
  cst: null,
  cclasstrib: null,
  geracredito: false,
  beneficiocodigo: null,
  observacoes: null,
  vigenciainicio: null,
  vigenciafim: null,
})

const novaRegra = () => {
  regraDialogMode.value = 'create'
  regraForm.value = {
    codtributacaoregra: null,
    codtributo: store.activeTab,
    codnaturezaoperacao: null,
    ncm: '',
    codestadodestino: null,
    codcidadedestino: null,
    basepercentual: 100,
    aliquota: null,
    cst: '',
    cclasstrib: '',
    geracredito: false,
    beneficiocodigo: null,
    observacoes: null,
    vigenciainicio: null,
    vigenciafim: null,
  }
  regraDialog.value = true
}

const editarRegra = (regra) => {
  regraDialogMode.value = 'edit'
  regraForm.value = {
    codtributacaoregra: regra.codtributacaoregra,
    codtributo: regra.tributo?.codtributo || store.activeTab,
    codnaturezaoperacao: regra.codnaturezaoperacao,
    ncm: regra.ncm,
    codestadodestino: regra.codestadodestino,
    codcidadedestino: regra.codcidadedestino,
    basepercentual: regra.basepercentual,
    aliquota: regra.aliquota,
    cst: regra.cst,
    cclasstrib: regra.cclasstrib,
    geracredito: regra.geracredito,
    beneficiocodigo: regra.beneficiocodigo,
    observacoes: regra.observacoes,
    vigenciainicio: regra.vigenciainicio,
    vigenciafim: regra.vigenciafim,
  }
  regraDialog.value = true
}

const salvarRegra = async () => {
  // Validações
  if (!regraForm.value.codtributo) {
    $q.notify({
      type: 'warning',
      message: 'Tributo não selecionado',
    })
    return
  }

  if (!regraForm.value.cclasstrib || regraForm.value.cclasstrib.length !== 6) {
    $q.notify({
      type: 'warning',
      message: 'Classificação Tributária deve ter 6 dígitos',
    })
    return
  }

  if (!regraForm.value.cst || regraForm.value.cst.length !== 3) {
    $q.notify({
      type: 'warning',
      message: 'CST deve ter 3 dígitos',
    })
    return
  }

  if (regraForm.value.ncm && (regraForm.value.ncm.length < 2 || regraForm.value.ncm.length > 8)) {
    $q.notify({
      type: 'warning',
      message: 'NCM deve ter entre 2 e 8 dígitos',
    })
    return
  }

  try {
    if (regraDialogMode.value === 'create') {
      await store.createRegra(regraForm.value)
      $q.notify({
        type: 'positive',
        message: 'Regra criada com sucesso',
        icon: 'check_circle',
      })
    } else {
      await store.updateRegra(regraForm.value.codtributacaoregra, regraForm.value)
      $q.notify({
        type: 'positive',
        message: 'Regra atualizada com sucesso',
        icon: 'check_circle',
      })
    }
    regraDialog.value = false
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: `Erro ao ${regraDialogMode.value === 'create' ? 'criar' : 'atualizar'} regra`,
      caption: error.message,
    })
  }
}

const duplicarRegra = async (regra) => {
  try {
    await store.duplicateRegra(regra.codtributacaoregra)
    $q.notify({
      type: 'positive',
      message: 'Regra duplicada com sucesso',
      icon: 'check_circle',
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao duplicar regra',
      caption: error.message,
    })
  }
}

const confirmarExclusao = (regra) => {
  $q.dialog({
    title: 'Confirmar exclusão',
    message: 'Deseja realmente excluir esta regra?',
    cancel: {
      flat: true,
      label: 'Cancelar',
    },
    ok: {
      unelevated: true,
      label: 'Excluir',
      color: 'negative',
    },
    persistent: true,
  }).onOk(async () => {
    try {
      console.log(regra.codtributacaoregra)
      await store.deleteRegra(regra.codtributacaoregra)
      $q.notify({
        type: 'positive',
        message: 'Regra excluída com sucesso',
        icon: 'check_circle',
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir regra',
        caption: error.message,
      })
    }
  })
}

// ========== TRIBUTOS ==========
const tributoDialog = ref(false)
const tributoDialogMode = ref('create') // 'create' | 'edit'
const tributoForm = ref({
  codtributo: null,
  codigo: '',
  descricao: '',
  ente: null,
})

const novoTributo = () => {
  tributoDialogMode.value = 'create'
  tributoForm.value = {
    codtributo: null,
    codigo: '',
    descricao: '',
    ente: null,
  }
  tributoDialog.value = true
}

const editarTributo = (tributo) => {
  tributoDialogMode.value = 'edit'
  tributoForm.value = {
    codtributo: tributo.codtributo,
    codigo: tributo.codigo,
    descricao: tributo.descricao,
    ente: tributo.ente,
  }
  tributoDialog.value = true
}

const salvarTributo = async () => {
  // Validação simples
  if (!tributoForm.value.codigo || !tributoForm.value.descricao || !tributoForm.value.ente) {
    $q.notify({
      type: 'warning',
      message: 'Preencha todos os campos obrigatórios',
    })
    return
  }

  try {
    if (tributoDialogMode.value === 'create') {
      await store.createTributo({
        codigo: tributoForm.value.codigo,
        descricao: tributoForm.value.descricao,
        ente: tributoForm.value.ente,
      })
      $q.notify({
        type: 'positive',
        message: 'Tributo criado com sucesso',
        icon: 'check_circle',
      })
    } else {
      await store.updateTributo(tributoForm.value.codtributo, {
        codigo: tributoForm.value.codigo,
        descricao: tributoForm.value.descricao,
        ente: tributoForm.value.ente,
      })
      $q.notify({
        type: 'positive',
        message: 'Tributo atualizado com sucesso',
        icon: 'check_circle',
      })
    }
    tributoDialog.value = false
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: `Erro ao ${tributoDialogMode.value === 'create' ? 'criar' : 'atualizar'} tributo`,
      caption: error.message,
    })
  }
}

const confirmarExclusaoTributo = () => {
  $q.dialog({
    title: 'Confirmar exclusão',
    message: `Deseja realmente excluir o tributo ${tributoForm.value.codigo}? Todas as regras associadas serão perdidas.`,
    cancel: {
      flat: true,
      label: 'Cancelar',
    },
    ok: {
      unelevated: true,
      label: 'Excluir',
      color: 'negative',
    },
    persistent: true,
  }).onOk(async () => {
    try {
      await store.deleteTributo(tributoForm.value.codtributo)
      $q.notify({
        type: 'positive',
        message: 'Tributo excluído com sucesso',
        icon: 'check_circle',
      })
      tributoDialog.value = false
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao excluir tributo',
        caption: error.message,
      })
    }
  })
}
</script>

<style scoped>
.text-mono {
  font-family: 'Courier New', monospace;
}
</style>
