<script setup>
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import { useNotaFiscalStore } from '../stores/notaFiscalStore'

const props = defineProps({
  nota: { type: Object, required: true },
  compact: { type: Boolean, default: false },
  showExtras: { type: Boolean, default: false },
})

const emit = defineEmits(['action-completed'])

const $q = useQuasar()
const notaFiscalStore = useNotaFiscalStore()

// Loading states
const loadingEnviar = ref(false)
const loadingConsultar = ref(false)
const loadingCancelar = ref(false)
const loadingInutilizar = ref(false)
const loadingEmail = ref(false)
const progressoNfe = ref({ status: '', percent: 0 })

// DANFE dialog
const danfeDialog = ref(false)
const danfeUrl = ref('')

// Computed — condições de visibilidade
const codnotafiscal = computed(() => props.nota?.codnotafiscal)
const podeEnviar = computed(
  () => props.nota?.emitida && ['DIG', 'ERR'].includes(props.nota?.status)
)
const podeConsultar = computed(
  () => props.nota?.emitida && ['AUT', 'CAN', 'ERR'].includes(props.nota?.status)
)
const podeCancelar = computed(() => props.nota?.emitida && props.nota?.status === 'AUT')
const podeInutilizar = computed(() => props.nota?.emitida && props.nota?.status === 'ERR')
const podeEnviarEmail = computed(() => props.nota?.emitida && props.nota?.status === 'AUT')
const podeAbrirDanfe = computed(
  () => props.nota?.emitida && ['AUT', 'CAN'].includes(props.nota?.status)
)
const podeAbrirXml = computed(() => props.nota?.emitida && props.nota?.nfechave)
const temAcoes = computed(
  () => podeEnviar.value || podeConsultar.value || podeCancelar.value || podeInutilizar.value
)

const btnSize = computed(() => (props.compact ? 'sm' : undefined))

// ==================== AÇÕES ====================

const enviarNfe = async (event) => {
  if (event) {
    event.preventDefault()
    event.stopPropagation()
  }
  loadingEnviar.value = true
  progressoNfe.value = { status: 'Criando Arquivo XML...', percent: 0 }

  try {
    const xmlResponse = await notaFiscalStore.criarNfe(codnotafiscal.value)
    progressoNfe.value = { status: 'Arquivo XML Criado...', percent: 25 }

    // Verifica contingência offline (tpEmis = 9)
    const parser = new DOMParser()
    const xmlDoc = parser.parseFromString(xmlResponse, 'text/xml')
    const tpEmis = xmlDoc.querySelector('tpEmis')?.textContent

    if (tpEmis === '9') {
      abrirDanfe()
    } else {
      progressoNfe.value = { status: 'Enviando NFe para Sefaz...', percent: 50 }
      const envioResponse = await notaFiscalStore.enviarNfeSincrono(codnotafiscal.value)

      if (envioResponse.sucesso) {
        progressoNfe.value = { status: 'Enviando Email...', percent: 75 }
        try {
          await notaFiscalStore.enviarEmailNfe(codnotafiscal.value)
        } catch {
          /* ignora erro de email */
        }
        progressoNfe.value = { status: 'Finalizado...', percent: 100 }
        abrirDanfe()
        $q.notify({ type: 'positive', message: 'NFe enviada com sucesso!' })
      } else {
        throw new Error(`${envioResponse.cStat} - ${envioResponse.xMotivo}`)
      }
    }
    emit('action-completed', 'enviar')
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao enviar NFe',
      caption: error.response?.data?.message || error.message,
    })
  } finally {
    loadingEnviar.value = false
    progressoNfe.value = { status: '', percent: 0 }
  }
}

const consultarNfe = async (event) => {
  if (event) {
    event.preventDefault()
    event.stopPropagation()
  }
  loadingConsultar.value = true
  try {
    const response = await notaFiscalStore.consultarNfe(codnotafiscal.value)
    const tipo = response.sucesso ? 'positive' : 'negative'
    $q.dialog({
      title: 'Consulta NFe',
      message: `${response.cStat} - ${response.xMotivo}`,
      ok: { label: 'OK', color: tipo },
    })
    emit('action-completed', 'consultar')
  } catch (error) {
    $q.notify({ type: 'negative', message: error.response?.data?.message || error.message })
  } finally {
    loadingConsultar.value = false
  }
}

const cancelarNfe = (event) => {
  if (event) {
    event.preventDefault()
    event.stopPropagation()
  }
  $q.dialog({
    title: 'Cancelar NFe',
    message: 'Digite a justificativa para cancelar a NFe',
    prompt: { model: '', type: 'text', outlined: true, isValid: (val) => val && val.length >= 15 },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar Cancelamento', color: 'negative' },
  }).onOk(async (justificativa) => {
    loadingCancelar.value = true
    try {
      const response = await notaFiscalStore.cancelarNfe(codnotafiscal.value, justificativa)
      const tipo = response.sucesso ? 'positive' : 'negative'
      $q.dialog({
        title: 'Cancelamento NFe',
        message: `${response.cStat} - ${response.xMotivo}`,
        ok: { label: 'OK', color: tipo },
      })
      emit('action-completed', 'cancelar')
    } catch (error) {
      $q.notify({ type: 'negative', message: error.response?.data?.message || error.message })
    } finally {
      loadingCancelar.value = false
    }
  })
}

const inutilizarNfe = (event) => {
  if (event) {
    event.preventDefault()
    event.stopPropagation()
  }
  $q.dialog({
    title: 'Inutilizar NFe',
    message: 'Digite a justificativa para inutilizar a NFe',
    prompt: { model: '', type: 'text', outlined: true, isValid: (val) => val && val.length >= 15 },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar Inutilização', color: 'negative' },
  }).onOk(async (justificativa) => {
    loadingInutilizar.value = true
    try {
      const response = await notaFiscalStore.inutilizarNfe(codnotafiscal.value, justificativa)
      const tipo = response.sucesso ? 'positive' : 'negative'
      $q.dialog({
        title: 'Inutilização NFe',
        message: `${response.cStat} - ${response.xMotivo}`,
        ok: { label: 'OK', color: tipo },
      })
      emit('action-completed', 'inutilizar')
    } catch (error) {
      $q.notify({ type: 'negative', message: error.response?.data?.message || error.message })
    } finally {
      loadingInutilizar.value = false
    }
  })
}

const enviarEmailNfe = (event) => {
  if (event) {
    event.preventDefault()
    event.stopPropagation()
  }
  $q.dialog({
    title: 'Enviar Email',
    message: 'Digite o endereço de e-mail',
    prompt: { model: props.nota.pessoa?.email || '', type: 'email', outlined: true },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Enviar', color: 'primary' },
  }).onOk(async (email) => {
    loadingEmail.value = true
    try {
      await notaFiscalStore.enviarEmailNfe(codnotafiscal.value, email)
      $q.notify({ type: 'positive', message: 'Email enviado com sucesso' })
    } catch (error) {
      $q.notify({ type: 'negative', message: error.response?.data?.message || error.message })
    } finally {
      loadingEmail.value = false
    }
  })
}

const abrirDanfe = async (event) => {
  if (event) {
    event.preventDefault()
    event.stopPropagation()
  }
  try {
    danfeUrl.value = await notaFiscalStore.getDanfeUrl(codnotafiscal.value)

    // Se for celular android, abre direto em nova aba
    const ua = navigator.userAgent
    const isAndroidPhone = /Android/i.test(ua) && /Mobile/i.test(ua) && !/CrOS/i.test(ua)
    if (isAndroidPhone) {
      window.open(danfeUrl.value, '_blank')
    } else {
      danfeDialog.value = true
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao abrir DANFE',
      caption: error?.response?.data?.message || error?.message,
    })
  }
}

const abrirXml = async (event) => {
  if (event) {
    event.preventDefault()
    event.stopPropagation()
  }
  try {
    const xmlUrl = await notaFiscalStore.getXmlUrl(codnotafiscal.value)
    window.open(xmlUrl, '_blank')
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao abrir XML',
      caption: error?.response?.data?.message || error?.message,
    })
  }
}

// Expor para uso externo (ex: F9 na ViewPage)
defineExpose({ enviarNfe, podeEnviar, loadingEnviar, progressoNfe })
</script>

<template>
  <div
    class="row no-wrap q-gutter-xs"
    v-if="temAcoes || (showExtras && (podeAbrirDanfe || podeAbrirXml || podeEnviarEmail))"
  >
    <!-- Enviar -->
    <q-btn
      v-if="podeEnviar"
      flat
      dense
      round
      :size="btnSize"
      color="secondary"
      icon="send"
      :loading="loadingEnviar"
      @click="enviarNfe"
    >
      <q-tooltip>Criar XML e enviar para SEFAZ</q-tooltip>
    </q-btn>

    <!-- Consultar -->
    <q-btn
      v-if="podeConsultar"
      flat
      dense
      round
      :size="btnSize"
      color="primary"
      icon="refresh"
      :loading="loadingConsultar"
      @click="consultarNfe"
    >
      <q-tooltip>Consultar situação na SEFAZ</q-tooltip>
    </q-btn>

    <!-- DANFE -->
    <q-btn
      v-if="podeAbrirDanfe"
      flat
      dense
      round
      :size="btnSize"
      color="secondary"
      icon="picture_as_pdf"
      @click="abrirDanfe"
    >
      <q-tooltip>Abrir DANFE</q-tooltip>
    </q-btn>

    <!-- XML -->
    <q-btn
      v-if="showExtras && podeAbrirXml"
      flat
      dense
      round
      :size="btnSize"
      color="orange"
      icon="code"
      @click="abrirXml"
    >
      <q-tooltip>Abrir XML</q-tooltip>
    </q-btn>

    <!-- Email -->
    <q-btn
      v-if="showExtras && podeEnviarEmail"
      flat
      dense
      round
      :size="btnSize"
      color="primary"
      icon="email"
      :loading="loadingEmail"
      @click="enviarEmailNfe"
    >
      <q-tooltip>Enviar por email</q-tooltip>
    </q-btn>

    <!-- Cancelar -->
    <q-btn
      v-if="podeCancelar"
      flat
      dense
      round
      :size="btnSize"
      color="negative"
      icon="cancel"
      :loading="loadingCancelar"
      @click="cancelarNfe"
    >
      <q-tooltip>Cancelar NFe</q-tooltip>
    </q-btn>

    <!-- Inutilizar -->
    <q-btn
      v-if="podeInutilizar"
      flat
      dense
      round
      :size="btnSize"
      color="warning"
      icon="block"
      :loading="loadingInutilizar"
      @click="inutilizarNfe"
    >
      <q-tooltip>Inutilizar NFe</q-tooltip>
    </q-btn>
  </div>

  <!-- Dialog DANFE -->
  <q-dialog v-model="danfeDialog">
    <q-card
      :style="
        nota.modelo === 65
          ? 'width: 400px; max-width: 90vw; height: 90vh'
          : 'width: 800px; max-width: 90vw; height: 90vh'
      "
    >
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">{{ nota.modelo === 65 ? 'DANFE NFCe' : 'DANFE NFe' }}</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section class="q-pa-md" style="height: calc(100% - 56px)">
        <iframe :src="danfeUrl" style="width: 100%; height: 100%; border: none" />
      </q-card-section>
    </q-card>
  </q-dialog>

  <!-- Dialog Progresso NFe -->
  <q-dialog :model-value="loadingEnviar" persistent>
    <q-card style="min-width: 350px">
      <q-card-section>
        <div class="text-h6">Processando NFe</div>
      </q-card-section>
      <q-card-section class="q-pt-none">
        <div class="text-body2 q-mb-md">{{ progressoNfe.status }}</div>
        <q-linear-progress :value="progressoNfe.percent / 100" color="primary" class="q-mt-md" />
      </q-card-section>
    </q-card>
  </q-dialog>
</template>
