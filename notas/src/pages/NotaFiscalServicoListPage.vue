<script setup>
import { computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useNotaFiscalServicoStore } from '../stores/notaFiscalServicoStore'
import {
  getNfseStatusColor,
  getNfseStatusIcon,
  formataCodigoTributacaoNacional,
} from '../constants/notaFiscalServico'
import { formataTimestamp, formataNumero, formataNumeroNota } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgNotaFiscalServicoAcoes from '@components/MgNotaFiscalServicoAcoes.vue'
import api from '../services/api'

const $q = useQuasar()
const store = useNotaFiscalServicoStore()

const loading = computed(() => store.pagination.loading)
const notas = computed(() => store.notas)
const hasActiveFilters = computed(() => store.hasActiveFilters)

// As acoes devolvem a nota atualizada no payload: atualiza a linha sem nova requisicao
function onAcaoConcluida(acao, nota) {
  if (acao === 'excluir') {
    store.removerDaLista(nota?.codnotafiscalservico)
    return
  }
  store.setNota(nota)
}

const onLoad = async (index, done) => {
  try {
    await store.fetchNotas()
    done(!store.pagination.hasMore)
  } catch (error) {
    $q.notify({
      color: 'red-5',
      icon: 'error',
      message: 'Erro ao carregar as notas de serviço',
      caption: error?.response?.data?.message || error?.message,
    })
    done(true)
  }
}

onMounted(async () => {
  if (store.initialLoadDone) return
  try {
    await store.fetchNotas(true)
  } catch (error) {
    $q.notify({
      color: 'red-5',
      icon: 'error',
      message: 'Erro ao carregar as notas de serviço',
      caption: error?.response?.data?.message || error?.message,
    })
  }
})
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <div v-if="loading && notas.length === 0" class="row justify-center q-py-xl">
        <q-spinner color="primary" size="3em" />
      </div>

      <MgEmptyState v-else-if="notas.length === 0" icon="receipt">
        Nenhuma nota de serviço encontrada.
        <div class="text-caption q-mt-xs">
          <template v-if="hasActiveFilters">Tente ajustar os filtros.</template>
          <template v-else>Clique em "Nova Nota de Serviço" para emitir a primeira.</template>
        </div>
      </MgEmptyState>

      <q-infinite-scroll v-else @load="onLoad" :offset="250">
        <q-card flat bordered>
          <q-list separator>
            <q-item
              v-for="nota in notas"
              :key="nota.codnotafiscalservico"
              clickable
              :to="'/nota-servico/' + nota.codnotafiscalservico"
            >
              <q-item-section avatar style="min-width: 32px" class="items-center">
                <q-icon
                  :name="getNfseStatusIcon(nota.status)"
                  :color="getNfseStatusColor(nota.status)"
                />
              </q-item-section>

              <q-item-section>
                <div class="row items-start full-width">
                  <!-- Número (NFS-e, ou a DPS enquanto não autoriza), com a filial embaixo -->
                  <div class="q-px-sm col-6 col-md-3">
                    <div class="text-weight-medium text-caption ellipsis">
                      <template v-if="nota.nfsenumero">NFS-e {{ nota.nfsenumero }}</template>
                      <template v-else-if="nota.numero">
                        DPS {{ formataNumeroNota(nota.numero) }} - S{{ nota.serie }}
                      </template>
                      <template v-else>DPS em digitação</template>
                    </div>
                    <div class="text-caption text-grey-7 ellipsis">
                      {{ nota.filial?.filial }}
                    </div>
                  </div>

                  <!-- Tomador, com o serviço embaixo -->
                  <div class="q-px-sm col-6 col-md-5">
                    <div class="text-weight-bold text-caption text-primary ellipsis">
                      {{ nota.pessoa?.fantasia || 'Sem tomador identificado' }}
                    </div>
                    <div class="text-caption text-grey-7 ellipsis">
                      {{ formataCodigoTributacaoNacional(nota.tributacaonacional?.codigo) }}
                      {{ nota.descricao }}
                    </div>
                  </div>

                  <div class="q-px-sm col-6 col-md-2 text-caption text-grey-7 ellipsis">
                    {{ nota.emissao ? formataTimestamp(nota.emissao, 4, true) : '-' }}
                  </div>

                  <div
                    class="q-px-sm col-6 col-md-2 text-weight-bold text-primary text-caption text-right ellipsis"
                  >
                    R$ {{ formataNumero(nota.valorservico) }}
                  </div>

                  <!-- Ações no tablet (sm): embaixo, à direita. No celular ficam no detalhe -->
                  <div class="q-px-sm col-12 row justify-end sm" @click.stop>
                    <MgNotaFiscalServicoAcoes
                      compact
                      :nota="nota"
                      :api="api"
                      @action-completed="onAcaoConcluida"
                    />
                  </div>
                </div>
              </q-item-section>

              <q-item-section side style="width: 140px" class="gt-sm">
                <MgNotaFiscalServicoAcoes
                  compact
                  :nota="nota"
                  :api="api"
                  @action-completed="onAcaoConcluida"
                />
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
      <q-btn fab icon="add" color="primary" :to="{ name: 'nota-servico-create' }">
        <q-tooltip anchor="top middle" self="bottom middle">Nova Nota de Serviço</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
