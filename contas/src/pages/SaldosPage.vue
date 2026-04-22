<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import moment from 'moment'
import { useQuasar } from 'quasar'
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

const formatNumero = (value) =>
  new Intl.NumberFormat('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value || 0)

const bancosUnicos = computed(() => {
  const map = new Map()
  store.filiais.forEach((f) =>
    f.bancos.forEach((b) => {
      if (!map.has(b.codbanco)) {
        map.set(b.codbanco, { codbanco: b.codbanco, nome: b.nome })
      }
    }),
  )
  return [...map.values()].sort((a, b) => a.nome.localeCompare(b.nome))
})

const linhas = computed(() =>
  store.filiais.map((f) => {
    const porBanco = {}
    f.bancos.forEach((b) => {
      porBanco[b.codbanco] = { total: b.totalBanco, portadores: b.portadores }
    })
    return {
      codfilial: f.codfilial,
      nome: f.nome || 'Sem Filial',
      totalFilial: f.totalFilial,
      porBanco,
    }
  }),
)

const expanded = ref({})
const chave = (codfilial, codbanco) => `${codfilial}-${codbanco}`
const isExpanded = (codfilial, codbanco) => !!expanded.value[chave(codfilial, codbanco)]
const toggleExpanded = (codfilial, codbanco) => {
  const k = chave(codfilial, codbanco)
  expanded.value[k] = !expanded.value[k]
}

const moneyColor = (value) => (value < 0 ? 'text-red' : '')

const larguraColuna = computed(() => `${100 / (bancosUnicos.value.length + 2)}%`)

const totalPorBanco = (codbanco) => {
  const t = store.totalPorBanco.find((x) => x.codbanco === codbanco)
  return t ? t.valor : 0
}

const anoAtual = computed(() => (store.dataSelecionada ? store.dataSelecionada.substring(6) : ''))
const mesAtual = computed(() =>
  store.dataSelecionada ? store.dataSelecionada.substring(3, 5) : '',
)

const linkExtrato = (codportador) => ({
  name: 'extrato',
  params: { codportador, ano: anoAtual.value, mes: mesAtual.value },
})

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
  <q-page class="relative-position">
    <div class="q-pa-md">
      <q-card bordered flat>
        <div class="overflow-auto">
          <table
            class="q-table q-table--flat q-table--cell-separator q-table--horizontal-separator"
            style="table-layout: fixed; width: 100%; border-collapse: collapse"
          >
            <colgroup>
              <col :style="{ width: larguraColuna }" />
              <col
                v-for="b in bancosUnicos"
                :key="`col-${b.codbanco}`"
                :style="{ width: larguraColuna }"
              />
              <col :style="{ width: larguraColuna }" />
            </colgroup>
            <thead>
              <tr>
                <th class="text-left">Filial</th>
                <th v-for="b in bancosUnicos" :key="`h-${b.codbanco}`" class="text-right">
                  {{ b.nome }}
                </th>
                <th class="text-right bg-yellow-1">Total Filial</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="l in linhas" :key="l.codfilial">
                <td class="text-left text-weight-bold">{{ l.nome }}</td>

                <td
                  v-for="b in bancosUnicos"
                  :key="`c-${l.codfilial}-${b.codbanco}`"
                  class="text-right overflow-hidden"
                >
                  <template v-if="l.porBanco[b.codbanco]">
                    <router-link
                      v-if="l.porBanco[b.codbanco].portadores.length === 1"
                      :to="linkExtrato(l.porBanco[b.codbanco].portadores[0].codportador)"
                      class="row items-center justify-end no-wrap cursor-pointer text-primary"
                      :class="moneyColor(l.porBanco[b.codbanco].total)"
                      style="text-decoration: none"
                    >
                      <span>{{ formatNumero(l.porBanco[b.codbanco].total) }}</span>
                      <q-icon name="horizontal_rule" size="16px" color="grey-5" class="q-ml-xs" />
                    </router-link>

                    <template v-else>
                      <div
                        class="row items-center justify-end no-wrap cursor-pointer"
                        :class="moneyColor(l.porBanco[b.codbanco].total)"
                        @click="toggleExpanded(l.codfilial, b.codbanco)"
                      >
                        <span>{{ formatNumero(l.porBanco[b.codbanco].total) }}</span>
                        <q-icon
                          :name="
                            isExpanded(l.codfilial, b.codbanco) ? 'expand_less' : 'expand_more'
                          "
                          size="16px"
                          class="q-ml-xs"
                        />
                      </div>
                      <div v-if="isExpanded(l.codfilial, b.codbanco)" class="q-mt-xs">
                        <router-link
                          v-for="p in l.porBanco[b.codbanco].portadores"
                          :key="p.codportador"
                          :to="linkExtrato(p.codportador)"
                          class="cursor-pointer q-py-xs"
                          style="text-decoration: none; display: block"
                        >
                          <div class="row items-center justify-end no-wrap">
                            <span class="text-primary" :class="moneyColor(p.saldobancario)">
                              {{ formatNumero(p.saldobancario) }}
                            </span>
                            <q-icon
                              name="horizontal_rule"
                              size="16px"
                              color="grey-5"
                              class="q-ml-xs"
                            />
                          </div>
                          <div
                            class="text-grey-6 text-uppercase text-right ellipsis"
                            style="font-size: xx-small"
                          >
                            {{ p.portador }}
                          </div>
                        </router-link>
                      </div>
                    </template>
                  </template>

                  <div v-else class="row items-center justify-end no-wrap text-grey">
                    <span>&nbsp;</span>
                    <q-icon name="horizontal_rule" size="16px" color="grey-5" class="q-ml-xs" />
                  </div>
                </td>

                <td
                  class="text-right text-weight-bold bg-yellow-1"
                  :class="moneyColor(l.totalFilial)"
                >
                  <div class="row items-center justify-end no-wrap">
                    <span>{{ formatNumero(l.totalFilial) }}</span>
                    <q-icon name="horizontal_rule" size="16px" color="grey-5" class="q-ml-xs" />
                  </div>
                </td>
              </tr>

              <tr v-if="!linhas.length && !store.isLoading">
                <td :colspan="bancosUnicos.length + 2" class="text-center text-grey">
                  Nenhum dado disponível
                </td>
              </tr>
              <tr v-if="linhas.length" class="bg-yellow-1 text-weight-bold">
                <td class="text-left">Total por Banco</td>
                <td
                  v-for="b in bancosUnicos"
                  :key="`t-${b.codbanco}`"
                  class="text-right"
                  :class="moneyColor(totalPorBanco(b.codbanco))"
                >
                  <div class="row items-center justify-end no-wrap">
                    <span>{{ formatNumero(totalPorBanco(b.codbanco)) }}</span>
                    <q-icon name="horizontal_rule" size="16px" color="grey-5" class="q-ml-xs" />
                  </div>
                </td>
                <td class="text-right" :class="moneyColor(store.totalGeral)">
                  <div class="row items-center justify-end no-wrap">
                    <span>{{ formatNumero(store.totalGeral) }}</span>
                    <q-icon name="horizontal_rule" size="16px" color="grey-5" class="q-ml-xs" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </q-card>
    </div>

    <q-inner-loading :showing="store.isLoading || store.buscandoIntervalo" color="primary" />

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab :loading="importandoOfx" color="primary" icon="upload_file" @click="openDialog">
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
          <q-btn flat label="Fechar" color="primary" v-close-popup :disable="enviandoOfx" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>
