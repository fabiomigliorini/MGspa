<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useNfseDfeStore } from '../stores/nfseDfeStore'
import nfseDfeService from '../services/nfseDfeService'
import api from '../services/api'
import { abrirXml } from '@components/abrirXml'
import { formataChave, formataCnpjCpf, formataNumero, tempoRelativo } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'
import NfseDfeConsultarDialog from '../components/dialogs/NfseDfeConsultarDialog.vue'

// Caixa de DF-e da NFS-e (TASK-279): o que o ADN distribuiu para a filial — NFS-e em que
// ela e' emitente, tomadora ou intermediaria, e os eventos delas. Mesmo formato da tela
// de DF-e das NF-e.
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

const iconeTipo = (item) => (item.tipodocumento === 'NFSE' ? 'description' : 'comment')
const corTipo = (item) => (item.tipoevento === 'CANCELAMENTO' ? 'red-5' : 'teal')
const descricaoEvento = (tipo) => EVENTOS[tipo] || tipo
const documento = (doc) => (doc ? formataCnpjCpf(doc, doc.length === 11) : '')

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
    <!-- Loading inicial -->
    <div v-if="loading && items.length === 0" class="row justify-center q-py-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Vazio -->
    <MgEmptyState v-else-if="items.length === 0" icon="inbox">
      Nenhum documento recebido do ADN.
      <template v-if="hasActiveFilters"> Tente ajustar os filtros no menu lateral.</template>
    </MgEmptyState>

    <!-- Lista com Scroll Infinito -->
    <q-infinite-scroll v-else @load="onLoad" :offset="250">
      <q-list separator>
        <q-item v-for="item in items" :key="item.coddistribuicaonfse">
          <!-- Ícone por tipo -->
          <q-item-section top avatar>
            <q-avatar :color="corTipo(item)" text-color="white" :icon="iconeTipo(item)" />
          </q-item-section>

          <!-- Filial e tipo -->
          <q-item-section top class="col-sm-2 gt-xs">
            <q-item-label class="text-weight-medium">
              {{ item.filial?.filial }}
            </q-item-label>
            <q-item-label caption>
              {{ item.tipodocumento === 'NFSE' ? 'NFS-e' : 'Evento' }}
            </q-item-label>
          </q-item-section>

          <!-- Dados da NFS-e / evento -->
          <q-item-section top>
            <template v-if="item.tipodocumento === 'NFSE'">
              <q-item-label lines="1">
                <span class="text-weight-medium">{{ item.emitente || '-' }}</span>
                <span class="text-grey-7 q-ml-sm" v-if="item.emitentecnpj">
                  {{ documento(item.emitentecnpj) }}
                </span>
              </q-item-label>
              <q-item-label lines="1">
                <span class="text-grey-8 text-weight-medium" v-if="item.valorservico != null">
                  R$ {{ formataNumero(item.valorservico) }}
                </span>
                <span class="text-grey-8" v-if="item.nfsenumero">
                  - NFS-e {{ item.nfsenumero }}</span
                >
                <span class="text-grey-8">
                  - Tomador: {{ item.tomador || 'não identificado' }}
                </span>
              </q-item-label>
            </template>

            <template v-else>
              <q-item-label lines="1">
                <span class="text-weight-medium">{{ descricaoEvento(item.tipoevento) }}</span>
              </q-item-label>
            </template>

            <!-- Chave -->
            <q-item-label caption lines="1">
              {{ formataChave(item.nfsechave) }}
            </q-item-label>
          </q-item-section>

          <!-- NSU e Data -->
          <q-item-section top side class="gt-xs">
            <q-chip size="sm" square icon="dialpad">
              {{ formataNumero(item.nsu || 0, 0) }}
            </q-chip>
            <span class="text-caption">
              {{ tempoRelativo(item.data) }}
            </span>
          </q-item-section>

          <!-- Ações -->
          <q-item-section side>
            <div class="row no-wrap">
              <q-btn
                v-if="item.codnotafiscalservico"
                flat
                round
                size="sm"
                color="grey-7"
                icon="receipt_long"
                :to="'/nota-servico/' + item.codnotafiscalservico"
              >
                <q-tooltip>Abrir a nota de serviço</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                size="sm"
                color="grey-7"
                icon="cloud_sync"
                :loading="consultando === 'nsu-' + item.coddistribuicaonfse"
                @click="consultarAdn(item, 'nsu')"
              >
                <q-tooltip>Buscar este NSU no ADN</q-tooltip>
              </q-btn>
              <q-btn
                v-if="item.nfsechave"
                flat
                round
                size="sm"
                color="grey-7"
                icon="history"
                :loading="consultando === 'eventos-' + item.coddistribuicaonfse"
                @click="consultarAdn(item, 'eventos')"
              >
                <q-tooltip>Eventos desta NFS-e no ADN</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                size="sm"
                color="grey-7"
                icon="replay"
                :loading="reprocessando === item.coddistribuicaonfse"
                @click="handleProcessar(item)"
              >
                <q-tooltip>Reprocessar</q-tooltip>
              </q-btn>
              <q-btn flat round size="sm" color="grey-7" icon="code" @click="handleVerXml(item)">
                <q-tooltip>Arquivo XML</q-tooltip>
              </q-btn>
            </div>
          </q-item-section>
        </q-item>
      </q-list>

      <template v-slot:loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="40px" />
        </div>
      </template>
    </q-infinite-scroll>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="cloud_download" color="primary" @click="consultarDialog = true">
        <q-tooltip>Consultar o ADN por novos documentos</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <NfseDfeConsultarDialog v-model="consultarDialog" @done="onConsultaConcluida" />
  </q-page>
</template>
