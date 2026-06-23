<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { api } from 'src/services/api'
import { abrirPdf } from '@components/abrirPdf'
import { useContratoDetalheStore } from 'src/stores/contratoDetalhe'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgEmptyState from '@components/MgEmptyState.vue'

// Card "Anexos". Especialista nos documentos do contrato (PDF/imagem): a lista,
// o upload, o download e a exclusão são GERIDOS pelo store da tela. O card só
// orquestra a UI (escolher arquivo, abrir o visualizador de PDF, montar o link
// de download). `abrirPdf` é helper de apresentação e recebe a instância axios.
const $q = useQuasar()
const store = useContratoDetalheStore()
const { anexos } = storeToRefs(store)

const novoAnexo = ref(null)
const enviando = ref(false)

async function enviar() {
  if (!novoAnexo.value || enviando.value) return
  enviando.value = true
  try {
    await store.enviarAnexo(novoAnexo.value)
    notifySuccess('Anexo enviado!')
    novoAnexo.value = null
  } catch (e) {
    notifyError(e)
  } finally {
    enviando.value = false
  }
}
// PDF -> visualizador; imagem -> nova aba (busca o blob pelo store).
async function visualizar(a) {
  if (a.tipo === 'pdf' || /\.pdf$/i.test(a.nome)) {
    await abrirPdf(
      api,
      store.urlAnexoDownload(a.nome),
      {},
      { title: a.label || 'Anexo', size: 'a4' },
    )
    return
  }
  try {
    const blob = await store.baixarAnexo(a.nome, { skipLoading: true })
    const blobUrl = URL.createObjectURL(blob)
    window.open(blobUrl, '_blank')
    setTimeout(() => URL.revokeObjectURL(blobUrl), 30000)
  } catch (e) {
    notifyError(e)
  }
}
async function baixar(a) {
  try {
    const blob = await store.baixarAnexo(a.nome)
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = a.label || a.nome
    link.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    notifyError(e)
  }
}
function excluir(a) {
  $q.dialog({
    title: 'Excluir anexo',
    message: `Excluir "${a.label}"?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await store.excluirAnexo(a)
      notifySuccess('Excluído!')
    } catch (e) {
      notifyError(e)
    }
  })
}

onMounted(store.carregarAnexos)
</script>

<template>
  <q-card flat bordered>
    <q-item class="bg-primary text-white">
      <q-item-section avatar>
        <q-avatar color="white" text-color="primary" icon="attach_file" />
      </q-item-section>
      <q-item-section>
        <q-item-label class="text-subtitle1">Anexos</q-item-label>
        <q-item-label class="text-caption">Contratos, aditivos e documentos (PDF/imagem)</q-item-label>
      </q-item-section>
    </q-item>
    <q-separator />
    <q-card-section class="row q-col-gutter-sm items-center">
      <div class="col">
        <q-file
          v-model="novoAnexo"
          label="Selecionar arquivo"
          outlined
          accept=".pdf,image/*"
          clearable
        >
          <template #prepend><q-icon name="upload_file" /></template>
        </q-file>
      </div>
      <div class="col-auto">
        <q-btn
          flat
          color="primary"
          icon="cloud_upload"
          label="Enviar"
          :disable="!novoAnexo"
          :loading="enviando"
          @click="enviar"
        />
      </div>
    </q-card-section>
    <q-separator />
    <q-list separator>
      <q-item v-for="a in anexos" :key="a.nome">
        <q-item-section avatar>
          <q-avatar
            :color="a.tipo === 'pdf' ? 'red-1' : 'blue-1'"
            :text-color="a.tipo === 'pdf' ? 'red-8' : 'blue-8'"
            :icon="a.tipo === 'pdf' ? 'picture_as_pdf' : 'image'"
          />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ a.label }}</q-item-label>
          <q-item-label caption>{{ (a.size / 1024).toFixed(0) }} KB</q-item-label>
        </q-item-section>
        <q-item-section side>
          <div class="row items-center no-wrap">
            <q-btn
              flat
              dense
              round
              size="sm"
              color="primary"
              icon="visibility"
              @click="visualizar(a)"
            >
              <q-tooltip>Visualizar</q-tooltip>
            </q-btn>
            <q-btn flat dense round size="sm" color="grey-7" icon="download" @click="baixar(a)">
              <q-tooltip>Baixar</q-tooltip>
            </q-btn>
            <q-btn flat dense round size="sm" color="grey-7" icon="delete" @click="excluir(a)">
              <q-tooltip>Excluir</q-tooltip>
            </q-btn>
          </div>
        </q-item-section>
      </q-item>
      <MgEmptyState v-if="!anexos.length" plain icon="attach_file"> Nenhum anexo. </MgEmptyState>
    </q-list>
  </q-card>
</template>
