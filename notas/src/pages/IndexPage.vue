<script setup>
import { onMounted, computed, watch, shallowRef } from 'vue'
import { useQuasar } from 'quasar'
import { useAuth } from 'src/composables/useAuth'
import { useDashboardStore } from 'src/stores/dashboard'
import { formatDateTime } from 'src/utils/formatters'
import VChart from 'vue-echarts'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart, BarChart } from 'echarts/charts'
import { GridComponent, TooltipComponent, LegendComponent } from 'echarts/components'

use([CanvasRenderer, LineChart, BarChart, GridComponent, TooltipComponent, LegendComponent])

const $q = useQuasar()
const { validateToken } = useAuth()
const store = useDashboardStore()

const loading = computed(() => store.loading)
const loadingVolumeMensal = computed(() => store.loadingVolumeMensal)
const period = computed(() => store.period)
const modelo = computed(() => store.modelo)
const isMobile = computed(() => $q.screen.lt.sm)
const sefazStatus = computed(() => store.sefazStatus)
const kpisGerais = computed(() => store.kpisGerais)
const volumeMensal = computed(() => store.volumeMensal)
const erroPorFilial = computed(() => store.erroPorFilial)
const kpisPorFilial = computed(() => store.kpisPorFilial)

const periodOptions = [
  { label: 'Hoje', value: 1 },
  { label: '7 dias', value: 7 },
  { label: '30 dias', value: 30 },
  { label: '6 meses', value: 180 },
  { label: '1 ano', value: 365 },
  { label: '5 anos', value: 1825 },
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
    status: null,
  },
  {
    label: 'Autorizadas',
    labelShort: 'Aut.',
    value: (kpisGerais.value?.autorizadas?.quantidade || 0).toLocaleString('pt-BR'),
    subtitle: `${(kpisGerais.value?.autorizadas?.percentual || 0).toFixed(1)}%`,
    icon: 'check_circle',
    color: 'positive',
    status: 'AUT',
  },
  {
    label: 'Erro',
    labelShort: 'Erro',
    value: (kpisGerais.value?.erro?.quantidade || 0).toLocaleString('pt-BR'),
    subtitle: `${Math.ceil((kpisGerais.value?.erro?.percentual || 0) * 10) / 10}%`,
    icon: 'highlight_off',
    color: (kpisGerais.value?.erro?.quantidade || 0) > 0 ? 'negative' : 'grey-7',
    status: 'ERR',
  },
  {
    label: 'Digitação',
    labelShort: 'Dig.',
    value: (kpisGerais.value?.digitacao?.quantidade || 0).toLocaleString('pt-BR'),
    subtitle: `${(kpisGerais.value?.digitacao?.percentual || 0).toFixed(1)}%`,
    icon: 'edit_note',
    color: (kpisGerais.value?.digitacao?.quantidade || 0) > 0 ? 'blue' : 'grey-7',
    status: 'DIG',
  },
  {
    label: 'Canceladas',
    labelShort: 'Canc.',
    value: (kpisGerais.value?.canceladas?.quantidade || 0).toLocaleString('pt-BR'),
    subtitle: `${(kpisGerais.value?.canceladas?.percentual || 0).toFixed(1)}%`,
    icon: 'highlight_off',
    color: 'negative',
    status: 'CAN',
  },
  {
    label: 'Inutilizadas',
    labelShort: 'Inut.',
    value: (kpisGerais.value?.inutilizadas?.quantidade || 0).toLocaleString('pt-BR'),
    subtitle: `${(kpisGerais.value?.inutilizadas?.percentual || 0).toFixed(1)}%`,
    icon: 'block',
    color: 'warning',
    status: 'INU',
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
    label: 'Ultima Nota',
    field: 'ultima_nota_emitida',
    align: 'center',
    format: (v) => (v ? formatDateTime(v) : '-'),
  },
  {
    name: 'ultimo_erro',
    label: 'Ultimo Erro',
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
        <div v-if="!isMobile" class="text-grey-7 q-mt-xs q-pa-none text-caption">Periodo:</div>
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
    <div flat :class="isMobile ? 'row q-col-gutter-xs q-mb-sm' : 'row q-col-gutter-md q-mb-md'" style="flex-wrap: wrap">
      <div v-for="kpi in kpiCards" :key="kpi.label" :class="isMobile ? 'col-4' : 'col-2'">
        <component
          :is="kpi.status ? 'router-link' : 'div'"
          :to="kpi.status ? { path: '/nota', query: { status: kpi.status } } : undefined"
          style="text-decoration: none; color: inherit; display: block"
        >
        <q-card
          flat bordered
          :class="kpi.status ? 'cursor-pointer' : ''"
        >
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
          <q-tooltip v-if="kpi.status">Clique para ver notas</q-tooltip>
        </q-card>
        </component>
      </div>
    </div>

    <!-- LINHA 2: Graficos -->
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
