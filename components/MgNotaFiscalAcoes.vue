<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { abrirPdf } from '@components/abrirPdf'
import { abrirXml } from '@components/abrirXml'
import { useNotaFiscalEnvio } from '@components/useNotaFiscalEnvio'

const props = defineProps({
  nota: { type: Object, required: true },
  api: { type: [Function, Object], required: true },
  compact: { type: Boolean, default: false },
  showExtras: { type: Boolean, default: false },
  // Impressora termica: quando informada, habilita impressao do cupom (modelo 65)
  impressora: { type: String, default: null },
  // Exibe o botao Excluir para notas em digitacao (status DIG)
  mostrarExcluir: { type: Boolean, default: false },
  // Abrir a DANFE apos enviar. Quando null, segue o padrao !compact
  abrirDanfeAposEnviar: { type: Boolean, default: null },
  // Libera os botoes de forcar contingencia/online (NFC-e). O host pluga o auth:
  // o componente e compartilhado por 3 apps e continua burro quanto a permissao.
  podeForcarContingencia: { type: Boolean, default: false },
})

const emit = defineEmits(['action-completed'])

const $q = useQuasar()

const loadingConsultar = ref(false)
const loadingCancelar = ref(false)
const loadingInutilizar = ref(false)
const loadingEmail = ref(false)
const loadingExcluir = ref(false)

const codnotafiscal = computed(() => props.nota?.codnotafiscal)

// Envio assincrono: o POST so enfileira e o progresso vem por polling.
// `enviando` e exposto como `loadingEnviar` para nao quebrar o F9 da NotaFiscalViewPage.
const {
  enviando: loadingEnviar,
  iniciarEnvio,
  checarEmAndamento,
} = useNotaFiscalEnvio({
  api: props.api,
  codnotafiscal,
})
const podeEnviar = computed(
  () => props.nota?.emitida && ['DIG', 'ERR'].includes(props.nota?.status),
)
const podeConsultar = computed(
  () => props.nota?.emitida && ['AUT', 'CAN', 'ERR'].includes(props.nota?.status),
)
const podeCancelar = computed(() => props.nota?.emitida && props.nota?.status === 'AUT')
const podeInutilizar = computed(() => props.nota?.emitida && props.nota?.status === 'ERR')
const podeEnviarEmail = computed(() => props.nota?.emitida && props.nota?.status === 'AUT')
const podeAbrirDanfe = computed(
  () => props.nota?.emitida && ['AUT', 'CAN'].includes(props.nota?.status),
)
const podeAbrirXml = computed(() => props.nota?.emitida && props.nota?.nfechave)
const podeExcluir = computed(() => props.nota?.emitida && props.nota?.status === 'DIG')
const temAcoes = computed(
  () =>
    podeEnviar.value ||
    podeConsultar.value ||
    podeCancelar.value ||
    podeInutilizar.value ||
    (props.mostrarExcluir && podeExcluir.value),
)

const deveAbrirDanfeAposEnviar = computed(() => props.abrirDanfeAposEnviar ?? !props.compact)
const cupom = computed(() => props.nota?.modelo == 65)

// tpemis 9 = emitida em contingencia off-line (faz parte da chave de acesso)
const emContingencia = computed(() => props.nota?.tpemis == 9)
// Forcar o modo so faz sentido na NFC-e, que e a unica com contingencia off-line
const podeForcarModo = computed(
  () => props.podeForcarContingencia && cupom.value && podeEnviar.value,
)

const btnSize = computed(() => (props.compact ? 'sm' : undefined))

// Consultar/cancelar/inutilizar continuam sincronos e uma chamada a SEFAZ com retry leva
// ate ~122s. O timeout global do axios e 15s (existe por causa de socket HTTP/2 morto),
// entao a sobrescrita e por request.
const TIMEOUT_SEFAZ = 150000

function stop(event) {
  if (event) {
    event.preventDefault?.()
    event.stopPropagation?.()
  }
}

function mensagemErro(error, fallback) {
  return error?.response?.data?.message || error?.message || fallback
}

async function imprimir() {
  if (!props.impressora) {
    $q.notify({ type: 'negative', message: 'Nenhuma impressora térmica selecionada!' })
    return
  }
  try {
    await props.api.post(`/v1/nota-fiscal/${codnotafiscal.value}/imprimir`, {
      impressora: props.impressora,
    })
    $q.notify({ type: 'positive', message: `Enviado para impressora ${props.impressora}!` })
  } catch (error) {
    $q.notify({ type: 'negative', message: mensagemErro(error) })
  }
}

/**
 * Envia a NFe.
 *
 * O backend virou um supermetodo (criar + enviar + mail). Antes disso este componente
 * orquestrava 3 POSTs e ainda parseava o XML com DOMParser para achar <tpEmis> e decidir
 * se era contingencia — decisao fiscal tomada no navegador. Agora o proprio backend
 * informa `contingencia` no progresso.
 *
 * Continua devolvendo Promise que so resolve no fim: o PDV faz `await enviarNfe()`.
 *
 * `offline` e tri-state: null = automatico (segue a conf da empresa),
 * true = forca contingencia, false = forca online.
 */
async function enviarNfe(event, offline = null) {
  stop(event)
  try {
    const r = await iniciarEnvio(offline)

    if (!r.contingencia && !r.sucesso) {
      throw new Error(`${r.cStat ?? ''} - ${r.xMotivo ?? 'Erro desconhecido'}`)
    }

    // Emite ANTES de imprimir e abrir o DANFE: sincronizar o estado da nota nao pode ficar
    // atras da impressora termica nem da geracao do PDF, senao a linha da listagem so fica
    // verde segundos depois de a nota ja estar autorizada.
    emit('action-completed', 'enviar', r.nota)

    // Contingencia nao foi a SEFAZ (o robo transmite depois, dentro do prazo de 24h), mas o
    // cupom precisa sair do mesmo jeito — por isso ela abre o DANFE mesmo em modo compact.
    if (cupom.value && props.impressora) await imprimir()
    if (r.contingencia || deveAbrirDanfeAposEnviar.value) await abrirDanfe()
  } catch (error) {
    // O Notify de erro ja foi emitido pelo composable; aqui so o caso do cStat negado
    if (error?.message && !error.message.startsWith('Sem conex')) {
      $q.notify({ type: 'negative', message: 'Erro ao enviar NFe', caption: mensagemErro(error) })
    }
  }
}

async function consultarNfe(event) {
  stop(event)
  loadingConsultar.value = true
  try {
    const resp = await props.api.post(
      `/v1/nota-fiscal/${codnotafiscal.value}/consultar`,
      {},
      { timeout: TIMEOUT_SEFAZ },
    )
    const r = resp.data?.resultado ?? resp.data
    const tipo = r?.sucesso ? 'positive' : 'negative'
    $q.notify({ type: tipo, message: `${r?.cStat ?? ''} - ${r?.xMotivo ?? ''}` })
    emit('action-completed', 'consultar', resp.data?.nota)
  } catch (error) {
    $q.notify({ type: 'negative', message: mensagemErro(error) })
  } finally {
    loadingConsultar.value = false
  }
}

function cancelarNfe(event) {
  stop(event)
  $q.dialog({
    title: 'Cancelar NFe',
    message: 'Digite a justificativa para cancelar a NFe',
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (val) => val && val.length >= 15,
    },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar Cancelamento', flat: true, color: 'negative' },
  }).onOk(async (justificativa) => {
    loadingCancelar.value = true
    try {
      const resp = await props.api.post(
        `/v1/nota-fiscal/${codnotafiscal.value}/cancelar`,
        { justificativa },
        { timeout: TIMEOUT_SEFAZ },
      )
      const r = resp.data?.resultado ?? resp.data
      const tipo = r?.sucesso ? 'positive' : 'negative'
      $q.notify({ type: tipo, message: `${r?.cStat ?? ''} - ${r?.xMotivo ?? ''}` })
      emit('action-completed', 'cancelar', resp.data?.nota)
    } catch (error) {
      $q.notify({ type: 'negative', message: mensagemErro(error) })
    } finally {
      loadingCancelar.value = false
    }
  })
}

function inutilizarNfe(event) {
  stop(event)
  $q.dialog({
    title: 'Inutilizar NFe',
    message: 'Digite a justificativa para inutilizar a NFe',
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (val) => val && val.length >= 15,
    },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar Inutilização', flat: true, color: 'negative' },
  }).onOk(async (justificativa) => {
    loadingInutilizar.value = true
    try {
      const resp = await props.api.post(
        `/v1/nota-fiscal/${codnotafiscal.value}/inutilizar`,
        { justificativa },
        { timeout: TIMEOUT_SEFAZ },
      )
      const r = resp.data?.resultado ?? resp.data
      const tipo = r?.sucesso ? 'positive' : 'negative'
      $q.notify({ type: tipo, message: `${r?.cStat ?? ''} - ${r?.xMotivo ?? ''}` })
      emit('action-completed', 'inutilizar', resp.data?.nota)
    } catch (error) {
      $q.notify({ type: 'negative', message: mensagemErro(error) })
    } finally {
      loadingInutilizar.value = false
    }
  })
}

function enviarEmailNfe(event) {
  stop(event)
  $q.dialog({
    title: 'Enviar Email',
    message: 'Digite o endereço de e-mail',
    prompt: {
      // Sugere os e-mails marcados "Envio de NFe" — os MESMOS que recebem o envio
      // automatico. Antes sugeria pessoa.email (coluna de tblpessoa, normalmente o de
      // cobranca), entao o reenvio manual ia para quem nunca recebeu o automatico.
      // `type` e text, e nao email, porque podem ser varios separados por virgula — o
      // NFePHPMailService ja aceita a lista assim.
      model: props.nota?.pessoa?.emailnfe || props.nota?.pessoa?.email || '',
      type: 'text',
      outlined: true,
    },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Enviar', flat: true, color: 'primary' },
  }).onOk(async (email) => {
    loadingEmail.value = true
    try {
      await props.api.post(`/v1/nota-fiscal/${codnotafiscal.value}/mail`, {
        destinatario: email,
      })
      $q.notify({ type: 'positive', message: 'Email enviado com sucesso' })
    } catch (error) {
      $q.notify({ type: 'negative', message: mensagemErro(error) })
    } finally {
      loadingEmail.value = false
    }
  })
}

function excluirNfe(event) {
  stop(event)
  $q.dialog({
    title: 'Excluir Nota Fiscal',
    message: 'Confirma a exclusão desta nota fiscal?',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Excluir', flat: true, color: 'negative' },
  }).onOk(async () => {
    loadingExcluir.value = true
    try {
      await props.api.delete(`/v1/nota-fiscal/${codnotafiscal.value}`)
      $q.notify({ type: 'positive', message: 'Nota fiscal excluída!' })
      emit('action-completed', 'excluir', props.nota)
    } catch (error) {
      $q.notify({ type: 'negative', message: mensagemErro(error) })
    } finally {
      loadingExcluir.value = false
    }
  })
}

async function abrirDanfe(event) {
  stop(event)
  await abrirPdf(
    props.api,
    `/v1/nota-fiscal/${codnotafiscal.value}/danfe`,
    {},
    {
      title: cupom.value ? 'DANFE NFC-e' : 'DANFE NFe',
      size: cupom.value ? 'cupom' : 'a4',
      onImprimir: cupom.value && props.impressora ? () => imprimir() : null,
    },
  )
}

function verXml(event) {
  stop(event)
  return abrirXml(
    props.api,
    `/v1/nota-fiscal/${codnotafiscal.value}/xml`,
    {},
    {
      titulo: `XML da ${cupom.value ? 'NFC-e' : 'NFe'}`,
      nomeArquivo: `nfe-${codnotafiscal.value}.xml`,
      erroFallback: 'Erro ao abrir XML',
    },
  )
}

// Retoma o acompanhamento se o envio ja estava correndo (F5 na tela de detalhe).
// So no detalhe: numa listagem com 20 linhas DIG/ERR isso viraria 20 GETs.
onMounted(() => {
  if (!props.compact && podeEnviar.value) checarEmAndamento()
})

defineExpose({ enviarNfe, podeEnviar, loadingEnviar })
</script>

<template>
  <div
    class="row no-wrap q-gutter-xs"
    v-if="temAcoes || (showExtras && (podeAbrirDanfe || podeAbrirXml || podeEnviarEmail))"
  >
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

    <!--
      Forcar o modo de emissao da NFC-e. So aparece com permissao (showExtras + prop do
      host), entao nao polui o balcao. O botao Enviar principal continua no automatico,
      seguindo a conf da empresa.
    -->
    <q-btn
      v-if="showExtras && podeForcarModo"
      flat
      dense
      round
      :size="btnSize"
      color="orange"
      icon="cloud_off"
      :loading="loadingEnviar"
      @click="(e) => enviarNfe(e, true)"
    >
      <q-tooltip>Forçar emissão em contingência off-line</q-tooltip>
    </q-btn>

    <q-btn
      v-if="showExtras && podeForcarModo"
      flat
      dense
      round
      :size="btnSize"
      color="teal"
      icon="cloud_done"
      :loading="loadingEnviar"
      @click="(e) => enviarNfe(e, false)"
    >
      <q-tooltip>Forçar envio on-line (ignora a contingência da empresa)</q-tooltip>
    </q-btn>

    <q-btn
      v-if="showExtras && podeAbrirXml"
      flat
      dense
      round
      :size="btnSize"
      color="orange"
      icon="code"
      @click="verXml"
    >
      <q-tooltip>Abrir XML</q-tooltip>
    </q-btn>

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

    <q-btn
      v-if="mostrarExcluir && podeExcluir"
      flat
      dense
      round
      :size="btnSize"
      color="negative"
      icon="delete"
      :loading="loadingExcluir"
      @click="excluirNfe"
    >
      <q-tooltip>Excluir</q-tooltip>
    </q-btn>
  </div>
</template>
