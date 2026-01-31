<script setup>
import { ref, onMounted } from 'vue'
import { useTributacaoStore } from 'stores/tributacao'
import { useQuasar } from 'quasar'
import { formatPercent, formatDate } from 'src/utils/formatters'
import { getEnteIcon } from 'src/composables/useTributoIcons'
import SelectEstado from 'src/components/selects/SelectEstado.vue'
import SelectCidade from 'src/components/selects/SelectCidade.vue'
import SelectNaturezaOperacao from 'src/components/selects/SelectNaturezaOperacao.vue'
import SelectTipoProduto from 'src/components/selects/SelectTipoProduto.vue'
import SelectTipoCliente from 'src/components/selects/SelectTipoCliente.vue'

const $q = useQuasar()
const store = useTributacaoStore()

// Colunas da tabela
const columns = [
  {
    name: 'incide_sobre',
    label: 'Incide Sobre',
    field: 'codnaturezaoperacao',
    align: 'left',
    sortable: false,
  },
  {
    name: 'cst_classtrib',
    label: 'CST',
    field: 'cst',
    align: 'center',
    sortable: true,
  },
  {
    name: 'aliquota_base',
    label: 'Alíquota',
    field: 'aliquota',
    align: 'center',
    sortable: true,
  },
  {
    name: 'credito_beneficio',
    label: 'Crédito',
    field: 'geracredito',
    align: 'center',
    sortable: true,
  },
  {
    name: 'vigenciainicio',
    label: 'Vigência',
    field: 'vigenciainicio',
    align: 'center',
    sortable: true,
  },
  {
    name: 'actions',
    label: 'Ações',
    align: 'center',
  },
]

// Inicialização
onMounted(() => {
  inicializar()
})

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
const onLoad = async (_index, done) => {
  if (!store.currentPagination?.hasMore) {
    done(true) // Para o infinite scroll
    return
  }

  await store.loadMore()
  done(!store.currentPagination?.hasMore) // Para se não houver mais dados
}

// Mudança de tab
const onTabChange = (newTab) => {
  store.setActiveTab(newTab)
}

// Converte ISO date (YYYY-MM-DD ou YYYY-MM-DDTHH:MM:SS) para DD/MM/YYYY
const isoToFormDate = (isoDate) => {
  if (!isoDate) return null
  const date = new Date(isoDate)
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const year = date.getFullYear()
  return `${day}/${month}/${year}`
}

// Converte DD/MM/YYYY para YYYY-MM-DD
const formDateToIso = (formDate) => {
  if (!formDate || formDate.length !== 10) return null
  const [day, month, year] = formDate.split('/')
  return `${year}-${month}-${day}`
}

// Retorna a cor do badge de tipo de cliente
const getTipoClienteColor = (tipo) => {
  const colors = {
    PFC: 'blue',
    PFN: 'cyan',
    PJC: 'green',
    PJN: 'teal',
  }
  return colors[tipo] || 'grey'
}

// Retorna o label descritivo do tipo de cliente
const getTipoClienteLabel = (tipo) => {
  const labels = {
    PFC: 'Pessoa Física Contribuinte',
    PFN: 'Pessoa Física Não Contribuinte',
    PJC: 'Pessoa Jurídica Contribuinte',
    PJN: 'Pessoa Jurídica Não Contribuinte',
  }
  return labels[tipo] || 'Desconhecido'
}

// ========== REGRAS ==========
const regraDialog = ref(false)
const regraDialogMode = ref('create') // 'create' | 'edit'
const regraForm = ref({
  codtributacaoregra: null,
  codtributo: null,
  codnaturezaoperacao: null,
  codtipoproduto: null,
  ncm: null,
  codestadodestino: null,
  codcidadedestino: null,
  tipocliente: null,
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
    codtipoproduto: null,
    ncm: '',
    codestadodestino: null,
    codcidadedestino: null,
    tipocliente: null,
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
    codtipoproduto: regra.codtipoproduto,
    ncm: regra.ncm,
    codestadodestino: regra.codestadodestino,
    codcidadedestino: regra.codcidadedestino,
    tipocliente: regra.tipocliente,
    basepercentual: regra.basepercentual,
    aliquota: regra.aliquota,
    cst: regra.cst,
    cclasstrib: regra.cclasstrib,
    geracredito: regra.geracredito,
    beneficiocodigo: regra.beneficiocodigo,
    observacoes: regra.observacoes,
    vigenciainicio: isoToFormDate(regra.vigenciainicio),
    vigenciafim: isoToFormDate(regra.vigenciafim),
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
    // Prepara os dados para envio convertendo as datas
    const dataToSend = {
      ...regraForm.value,
      vigenciainicio: formDateToIso(regraForm.value.vigenciainicio),
      vigenciafim: formDateToIso(regraForm.value.vigenciafim),
    }

    if (regraDialogMode.value === 'create') {
      await store.createRegra(dataToSend)
      $q.notify({
        type: 'positive',
        message: 'Regra criada com sucesso',
        icon: 'check_circle',
      })
    } else {
      await store.updateRegra(regraForm.value.codtributacaoregra, dataToSend)
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

const duplicarRegra = (regra) => {
  // Abre o formulário de nova regra com os dados da regra existente
  regraDialogMode.value = 'create'
  regraForm.value = {
    codtributacaoregra: null, // Não copia o ID
    codtributo: regra.tributo?.codtributo || store.activeTab,
    codnaturezaoperacao: regra.codnaturezaoperacao,
    codtipoproduto: regra.codtipoproduto,
    ncm: regra.ncm,
    codestadodestino: regra.codestadodestino,
    codcidadedestino: regra.codcidadedestino,
    tipocliente: regra.tipocliente,
    basepercentual: regra.basepercentual,
    aliquota: regra.aliquota,
    cst: regra.cst,
    cclasstrib: regra.cclasstrib,
    geracredito: regra.geracredito,
    beneficiocodigo: regra.beneficiocodigo,
    observacoes: regra.observacoes,
    vigenciainicio: isoToFormDate(regra.vigenciainicio),
    vigenciafim: isoToFormDate(regra.vigenciafim),
  }
  regraDialog.value = true
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

// Opções de ente com ícones
const enteOptions = [
  { label: 'FEDERAL', value: 'FEDERAL', icon: 'account_balance' },
  { label: 'ESTADUAL', value: 'ESTADUAL', icon: 'map' },
  { label: 'MUNICIPAL', value: 'MUNICIPAL', icon: 'location_city' },
]

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
          class="text-grey-7 bg-grey-2"
          active-color="white"
          active-bg-color="primary"
          indicator-color="transparent"
          align="left"
          inline-label
          no-caps
          @update:model-value="onTabChange"
        >
          <!-- Tabs dinâmicas dos tributos -->
          <q-tab
            v-for="tributo in store.tributos"
            :key="tributo.codtributo"
            :name="tributo.codtributo"
            :icon="getEnteIcon(tributo.ente)"
            class="q-px-lg"
          >
            <span>{{ tributo.codigo }}</span>
            <q-tooltip>{{ tributo.descricao }} - {{ tributo.ente }}</q-tooltip>
          </q-tab>

          <!-- Tab para adicionar novo tributo -->
          <q-btn flat icon="add" unelevated @click="novoTributo">
            <q-tooltip>Novo Tributo</q-tooltip>
          </q-btn>
        </q-tabs>
      </q-card>

      <!-- Conteúdo da Tab Ativa -->
      <div class="q-pa-md">
        <q-card flat>
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
                round
                size="sm"
                icon="edit"
                color="primary"
                @click="editarTributo(store.currentTributo)"
              >
                <q-tooltip>Editar tributo</q-tooltip>
              </q-btn>
            </div>
          </q-card-section>

          <q-separator />

          <!-- Tabela com Scroll Infinito -->
          <q-infinite-scroll @load="onLoad" :offset="250">
            <q-table
              :rows="store.currentRegras"
              :columns="columns"
              row-key="codtributacaoregra"
              flat
              hide-pagination
              :loading="store.currentPagination?.loading"
              virtual-scroll
              :rows-per-page-options="[0]"
              wrap-cells
            >
              <!-- Coluna Incide Sobre (consolidada) -->
              <template v-slot:body-cell-incide_sobre="props">
                <q-td :props="props">
                  <div class="column q-gutter-xs">
                    <!-- 1. Natureza de Operação -->
                    <div v-if="props.row.codnaturezaoperacao" class="row items-center q-gutter-xs">
                      <q-badge color="blue-grey-7" outline dense>
                        {{ props.row.codnaturezaoperacao }}
                      </q-badge>
                      <span class="text-caption text-grey-7">
                        {{ props.row.naturezaOperacao?.naturezaoperacao || 'Nat. Op.' }}
                      </span>
                    </div>

                    <!-- 2. Estado Destino -->
                    <div v-if="props.row.codestadodestino" class="row items-center q-gutter-xs">
                      <q-badge color="indigo" outline dense>UF</q-badge>
                      <span class="text-caption">
                        {{ props.row.estadoDestino?.sigla || props.row.codestadodestino }}
                      </span>
                    </div>

                    <!-- 3. Cidade Destino -->
                    <div v-if="props.row.codcidadedestino" class="row items-center q-gutter-xs">
                      <q-badge color="indigo" outline dense>Cidade</q-badge>
                      <span class="text-caption">
                        {{ props.row.cidadeDestino?.cidade || `#${props.row.codcidadedestino}` }}
                      </span>
                    </div>

                    <!-- 4. Tipo de Produto -->
                    <div v-if="props.row.codtipoproduto" class="row items-center q-gutter-xs">
                      <q-badge color="purple" outline dense>
                        {{ props.row.codtipoproduto }}
                      </q-badge>
                      <span class="text-caption text-grey-7">
                        {{ props.row.tipoProduto?.tipoproduto || 'Tipo Produto' }}
                      </span>
                    </div>

                    <!-- 5. Tipo de Cliente -->
                    <div v-if="props.row.tipocliente" class="row items-center q-gutter-xs">
                      <q-badge :color="getTipoClienteColor(props.row.tipocliente)" outline dense>
                        {{ props.row.tipocliente }}
                      </q-badge>
                      <span class="text-caption">
                        {{ getTipoClienteLabel(props.row.tipocliente) }}
                      </span>
                    </div>

                    <!-- 6. NCM -->
                    <div v-if="props.row.ncm" class="row items-center q-gutter-xs">
                      <q-badge color="deep-orange" outline dense>NCM</q-badge>
                      <span class="text-caption text-mono">{{ props.row.ncm }}</span>
                    </div>

                    <!-- Regra Genérica (nenhum critério específico) -->
                    <div
                      v-if="
                        !props.row.codnaturezaoperacao &&
                        !props.row.codestadodestino &&
                        !props.row.codcidadedestino &&
                        !props.row.codtipoproduto &&
                        !props.row.tipocliente &&
                        !props.row.ncm
                      "
                    >
                      <q-badge color="grey-5" outline dense>REGRA GENÉRICA</q-badge>
                      <div class="text-caption text-grey-6">Aplica-se a todos os casos</div>
                    </div>
                  </div>
                </q-td>
              </template>

              <!-- Coluna CST / Classificação Tributária -->
              <template v-slot:body-cell-cst_classtrib="props">
                <q-td :props="props">
                  <div class="text-center">
                    <div v-if="props.row.cst" class="text-weight-medium text-primary">
                      {{ props.row.cst }}
                    </div>
                    <div v-if="props.row.cclasstrib" class="text-mono text-caption text-grey-7">
                      {{ props.row.cclasstrib }}
                    </div>
                    <span v-if="!props.row.cst && !props.row.cclasstrib" class="text-grey-5">
                      -
                    </span>
                  </div>
                </q-td>
              </template>

              <!-- Coluna Alíquota / Base -->
              <template v-slot:body-cell-aliquota_base="props">
                <q-td :props="props">
                  <div class="text-center">
                    <div v-if="props.row.aliquota !== null" class="text-weight-medium text-mono">
                      {{ formatPercent(props.row.aliquota) }}
                    </div>
                    <div
                      v-if="props.row.basepercentual !== null && props.row.basepercentual !== 100"
                      class="text-caption text-grey-7 text-mono"
                    >
                      Base: {{ formatPercent(props.row.basepercentual) }}
                    </div>
                    <span
                      v-if="props.row.aliquota === null && props.row.basepercentual === null"
                      class="text-grey-5"
                    >
                      -
                    </span>
                  </div>
                </q-td>
              </template>

              <!-- Coluna Crédito / Benefício -->
              <template v-slot:body-cell-credito_beneficio="props">
                <q-td :props="props">
                  <div class="column q-gutter-xs items-center">
                    <q-icon
                      :name="props.row.geracredito ? 'check_circle' : 'cancel'"
                      :color="props.row.geracredito ? 'positive' : 'grey-5'"
                      size="sm"
                    />
                    <q-badge v-if="props.row.beneficiocodigo" color="orange" outline dense>
                      {{ props.row.beneficiocodigo }}
                    </q-badge>
                  </div>
                </q-td>
              </template>

              <!-- Coluna Vigência -->
              <template v-slot:body-cell-vigenciainicio="props">
                <q-td :props="props">
                  <div v-if="props.row.vigenciainicio">
                    <div class="text-caption">
                      {{ formatDate(props.row.vigenciainicio) }}
                    </div>
                    <div v-if="props.row.vigenciafim" class="text-caption text-grey-6">
                      até {{ formatDate(props.row.vigenciafim) }}
                    </div>
                  </div>
                  <span v-else class="text-grey-5">-</span>
                </q-td>
              </template>

              <!-- Coluna Ações -->
              <template v-slot:body-cell-actions="props">
                <q-td :props="props">
                  <div class="row no-wrap q-gutter-xs">
                    <q-btn
                      flat
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
          </q-infinite-scroll>
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
    <q-dialog v-model="tributoDialog">
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
            :options="enteOptions"
            outlined
            emit-value
            map-options
            option-value="value"
            option-label="label"
            class="q-mt-md"
            :rules="[(val) => !!val || 'Campo obrigatório']"
          >
            <template v-slot:option="scope">
              <q-item v-bind="scope.itemProps">
                <q-item-section avatar>
                  <q-icon :name="scope.opt.icon" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ scope.opt.label }}</q-item-label>
                </q-item-section>
              </q-item>
            </template>
            <template v-slot:selected>
              <div v-if="tributoForm.ente" class="row items-center q-gutter-xs">
                <q-icon :name="getEnteIcon(tributoForm.ente)" size="sm" />
                <span>{{ tributoForm.ente }}</span>
              </div>
            </template>
          </q-select>
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
    <q-dialog v-model="regraDialog">
      <q-card style="min-width: 800px">
        <q-card-section class="bg-primary text-white">
          <div class="text-h6">
            {{ regraDialogMode === 'create' ? 'Nova Regra' : 'Editar Regra' }}
          </div>
          <div class="text-caption">
            {{ store.currentTributo?.descricao || '' }}
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section class="q-pt-md q-pb-md">
          <!-- Chave de busca da regra - ORDEM DO MOTOR -->
          <div class="text-caption text-grey-7 q-mb-sm">
            Incide sobre (ordem de prioridade do motor)
          </div>
          <div class="row q-col-gutter-md q-mb-sm">
            <!-- 1. NATUREZA DE OPERAÇÃO (maior prioridade) -->
            <SelectNaturezaOperacao
              v-model="regraForm.codnaturezaoperacao"
              label="1. Natureza de Operação"
              custom-class="col-5"
            />

            <!-- 2. ESTADO DESTINO -->
            <SelectEstado
              v-model="regraForm.codestadodestino"
              label="2. Estado Destino"
              custom-class="col-3"
              @clear="regraForm.codcidadedestino = null"
            />

            <!-- 3. CIDADE DESTINO -->
            <SelectCidade
              v-model="regraForm.codcidadedestino"
              label="3. Cidade Destino"
              custom-class="col-4"
            />

            <!-- 4. TIPO DE PRODUTO -->
            <SelectTipoProduto
              v-model="regraForm.codtipoproduto"
              label="4. Tipo de Produto"
              custom-class="col-5"
            />

            <!-- 5. TIPO DE CLIENTE -->
            <SelectTipoCliente
              v-model="regraForm.tipocliente"
              label="5. Tipo de Cliente"
              custom-class="col-3"
            />

            <!-- 6. NCM (menor prioridade, considera tamanho) -->
            <q-input
              v-model="regraForm.ncm"
              label="6. NCM"
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
              class="col-4"
              input-class="text-center"
            />
          </div>

          <div class="text-caption text-grey-7 q-mb-sm q-mt-sm">
            Classificação, Alíquotas e Benefício
          </div>
          <div class="row q-col-gutter-md q-mb-sm">
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
              class="col-2"
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
              class="col-2"
              input-class="text-center"
            />

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
              class="col-2"
              input-class="text-right"
            />

            <!-- Alíquota -->
            <q-input
              v-model.number="regraForm.aliquota"
              label="Alíquota (%)"
              outlined
              type="number"
              min="0"
              max="100"
              step="0.01"
              placeholder="Ex: 8.5"
              bottom-slots
              class="col-2"
              input-class="text-right"
            />

            <!-- SE GERA CREDITO -->
            <div class="col-4" style="height: 56px; display: flex; align-items: center">
              <q-toggle v-model="regraForm.geracredito" label="Gera crédito" color="primary" />
            </div>

            <!-- Benefício -->
            <q-input
              v-model="regraForm.beneficiocodigo"
              label="Benefício"
              outlined
              clearable
              placeholder="Ex: BE001"
              bottom-slots
              class="col-4"
            />

            <!-- Vigência Início -->
            <q-input
              v-model="regraForm.vigenciainicio"
              label="Vigente do dia"
              outlined
              clearable
              placeholder="DD/MM/AAAA"
              mask="##/##/####"
              bottom-slots
              class="col-4"
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
              class="col-4"
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

            <!-- Observações -->
            <q-input
              v-model="regraForm.observacoes"
              label="Observações"
              outlined
              type="textarea"
              rows="2"
              clearable
              placeholder="Observações sobre esta regra"
              bottom-slots
              class="col-12"
            />
          </div>
        </q-card-section>

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

  <!-- FAB para Nova Regra -->
  <q-page-sticky position="bottom-right" :offset="[18, 18]">
    <q-btn fab icon="add" color="primary" @click="novaRegra">
      <q-tooltip>Nova Regra</q-tooltip>
    </q-btn>
  </q-page-sticky>
</template>
