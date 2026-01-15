<script setup>
import { onMounted, computed, watch, shallowRef, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useAuth } from 'src/composables/useAuth'
import { useDashboardStore } from 'src/stores/dashboard'
import { useNotaFiscalStore } from 'src/stores/notaFiscalStore'
import { formatDateTime, formatNumero, formatCurrency } from 'src/utils/formatters'
import VChart from 'vue-echarts'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart, BarChart } from 'echarts/charts'
import { GridComponent, TooltipComponent, LegendComponent } from 'echarts/components'

use([CanvasRenderer, LineChart, BarChart, GridComponent, TooltipComponent, LegendComponent])

const $q = useQuasar()
const { validateToken } = useAuth()
const store = useDashboardStore()
const notaFiscalStore = useNotaFiscalStore()

const loading = computed(() => store.loading)
const loadingVolumeMensal = computed(() => store.loadingVolumeMensal)
const enviandoNota = ref(null)
const consultandoNota = ref(null)
const period = computed(() => store.period)
const modelo = computed(() => store.modelo)
const isMobile = computed(() => $q.screen.lt.sm)
const carouselSlide = ref('erro')
const sefazStatus = computed(() => store.sefazStatus)
const kpisGerais = computed(() => store.kpisGerais)
const volumeMensal = computed(() => store.volumeMensal)
const erroPorFilial = computed(() => store.erroPorFilial)
const kpisPorFilial = computed(() => store.kpisPorFilial)
const listas = computed(() => store.listas)

const periodOptions = [
  { label: 'Hoje', value: 1 },
  { label: '7 dias', value: 7 },
  { label: '30 dias', value: 30 },
  { label: '60 dias', value: 60 },
  { label: '90 dias', value: 90 },
]

const modeloOptions = [
  { label: 'Ambos', value: 'ambos' },
  { label: 'NFe', value: 'nfe' },
  { label: 'NFCe', value: 'nfce' },
]

const sefazOnline = computed(() => {
  if (!sefazStatus.value) return false
  const cstat = sefazStatus.value.cstat || sefazStatus.value.cStat
  return cstat === 107 || cstat === '107' || sefazStatus.value.status === 'online'
})

const sefazColor = computed(() => {
  if (!sefazStatus.value) return 'grey'
  if (sefazOnline.value) return 'positive'
  const s = sefazStatus.value.status
  if (s === 'instavel') return 'warning'
  return 'negative'
})

const sefazIcon = computed(() => {
  if (!sefazStatus.value) return 'cloud_off'
  if (sefazOnline.value) return 'cloud_done'
  const s = sefazStatus.value.status
  if (s === 'instavel') return 'cloud_sync'
  return 'cloud_off'
})

const sefazLabel = computed(() => {
  if (!sefazStatus.value) return 'Verificando...'
  if (sefazOnline.value) return 'Online'
  const s = sefazStatus.value.status
  if (s === 'instavel') return 'Instável'
  return 'Offline'
})

const kpiCards = computed(() => [
  {
    label: 'Total de Notas',
    labelShort: 'Total',
    value: (kpisGerais.value?.total_notas || 0).toLocaleString('pt-BR'),
    subtitle: null,
    icon: 'description',
    color: 'primary',
  },
  {
    label: 'Autorizadas',
    labelShort: 'Aut.',
    value: (kpisGerais.value?.autorizadas?.quantidade || 0).toLocaleString('pt-BR'),
    subtitle: `${(kpisGerais.value?.autorizadas?.percentual || 0).toFixed(1)}%`,
    icon: 'check_circle',
    color: 'positive',
  },
  {
    label: 'Erro',
    labelShort: 'Erro',
    value: (kpisGerais.value?.erro?.quantidade || 0).toLocaleString('pt-BR'),
    subtitle: `${Math.ceil((kpisGerais.value?.erro?.percentual || 0) * 10) / 10}%`,
    icon: 'highlight_off',
    color: (kpisGerais.value?.erro?.quantidade || 0) > 0 ? 'negative' : 'grey-7',
  },
  {
    label: 'Canceladas/Inutilizadas',
    labelShort: 'Canc.',
    value: (kpisGerais.value?.canceladas?.quantidade || 0).toLocaleString('pt-BR'),
    subtitle: `${(kpisGerais.value?.canceladas?.percentual || 0).toFixed(1)}%`,
    icon: 'block',
    color: 'warning',
  },
])

const volumeChartOption = shallowRef({})
watch(
  volumeMensal,
  (data) => {
    if (!data || data.length === 0) {
      volumeChartOption.value = {}
      return
    }
    volumeChartOption.value = {
      tooltip: { trigger: 'axis' },
      legend: { data: ['NFe', 'NFCe'], bottom: 0 },
      grid: { left: 40, right: 20, top: 20, bottom: 40 },
      xAxis: {
        type: 'category',
        data: data.map((d) => d.mes),
        axisLabel: { fontSize: 10 },
      },
      yAxis: { type: 'value', axisLabel: { fontSize: 10 } },
      series: [
        {
          name: 'NFe',
          type: 'line',
          data: data.map((d) => d.nfe || 0),
          smooth: true,
          itemStyle: { color: '#1976D2' },
        },
        {
          name: 'NFCe',
          type: 'line',
          data: data.map((d) => d.nfce || 0),
          smooth: true,
          itemStyle: { color: '#26A69A' },
        },
      ],
    }
  },
  { immediate: true }
)

const canceladasChartOption = shallowRef({})
let lastCanceladasPorFilialHash = ''
watch(
  erroPorFilial,
  (data) => {
    if (!data || data.length === 0) {
      canceladasChartOption.value = {}
      lastCanceladasPorFilialHash = ''
      return
    }
    const hash = JSON.stringify(data.map((d) => ({ f: d.filial, c: d.percent_canceladas })))
    if (hash === lastCanceladasPorFilialHash) return
    lastCanceladasPorFilialHash = hash

    const sorted = [...data].sort(
      (a, b) => (b.percent_canceladas || 0) - (a.percent_canceladas || 0)
    )
    const maxCanceladas =
      Math.ceil(Math.max(...data.map((d) => d.percent_canceladas || 0)) * 10) / 10 || 1
    canceladasChartOption.value = {
      tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' },
        formatter: (params) => `${params[0].marker} ${params[0].name}: ${params[0].value}%`,
      },
      grid: { left: 80, right: 20, top: 10, bottom: 20 },
      xAxis: {
        type: 'value',
        max: maxCanceladas,
        axisLabel: { formatter: '{value}%', fontSize: 10 },
      },
      yAxis: {
        type: 'category',
        data: sorted.map((d) => d.filial || d.codfilial),
        axisLabel: { fontSize: 10 },
      },
      series: [
        {
          type: 'bar',
          data: sorted.map((d) => d.percent_canceladas || 0),
          itemStyle: { color: '#FF9800' },
          barWidth: 12,
        },
      ],
    }
  },
  { immediate: true }
)

const filialColumns = [
  { name: 'filial', label: 'Filial', field: 'filial', align: 'left', sortable: true },
  { name: 'total', label: 'Total', field: 'total_notas', align: 'right', sortable: true },
  {
    name: 'autorizadas',
    label: '% Aut.',
    field: 'percent_autorizadas',
    align: 'right',
    sortable: true,
    format: (v) => `${(v || 0).toFixed(1)}%`,
  },
  {
    name: 'erro',
    label: '% Erro',
    field: 'percent_erro',
    align: 'right',
    sortable: true,
    format: (v) => `${Math.ceil((v || 0) * 10) / 10}%`,
  },
  {
    name: 'canceladas',
    label: '% Canc.',
    field: 'percent_canceladas',
    align: 'right',
    sortable: true,
    format: (v) => `${(v || 0).toFixed(1)}%`,
  },
  {
    name: 'ultima_nota',
    label: 'Última Nota',
    field: 'ultima_nota_emitida',
    align: 'center',
    format: (v) => (v ? formatDateTime(v) : '-'),
  },
  {
    name: 'ultimo_erro',
    label: 'Último Erro',
    field: 'ultima_nota_com_erro',
    align: 'center',
    format: (v) => (v ? formatDateTime(v) : '-'),
  },
]

const onPeriodChange = async (val) => {
  store.setPeriod(val)
  await store.fetchByPeriod()
}

const onModeloChange = async (val) => {
  store.setModelo(val)
  await store.fetchByPeriod()
}

const refresh = async () => {
  await store.fetchAll()
}

const enviarNfe = async (codnotafiscal, event) => {
  event.preventDefault()
  event.stopPropagation()

  enviandoNota.value = codnotafiscal
  try {
    await notaFiscalStore.criarNfe(codnotafiscal)
    const response = await notaFiscalStore.enviarNfeSincrono(codnotafiscal)
    const resultado = response?.resultado || response
    const cStat = resultado?.cStat || ''
    if (resultado?.sucesso || cStat === '100' || cStat === 100) {
      $q.notify({
        type: 'positive',
        message: `${cStat} - ${resultado?.xMotivo || 'NFe enviada com sucesso!'}`,
      })
      await store.fetchListas()
    } else {
      $q.notify({
        type: 'negative',
        message: `${cStat} - ${resultado?.xMotivo || 'Erro ao enviar NFe'}`,
      })
    }
  } catch (error) {
    $q.notify({ type: 'negative', message: error.message || 'Erro ao enviar NFe' })
  } finally {
    enviandoNota.value = null
  }
}

const consultarNfe = async (codnotafiscal, event) => {
  event.preventDefault()
  event.stopPropagation()

  consultandoNota.value = codnotafiscal
  try {
    const response = await notaFiscalStore.consultarNfe(codnotafiscal)
    const cStat = response?.cStat || ''
    const tipo = response?.sucesso || cStat === '100' || cStat === 100 ? 'positive' : 'negative'
    $q.notify({
      type: tipo,
      message: `${cStat} - ${response?.xMotivo || 'Consulta realizada'}`,
    })
    if (tipo === 'positive') {
      await store.fetchListas()
    }
  } catch (error) {
    $q.notify({ type: 'negative', message: error.message || 'Erro ao consultar NFe' })
  } finally {
    consultandoNota.value = null
  }
}

onMounted(async () => {
  await validateToken()
  await store.fetchAll()
})
</script>

<template>
  <q-page class="q-pa-sm bg-grey-">
    <!-- TOPO: Filtros e Status SEFAZ -->
    <div
      :class="isMobile ? 'column q-gutter-y-sm q-mb-sm' : 'row items-center q-mb-md q-gutter-x-md'"
    >
      <div class="column q-pa-none">
        <div v-if="!isMobile" class="text-grey-7 q-mt-xs q-pa-none text-caption">Período:</div>
        <q-tabs
          :model-value="period"
          :class="isMobile ? 'text-grey-7 bg-grey-3 compact-tabs' : 'text-grey-7 bg-grey-3'"
          active-bg-color="primary"
          active-color="white"
          indicator-color="transparent"
          inline-label
          no-caps
          dense
          @update:model-value="onPeriodChange"
        >
          <q-tab
            v-for="opt in periodOptions"
            :key="opt.value"
            :name="opt.value"
            :label="opt.label"
            :class="isMobile ? 'rounded-borders compact-tab' : 'rounded-borders'"
          />
        </q-tabs>
      </div>

      <div class="column q-pa-none">
        <div v-if="!isMobile" class="text-grey-7 q-mt-xs q-pa-none text-caption">Modelo:</div>
        <q-tabs
          :model-value="modelo"
          :class="isMobile ? 'text-grey-7 bg-grey-3 compact-tabs' : 'text-grey-7 bg-grey-3'"
          active-bg-color="primary"
          active-color="white"
          indicator-color="transparent"
          inline-label
          no-caps
          dense
          @update:model-value="onModeloChange"
        >
          <q-tab
            v-for="opt in modeloOptions"
            :key="opt.value"
            :name="opt.value"
            :label="opt.label"
            :class="isMobile ? 'rounded-borders compact-tab' : 'rounded-borders'"
          />
        </q-tabs>
      </div>

      <q-space v-if="!isMobile" />

      <div :class="isMobile ? 'row items-center justify-end q-mr-md q-my-sm' : 'row items-center'">
        <q-chip :color="sefazColor" text-color="white" :icon="sefazIcon" class="q-mr-none q-pa-sm">
          SEFAZ: {{ sefazLabel }}
        </q-chip>
        <q-btn flat icon="refresh" :loading="loading" @click="refresh">
          <q-tooltip>Atualizar</q-tooltip>
        </q-btn>
      </div>
    </div>

    <!-- LINHA 1: Cards KPI -->
    <div flat :class="isMobile ? 'row q-col-gutter-xs q-mb-sm' : 'row q-col-gutter-md q-mb-md'">
      <div v-for="kpi in kpiCards" :key="kpi.label" class="col">
        <q-card flat bordered>
          <q-card-section
            :horizontal="!isMobile"
            :class="isMobile ? 'items-center text-center q-pa-xs' : 'items-center q-pa-sm'"
          >
            <q-avatar
              :color="kpi.color"
              text-color="white"
              :size="isMobile ? '24px' : '36px'"
              square
              style="border-radius: 4px"
              :class="isMobile ? 'q-mb-xs' : 'q-mr-sm'"
            >
              <q-icon :name="kpi.icon" :size="isMobile ? '14px' : '20px'" />
            </q-avatar>
            <div>
              <div
                :class="
                  isMobile ? 'text-subtitle2 text-weight-bold' : 'text-body1 text-weight-bold'
                "
              >
                {{ loading ? '...' : kpi.value }}
              </div>
              <div
                :class="isMobile ? 'text-caption text-grey-7' : 'text-caption text-grey-7 ellipsis'"
              >
                <template v-if="kpi.subtitle && !isMobile">
                  {{ kpi.subtitle }}
                </template>
                {{ isMobile ? kpi.labelShort : kpi.label }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- LINHA 2: Gráficos -->
    <div
      :class="isMobile ? 'column q-gutter-y-sm q-mb-sm' : 'row q-col-gutter-md q-mb-md'"
      :style="isMobile ? '' : 'height: 200px'"
    >
      <div :class="isMobile ? '' : 'col-7'" :style="isMobile ? 'height: 180px' : ''">
        <q-card flat bordered style="height: 100%">
          <q-card-section class="q-pa-sm column" style="height: 100%">
            <div class="text-subtitle2 text-weight-medium q-mb-xs">Volume Mensal</div>
            <div class="col relative-position">
              <q-spinner v-if="loadingVolumeMensal" color="primary" class="absolute-center" />
              <v-chart
                v-else-if="volumeMensal.length"
                :option="volumeChartOption"
                autoresize
                style="height: 100%; width: 100%"
              />
              <div v-else class="absolute-center text-grey">Sem dados</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div :class="isMobile ? '' : 'col-5'" :style="isMobile ? 'height: 180px' : ''">
        <q-card flat bordered style="height: 100%">
          <q-card-section class="q-pa-sm column" style="height: 100%">
            <div class="text-subtitle2 text-weight-medium q-mb-xs">
              Canceladas/Inutilizadas por Filial
            </div>
            <div class="col relative-position">
              <q-spinner
                v-if="loading && !erroPorFilial.length"
                color="primary"
                class="absolute-center"
              />
              <v-chart
                v-else-if="erroPorFilial.length"
                :option="canceladasChartOption"
                autoresize
                style="height: 100%; width: 100%"
              />
              <div v-else class="absolute-center text-grey">Sem dados</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- LINHA 3: Tabela KPIs por Filial -->
    <div class="row q-mb-md">
      <div class="col-12">
        <q-card flat bordered>
          <q-table
            :rows="kpisPorFilial"
            :columns="filialColumns"
            row-key="codfilial"
            flat
            hide-bottom
            :loading="loading"
            :dense="isMobile"
            virtual-scroll
            :rows-per-page-options="[0]"
          >
            <template #body-cell-erro="props">
              <q-td
                :props="props"
                :class="props.row.percent_erro > 0 ? 'text-negative text-weight-bold' : ''"
              >
                {{ Math.ceil((props.row.percent_erro || 0) * 10) / 10 }}%
              </q-td>
            </template>
          </q-table>
        </q-card>
      </div>
    </div>

    <!-- LINHA 4: Listas -->
    <q-card flat bordered>
      <q-card-section class="q-pa-sm q-pb-none">
        <div class="text-subtitle2 text-weight-medium text-grey-8">
          As 20 notas mais recentes em
        </div>
      </q-card-section>
      <q-card-section class="q-pa-sm">
        <!-- Desktop -->
        <div v-if="!isMobile" class="row q-col-gutter-md">
          <!-- Lista Erro -->
          <div class="col-4">
            <q-card flat bordered class="column">
              <q-card-section class="q-pa-sm q-pb-none">
                <div class="text-subtitle2 text-weight-medium text-negative">
                  <q-icon name="highlight_off" class="q-mr-xs" />
                  Erro
                </div>
              </q-card-section>
              <q-card-section class="col q-pa-none scroll">
                <q-spinner v-if="loading" color="primary" class="absolute-center" />
                <q-list v-else-if="listas.erro.length" separator>
                  <q-item
                    v-for="item in listas.erro"
                    :key="item.codnotafiscal"
                    :to="`/nota/${item.codnotafiscal}`"
                  >
                    <q-item-section side>
                      <div class="row no-wrap q-gutter-x-xs">
                        <q-btn
                          flat
                          round
                          size="sm"
                          color="primary"
                          icon="refresh"
                          :loading="consultandoNota === item.codnotafiscal"
                          @click="consultarNfe(item.codnotafiscal, $event)"
                        >
                          <q-tooltip>Consultar NFe</q-tooltip>
                        </q-btn>
                        <q-btn
                          flat
                          round
                          size="sm"
                          color="secondary"
                          icon="send"
                          :loading="enviandoNota === item.codnotafiscal"
                          @click="enviarNfe(item.codnotafiscal, $event)"
                        >
                          <q-tooltip>Enviar NFe</q-tooltip>
                        </q-btn>
                      </div>
                    </q-item-section>
                    <q-item-section>
                      <q-item-label class="text-caption text-weight-medium">
                        {{ item.modelo === 55 ? 'NFe' : 'NFCe' }}
                        {{ formatNumero(item.numero) }} - Série {{ item.serie }}
                      </q-item-label>
                      <q-item-label caption>
                        {{ item.filial }}
                      </q-item-label>
                      <q-item-label caption>
                        {{ item.data ? formatDateTime(item.data) : '-' }}
                      </q-item-label>
                    </q-item-section>
                    <div class="column q-mt-md">
                      <q-item-section
                        align="right"
                        class="text-caption text-weight-medium text-grey-7 q-mb-none"
                      >
                        R$ {{ formatCurrency(item.valortotal) }}
                      </q-item-section>
                      <q-item-section
                        align="right"
                        class="text-caption text-weight-medium text-grey-7"
                      >
                        {{ item.naturezaOperacao }}
                      </q-item-section>
                    </div>
                  </q-item>
                </q-list>
                <div v-else class="absolute-center text-grey text-caption">Nenhum erro</div>
              </q-card-section>
            </q-card>
          </div>

          <!-- Lista Canceladas/Inutilizadas -->
          <div class="col-4">
            <q-card flat bordered class="column">
              <q-card-section class="q-pa-sm q-pb-none">
                <div class="text-subtitle2 text-weight-medium text-warning">
                  <q-icon name="block" class="q-mr-xs" />
                  Canceladas e Inutilizadas
                </div>
              </q-card-section>
              <q-card-section class="col q-pa-none scroll">
                <q-spinner v-if="loading" color="primary" class="absolute-center" />
                <q-list v-else-if="listas.canceladasInutilizadas.length" separator>
                  <q-item
                    v-for="item in listas.canceladasInutilizadas"
                    :key="item.codnotafiscal"
                    :to="`/nota/${item.codnotafiscal}`"
                  >
                    <q-item-section>
                      <q-item-label class="text-caption text-weight-medium">
                        {{ item.modelo === 55 ? 'NFe' : 'NFCe' }}
                        {{ formatNumero(item.numero) }} - Série {{ item.serie }}
                      </q-item-label>
                      <q-item-label caption>
                        {{ item.filial }}
                      </q-item-label>
                      <q-item-label caption>
                        {{ item.data ? formatDateTime(item.data) : '-' }}
                      </q-item-label>
                    </q-item-section>
                    <div class="column q-mt-md">
                      <q-item-section
                        align="right"
                        class="text-caption text-weight-medium text-grey-7 q-mb-none"
                      >
                        R$ {{ formatCurrency(item.valortotal) }}
                      </q-item-section>
                      <q-item-section
                        align="right"
                        class="text-caption text-weight-medium text-grey-7"
                      >
                        {{ item.naturezaOperacao }}
                      </q-item-section>
                    </div>
                  </q-item>
                </q-list>
                <div v-else class="absolute-center text-grey text-caption">
                  Nenhuma cancelada/inutilizada
                </div>
              </q-card-section>
            </q-card>
          </div>

          <!-- Lista Digitação -->
          <div class="col-4">
            <q-card flat bordered class="column">
              <q-card-section class="q-pa-sm q-pb-none">
                <div class="text-subtitle2 text-weight-medium text-primary">
                  <q-icon name="edit" class="q-mr-xs" />
                  Digitação
                </div>
              </q-card-section>
              <q-card-section class="col q-pa-none scroll">
                <q-spinner v-if="loading" color="primary" class="absolute-center" />
                <q-list v-else-if="listas.digitacao.length" separator>
                  <q-item
                    v-for="item in listas.digitacao"
                    :key="item.codnotafiscal"
                    :to="`/nota/${item.codnotafiscal}`"
                  >
                    <q-item-section>
                      <q-item-label class="text-caption text-weight-medium">
                        {{ item.modelo === 55 ? 'NFe' : 'NFCe' }}
                        {{ formatNumero(item.numero) }} - Série {{ item.serie }}
                      </q-item-label>
                      <q-item-label caption>
                        {{ item.filial }}
                      </q-item-label>
                      <q-item-label caption>
                        {{ item.data ? formatDateTime(item.data) : '-' }}
                      </q-item-label>
                    </q-item-section>
                    <div class="column q-mt-md">
                      <q-item-section
                        align="right"
                        class="text-caption text-weight-medium text-grey-7 q-mb-none"
                      >
                        R$ {{ formatCurrency(item.valortotal) }}
                      </q-item-section>
                      <q-item-section
                        align="right"
                        class="text-caption text-weight-medium text-grey-7"
                      >
                        {{ item.naturezaOperacao }}
                      </q-item-section>
                    </div>
                  </q-item>
                </q-list>
                <div v-else class="absolute-center text-grey text-caption">
                  Nenhuma em digitação
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- Mobile (Carrossel) -->
        <q-carousel
          v-else
          v-model="carouselSlide"
          swipeable
          animated
          navigation
          padding
          control-color="primary"
          class="bg-transparent"
          style="height: 320px"
        >
          <!-- Slide Erro -->
          <q-carousel-slide name="erro" class="q-pa-none">
            <q-card flat bordered class="column full-height">
              <q-card-section class="q-pa-sm q-pb-none">
                <div class="text-subtitle2 text-weight-medium text-negative">
                  <q-icon name="highlight_off" class="q-mr-xs" />
                  Erro
                </div>
              </q-card-section>
              <q-card-section class="col q-pa-none scroll">
                <q-spinner v-if="loading" color="primary" class="absolute-center" />
                <q-list v-else-if="listas.erro.length" separator dense>
                  <q-item
                    v-for="item in listas.erro"
                    :key="item.codnotafiscal"
                    :to="`/nota/${item.codnotafiscal}`"
                    dense
                  >
                    <q-item-section side>
                      <div class="row no-wrap q-gutter-x-xs">
                        <q-btn
                          flat
                          round
                          size="xs"
                          color="primary"
                          icon="refresh"
                          :loading="consultandoNota === item.codnotafiscal"
                          @click="consultarNfe(item.codnotafiscal, $event)"
                        />
                        <q-btn
                          flat
                          round
                          size="xs"
                          color="secondary"
                          icon="send"
                          :loading="enviandoNota === item.codnotafiscal"
                          @click="enviarNfe(item.codnotafiscal, $event)"
                        />
                      </div>
                    </q-item-section>
                    <q-item-section>
                      <q-item-label class="text-caption text-weight-medium">
                        {{ item.modelo === 55 ? 'NFe' : 'NFCe' }}
                        {{ formatNumero(item.numero) }} - S{{ item.serie }}
                      </q-item-label>
                      <q-item-label caption>{{ item.filial }}</q-item-label>
                      <q-item-label caption>
                        {{ item.data ? formatDateTime(item.data) : '-' }}
                      </q-item-label>
                    </q-item-section>
                    <div class="column q-mt-md">
                      <q-item-section
                        align="right"
                        class="text-caption text-weight-medium text-grey-7 q-mb-none"
                      >
                        R$ {{ formatCurrency(item.valortotal) }}
                      </q-item-section>
                      <q-item-section
                        align="right"
                        class="text-caption text-weight-medium text-grey-7"
                      >
                        {{ item.naturezaOperacao }}
                      </q-item-section>
                    </div>
                  </q-item>
                </q-list>
                <div v-else class="absolute-center text-grey text-caption">Nenhum erro</div>
              </q-card-section>
            </q-card>
          </q-carousel-slide>

          <!-- Slide Canceladas -->
          <q-carousel-slide name="canceladas" class="q-pa-none">
            <q-card flat bordered class="column full-height">
              <q-card-section class="q-pa-sm q-pb-none">
                <div class="text-subtitle2 text-weight-medium text-warning">
                  <q-icon name="block" class="q-mr-xs" />
                  Canceladas/Inutilizadas
                </div>
              </q-card-section>
              <q-card-section class="col q-pa-none scroll">
                <q-spinner v-if="loading" color="primary" class="absolute-center" />
                <q-list v-else-if="listas.canceladasInutilizadas.length" separator dense>
                  <q-item
                    v-for="item in listas.canceladasInutilizadas"
                    :key="item.codnotafiscal"
                    :to="`/nota/${item.codnotafiscal}`"
                    dense
                  >
                    <q-item-section>
                      <q-item-label class="text-caption text-weight-medium">
                        {{ item.modelo === 55 ? 'NFe' : 'NFCe' }}
                        {{ formatNumero(item.numero) }} - S{{ item.serie }}
                      </q-item-label>
                      <q-item-label caption>{{ item.filial }}</q-item-label>
                      <q-item-label caption>
                        {{ item.data ? formatDateTime(item.data) : '-' }}
                      </q-item-label>
                    </q-item-section>
                    <div class="column q-mt-md">
                      <q-item-section
                        align="right"
                        class="text-caption text-weight-medium text-grey-7 q-mb-none"
                      >
                        R$ {{ formatCurrency(item.valortotal) }}
                      </q-item-section>
                      <q-item-section
                        align="right"
                        class="text-caption text-weight-medium text-grey-7"
                      >
                        {{ item.naturezaOperacao }}
                      </q-item-section>
                    </div>
                  </q-item>
                </q-list>
                <div v-else class="absolute-center text-grey text-caption">Nenhuma</div>
              </q-card-section>
            </q-card>
          </q-carousel-slide>

          <!-- Slide Digitação -->
          <q-carousel-slide name="digitacao" class="q-pa-none">
            <q-card flat bordered class="column full-height">
              <q-card-section class="q-pa-sm q-pb-none">
                <div class="text-subtitle2 text-weight-medium text-primary">
                  <q-icon name="edit" class="q-mr-xs" />
                  Digitação
                </div>
              </q-card-section>
              <q-card-section class="col q-pa-none scroll">
                <q-spinner v-if="loading" color="primary" class="absolute-center" />
                <q-list v-else-if="listas.digitacao.length" separator dense>
                  <q-item
                    v-for="item in listas.digitacao"
                    :key="item.codnotafiscal"
                    :to="`/nota/${item.codnotafiscal}`"
                    dense
                  >
                    <q-item-section>
                      <q-item-label class="text-caption text-weight-medium">
                        {{ item.modelo === 55 ? 'NFe' : 'NFCe' }}
                        {{ formatNumero(item.numero) }} - S{{ item.serie }}
                      </q-item-label>
                      <q-item-label caption>{{ item.filial }}</q-item-label>
                      <q-item-label caption>
                        {{ item.data ? formatDateTime(item.data) : '-' }}
                      </q-item-label>
                    </q-item-section>
                    <div class="column q-mt-md">
                      <q-item-section
                        align="right"
                        class="text-caption text-weight-medium text-grey-7 q-mb-none"
                      >
                        R$ {{ formatCurrency(item.valortotal) }}
                      </q-item-section>
                      <q-item-section
                        align="right"
                        class="text-caption text-weight-medium text-grey-7"
                      >
                        {{ item.naturezaOperacao }}
                      </q-item-section>
                    </div>
                  </q-item>
                </q-list>
                <div v-else class="absolute-center text-grey text-caption">Nenhuma</div>
              </q-card-section>
            </q-card>
          </q-carousel-slide>
        </q-carousel>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<style scoped>
.compact-tabs {
  min-height: 30px;
}

.compact-tab {
  min-height: 30px;
  padding: 0px;
  font-size: 11px;
}

.compact-tab :deep(.q-tab__label) {
  font-size: 15px;
}
</style>
