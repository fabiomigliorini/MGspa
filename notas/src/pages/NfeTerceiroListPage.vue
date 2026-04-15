<script setup>
import { computed, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { useNfeTerceiroStore } from '../stores/nfeTerceiroStore'
import nfeTerceiroService from '../services/nfeTerceiroService'
import { formatChave, formatCnpjCpf, formatCurrency, formatDateTime } from 'src/utils/formatters'

const $q = useQuasar()
const router = useRouter()
const nfeTerceiroStore = useNfeTerceiroStore()
const uploadingXml = ref(false)
const xmlInput = ref(null)

const loading = computed(() => nfeTerceiroStore.pagination.loading)
const items = computed(() => nfeTerceiroStore.items)
const hasActiveFilters = computed(() => nfeTerceiroStore.hasActiveFilters)

const manifestacaoLabel = (indmanifestacao) => {
  switch (indmanifestacao) {
    case 210200:
      return { label: 'Realizada', color: 'green', icon: 'check_circle' }
    case 210210:
      return { label: 'Ciencia', color: 'orange', icon: 'info' }
    case 210220:
      return { label: 'Desconhecida', color: 'red', icon: 'help' }
    case 210240:
      return { label: 'Nao Realizada', color: 'red', icon: 'cancel' }
    default:
      return { label: 'Sem Manifestacao', color: 'grey', icon: 'remove_circle_outline' }
  }
}

const situacaoLabel = (indsituacao) => {
  switch (indsituacao) {
    case 1:
      return { label: 'Autorizada', color: 'green' }
    case 2:
      return { label: 'Denegada', color: 'red' }
    case 3:
      return { label: 'Cancelada', color: 'red' }
    default:
      return { label: '-', color: 'grey' }
  }
}

const onLoad = async (index, done) => {
  try {
    await nfeTerceiroStore.fetchItems()
    done(!nfeTerceiroStore.pagination.hasMore)
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar NFe de terceiros',
      caption: error.message,
    })
    done(true)
  }
}

const handleClick = (item) => ({
  name: 'nfe-terceiro-view',
  params: { codnfeterceiro: item.codnfeterceiro },
})

const handleUploadXml = (files) => {
  if (!files || files.length === 0) return
  const file = files[0]
  uploadingXml.value = true
  nfeTerceiroService
    .uploadXml(file)
    .then((response) => {
      $q.notify({ type: 'positive', message: 'XML importado com sucesso' })
      if (response.data?.codnfeterceiro) {
        router.push({
          name: 'nfe-terceiro-view',
          params: { codnfeterceiro: response.data.codnfeterceiro },
        })
      } else {
        nfeTerceiroStore.fetchItems(true)
      }
    })
    .catch((error) => {
      $q.notify({
        type: 'negative',
        message: 'Erro ao importar XML',
        caption: error.response?.data?.message || error.message,
      })
    })
    .finally(() => {
      uploadingXml.value = false
    })
}

onMounted(async () => {
  if (!nfeTerceiroStore.initialLoadDone) {
    try {
      await nfeTerceiroStore.fetchItems(true)
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao carregar NFe de terceiros',
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
      <div class="text-h6 text-grey-7 q-mt-md">Nenhuma NFe de terceiro encontrada</div>
      <div class="text-caption text-grey-7 q-mt-sm">
        <template v-if="hasActiveFilters">Tente ajustar os filtros no menu lateral</template>
        <template v-else>Nenhuma nota fiscal de terceiro disponivel</template>
      </div>
    </q-card>

    <!-- Lista com Scroll Infinito -->
    <q-infinite-scroll v-else @load="onLoad" :offset="250">
      <q-list separator>
        <q-item v-for="item in items" :key="item.codnfeterceiro" clickable :to="handleClick(item)">
          <!-- Icone manifestacao -->
          <q-item-section avatar>
            <q-avatar
              :color="manifestacaoLabel(item.indmanifestacao).color"
              text-color="white"
              :icon="manifestacaoLabel(item.indmanifestacao).icon"
            />
          </q-item-section>

          <!-- Filial e Emitente -->
          <q-item-section top>
            <q-item-label>
              <span class="text-weight-medium text-caption text-primary">
                {{ item.pessoa?.fantasia || item.emitente }}
              </span>
              <q-badge
                v-if="item.indsituacao !== 1"
                :color="situacaoLabel(item.indsituacao).color"
                :label="situacaoLabel(item.indsituacao).label"
                class="q-ml-sm"
              />
              <q-badge v-if="item.ignorada" color="grey" label="Ignorada" class="q-ml-sm" />
            </q-item-label>
            <q-item-label class="text-caption text-grey-7">
              {{ item.cnpj ? formatCnpjCpf(item.cnpj, false) : '' }}-{{ item.natureza }}
            </q-item-label>
            <q-item-label caption class="text-caption text-grey-7">
              {{ formatChave(item.nfechave) }}
            </q-item-label>
          </q-item-section>

          <!-- Filial -->
          <q-item-section top side class="text-caption text-grey-7">
            <q-item-label class="text-caption text-black text-weight-medium">
              {{ item.filial?.filial }}
            </q-item-label>
            <q-item-label class="text-caption text-grey-7">
              {{ formatDateTime(item.emissao) }}
            </q-item-label>
            <q-item-label v-if="!item.codnotafiscal" class="text-orange text-caption">
              Pendente Importação
            </q-item-label>
          </q-item-section>

          <!-- Valor -->
          <q-item-section top side class="gt-xs">
            <q-item-label class="text-weight-bold text-primary">
              R$ {{ formatCurrency(item.valortotal) }}
            </q-item-label>
            <q-item-label caption :class="`text-${manifestacaoLabel(item.indmanifestacao).color}`">
              {{ manifestacaoLabel(item.indmanifestacao).label }}
            </q-item-label>
          </q-item-section>
        </q-item>
      </q-list>

      <template v-slot:loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="40px" />
        </div>
      </template>
    </q-infinite-scroll>

    <!-- FAB Upload XML -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn
        fab
        icon="upload_file"
        color="primary"
        :loading="uploadingXml"
        @click="xmlInput?.click()"
      >
        <q-tooltip>Importar XML</q-tooltip>
      </q-btn>
      <input
        ref="xmlInput"
        type="file"
        accept=".xml"
        style="display: none"
        @change="(e) => handleUploadXml(e.target.files)"
      />
    </q-page-sticky>
  </q-page>
</template>
