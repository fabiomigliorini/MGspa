<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useDfeDistribuicaoStore } from '../stores/dfeDistribuicaoStore'
import dfeDistribuicaoService from '../services/dfeDistribuicaoService'
import api from '../services/api'
import { abrirXml } from '@components/abrirXml'
import {
  formataChave,
  formataCnpjCpf,
  formataNumero,
  formataTimestamp,
} from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'
import DfeConsultarSefazDialog from '../components/dialogs/DfeConsultarSefazDialog.vue'

const $q = useQuasar()
const dfeStore = useDfeDistribuicaoStore()
const consultarSefazDialog = ref(false)

const onConsultaConcluida = () => {
  dfeStore.fetchItems(true)
}

const loading = computed(() => dfeStore.pagination.loading)
const items = computed(() => dfeStore.items)
const hasActiveFilters = computed(() => dfeStore.hasActiveFilters)

const iconeDfeTipo = (schemaxml) => {
  switch (schemaxml) {
    case 'procNFe_v4.00.xsd':
      return 'description'
    case 'procEventoNFe_v1.00.xsd':
      return 'comment'
    case 'resEvento_v1.01.xsd':
      return 'comment'
    case 'resNFe_v1.01.xsd':
      return 'add'
    default:
      return 'info'
  }
}

const formatNsu = (nsu) => {
  if (!nsu) return '0'
  return formataNumero(nsu, 0)
}

// O que o documento e': o evento pelo nome; a NF-e (completa ou resumida) pelo tipo
const titulo = (item) => item.DistribuicaoDfeEvento?.DfeEvento?.dfeevento || item.DfeTipo?.dfetipo

// Quem: o emitente da NF-e, ou o autor do evento (CNPJ/CPF e orgao)
const quem = (item) => {
  if (item.codnfeterceiro > 0) {
    return item.NfeTerceiro?.Pessoa?.fantasia || item.NfeTerceiro?.emitente
  }
  const ev = item.DistribuicaoDfeEvento
  if (!ev?.coddistribuicaodfeevento) return null
  const doc = ev.cnpj ? formataCnpjCpf(ev.cnpj, false) : ev.cpf ? formataCnpjCpf(ev.cpf, true) : ''
  return [doc, ev.orgao ? `órgão ${ev.orgao}` : ''].filter(Boolean).join(' - ')
}

const onLoad = async (index, done) => {
  try {
    await dfeStore.fetchItems()
    done(!dfeStore.pagination.hasMore)
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: 'Erro ao carregar distribuições DFe',
      caption: error.message,
    })
    done(true)
  }
}

const handleProcessar = async (coddistribuicaodfe) => {
  try {
    await dfeDistribuicaoService.processar(coddistribuicaodfe)
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: 'Reprocessamento solicitado',
    })
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: 'Erro ao reprocessar',
      caption: error.message,
    })
  }
}

const handleConsultarSefaz = async (coddistribuicaodfe) => {
  try {
    await dfeDistribuicaoService.consultarSefaz(coddistribuicaodfe)
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: 'Consulta na SEFAZ realizada',
    })
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: 'Erro ao consultar SEFAZ',
      caption: error.response?.data?.message || error.message,
    })
  }
}

const handleVerXml = (coddistribuicaodfe) =>
  abrirXml(
    api,
    `/v1/dfe/distribuicao/${coddistribuicaodfe}/xml`,
    {},
    {
      titulo: `Documento #${coddistribuicaodfe}`,
      nomeArquivo: `dfe-${coddistribuicaodfe}.xml`,
      erroFallback: 'Erro ao carregar XML',
    },
  )

// Acoes da linha. Sem tela de detalhe: no celular e no tablet os botoes descem para baixo
// da linha; no desktop ficam a direita (o template desenha a mesma lista nos dois)
const acoes = (item) => [
  {
    icon: 'cloud_sync',
    tooltip: 'Consultar na SEFAZ',
    click: () => handleConsultarSefaz(item.coddistribuicaodfe),
  },
  { icon: 'replay', tooltip: 'Reprocessar', click: () => handleProcessar(item.coddistribuicaodfe) },
  { icon: 'code', tooltip: 'Arquivo XML', click: () => handleVerXml(item.coddistribuicaodfe) },
]

onMounted(async () => {
  if (!dfeStore.initialLoadDone) {
    try {
      await dfeStore.fetchItems(true)
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: 'Erro ao carregar distribuições DFe',
        caption: error.response?.data?.message || error.message,
      })
    }
  }
})
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <div v-if="loading && items.length === 0" class="row justify-center q-py-xl">
        <q-spinner color="primary" size="3em" />
      </div>

      <MgEmptyState v-else-if="items.length === 0" icon="inbox">
        Nenhuma distribuição DFe encontrada.
        <div class="text-caption q-mt-xs">
          <template v-if="hasActiveFilters">Tente ajustar os filtros.</template>
          <template v-else>Nenhum documento fiscal eletrônico distribuído.</template>
        </div>
      </MgEmptyState>

      <q-infinite-scroll v-else @load="onLoad" :offset="250">
        <q-card flat bordered>
          <q-list separator>
            <q-item v-for="item in items" :key="item.coddistribuicaodfe">
              <q-item-section avatar style="min-width: 32px" class="items-center">
                <q-icon :name="iconeDfeTipo(item.DfeTipo?.schemaxml)" color="teal" />
              </q-item-section>

              <q-item-section>
                <div class="row items-start full-width">
                  <!-- Documento (evento pelo nome, NF-e pelo tipo), com a filial embaixo -->
                  <div class="q-px-sm col-6 col-md-3">
                    <div class="text-weight-medium text-caption ellipsis">
                      {{ titulo(item) || '-' }}
                    </div>
                    <div class="text-caption text-grey-7 ellipsis">
                      {{ item.Filial?.filial }}
                    </div>
                  </div>

                  <!-- Emitente da NF-e (com o CNPJ embaixo) ou autor do evento -->
                  <div class="q-px-sm col-6 col-md-5">
                    <div
                      v-if="quem(item)"
                      class="text-weight-bold text-caption text-primary ellipsis"
                    >
                      {{ quem(item) }}
                    </div>
                    <div v-if="item.NfeTerceiro?.cnpj" class="text-caption text-grey-7 ellipsis">
                      {{ formataCnpjCpf(item.NfeTerceiro.cnpj, false) }}
                    </div>
                  </div>

                  <div class="q-px-sm col-6 col-md-2 text-caption text-grey-7">
                    <div class="ellipsis">
                      {{ item.data ? formataTimestamp(item.data, 4, true) : '-' }}
                    </div>
                    <div class="ellipsis">NSU {{ formatNsu(item.nsu) }}</div>
                  </div>

                  <!-- Valor da NF-e, com a natureza embaixo -->
                  <div class="q-px-sm col-6 col-md-2 text-caption text-right">
                    <div
                      v-if="item.NfeTerceiro?.valortotal"
                      class="text-weight-bold text-primary ellipsis"
                    >
                      R$ {{ formataNumero(item.NfeTerceiro.valortotal) }}
                    </div>
                    <div
                      v-if="item.NfeTerceiro?.natureza"
                      class="text-grey-7 ellipsis"
                      :title="item.NfeTerceiro.natureza"
                    >
                      {{ item.NfeTerceiro.natureza }}
                    </div>
                  </div>

                  <!-- Chave: linha inteira, para caber os 44 dígitos -->
                  <div class="q-px-sm col-12 text-caption text-grey-7">
                    {{ formataChave(item.nfechave) }}
                  </div>

                  <!-- Ações no celular e no tablet: embaixo, à direita -->
                  <div class="q-px-sm col-12 row justify-end lt-md">
                    <q-btn
                      v-for="acao in acoes(item)"
                      :key="acao.icon"
                      flat
                      round
                      size="sm"
                      color="grey-7"
                      :icon="acao.icon"
                      @click="acao.click()"
                    >
                      <q-tooltip>{{ acao.tooltip }}</q-tooltip>
                    </q-btn>
                  </div>
                </div>
              </q-item-section>

              <!-- Largura fixa: as colunas alinham entre as linhas -->
              <q-item-section side top style="width: 110px" class="gt-sm">
                <div class="row no-wrap justify-end full-width">
                  <q-btn
                    v-for="acao in acoes(item)"
                    :key="acao.icon"
                    flat
                    round
                    size="sm"
                    color="grey-7"
                    :icon="acao.icon"
                    @click="acao.click()"
                  >
                    <q-tooltip>{{ acao.tooltip }}</q-tooltip>
                  </q-btn>
                </div>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>

        <template v-slot:loading>
          <div class="row justify-center q-my-md">
            <q-spinner-dots color="primary" size="40px" />
          </div>
        </template>
      </q-infinite-scroll>

      <!-- respiro para o FAB não cobrir o fim da lista -->
      <div class="q-py-xl" />
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="cloud_download" color="primary" @click="consultarSefazDialog = true">
        <q-tooltip anchor="top middle" self="bottom middle">
          Consultar SEFAZ por novos documentos
        </q-tooltip>
      </q-btn>
    </q-page-sticky>

    <DfeConsultarSefazDialog v-model="consultarSefazDialog" @done="onConsultaConcluida" />
  </q-page>
</template>
