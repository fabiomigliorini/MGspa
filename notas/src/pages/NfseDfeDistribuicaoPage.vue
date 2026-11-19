<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useNfseDfeStore } from '../stores/nfseDfeStore'
import nfseDfeService from '../services/nfseDfeService'
import api from '../services/api'
import { abrirXml } from '@components/abrirXml'
import { abrirPdf } from '@components/abrirPdf'
import {
  formataChave,
  formataCnpjCpf,
  formataNumero,
  formataTimestamp,
} from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'
import NfseDfeConsultarDialog from '../components/dialogs/NfseDfeConsultarDialog.vue'

// Caixa de DF-e da NFS-e (TASK-279): o que o ADN distribuiu para a filial — NFS-e em que
// ela e' emitente, tomadora ou intermediaria, e os eventos delas. Lista no padrao da de
// Notas de Servico, como a tela de DF-e das NF-e.
const $q = useQuasar()
const dfeStore = useNfseDfeStore()
const consultarDialog = ref(false)

const loading = computed(() => dfeStore.pagination.loading)
const items = computed(() => dfeStore.items)
const hasActiveFilters = computed(() => dfeStore.hasActiveFilters)

const EVENTOS = {
  CANCELAMENTO: 'Cancelamento',
  SUBSTITUICAO: 'Cancelamento por substituição',
  CONFIRMACAO_PRESTADOR: 'Confirmação do prestador',
  CONFIRMACAO_TOMADOR: 'Confirmação do tomador',
  REJEICAO_PRESTADOR: 'Rejeição do prestador',
  REJEICAO_TOMADOR: 'Rejeição do tomador',
  ANULACAO_REJEICAO: 'Anulação da rejeição',
}

// NFS-e: a seta diz a direcao (emitida pela filial / tomada por ela). Evento: comentario.
const iconeTipo = (item) => {
  if (item.tipodocumento !== 'NFSE') return 'comment'
  return item.emitida ? 'call_made' : 'call_received'
}
const corTipo = (item) => (item.tipoevento === 'CANCELAMENTO' ? 'red-5' : 'teal')
const descricaoEvento = (tipo) => EVENTOS[tipo] || tipo
const documento = (doc) => (doc ? formataCnpjCpf(doc, doc.length === 11) : '')

// So a outra parte: o tomador quando a filial emitiu, o emitente quando ela tomou
const contraparte = (item) =>
  item.emitida
    ? { nome: item.tomador || 'Tomador não identificado', doc: item.tomadorcnpj }
    : { nome: item.emitente || '-', doc: item.emitentecnpj }

const onConsultaConcluida = () => {
  dfeStore.fetchItems(true)
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
      message: 'Erro ao carregar a caixa de DF-e da NFS-e',
      caption: error.message,
    })
    done(true)
  }
}

// Chamadas ao ADN por linha: o documento pelo NSU, ou os eventos da NFS-e pela chave
const consultando = ref(null)
const consultarAdn = async (item, tipo) => {
  consultando.value = `${tipo}-${item.coddistribuicaonfse}`
  try {
    const ret =
      tipo === 'nsu'
        ? await nfseDfeService.consultarNsu(item.coddistribuicaonfse)
        : await nfseDfeService.consultarEventos(item.codfilial, item.nfsechave)
    $q.notify({
      color: ret.documentos > 0 ? 'green-5' : 'red-5',
      textColor: 'white',
      icon: ret.documentos > 0 ? 'done' : 'info',
      message: ret.mensagem,
    })
    if (ret.novos > 0 || ret.documentos > 0) dfeStore.fetchItems(true)
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: error.response?.data?.message || 'Erro ao consultar o ADN',
    })
  } finally {
    consultando.value = null
  }
}

const reprocessando = ref(null)
const handleProcessar = async (item) => {
  reprocessando.value = item.coddistribuicaonfse
  try {
    await nfseDfeService.processar(item.coddistribuicaonfse)
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: 'Documento reprocessado',
    })
    dfeStore.fetchItems(true)
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: error.response?.data?.message || 'Erro ao reprocessar',
    })
  } finally {
    reprocessando.value = null
  }
}

// DANFSe da NFS-e da chave da linha (NFS-e ou evento); o backend marca CANCELADA se preciso
const abrindoDanfse = ref(null)
const handleDanfse = async (item) => {
  abrindoDanfse.value = item.coddistribuicaonfse
  try {
    await abrirPdf(
      api,
      `/v1/nfse-dfe/distribuicao/${item.coddistribuicaonfse}/danfse`,
      {},
      { title: 'DANFSe', size: 'a4' },
    )
  } finally {
    abrindoDanfse.value = null
  }
}

const handleVerXml = (item) =>
  abrirXml(
    api,
    `/v1/nfse-dfe/distribuicao/${item.coddistribuicaonfse}/xml`,
    {},
    {
      titulo: `NSU ${formataNumero(item.nsu, 0)}`,
      nomeArquivo: `nfse-dfe-${item.nsu}.xml`,
      erroFallback: 'Erro ao carregar XML',
    },
  )

// Acoes da linha. A caixa nao tem tela de detalhe: no celular e no tablet os botoes descem
// para baixo da linha; no desktop ficam a direita (o template desenha a mesma lista nos dois)
const acoes = (item) =>
  [
    item.codnotafiscalservico && {
      icon: 'receipt_long',
      tooltip: 'Abrir a nota de serviço',
      to: '/nota-servico/' + item.codnotafiscalservico,
    },
    item.nfsechave && {
      icon: 'picture_as_pdf',
      tooltip: 'Abrir DANFSe',
      loading: abrindoDanfse.value === item.coddistribuicaonfse,
      click: () => handleDanfse(item),
    },
    {
      icon: 'cloud_sync',
      tooltip: 'Buscar este NSU no ADN',
      loading: consultando.value === 'nsu-' + item.coddistribuicaonfse,
      click: () => consultarAdn(item, 'nsu'),
    },
    item.nfsechave && {
      icon: 'history',
      tooltip: 'Eventos desta NFS-e no ADN',
      loading: consultando.value === 'eventos-' + item.coddistribuicaonfse,
      click: () => consultarAdn(item, 'eventos'),
    },
    {
      icon: 'replay',
      tooltip: 'Reprocessar',
      loading: reprocessando.value === item.coddistribuicaonfse,
      click: () => handleProcessar(item),
    },
    { icon: 'code', tooltip: 'Arquivo XML', click: () => handleVerXml(item) },
  ].filter(Boolean)

onMounted(async () => {
  if (!dfeStore.initialLoadDone) {
    try {
      await dfeStore.fetchItems(true)
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: 'Erro ao carregar a caixa de DF-e da NFS-e',
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
        Nenhum documento recebido do ADN.
        <div class="text-caption q-mt-xs" v-if="hasActiveFilters">Tente ajustar os filtros.</div>
      </MgEmptyState>

      <q-infinite-scroll v-else @load="onLoad" :offset="250">
        <q-card flat bordered>
          <q-list separator>
            <q-item v-for="item in items" :key="item.coddistribuicaonfse">
              <q-item-section avatar style="min-width: 32px" class="items-center">
                <q-icon :name="iconeTipo(item)" :color="corTipo(item)" />
              </q-item-section>

              <q-item-section>
                <div class="row items-start full-width">
                  <!-- Documento (NFS-e com o número, ou o evento), com a filial embaixo -->
                  <div class="q-px-sm col-6 col-md-3">
                    <div class="text-weight-medium text-caption ellipsis">
                      <template v-if="item.tipodocumento === 'NFSE'">
                        NFS-e {{ item.nfsenumero || '' }}
                      </template>
                      <template v-else>{{ descricaoEvento(item.tipoevento) }}</template>
                    </div>
                    <div class="text-caption text-grey-7 ellipsis">
                      {{ item.filial?.filial }}
                    </div>
                  </div>

                  <!-- Só a outra parte, com o CNPJ/CPF embaixo (o evento não tem) -->
                  <div class="q-px-sm col-6 col-md-5">
                    <template v-if="item.tipodocumento === 'NFSE'">
                      <div class="text-weight-bold text-caption text-primary ellipsis">
                        {{ contraparte(item).nome }}
                      </div>
                      <div class="text-caption text-grey-7 ellipsis">
                        {{ documento(contraparte(item).doc) }}
                      </div>
                    </template>
                  </div>

                  <div class="q-px-sm col-6 col-md-2 text-caption text-grey-7">
                    <div class="ellipsis">
                      {{ item.data ? formataTimestamp(item.data, 4, true) : '-' }}
                    </div>
                    <div class="ellipsis">NSU {{ formataNumero(item.nsu || 0, 0) }}</div>
                  </div>

                  <div
                    class="q-px-sm col-6 col-md-2 text-weight-bold text-primary text-caption text-right ellipsis"
                  >
                    <template v-if="item.valorservico != null">
                      R$ {{ formataNumero(item.valorservico) }}
                    </template>
                  </div>

                  <!-- Chave: linha inteira, para caber os 50 dígitos -->
                  <div class="q-px-sm col-12 text-caption text-grey-7">
                    {{ formataChave(item.nfsechave) }}
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
                      :to="acao.to"
                      :loading="acao.loading"
                      @click="acao.click?.()"
                    >
                      <q-tooltip>{{ acao.tooltip }}</q-tooltip>
                    </q-btn>
                  </div>
                </div>
              </q-item-section>

              <!-- Largura fixa: as colunas alinham mesmo com 5 ou 6 botões -->
              <q-item-section side top style="width: 210px" class="gt-sm">
                <div class="row no-wrap justify-end full-width">
                  <q-btn
                    v-for="acao in acoes(item)"
                    :key="acao.icon"
                    flat
                    round
                    size="sm"
                    color="grey-7"
                    :icon="acao.icon"
                    :to="acao.to"
                    :loading="acao.loading"
                    @click="acao.click?.()"
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
      <q-btn fab icon="cloud_download" color="primary" @click="consultarDialog = true">
        <q-tooltip anchor="top middle" self="bottom middle">
          Consultar o ADN por novos documentos
        </q-tooltip>
      </q-btn>
    </q-page-sticky>

    <NfseDfeConsultarDialog v-model="consultarDialog" @done="onConsultaConcluida" />
  </q-page>
</template>
