<script setup>
import { ref, watch, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { date } from 'quasar'
import { formatMoney } from 'src/utils/formatters.js'
import { useExtratoStore } from 'src/stores/extratoStore'

const route = useRoute()
const store = useExtratoStore()
const tabela = ref(null)

const columns = [
  {
    name: 'dia',
    label: 'Dia',
    field: 'dia',
    align: 'left',
    format: (v) => (v ? date.formatDate(v, 'DD/MM/YYYY') : ''),
  },
  { name: 'observacoes', label: 'Observação', field: 'observacoes', align: 'left' },
  { name: 'numero', label: 'Documento', field: 'numero', align: 'left' },
  {
    name: 'valor',
    label: 'Valor',
    field: 'valor',
    align: 'right',
    format: (v) => (v !== null && v !== undefined ? formatMoney(v) : ''),
  },
  {
    name: 'saldo',
    label: 'Saldo',
    field: 'saldo',
    align: 'right',
    format: (v) => (v !== null && v !== undefined ? formatMoney(v) : ''),
  },
]

function cellClass(row, col) {
  const classes = []
  if (col.name === 'valor' && row.valor != null) {
    classes.push(row.valor < 0 ? 'text-red' : 'text-blue')
  }
  if (col.name === 'saldo' && row.saldo != null) {
    classes.push(row.saldo < 0 ? 'text-red' : 'text-blue')
  }
  if (row.tipo === 'saldo') classes.push('text-weight-bold bg-grey-2')
  return classes.join(' ')
}

function scrollParaDia(dia) {
  if (!dia || !tabela.value) return
  const idx = store.linhas.findIndex((l) => date.formatDate(l.dia, 'DD') === dia)
  if (idx >= 0) tabela.value.scrollTo(idx, 'start-force')
}

watch(
  () => [route.params.codportador, route.params.ano, route.params.mes],
  async ([cod, ano, mes]) => {
    if (!cod || !ano || !mes) return
    store.codportador = Number(cod)
    store.ano = ano
    store.mes = mes
    await store.carregar()
    await nextTick()
    scrollParaDia(store.diaSelecionado)
  },
)

watch(
  () => store.diaSelecionado,
  (dia) => scrollParaDia(dia),
)

onMounted(async () => {
  store.codportador = Number(route.params.codportador)
  store.ano = route.params.ano
  store.mes = route.params.mes
  await Promise.all([store.buscaPortador(), store.buscaIntervalo()])
  await store.carregar()
  await nextTick()
  scrollParaDia(store.diaSelecionado)
})
</script>

<template>
  <q-page>
    <div class="q-pa-md">
      <q-card flat bordered class="q-mb-md" v-if="store.portador">
        <q-card-section class="q-py-sm row items-center no-wrap">
          <q-btn
            flat
            round
            dense
            icon="arrow_back"
            color="grey-7"
            class="q-mr-sm"
            :to="{ name: 'portador-saldos' }"
          />
          <div class="col">
            <div class="text-subtitle1 text-weight-bold">{{ store.portador.portador }}</div>
            <div class="text-caption text-grey-7">
              {{ store.portador.filial || 'Sem Filial' }} · {{ store.portador.banco }}
            </div>
          </div>
          <q-chip outline color="primary" text-color="primary" class="q-ml-sm">
            {{ store.mes }}/{{ store.ano }}
          </q-chip>
        </q-card-section>
      </q-card>

      <q-table
        ref="tabela"
        flat
        bordered
        :rows="store.linhas"
        :columns="columns"
        :loading="store.isLoading"
        virtual-scroll
        row-key="_key"
        :rows-per-page-options="[0]"
        loading-label="Carregando"
        hide-bottom
        style="max-height: calc(100vh - 200px)"
      >
        <template v-slot:body="props">
          <q-tr :props="props">
            <q-td
              v-for="col in props.cols"
              :key="col.name"
              :props="props"
              :class="cellClass(props.row, col)"
            >
              {{ col.value }}
            </q-td>
          </q-tr>
        </template>
      </q-table>
    </div>

    <q-page-sticky
      position="bottom-right"
      :offset="[18, 18]"
      v-if="store.portador?.codbanco == 1"
    >
      <q-btn
        fab
        icon="cloud_download"
        color="primary"
        :loading="store.buscandoApi"
        @click="store.consultarApiBB()"
      >
        <template v-slot:loading>
          <q-spinner-oval />
        </template>
        <q-tooltip anchor="center left" self="center right">Consultar API</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
