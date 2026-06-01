<script setup>
import { ref, computed, onMounted } from 'vue'
import { useMdfeStore, statusColor } from '../stores/mdfeStore'
import { formataCodigo, formataChave, tempoRelativo } from '@components/formatters'
import { notificarErro } from '../utils/notify'
import MdfeCriarChaveDialog from '../components/dialogs/MdfeCriarChaveDialog.vue'

const store = useMdfeStore()
const criarDialog = ref(false)

const mdfes = computed(() => store.mdfes)
const loading = computed(() => store.pagination.loading)

const onLoad = async (index, done) => {
  try {
    await store.fetchMdfes()
    done(!store.pagination.hasMore)
  } catch (error) {
    notificarErro(error, 'Falha ao carregar MDFes')
    done(true)
  }
}

onMounted(() => {
  if (!store.initialLoadDone) {
    store.fetchMdfes(true).catch((e) => notificarErro(e, 'Falha ao carregar MDFes'))
  }
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 900px; margin: auto">
      <div v-if="loading && !mdfes.length" class="row justify-center q-mt-xl">
        <q-spinner color="primary" size="3em" />
      </div>

      <q-card v-else-if="!mdfes.length" flat bordered class="text-center q-pa-lg">
        <q-icon name="local_shipping" size="4em" color="grey-5" />
        <div class="text-h6 q-mt-md text-grey-7">Nenhum MDFe encontrado</div>
      </q-card>

      <q-infinite-scroll v-else @load="onLoad" :offset="250">
        <q-card flat bordered>
          <q-list separator>
            <q-item
              v-for="mdfe in mdfes"
              :key="mdfe.codmdfe"
              clickable
              :to="{ name: 'mdfe-view', params: { codmdfe: mdfe.codmdfe } }"
            >
              <q-item-section avatar>
                <q-avatar :color="statusColor(mdfe.codmdfestatus)" text-color="white">
                  {{ mdfe.mdfestatussigla }}
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label>
                  {{ formataCodigo(mdfe.codmdfe) }} · {{ mdfe.filial }}
                  <span v-if="mdfe.numero"> · Nº {{ mdfe.numero }}</span>
                  <span v-if="mdfe.inicioviagem" class="text-grey-7">
                    · {{ tempoRelativo(mdfe.inicioviagem) }}
                  </span>
                </q-item-label>
                <q-item-label caption>
                  <q-chip
                    v-for="v in mdfe.MdfeVeiculoS"
                    :key="v.codveiculo"
                    size="sm"
                    dense
                    icon="local_shipping"
                  >
                    {{ v.placa }}
                  </q-chip>
                </q-item-label>
                <q-item-label caption class="text-grey-6">
                  <div v-for="nfe in mdfe.MdfeNfeS" :key="nfe.nfechave">
                    NFe {{ formataChave(nfe.nfechave) }}
                  </div>
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>

        <template v-slot:loading>
          <div class="row justify-center q-my-md">
            <q-spinner color="primary" size="2em" />
          </div>
        </template>
      </q-infinite-scroll>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="criarDialog = true">
        <q-tooltip>Novo MDFe pela Chave da NFe</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <MdfeCriarChaveDialog v-model="criarDialog" />
  </q-page>
</template>
