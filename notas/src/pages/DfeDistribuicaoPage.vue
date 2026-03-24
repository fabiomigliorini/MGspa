<script setup>
import { computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useDfeDistribuicaoStore } from '../stores/dfeDistribuicaoStore'
import dfeDistribuicaoService from '../services/dfeDistribuicaoService'
import { formatChave, formatCnpjCpf, formatCurrency } from 'src/utils/formatters'

const $q = useQuasar()
const dfeStore = useDfeDistribuicaoStore()

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

const formatTimeAgo = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  const now = new Date()
  const diffMs = now - date
  const diffMinutes = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMinutes < 1) return 'agora'
  if (diffMinutes < 60) return `há ${diffMinutes} min`
  if (diffHours < 24) return `há ${diffHours}h`
  if (diffDays === 1) return 'há 1 dia'
  return `há ${diffDays} dias`
}

const formatNsu = (nsu) => {
  if (!nsu) return '0'
  return Number(nsu).toLocaleString('pt-BR')
}

const onLoad = async (index, done) => {
  try {
    await dfeStore.fetchItems()
    done(!dfeStore.pagination.hasMore)
  } catch (error) {
    $q.notify({
      type: 'negative',
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
      type: 'positive',
      message: 'Reprocessamento solicitado',
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao reprocessar',
      caption: error.message,
    })
  }
}

const handleConsultarSefaz = async (coddistribuicaodfe) => {
  try {
    await dfeDistribuicaoService.consultarSefaz(coddistribuicaodfe)
    $q.notify({
      type: 'positive',
      message: 'Consulta na SEFAZ realizada',
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao consultar SEFAZ',
      caption: error.response?.data?.message || error.message,
    })
  }
}

const handleVerXml = async (coddistribuicaodfe) => {
  try {
    const url = await dfeDistribuicaoService.xml(coddistribuicaodfe)
    window.open(url)
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar XML',
      caption: error.message,
    })
  }
}

onMounted(async () => {
  if (!dfeStore.initialLoadDone) {
    try {
      await dfeStore.fetchItems(true)
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao carregar distribuições DFe',
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

    <!-- Empty State -->
    <q-card v-else-if="items.length === 0" flat bordered class="q-pa-xl text-center">
      <q-icon name="inbox" size="4em" color="grey-5" />
      <div class="text-h6 text-grey-7 q-mt-md">Nenhuma distribuição DFe encontrada</div>
      <div class="text-caption text-grey-7 q-mt-sm">
        <template v-if="hasActiveFilters">Tente ajustar os filtros no menu lateral</template>
        <template v-else>Nenhum documento fiscal eletrônico distribuído</template>
      </div>
    </q-card>

    <!-- Lista com Scroll Infinito -->
    <q-infinite-scroll v-else @load="onLoad" :offset="250">
      <q-list separator>
        <q-item v-for="item in items" :key="item.coddistribuicaodfe">
          <!-- Ícone por tipo -->
          <q-item-section top avatar>
            <q-avatar
              color="teal"
              text-color="white"
              :icon="iconeDfeTipo(item.DfeTipo?.schemaxml)"
            />
          </q-item-section>

          <!-- Filial e Schema -->
          <q-item-section top class="col-sm-2 gt-xs">
            <q-item-label class="text-weight-medium">
              {{ item.Filial?.filial }}
            </q-item-label>
            <q-item-label caption>
              {{ item.DfeTipo?.schemaxml }}
            </q-item-label>
          </q-item-section>

          <!-- Dados da nota/evento -->
          <q-item-section top>
            <!-- NotaFiscalTerceiro -->
            <template v-if="item.codnotafiscalterceiro > 0">
              <q-item-label lines="1">
                <span class="text-weight-medium" v-if="item.NotaFiscalTerceiro?.codpessoa">
                  {{ item.NotaFiscalTerceiro.Pessoa?.fantasia }}
                </span>
                <span class="text-weight-medium" v-else>
                  {{ item.NotaFiscalTerceiro?.emitente }}
                </span>
              </q-item-label>
              <q-item-label lines="1">
                <span
                  class="text-grey-8 text-weight-medium"
                  v-if="item.NotaFiscalTerceiro?.valortotal"
                >
                  R$ {{ formatCurrency(item.NotaFiscalTerceiro.valortotal) }}
                </span>
                <span class="text-grey-8" v-if="item.NotaFiscalTerceiro?.natop">
                  - {{ item.NotaFiscalTerceiro.natop }}
                </span>
              </q-item-label>
            </template>

            <!-- Evento -->
            <template v-if="item.coddistribuicaodfeevento > 0">
              <q-item-label lines="1">
                <span class="text-weight-medium">
                  {{ item.DistribuicaoDfeEvento?.orgao }}
                </span>
                <span class="text-weight-medium">
                  {{
                    item.DistribuicaoDfeEvento?.cnpj
                      ? formatCnpjCpf(item.DistribuicaoDfeEvento.cnpj, false)
                      : ''
                  }}
                  {{
                    item.DistribuicaoDfeEvento?.cpf
                      ? formatCnpjCpf(item.DistribuicaoDfeEvento.cpf, true)
                      : ''
                  }}
                </span>
              </q-item-label>
              <q-item-label
                lines="1"
                v-if="item.DistribuicaoDfeEvento?.coddistribuicaodfeevento > 0"
              >
                <span class="text-grey-8">
                  {{ item.DistribuicaoDfeEvento?.DfeEvento?.dfeevento }}
                  ({{ item.DistribuicaoDfeEvento?.DfeEvento?.tpevento }})
                </span>
              </q-item-label>
            </template>

            <!-- Chave -->
            <q-item-label caption lines="1">
              {{ formatChave(item.nfechave) }}
            </q-item-label>
          </q-item-section>

          <!-- NSU e Data -->
          <q-item-section top side class="gt-xs">
            <q-chip size="sm" square icon="dialpad">
              {{ formatNsu(item.nsu) }}
            </q-chip>
            <span class="text-caption">
              {{ formatTimeAgo(item.data) }}
            </span>
          </q-item-section>

          <!-- Ações -->
          <q-item-section side>
            <q-btn-group flat>
              <q-btn
                flat
                dense
                color="orange"
                icon="cloud_download"
                @click="handleConsultarSefaz(item.coddistribuicaodfe)"
              >
                <q-tooltip>Consultar SEFAZ</q-tooltip>
              </q-btn>
              <q-btn
                flat
                dense
                color="blue"
                icon="replay"
                @click="handleProcessar(item.coddistribuicaodfe)"
              >
                <q-tooltip>Reprocessar</q-tooltip>
              </q-btn>
              <q-btn
                flat
                dense
                color="blue"
                icon="code"
                @click="handleVerXml(item.coddistribuicaodfe)"
              >
                <q-tooltip>Arquivo XML</q-tooltip>
              </q-btn>
            </q-btn-group>
          </q-item-section>
        </q-item>
      </q-list>

      <template v-slot:loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="40px" />
        </div>
      </template>
    </q-infinite-scroll>
  </q-page>
</template>
