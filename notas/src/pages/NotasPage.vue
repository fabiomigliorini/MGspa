<script setup>
import { computed, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { useNotaFiscalStore } from '../stores/notaFiscalStore'
import { useControleStore } from '../stores/controle'
import { useSelectFilialStore } from 'stores/selects/filial'
import { useAuth } from 'src/composables/useAuth'
import { getStatusColor, getStatusIcon, getModeloLabel } from '../constants/notaFiscal'
import { formatDateTime, formatCurrency, formatNumero } from 'src/utils/formatters'
import NotaFiscalAcoes from '../components/NotaFiscalAcoes.vue'
import notaFiscalService from '../services/notaFiscalService'

const $q = useQuasar()
const route = useRoute()
const notaFiscalStore = useNotaFiscalStore()
const controleStore = useControleStore()
const filialStore = useSelectFilialStore()
const { hasPermission } = useAuth()

// Permissão para FABs
const podeGerar = computed(() => hasPermission('Administrador') || hasPermission('Financeiro'))

// Dialogs
const showDialogNfce = ref(false)
const showDialogTransferencias = ref(false)
const selectedNegocios = ref([])
const selectedFiliais = ref([])
const transferenciasProgresso = ref({})
const transferenciasRodando = ref(false)

// State
const loading = computed(() => notaFiscalStore.pagination.loading)
const notas = computed(() => notaFiscalStore.notas)
const hasActiveFilters = computed(() => notaFiscalStore.hasActiveFilters)

// Infinite scroll
const onLoad = async (index, done) => {
  try {
    await notaFiscalStore.fetchNotas()
    done(!notaFiscalStore.pagination.hasMore)
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Erro ao carregar notas', caption: error.message })
    done(true)
  }
}

// ==================== RELATÓRIO PDF ====================

const relatorioUrl = ref(null)
const relatorioDialog = ref(false)
const loadingRelatorio = ref(false)

const abrirRelatorio = async () => {
  loadingRelatorio.value = true
  try {
    const url = await notaFiscalStore.getRelatorioUrl()
    relatorioUrl.value = url
    const ua = navigator.userAgent
    const isAndroidPhone = /Android/i.test(ua) && /Mobile/i.test(ua) && !/CrOS/i.test(ua)
    if (isAndroidPhone) {
      window.open(url, '_blank')
    } else {
      relatorioDialog.value = true
    }
  } catch (error) {
    $q.notify({
      color: 'red-5',
      icon: 'error',
      message: 'Erro ao gerar relatório',
      caption: error?.response?.data?.message || error?.message,
    })
  } finally {
    loadingRelatorio.value = false
  }
}

// ==================== DIALOG NFC-e FALTANTES ====================

const abrirDialogNfce = async () => {
  showDialogNfce.value = true
  controleStore.resultadoNfce = null
  selectedNegocios.value = []
  try {
    await controleStore.fetchNegociosSemNfce()
    selectedNegocios.value = controleStore.negociosSemNfce.map((n) => n.codnegocio)
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Erro ao buscar negócios',
    })
  }
}

const toggleSelecionarTodosNegocios = () => {
  if (selectedNegocios.value.length === controleStore.negociosSemNfce.length) {
    selectedNegocios.value = []
  } else {
    selectedNegocios.value = controleStore.negociosSemNfce.map((n) => n.codnegocio)
  }
}

const gerarNfceFaltantes = async () => {
  if (selectedNegocios.value.length === 0) {
    $q.notify({ type: 'warning', message: 'Selecione ao menos um negócio' })
    return
  }
  try {
    const resultado = await controleStore.gerarNfceFaltantes(selectedNegocios.value)
    const qtdSucesso = resultado.sucesso?.length || 0
    const qtdErro = resultado.erro?.length || 0
    $q.notify({
      color: qtdErro > 0 ? 'orange-5' : 'green-5',
      icon: qtdErro > 0 ? 'warning' : 'done',
      message: `${qtdSucesso} NFC-e gerada(s)${qtdErro > 0 ? `, ${qtdErro} erro(s)` : ''}`,
    })
  } catch (error) {
    $q.notify({ type: 'negative', message: error.response?.data?.message || 'Erro ao gerar NFC-e' })
  }
}

// ==================== DIALOG TRANSFERÊNCIAS ====================

const abrirDialogTransferencias = async () => {
  showDialogTransferencias.value = true
  transferenciasProgresso.value = {}
  transferenciasRodando.value = false
  try {
    await filialStore.loadAll()
    selectedFiliais.value = filialStore.filiais.map((f) => f.value)
  } catch (e) {
    $q.notify({
      type: 'negative',
      message: e.response?.data?.message || 'Erro ao carregar filiais',
    })
  }
}

const toggleSelecionarTodasFiliais = () => {
  if (selectedFiliais.value.length === filialStore.filiais.length) {
    selectedFiliais.value = []
  } else {
    selectedFiliais.value = filialStore.filiais.map((f) => f.value)
  }
}

// Conta notas no resultado aninhado { filial: { pessoa: { natureza: { itens, codnotafiscal } } } }
const contarNotasTransferencia = (gerados) => {
  let total = 0
  for (const filial of Object.values(gerados || {})) {
    for (const pessoa of Object.values(filial)) {
      total += Object.keys(pessoa).length
    }
  }
  return total
}

const gerarTransferencias = async () => {
  if (selectedFiliais.value.length === 0) {
    $q.notify({ type: 'warning', message: 'Selecione ao menos uma filial' })
    return
  }
  transferenciasRodando.value = true
  let totalFiliaisComErro = 0

  for (const codfilial of selectedFiliais.value) {
    transferenciasProgresso.value[codfilial] = {
      status: 'rodando',
      qtdNotas: 0,
      erros: [],
      expandido: false,
    }
    try {
      let totalNotas = 0
      const errosFilial = []
      let continuar = true

      // Loop para tratar o limite de 600 itens por chamada
      // Para quando uma chamada nao gerar nenhuma nota nova (so erros restantes)
      while (continuar) {
        const resultado = await controleStore.gerarTransferencias(codfilial)
        const qtdNotas = contarNotasTransferencia(resultado.gerados)
        totalNotas += qtdNotas
        if (resultado.erros?.length) {
          errosFilial.push(...resultado.erros)
        }
        transferenciasProgresso.value[codfilial] = {
          status: 'rodando',
          qtdNotas: totalNotas,
          erros: errosFilial,
          expandido: transferenciasProgresso.value[codfilial].expandido,
        }
        continuar = qtdNotas > 0
      }

      let status
      if (errosFilial.length > 0 && totalNotas > 0) {
        status = 'parcial'
      } else if (errosFilial.length > 0) {
        status = 'erro'
      } else if (totalNotas > 0) {
        status = 'concluido'
      } else {
        status = 'vazio'
      }

      transferenciasProgresso.value[codfilial] = {
        status,
        qtdNotas: totalNotas,
        erros: errosFilial,
        expandido: errosFilial.length > 0,
      }

      if (errosFilial.length > 0) {
        totalFiliaisComErro++
      }
    } catch (error) {
      totalFiliaisComErro++
      const mensagem = error.response?.data?.message || error.message
      transferenciasProgresso.value[codfilial] = {
        status: 'erro',
        qtdNotas: 0,
        erros: [{ mensagem }],
        expandido: true,
      }
      $q.notify({
        color: 'red-5',
        icon: 'error',
        message: `${getFilialLabel(codfilial)}: ${mensagem}`,
        timeout: 10000,
      })
    }
  }
  transferenciasRodando.value = false

  if (totalFiliaisComErro > 0) {
    $q.notify({
      color: 'orange-5',
      icon: 'warning',
      message: `Concluído com ${totalFiliaisComErro} filial(is) com erros. Verifique os detalhes.`,
    })
  } else {
    $q.notify({ color: 'green-5', icon: 'done', message: 'Transferências concluídas' })
  }
}

const getFilialLabel = (codfilial) => {
  const filial = filialStore.getByCode(codfilial)
  return filial ? filial.label : codfilial
}

// ==================== DIALOG LACUNAS ====================

const showDialogLacunas = ref(false)
const lacunasGrupos = ref([])
const lacunasLoading = ref(false)
const justificativa = ref('Falha de sistema, saltou numeracao')
const inutilizando = ref(new Set())
const inutilizados = ref(new Set())

const chave = (g, n) => `${g.codfilial}-${g.serie}-${g.modelo}-${n}`

const abrirDialogLacunas = async () => {
  showDialogLacunas.value = true
  lacunasGrupos.value = []
  inutilizados.value = new Set()
  lacunasLoading.value = true
  try {
    lacunasGrupos.value = await notaFiscalService.detectarLacunas()
  } catch (error) {
    $q.notify({
      color: 'red-5',
      icon: 'error',
      message: error.response?.data?.message || 'Erro ao detectar lacunas',
    })
  } finally {
    lacunasLoading.value = false
  }
}

const inutilizarLacuna = async (grupo, numero) => {
  const k = chave(grupo, numero)
  const novoSet = new Set(inutilizando.value)
  novoSet.add(k)
  inutilizando.value = novoSet

  try {
    const resultado = await notaFiscalService.criarParaInutilizar({
      codfilial: grupo.codfilial,
      serie: grupo.serie,
      modelo: grupo.modelo,
      numero,
      justificativa: justificativa.value,
    })

    const novoInutilizados = new Set(inutilizados.value)
    novoInutilizados.add(k)
    inutilizados.value = novoInutilizados

    $q.notify({
      color: 'green-5',
      icon: 'done',
      message: `Número ${numero} inutilizado — ${resultado.cStat} ${resultado.xMotivo}`,
    })
  } catch (error) {
    $q.notify({
      color: 'red-5',
      icon: 'error',
      message: `Número ${numero}: ${error.response?.data?.message || error.message}`,
    })
  } finally {
    const novoSet = new Set(inutilizando.value)
    novoSet.delete(k)
    inutilizando.value = novoSet
  }
}

// Lifecycle
onMounted(async () => {
  // Ler query params da URL — limpa filtros antigos e seta só o status
  // O watcher da drawer vai disparar fetchNotas automaticamente
  if (route.query.status) {
    notaFiscalStore.clearFilters()
    notaFiscalStore.setFilters({ status: route.query.status })
    window.history.replaceState({}, '', route.path)
  } else if (!notaFiscalStore.initialLoadDone) {
    // Sem query param e sem dados carregados — busca inicial
    try {
      await notaFiscalStore.fetchNotas(true)
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao carregar notas fiscais',
        caption: error.response?.data?.message || error.message,
      })
    }
  }
})
</script>

<template>
  <q-page>
    <!-- Loading inicial -->
    <div v-if="loading && notas.length === 0" class="row justify-center q-py-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Empty State -->
    <q-card v-else-if="notas.length === 0" flat bordered class="q-pa-xl text-center q-ma-sm">
      <q-icon name="description" size="4em" color="grey-5" />
      <div class="text-h6 text-grey-7 q-mt-md">Nenhuma nota fiscal encontrada</div>
      <div class="text-caption text-grey-7 q-mt-sm">
        <template v-if="hasActiveFilters">Tente ajustar os filtros</template>
        <template v-else>Clique em "Nova Nota" para criar sua primeira nota fiscal</template>
      </div>
    </q-card>

    <!-- Lista de Notas -->
    <q-infinite-scroll v-else @load="onLoad" :offset="250">
      <q-list separator>
        <q-item
          dense
          hoverable
          v-for="nota in notas"
          :key="nota.codnotafiscal"
          clickable
          :to="'/nota/' + nota.codnotafiscal"
          class="q-py-xs"
        >
          <q-item-section avatar style="min-width: 32px">
            <q-icon :name="getStatusIcon(nota.status)" :color="getStatusColor(nota.status)" />
          </q-item-section>

          <q-item-section>
            <div class="row items-center">
              <!-- Número -->
              <div class="q-px-sm col-6 col-md-3 text-weight-medium text-caption">
                {{ getModeloLabel(nota.modelo) }} {{ formatNumero(nota.numero) }} - S{{
                  nota.serie
                }}
              </div>

              <!-- Filial -->
              <div class="q-px-sm col-6 col-md-1 text-caption text-grey-7 text-right">
                {{ nota.filial?.filial }}
              </div>

              <!-- Pessoa -->
              <div
                class="q-px-sm col-8 col-md-3 text-weight-bold text-caption text-primary ellipsis"
              >
                {{ nota.pessoa?.fantasia || 'Sem pessoa' }}
              </div>

              <!-- Cidade -->
              <div class="q-px-sm col-4 col-md-1 text-caption text-grey-7 text-right">
                {{ nota.pessoa?.cidade ? `${nota.pessoa.cidade}/${nota.pessoa.uf}` : '' }}
              </div>

              <!-- Data -->
              <div class="q-px-sm col-6 col-md-2 text-caption text-grey-7 ellipsis">
                {{ formatDateTime(nota.saida) }}
              </div>

              <!-- Valor -->
              <div
                class="q-px-sm col-6 col-md-2 text-weight-bold text-primary text-caption text-right"
              >
                R$ {{ formatCurrency(nota.valortotal) }}
              </div>

              <!-- Natureza -->
              <div class="q-px-sm col-12 text-caption text-grey-7">
                {{ nota?.naturezaOperacao?.naturezaoperacao }}
              </div>

              <!-- Ações -->
              <div class="q-px-sm col-12 col-md-2 text-right lt-md" @click.stop>
                <NotaFiscalAcoes compact :nota="nota" />
              </div>
            </div>
          </q-item-section>

          <!-- Ações -->
          <q-item-section side style="width: 95px" class="gt-sm">
            <NotaFiscalAcoes compact :nota="nota" />
          </q-item-section>
        </q-item>
      </q-list>

      <template v-slot:loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="40px" />
        </div>
      </template>
    </q-infinite-scroll>

    <!-- FABs -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <div class="row items-center q-gutter-sm">
        <!-- Gerar NFC-e Faltantes -->
        <q-btn
          v-if="podeGerar"
          fab-mini
          icon="receipt_long"
          color="orange-7"
          @click="abrirDialogNfce"
        >
          <q-tooltip>Gerar NFC-e Faltantes</q-tooltip>
        </q-btn>

        <!-- Gerar Transferências -->
        <q-btn
          v-if="podeGerar"
          fab-mini
          icon="swap_horiz"
          color="teal"
          @click="abrirDialogTransferencias"
        >
          <q-tooltip>Gerar Transferências</q-tooltip>
        </q-btn>

        <!-- Imprimir Relatório -->
        <q-btn
          fab-mini
          icon="print"
          color="grey-7"
          :loading="loadingRelatorio"
          @click="abrirRelatorio"
        >
          <q-tooltip>Imprimir Relatório</q-tooltip>
        </q-btn>

        <!-- Inutilizar Lacunas -->
        <q-btn
          v-if="podeGerar"
          fab-mini
          icon="playlist_remove"
          color="red-7"
          @click="abrirDialogLacunas"
        >
          <q-tooltip>Inutilizar Lacunas</q-tooltip>
        </q-btn>

        <!-- Nova Nota -->
        <q-btn
          fab
          icon="add"
          color="primary"
          :to="{ name: 'nota-fiscal-create' }"
          :disable="loading"
        >
          <q-tooltip>Nova Nota</q-tooltip>
        </q-btn>
      </div>
    </q-page-sticky>

    <!-- Dialog: Relatório PDF -->
    <q-dialog v-model="relatorioDialog">
      <q-card style="width: 1100px; max-width: 95vw; height: 90vh">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Relatório de Notas Fiscais</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section class="q-pa-md" style="height: calc(100% - 56px)">
          <iframe :src="relatorioUrl" style="width: 100%; height: 100%; border: none" />
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Dialog: Gerar NFC-e Faltantes -->
    <q-dialog v-model="showDialogNfce" persistent>
      <q-card flat style="width: 600px; max-width: 90vw">
        <q-card-section class="bg-orange-7 text-white">
          <div class="text-h6">Gerar NFC-e Faltantes</div>
          <div class="text-caption">Negócios fechados sem NFC-e (últimos 30 dias)</div>
        </q-card-section>

        <q-card-section>
          <!-- Loading -->
          <div v-if="controleStore.loadingNegocios" class="text-center q-pa-lg">
            <q-spinner color="primary" size="2em" />
            <div class="text-caption q-mt-sm">Buscando negócios...</div>
          </div>

          <!-- Resultado da geração -->
          <div v-else-if="controleStore.resultadoNfce" class="q-pa-sm">
            <div v-if="controleStore.resultadoNfce.sucesso?.length" class="q-mb-md">
              <div class="text-positive text-weight-medium q-mb-xs">
                <q-icon name="check_circle" />
                {{ controleStore.resultadoNfce.sucesso.length }} NFC-e gerada(s)
              </div>
            </div>
            <div v-if="controleStore.resultadoNfce.erro?.length">
              <div class="text-negative text-weight-medium q-mb-xs">
                <q-icon name="error" />
                {{ controleStore.resultadoNfce.erro.length }} erro(s)
              </div>
              <div
                v-for="e in controleStore.resultadoNfce.erro"
                :key="e.codnegocio"
                class="text-caption"
              >
                Negócio {{ e.codnegocio }}: {{ e.mensagem }}
              </div>
            </div>
          </div>

          <!-- Lista de negócios -->
          <div v-else>
            <div
              v-if="controleStore.negociosSemNfce.length === 0"
              class="text-center text-grey-7 q-pa-lg"
            >
              Nenhum negócio pendente encontrado
            </div>
            <div v-else>
              <div class="row items-center justify-between q-mb-sm">
                <div class="text-caption text-grey-7">
                  {{ controleStore.negociosSemNfce.length }} negócio(s) encontrado(s)
                </div>
                <q-btn
                  flat
                  dense
                  size="sm"
                  :label="
                    selectedNegocios.length === controleStore.negociosSemNfce.length
                      ? 'Desmarcar todos'
                      : 'Selecionar todos'
                  "
                  @click="toggleSelecionarTodosNegocios"
                />
              </div>
              <q-list separator dense style="height: 50vh; overflow-y: auto">
                <q-item
                  v-for="neg in controleStore.negociosSemNfce"
                  :key="neg.codnegocio"
                  tag="label"
                >
                  <q-item-section avatar>
                    <q-checkbox v-model="selectedNegocios" :val="neg.codnegocio" dense />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ neg.filial }} - {{ neg.cliente }}</q-item-label>
                    <q-item-label caption>
                      #{{ neg.codnegocio }} | {{ formatDateTime(neg.lancamento) }} | R$
                      {{ formatCurrency(neg.valortotal) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right">
          <q-btn flat label="Fechar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn
            v-if="!controleStore.resultadoNfce && controleStore.negociosSemNfce.length > 0"
            label="Gerar NFC-e"
            color="orange-7"
            :loading="controleStore.gerandoNfce"
            :disable="selectedNegocios.length === 0"
            @click="gerarNfceFaltantes"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialog: Gerar Transferências -->
    <q-dialog v-model="showDialogTransferencias" persistent>
      <q-card flat style="width: 600px; max-width: 90vw">
        <q-card-section class="bg-teal text-white">
          <div class="text-h6">Gerar Transferências</div>
          <div class="text-caption">Notas de transferência entre filiais</div>
        </q-card-section>

        <q-card-section>
          <div class="row items-center justify-between q-mb-sm">
            <div class="text-caption text-grey-7">Selecione as filiais</div>
            <q-btn
              flat
              dense
              size="sm"
              :label="
                selectedFiliais.length === filialStore.filiais.length
                  ? 'Desmarcar todas'
                  : 'Selecionar todas'
              "
              :disable="transferenciasRodando"
              @click="toggleSelecionarTodasFiliais"
            />
          </div>

          <q-list separator dense style="height: 50vh; overflow-y: auto">
            <template v-for="filial in filialStore.filiais" :key="filial.value">
              <q-item tag="label">
                <q-item-section avatar>
                  <q-checkbox
                    v-model="selectedFiliais"
                    :val="filial.value"
                    dense
                    :disable="transferenciasRodando"
                  />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ filial.label }}</q-item-label>
                </q-item-section>
                <q-item-section side v-if="transferenciasProgresso[filial.value]">
                  <!-- Rodando -->
                  <q-spinner
                    v-if="transferenciasProgresso[filial.value].status === 'rodando'"
                    color="primary"
                    size="1.2em"
                  />
                  <!-- Concluído -->
                  <q-badge
                    v-else-if="transferenciasProgresso[filial.value].status === 'concluido'"
                    color="positive"
                  >
                    {{ transferenciasProgresso[filial.value].qtdNotas }} nota(s)
                  </q-badge>
                  <!-- Parcial -->
                  <q-badge
                    v-else-if="transferenciasProgresso[filial.value].status === 'parcial'"
                    color="orange-7"
                  >
                    {{ transferenciasProgresso[filial.value].qtdNotas }} ok /
                    {{ transferenciasProgresso[filial.value].erros.length }} erro(s)
                  </q-badge>
                  <!-- Erro -->
                  <q-badge
                    v-else-if="transferenciasProgresso[filial.value].status === 'erro'"
                    color="negative"
                  >
                    {{ transferenciasProgresso[filial.value].erros.length }} erro(s)
                  </q-badge>
                </q-item-section>
                <q-item-section
                  side
                  v-if="transferenciasProgresso[filial.value]?.erros?.length"
                >
                  <q-btn
                    flat
                    dense
                    round
                    size="sm"
                    color="grey-7"
                    :icon="
                      transferenciasProgresso[filial.value].expandido
                        ? 'expand_less'
                        : 'expand_more'
                    "
                    @click.prevent.stop="
                      transferenciasProgresso[filial.value].expandido =
                        !transferenciasProgresso[filial.value].expandido
                    "
                  />
                </q-item-section>
              </q-item>
              <q-item
                v-if="
                  transferenciasProgresso[filial.value]?.expandido &&
                  transferenciasProgresso[filial.value]?.erros?.length
                "
                class="q-pl-xl bg-red-1"
              >
                <q-item-section>
                  <q-item-label
                    v-for="(err, idx) in transferenciasProgresso[filial.value].erros"
                    :key="idx"
                    class="text-caption text-red-8 q-py-xs"
                  >
                    {{ err.mensagem }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </template>
          </q-list>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right">
          <q-btn
            flat
            label="Fechar"
            color="grey-8"
            v-close-popup
            :disable="transferenciasRodando"
            tabindex="-1"
          />
          <q-btn
            label="Gerar Transferências"
            color="teal"
            :loading="transferenciasRodando"
            :disable="selectedFiliais.length === 0"
            @click="gerarTransferencias"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialog: Inutilizar Lacunas -->
    <q-dialog v-model="showDialogLacunas" persistent>
      <q-card
        flat
        style="
          width: 600px;
          max-width: 90vw;
          max-height: 80vh;
          display: flex;
          flex-direction: column;
        "
      >
        <q-card-section class="bg-red-7 text-white">
          <div class="text-h6">Inutilizar Lacunas</div>
          <div class="text-caption">Números saltados na numeração (últimos 90 dias)</div>
        </q-card-section>

        <q-card-section class="q-pb-none q-mb-none">
          <q-input
            v-model="justificativa"
            label="Justificativa"
            dense
            outlined
            :rules="[(v) => v.length >= 15 || 'Mínimo 15 caracteres']"
          />
        </q-card-section>

        <q-card-section
          class="q-pt-none q-mt-none"
          style="overflow-y: auto; flex: 1; min-height: 0"
        >
          <!-- Loading -->
          <template v-if="lacunasLoading">
            <div class="text-center q-pa-lg">
              <q-spinner color="primary" size="2em" />
              <div class="text-caption q-mt-sm">Buscando lacunas...</div>
            </div>
          </template>

          <!-- Sem lacunas -->
          <template v-else-if="!lacunasLoading && lacunasGrupos.length === 0">
            <div class="text-center q-pa-lg text-grey-6">Nenhuma lacuna encontrada...</div>
          </template>

          <!-- Agrupado por filial/serie/modelo -->
          <template v-else>
            <template
              v-for="grupo in lacunasGrupos"
              :key="`${grupo.codfilial}-${grupo.serie}-${grupo.modelo}`"
            >
              <q-item-label header class="text-weight-medium">
                {{ grupo.filial }} — Série {{ grupo.serie }} — Modelo {{ grupo.modelo }}
              </q-item-label>
              <q-list separator dense>
                <q-item v-for="numero in grupo.lacunas" :key="numero" dense>
                  <q-item-section>
                    <q-item-label>Número {{ numero }}</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-icon
                      v-if="inutilizados.has(chave(grupo, numero))"
                      name="done"
                      color="green"
                    />
                    <q-btn
                      v-else
                      flat
                      dense
                      round
                      size="sm"
                      color="red-7"
                      icon="block"
                      :loading="inutilizando.has(chave(grupo, numero))"
                      :disable="justificativa.length < 15"
                      @click="inutilizarLacuna(grupo, numero)"
                    >
                      <q-tooltip>Inutilizar</q-tooltip>
                    </q-btn>
                  </q-item-section>
                </q-item>
              </q-list>
            </template>
          </template>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Fechar" color="grey-8" v-close-popup tabindex="-1" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>
