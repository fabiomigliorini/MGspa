<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import moment from 'moment'
import { useQuasar } from 'quasar'
import { formatMoney } from 'src/utils/formatters.js'
import { useAuthStore } from 'stores/auth'
import { useSaldoStore } from 'src/stores/saldoStore'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const authStore = useAuthStore()
const store = useSaldoStore()

watch(
  () => store.dataSelecionada,
  (dia) => {
    if (!dia) return
    if (route.query.dia !== dia) {
      router.replace({ query: { ...route.query, dia } })
    }
  },
)

const ofxDialog = ref(false)
const falhaImportacaoOfx = ref(false)
const enviandoOfx = ref(false)
const importandoOfx = ref(false)

const urlUploadOfx = computed(() => process.env.API_URL + 'v1/portador/importar-ofx')

const columns = computed(() => [
  { name: 'banco', label: 'Banco', field: 'banco', align: 'left' },
  ...store.filiais.map((filial, idx) => ({
    name: String(idx),
    label: filial.nome ? filial.nome : 'Sem Filial',
    field: String(idx),
    align: 'right',
  })),
  { name: 'totalBanco', label: 'Total Banco', field: 'totalBanco', align: 'right' },
])

const rows = computed(() => {
  const linhasPorBanco = {}
  store.filiais.forEach((filial, idxFilial) => {
    filial.bancos.forEach((banco) => {
      const codbanco = banco.codbanco
      if (!linhasPorBanco[codbanco]) {
        linhasPorBanco[codbanco] = { banco: banco.nome, totalBanco: 0 }
        store.filiais.forEach((_, idx) => {
          linhasPorBanco[codbanco][String(idx)] = []
        })
      }
      linhasPorBanco[codbanco][String(idxFilial)] = banco.portadores
      const total = store.totalPorBanco.find((t) => t.codbanco === codbanco)
      linhasPorBanco[codbanco].totalBanco = total ? total.valor : 0
    })
  })
  return Object.values(linhasPorBanco)
})

const moneyTextColor = (value) => (value < 0 ? 'text-red' : '')

const mesAnoAtual = computed(() =>
  store.dataSelecionada ? store.dataSelecionada.substring(3) : '',
)

const openDialog = () => {
  falhaImportacaoOfx.value = false
  ofxDialog.value = true
}

const finalImportacaoOfx = () => {
  enviandoOfx.value = false
  if (!falhaImportacaoOfx.value) ofxDialog.value = false
  store.buscaIntervalo()
  store.listaSaldos()
}

const ofxImportado = (info) => {
  const resp = JSON.parse(info.xhr.response)
  Object.keys(resp).forEach((arquivo) => {
    const r = resp[arquivo]
    $q.notify({
      message: `Importados ${r.registros} registros no portador "${r.portador}" com ${r.falhas} falhas!`,
      color: 'positive',
    })
  })
}

const ofxFalha = (response) => {
  falhaImportacaoOfx.value = true
  response.files.forEach((arquivo) => {
    $q.notify({
      message: `Falha ao importar o arquivo "${arquivo.name}"!`,
      color: 'negative',
    })
  })
}

onMounted(() => {
  const dia = route.query.dia || moment().format('DD-MM-YYYY')
  store.dataSelecionada = dia
  store.buscaIntervalo()
  store.listaSaldos()
})
</script>

<template>
  <q-page>
    <div class="q-pa-md">
      <q-table
        :columns="columns"
        :rows="rows"
        row-key="banco"
        flat
        bordered
        hide-pagination
        no-data-label="Nenhum dado disponível"
        :loading="store.isLoading || store.buscandoIntervalo"
      >
        <template v-slot:body-cell-banco="props">
          <q-td :props="props" class="text-weight-bold" style="vertical-align: top">
            {{ props.row.banco }}
          </q-td>
        </template>

        <template v-slot:body-cell="props">
          <q-td :props="props" class="q-pa-0" style="vertical-align: top">
            <div
              v-if="Array.isArray(props.value) && props.value.length"
              style="display: flex; flex-direction: column"
            >
              <router-link
                v-for="port in props.value"
                :key="port.codportador"
                style="text-decoration: none; color: dodgerblue"
                :class="moneyTextColor(port.saldobancario)"
                :to="{
                  name: 'extrato',
                  params: { id: port.codportador, mesAno: mesAnoAtual },
                }"
              >
                {{ formatMoney(port.saldobancario) }}
              </router-link>
            </div>
            <div v-else class="text-grey">---</div>
          </q-td>
        </template>

        <template v-slot:body-cell-totalBanco="props">
          <q-td
            :props="props"
            :class="'text-weight-bold bg-yellow-1 ' + moneyTextColor(props.value)"
            style="vertical-align: top"
          >
            {{ formatMoney(props.value) }}
          </q-td>
        </template>

        <template v-slot:bottom-row>
          <q-tr class="bg-yellow-1 text-weight-bold">
            <q-td key="banco">Total por Filial</q-td>
            <q-td
              v-for="f in store.filiais"
              :key="f.codfilial"
              align="right"
              :class="moneyTextColor(f.totalFilial)"
            >
              {{ formatMoney(f.totalFilial) }}
            </q-td>
            <q-td align="right" :class="moneyTextColor(store.totalGeral)">
              {{ formatMoney(store.totalGeral) }}
            </q-td>
          </q-tr>
        </template>

        <template v-slot:loading>
          <q-inner-loading showing color="primary" />
        </template>
      </q-table>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn
        fab
        :loading="importandoOfx"
        color="primary"
        icon="upload_file"
        @click="openDialog"
      >
        <template v-slot:loading>
          <q-spinner-oval />
        </template>
        <q-tooltip anchor="center left" self="center right"> Importar OFX </q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="ofxDialog" persistent>
      <q-card style="min-width: 800px">
        <q-card-section>
          <q-uploader
            ref="uploader"
            :url="urlUploadOfx"
            field-name="arquivos[]"
            accept=".ofx"
            label="Importar Arquivos OFX"
            @finish="finalImportacaoOfx"
            @uploaded="ofxImportado"
            @failed="ofxFalha"
            @uploading="enviandoOfx = true"
            :headers="[{ name: 'Authorization', value: `Bearer ${authStore.token}` }]"
            multiple
            flat
            style="width: 100%"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            label="Fechar"
            color="primary"
            v-close-popup
            :disable="enviandoOfx"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>
