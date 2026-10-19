<script setup>
import { formataNumero, formataHora, formataDataCompleta } from '@components/formatters'
import { onMounted, ref, watch } from 'vue'
import { exportFile, Notify, Dialog } from 'quasar'
import { fimMes, hoje, inicioMes, somaDias } from '@components/utils/datas'
import { api } from 'src/services/api'

const anos = ref([])
const ano = ref(null)

const meses = ref([
  { numero: 1, label: 'Jan' },
  { numero: 2, label: 'Fev' },
  { numero: 3, label: 'Mar' },
  { numero: 4, label: 'Abr' },
  { numero: 5, label: 'Mai' },
  { numero: 6, label: 'Jun' },
  { numero: 7, label: 'Jul' },
  { numero: 8, label: 'Ago' },
  { numero: 9, label: 'Set' },
  { numero: 10, label: 'Out' },
  { numero: 11, label: 'Nov' },
  { numero: 12, label: 'Dez' },
])
const mes = ref(null)

const codfilial = ref(null) // filial selecionada

const data = ref(null) // data selecionada no q-date
const datas = ref([])
const datasDisponiveis = ref([])
const datasFaltando = ref([])

const detalhado = ref([])

const colunasTabelaNegocios = [
  { name: 'codnegocio', label: '#', field: 'codnegocio', sortable: true },
  {
    name: 'valortotal',
    label: 'Valor',
    field: (row) => parseFloat(row.valortotal),
    format: (val) => formataNumero(val),
    sortable: true,
  },
  {
    name: 'valorsaldo',
    label: 'Saldo',
    field: (row) => parseFloat(row.valorsaldo),
    format: (val) => formataNumero(val),
    sortable: true,
  },
  { name: 'fantasia', label: 'Fantasia', field: 'fantasia', align: 'left', sortable: true },
  {
    name: 'lancamento',
    label: 'Hora',
    field: 'lancamento',
    format: (val) => formataHora(val),
    sortable: true,
  },
  { name: 'usuario', align: 'left', label: 'Usuário', field: 'usuario', sortable: true },
  {
    name: 'pdv',
    label: 'PDV',
    align: 'left',
    field: 'pdv',
    sortable: true,
  },
]

const inicializaAnosMeses = async () => {
  ano.value = new Date().getFullYear()
  let i = ano.value - 3
  do {
    anos.value.push(i)
    i++
  } while (i <= ano.value)
  mes.value = new Date().getMonth() + 1
  data.value = hoje()
  inicializaDiaInicialFinal()
}

// 1º dia do mês escolhido, 'YYYY-MM-DD' (comparar o texto é comparar o dia)
const primeiroDia = () => `${ano.value}-${String(mes.value).padStart(2, '0')}-01`

const inicializaDiaInicialFinal = () => {
  const fim = fimMes(primeiroDia())
  datasDisponiveis.value = []
  for (let d = primeiroDia(); d <= fim; d = somaDias(d, 1)) {
    datasDisponiveis.value.push(d.replaceAll('-', '/'))
  }
  decideDia()
  buscaFaltando()
}

// no mês corrente, hoje; nos outros, o último dia
const decideDia = () => {
  const d = primeiroDia()
  data.value = d === inicioMes(hoje()) ? hoje() : fimMes(d)
}

const buscaFaltando = async () => {
  try {
    const ret = await api.get('/v1/negocio/anexo/faltando/' + ano.value + '/' + mes.value)
    // datasFaltando.value = ret.data.resumo;
    datasFaltando.value = []
    ret.data.resumo.forEach((d) => {
      datasFaltando.value.push(d.replaceAll('-', '/'))
    })
    datas.value = ret.data.datas
    await detalhesDoDia()
  } catch (error) {
    console.log(error)
    var message = error?.response?.data?.message
    if (!message) {
      message = error?.message
    }
    Notify.create({
      type: 'negative',
      message: message,
      timeout: 3000, // 3 segundos
      actions: [{ icon: 'close', color: 'white' }],
    })
    return false
  }
}

const detalhesDoDia = async () => {
  const achou = datas.value.find((e) => {
    return e.data == data.value
  })
  if (!achou) {
    detalhado.value = false
    return
  }
  detalhado.value = achou
  codfilial.value = achou.filiais[0].codfilial
}

const wrapCsvValue = (val, formatFn, row) => {
  let formatted = formatFn !== void 0 ? formatFn(val, row) : val

  formatted = formatted === void 0 || formatted === null ? '' : String(formatted)

  formatted = formatted.split('"').join('""')
  /**
   * Excel accepts \n and \r in strings, but some other CSV parsers do not
   * Uncomment the next two lines to escape new lines
   */
  // .split('\n').join('\\n')
  // .split('\r').join('\\r')

  return `"${formatted}"`
}

const exportTable = () => {
  // naive encoding to csv format
  const rows = detalhado.value.filiais.find((e) => e.codfilial == codfilial.value).negocios
  const content = [colunasTabelaNegocios.map((col) => wrapCsvValue(col.label))]
    .concat(
      rows.map((row) =>
        colunasTabelaNegocios
          .map((col) =>
            wrapCsvValue(
              typeof col.field === 'function'
                ? col.field(row)
                : row[col.field === void 0 ? col.name : col.field],
              col.format,
              row,
            ),
          )
          .join(','),
      ),
    )
    .join('\r\n')

  const status = exportFile(
    'anexos-faltando-' + data.value + '-' + codfilial.value + '.csv',
    content,
    'text/csv',
  )

  if (status !== true) {
    Notify.create({
      type: 'negative',
      icon: 'warning',
      message: 'Navegador não permitiu o download...',
      timeout: 3000, // 3 segundos
      actions: [{ icon: 'close', color: 'white' }],
    })
  }
}

const ignorar = (codnegocio) => {
  Dialog.create({
    title: 'Ignorar',
    message:
      'Tem certeza que você deseja ignorar a confissão de dívida para o negocio ' +
      codnegocio +
      '?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'OK', color: 'primary', flat: true },
  }).onOk(async () => {
    try {
      const ret = await api.post('/v1/negocio/' + codnegocio + '/ignorar-confissao')
      datasFaltando.value = []
      ret.data.resumo.forEach((d) => {
        datasFaltando.value.push(d.replaceAll('-', '/'))
      })
      datas.value = ret.data.datas
      await detalhesDoDia()
    } catch (error) {
      console.log(error)
      var message = error?.response?.data?.message
      if (!message) {
        message = error?.message
      }
      Notify.create({
        type: 'negative',
        message: message,
        timeout: 3000, // 3 segundos
        actions: [{ icon: 'close', color: 'white' }],
      })
      return false
    }
  })
}

onMounted(() => {
  inicializaAnosMeses()
})

watch(ano, () => {
  inicializaDiaInicialFinal()
})

watch(mes, () => {
  inicializaDiaInicialFinal()
})

watch(data, () => {
  detalhesDoDia()
})
</script>
<template>
  <q-page>
    <!-- ANOS -->
    <q-tabs v-model="ano" inline-label class="bg-primary text-white">
      <template v-for="a in anos" :key="a">
        <q-tab :name="a" :label="a" />
      </template>
    </q-tabs>

    <!-- MESES -->
    <q-tabs v-model="mes" inline-label class="bg-primary text-white">
      <template v-for="m in meses" :key="m.numero">
        <q-tab :name="m.numero" :label="m.label" />
      </template>
    </q-tabs>

    <div class="row q-pa-md q-col-gutter-md">
      <!-- CALENDARIO -->
      <div class="col-xs-12 col-sm-6 col-md-3 col-lg-2" style="min-width: 310px">
        <q-date
          flat
          bordered
          minimal
          v-model="data"
          :options="datasDisponiveis"
          :events="datasFaltando"
          event-color="negative"
          no-unset
          mask="YYYY-MM-DD"
          style="width: 100%"
        />
      </div>

      <!-- CARD DETALHES -->
      <div class="col" v-if="detalhado">
        <q-card class="shadow-1" bordered flat>
          <!-- TITULO -->
          <q-card-section class="bg-primary text-white">
            <q-btn
              dense
              round
              flat
              color="white"
              icon="archive"
              class="float-right"
              no-caps
              @click="exportTable"
            />
            <div class="text-h6">
              {{ detalhado.faltando }} confissões faltando |
              {{ formataDataCompleta(data) }}
            </div>
          </q-card-section>

          <!-- FILIAIS -->
          <q-tabs dense v-model="codfilial" inline-label class="bg-primary text-white">
            <template v-for="f in detalhado.filiais" :key="f.codfilial">
              <q-tab :label="f.filial" :name="f.codfilial">
                <q-badge rounded="" color="negative" floating>{{ f.faltando }}</q-badge>
              </q-tab>
            </template>
          </q-tabs>

          <!-- DETALHES -->
          <q-tab-panels v-model="codfilial" animated>
            <template v-for="f in detalhado.filiais" :key="f.codfilial">
              <q-tab-panel :name="f.codfilial" class="q-pa-none">
                <!-- TABELA -->
                <q-table
                  dense
                  flat
                  :rows="f.negocios"
                  :columns="colunasTabelaNegocios"
                  row-key="codnegocio"
                  virtual-scroll
                  :pagination="{
                    rowsPerPage: 0,
                  }"
                  :rows-per-page-options="[0]"
                  style="max-height: 60vh"
                >
                  <!-- LINK NEGOCIO -->
                  <template v-slot:body-cell-codnegocio="props">
                    <q-td :props="props">
                      <q-btn
                        link
                        size="12px"
                        round
                        flat
                        ripple
                        dense
                        color="negative"
                        icon="not_interested"
                        @click="ignorar(props.key)"
                      />
                      <q-btn
                        link
                        size="12px"
                        flat
                        ripple
                        dense
                        color="primary"
                        :label="props.value"
                        :to="'/negocio/' + props.key"
                      />
                    </q-td>
                  </template>
                </q-table>
              </q-tab-panel>
            </template>
          </q-tab-panels>
        </q-card>
      </div>
    </div>
  </q-page>
</template>
