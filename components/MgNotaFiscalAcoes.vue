<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { abrirPdf } from '@components/abrirPdf'
import { abrirXml } from '@components/abrirXml'
import { useNotaFiscalTransmissao } from '@components/useNotaFiscalTransmissao'
import { NOTA_FISCAL_STATUS_OPTIONS } from '@components/notaFiscalStatus'

const props = defineProps({
  nota: { type: Object, required: true },
  api: { type: [Function, Object], required: true },
  compact: { type: Boolean, default: false },
  showExtras: { type: Boolean, default: false },
  // Impressora termica: quando informada, habilita impressao do cupom (modelo 65)
  impressora: { type: String, default: null },
  // Exibe o botao Excluir para notas em digitacao (status DIG)
  mostrarExcluir: { type: Boolean, default: false },
  // Abrir a DANFE ao fim do Emitir. Quando null, segue o padrao !compact
  abrirDanfeAposEnviar: { type: Boolean, default: null },
})

const emit = defineEmits(['action-completed'])

const $q = useQuasar()

const loadingCriar = ref(false)
const loadingConsultar = ref(false)
const loadingCancelar = ref(false)
const loadingEmail = ref(false)
const loadingXmls = ref(false)
const loadingDanfe = ref(false)
const loadingStatus = ref(false)
const loadingExcluir = ref(false)
const statusDialog = ref(false)
// O Emitir orquestra criar + transmitir, entao acende junto com os dois
const emitindo = ref(false)

const codnotafiscal = computed(() => props.nota?.codnotafiscal)

// Transmissao assincrona: o POST so enfileira e o progresso vem por polling. E a unica
// acao que precisa disso — as demais sao chamadas diretas, logo abaixo.
const { transmitindo, iniciarTransmissao, checarEmAndamento } = useNotaFiscalTransmissao({
  api: props.api,
  codnotafiscal,
})

/**
 * Uma acao de cada vez: a clicada gira, as outras desabilitam.
 *
 * Nao e so estetica — no backend cada acao pega o lock da nota, entao mandar cancelar no
 * meio de uma transmissao daria "Outra operacao ja esta em andamento".
 *
 * Cobre TODAS as acoes que tocam a nota, inclusive as do popup de status: por isso elas
 * moraram para ca, em vez de a pagina coordenar por fora.
 */
const ocupado = computed(
  () =>
    emitindo.value ||
    loadingCriar.value ||
    transmitindo.value ||
    loadingConsultar.value ||
    loadingCancelar.value ||
    loadingEmail.value ||
    loadingXmls.value ||
    loadingDanfe.value ||
    loadingStatus.value ||
    loadingExcluir.value,
)

// Durante o Emitir os botoes das etapas internas NAO giram: quem o usuario clicou foi o
// Emitir, entao e so ele que gira — os outros apenas desabilitam, como qualquer outro.
const girandoCriar = computed(() => loadingCriar.value && !emitindo.value)
const girandoTransmitir = computed(() => transmitindo.value && !emitindo.value)
const girandoDanfe = computed(() => loadingDanfe.value && !emitindo.value)

// tpemis 9 = emitida em contingencia off-line (faz parte da chave de acesso)
const emContingencia = computed(() => props.nota?.tpemis == 9)
const cupom = computed(() => props.nota?.modelo == 65)
// Nota em fluxo de emissao: ainda pode ganhar (ou refazer) XML e ir para a SEFAZ
const emFluxo = computed(() => props.nota?.emitida && ['DIG', 'ERR'].includes(props.nota?.status))

// Ha o que fazer com esta nota. Exposto para o F9 da tela de detalhe, que chama emitir() —
// o metodo cobre os dois casos, criando o XML so quando ele ainda nao existe.
const podeEmitir = computed(() => emFluxo.value)

// Emitir e Transmitir sao excludentes, e o que decide e a existencia da chave: sem XML o
// caminho e criar+transmitir; com XML pronto so falta entregar a SEFAZ, e oferecer "Emitir"
// ali sugeriria refazer o documento do zero.
const mostrarEmitir = computed(() => emFluxo.value && !props.nota?.nfechave)
const podeTransmitir = computed(() => emFluxo.value && !!props.nota?.nfechave)

const podeCriarXml = computed(() => emFluxo.value)
// Espelha a unica pre-condicao do consultarSemLock: ter chave. Status nao entra — e
// justamente a nota em estado inconsistente que mais precisa reconsultar a SEFAZ.
const podeConsultar = computed(() => props.nota?.emitida && !!props.nota?.nfechave)
// Cancelar exige AUT e inutilizar exige ERR — nunca coexistem, entao sao UM botao que
// sabe qual das duas cabe. Um popup perguntando seria escolha entre uma opcao valida e
// uma impossivel.
const acaoCancelamento = computed(() => {
  if (!props.nota?.emitida) return null
  if (props.nota?.status === 'AUT') return 'cancelar'
  if (props.nota?.status === 'ERR') return 'inutilizar'
  return null
})
const podeEnviarEmail = computed(() => props.nota?.emitida && props.nota?.status === 'AUT')
// Contingencia entra aqui porque o cupom precisa ser reimpresso mesmo antes da autorizacao:
// o backend gera a DANFE a partir do XML assinado quando tpEmis e 9.
const podeAbrirDanfe = computed(
  () =>
    props.nota?.emitida && (['AUT', 'CAN'].includes(props.nota?.status) || emContingencia.value),
)
const podeAbrirXml = computed(() => props.nota?.emitida && props.nota?.nfechave)
const podeExcluir = computed(() => props.nota?.emitida && props.nota?.status === 'DIG')
// Nao faz sentido oferecer o status que a nota ja tem
const statusDisponiveis = computed(() =>
  NOTA_FISCAL_STATUS_OPTIONS.filter((s) => s.value !== props.nota?.status),
)

/**
 * Limpezas de protocolo, so as que a nota tem de fato.
 *
 * Sao presets de alteracao de status: apagam o protocolo e deixam o backend concluir. O
 * `status` vai junto porque o NotaFiscalStatusRequest o exige, mas o NotaFiscalObserver o
 * SOBRESCREVE — mexer num campo de protocolo dispara o calcularStatus().
 */
const limpezasDisponiveis = computed(() =>
  [
    props.nota?.nfeautorizacao && {
      chave: 'autorizacao',
      label: 'Limpar Autorização',
      caption: 'Apaga o protocolo de autorização e o de cancelamento',
      payload: {
        status: 'ERR',
        nfeautorizacao: null,
        nfedataautorizacao: null,
        nfecancelamento: null,
        nfedatacancelamento: null,
      },
    },
    props.nota?.nfecancelamento && {
      chave: 'cancelamento',
      label: 'Limpar Cancelamento',
      caption: 'Apaga o protocolo de cancelamento; a nota volta a autorizada',
      payload: { status: 'AUT', nfecancelamento: null, nfedatacancelamento: null },
    },
    props.nota?.nfeinutilizacao && {
      chave: 'inutilizacao',
      label: 'Limpar Inutilização',
      caption: 'Apaga o protocolo de inutilização',
      payload: { status: 'ERR', nfeinutilizacao: null, nfedatainutilizacao: null },
    },
  ].filter(Boolean),
)
const temAcoes = computed(
  () =>
    podeEmitir.value ||
    podeConsultar.value ||
    !!acaoCancelamento.value ||
    (props.mostrarExcluir && podeExcluir.value),
)

const deveAbrirDanfeAposEnviar = computed(() => props.abrirDanfeAposEnviar ?? !props.compact)

const btnSize = computed(() => (props.compact ? 'sm' : undefined))

// Consultar/cancelar/inutilizar sao sincronos e uma chamada a SEFAZ com retry leva
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
 * Cria e assina o XML. Devolve a nota atualizada.
 *
 * `offline` e tri-state: null = automatico (segue o modo de emissao da empresa),
 * true = contingencia off-line, false = on-line. So a NFC-e tem contingencia.
 *
 * Sem dialog aqui: quem pergunta ao usuario e o botao (criarXmlComEscolha).
 */
async function criarXml(offline = null) {
  loadingCriar.value = true
  try {
    const corpo = offline === null ? {} : { offline }
    const { data } = await props.api.post(`/v1/nota-fiscal/${codnotafiscal.value}/criar`, corpo)
    const nota = data?.data ?? data
    emit('action-completed', 'criar', nota)
    return nota
  } finally {
    loadingCriar.value = false
  }
}

/**
 * Entrega a SEFAZ o XML assinado que JA existe — nao cria nem recria nada, entao a chave de
 * acesso nunca muda. Devolve a nota atualizada.
 *
 * Emite ANTES de tratar o erro: mesmo recusada ou sem resposta, a nota volta do backend com
 * o status novo. Sair pelo throw sem sincronizar deixava o card desatualizado ate um F5.
 */
async function transmitirXml() {
  const r = await iniciarTransmissao()

  if (r.nota) emit('action-completed', 'transmitir', r.nota)

  if (!r.sucesso) {
    throw new Error(`${r.cStat ?? ''} - ${r.xMotivo ?? 'Erro desconhecido'}`)
  }

  return r.nota ?? props.nota
}

/**
 * O caminho feliz, num clique so: cria o XML se ainda nao existe, transmite se for o caso e
 * abre o documento no fim.
 *
 * A orquestracao mora aqui, e nao no backend, porque ela e uma decisao de INTERFACE — cada
 * passo continua existindo como botao proprio para quem precisa resolver um problema no meio
 * do caminho. Nao ha fallback automatico: se o XML assinado sumiu do disco, a transmissao
 * falha com mensagem clara e o usuario clica Criar XML. Recriar por baixo dos panos seria
 * justamente a magica que este desenho existe para eliminar.
 *
 * Devolve Promise que so resolve no fim: o PDV faz `await comp.emitir()`.
 */
async function emitir(event) {
  stop(event)
  emitindo.value = true
  try {
    let nota = props.nota

    // 1. Sem chave = sem XML. Cria seguindo o modo de emissao configurado na empresa.
    if (!nota?.nfechave) {
      nota = await criarXml()
    }

    // 2. Contingencia off-line nao vai a SEFAZ agora: o cupom sai e o robo de pendentes
    //    transmite depois, dentro do prazo legal de 24h.
    if (nota?.tpemis != 9) {
      nota = await transmitirXml()
    }

    // 3. O documento so existe para o cliente se ele sair impresso.
    if (nota?.tpemis == 9 || nota?.status === 'AUT') {
      if (props.nota?.modelo == 65 && props.impressora) await imprimir()
      if (nota?.tpemis == 9 || deveAbrirDanfeAposEnviar.value) await abrirDanfe()
    }
  } catch (error) {
    // O Notify da transmissao ja saiu pelo composable; aqui cobre criar e cStat recusado
    if (error?.message && !error.message.startsWith('Sem conex')) {
      $q.notify({ type: 'negative', message: 'Erro ao emitir NFe', caption: mensagemErro(error) })
    }
  } finally {
    emitindo.value = false
  }
}

/**
 * Botao Criar XML. A NFC-e pergunta o modo de emissao; a NF-e (55) nao tem contingencia
 * off-line, entao vai direto.
 */
function criarXmlComEscolha(event) {
  stop(event)

  const criar = (offline) =>
    criarXml(offline).catch((error) =>
      $q.notify({ type: 'negative', message: 'Erro ao criar XML', caption: mensagemErro(error) }),
    )

  if (!cupom.value) return criar(null)

  $q.dialog({
    title: 'Criar XML',
    message: 'Como esta NFC-e deve ser emitida?',
    options: {
      type: 'radio',
      model: 'online',
      items: [
        { label: 'On-line — transmite à SEFAZ agora', value: 'online' },
        { label: 'Off-line — contingência, o robô transmite depois', value: 'offline' },
      ],
    },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Criar', flat: true, color: 'primary' },
  }).onOk((escolha) => criar(escolha === 'offline'))
}

function transmitirNfe(event) {
  stop(event)
  return transmitirXml().catch((error) => {
    if (error?.message && !error.message.startsWith('Sem conex')) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao transmitir NFe',
        caption: mensagemErro(error),
      })
    }
  })
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

/**
 * Cancelar e inutilizar sao a MESMA decisao do usuario ("essa nota nao vale"), e o status
 * ja diz qual das duas e possivel — cancelar exige AUT, inutilizar exige ERR. Por isso um
 * botao so, que pede apenas a justificativa.
 */
function cancelarOuInutilizar(event) {
  stop(event)

  const acao = acaoCancelamento.value
  if (!acao) return

  const cancelando = acao === 'cancelar'
  const titulo = cancelando ? 'Cancelar NFe' : 'Inutilizar NFe'

  $q.dialog({
    title: titulo,
    message: `Digite a justificativa para ${cancelando ? 'cancelar' : 'inutilizar'} a NFe`,
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (val) => val && val.length >= 15,
    },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: titulo, flat: true, color: 'negative' },
  }).onOk(async (justificativa) => {
    loadingCancelar.value = true
    try {
      const resp = await props.api.post(
        `/v1/nota-fiscal/${codnotafiscal.value}/${acao}`,
        { justificativa },
        { timeout: TIMEOUT_SEFAZ },
      )
      const r = resp.data?.resultado ?? resp.data
      const tipo = r?.sucesso ? 'positive' : 'negative'
      $q.notify({ type: tipo, message: `${r?.cStat ?? ''} - ${r?.xMotivo ?? ''}` })
      emit('action-completed', acao, resp.data?.nota)
    } catch (error) {
      $q.notify({ type: 'negative', message: mensagemErro(error) })
    } finally {
      loadingCancelar.value = false
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
  loadingDanfe.value = true
  try {
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
  } finally {
    loadingDanfe.value = false
  }
}

/**
 * Escotilha de emergencia: grava o status na mao, sem passar pela SEFAZ. Existe para
 * destravar nota que ficou inconsistente, dai a confirmacao digitada.
 */
function gravarStatus({ payload, titulo, mensagem, confirmacao, sucesso }) {
  $q.dialog({
    title: titulo,
    message: `${mensagem} Digite ${confirmacao} para confirmar:`,
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (val) => val === confirmacao,
    },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Confirmar', color: 'warning', flat: true },
  }).onOk(async () => {
    loadingStatus.value = true
    try {
      const { data } = await props.api.put(`/v1/nota-fiscal/${codnotafiscal.value}/status`, payload)
      $q.notify({ type: 'positive', message: sucesso })
      statusDialog.value = false
      emit('action-completed', 'status', data?.data ?? data)
    } catch (error) {
      $q.notify({ type: 'negative', message: 'Erro ao gravar', caption: mensagemErro(error) })
    } finally {
      loadingStatus.value = false
    }
  })
}

function alterarStatus(novoStatus) {
  const label = NOTA_FISCAL_STATUS_OPTIONS.find((s) => s.value === novoStatus)?.label

  gravarStatus({
    payload: { status: novoStatus },
    titulo: 'Confirmar alteração de status',
    mensagem: `Esta ação irá alterar o status da nota para "${label}".`,
    confirmacao: 'ALTERAR',
    sucesso: 'Status alterado com sucesso',
  })
}

function limparProtocolo(limpeza) {
  gravarStatus({
    payload: limpeza.payload,
    titulo: limpeza.label,
    mensagem: `${limpeza.caption}.`,
    confirmacao: 'LIMPAR',
    sucesso: `${limpeza.label} concluído`,
  })
}

function abrirXmlDoTipo(tipo) {
  return abrirXml(
    props.api,
    `/v1/nota-fiscal/${codnotafiscal.value}/xml/${tipo}`,
    {},
    {
      titulo: `XML da ${cupom.value ? 'NFC-e' : 'NFe'}`,
      nomeArquivo: `nfe-${codnotafiscal.value}-${tipo}.xml`,
      erroFallback: 'Erro ao abrir XML',
    },
  )
}

/**
 * Uma nota tem varios XMLs ao longo da vida (assinado, autorizado, denegado, cancelado e uma
 * carta de correcao por sequencia). O backend so lista o que existe em disco, entao com um
 * unico arquivo nao ha o que perguntar — abre direto.
 */
async function verXmls(event) {
  stop(event)
  loadingXmls.value = true
  try {
    const { data } = await props.api.get(`/v1/nota-fiscal/${codnotafiscal.value}/xml`)
    const xmls = data?.data ?? []

    if (!xmls.length) {
      $q.notify({ type: 'negative', message: 'Nenhum XML disponível para esta nota fiscal!' })
      return
    }

    if (xmls.length === 1) return await abrirXmlDoTipo(xmls[0].tipo)

    $q.dialog({
      title: 'XMLs da Nota Fiscal',
      options: {
        type: 'radio',
        model: xmls[xmls.length - 1].tipo,
        items: xmls.map((x) => ({ label: x.label, value: x.tipo })),
      },
      cancel: { label: 'Cancelar', flat: true },
      ok: { label: 'Abrir', flat: true, color: 'primary' },
    }).onOk((tipo) => abrirXmlDoTipo(tipo))
  } catch (error) {
    $q.notify({ type: 'negative', message: mensagemErro(error, 'Erro ao listar os XMLs') })
  } finally {
    loadingXmls.value = false
  }
}

// Retoma o acompanhamento se a transmissao ja estava correndo (F5 na tela de detalhe).
// So no detalhe: numa listagem com 20 linhas DIG/ERR isso viraria 20 GETs.
onMounted(() => {
  if (!props.compact && podeTransmitir.value) checarEmAndamento()
})

defineExpose({ emitir, podeEmitir, loadingEmitir: emitindo })
</script>

<template>
  <div
    class="row no-wrap q-gutter-xs"
    v-if="temAcoes || podeAbrirDanfe || (showExtras && nota?.emitida)"
  >
    <!-- O caminho feliz num clique: criar -> transmitir -> DANFE -->
    <q-btn
      v-if="mostrarEmitir"
      flat
      dense
      round
      :size="btnSize"
      color="secondary"
      icon="send"
      :loading="emitindo"
      :disable="ocupado && !emitindo"
      @click="emitir"
    >
      <q-tooltip>Emitir a NFe</q-tooltip>
    </q-btn>

    <!--
      Criar XML e a metade do Emitir que so interessa a quem esta resolvendo um problema:
      fica atras do showExtras. Ja o Transmitir aparece em qualquer tela, porque ele SUBSTITUI
      o Emitir assim que a nota tem chave — nesse ponto o documento ja existe e refaze-lo do
      zero nao e o que se quer.
    -->
    <q-btn
      v-if="showExtras && podeCriarXml"
      flat
      dense
      round
      :size="btnSize"
      color="orange"
      icon="note_add"
      :loading="girandoCriar"
      :disable="ocupado && !girandoCriar"
      @click="criarXmlComEscolha"
    >
      <q-tooltip>Criar e assinar o XML (não transmite)</q-tooltip>
    </q-btn>

    <q-btn
      v-if="podeTransmitir"
      flat
      dense
      round
      :size="btnSize"
      color="teal"
      icon="cloud_upload"
      :loading="girandoTransmitir"
      :disable="ocupado && !girandoTransmitir"
      @click="transmitirNfe"
    >
      <q-tooltip>Transmitir à SEFAZ o XML já assinado (mantém a chave de acesso)</q-tooltip>
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
      :disable="ocupado && !loadingConsultar"
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
      :loading="girandoDanfe"
      :disable="ocupado && !girandoDanfe"
      @click="abrirDanfe"
    >
      <q-tooltip>Abrir DANFE</q-tooltip>
    </q-btn>

    <q-btn
      v-if="showExtras && podeAbrirXml"
      flat
      dense
      round
      :size="btnSize"
      color="orange"
      icon="code"
      :loading="loadingXmls"
      :disable="ocupado && !loadingXmls"
      @click="verXmls"
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
      :disable="ocupado && !loadingEmail"
      @click="enviarEmailNfe"
    >
      <q-tooltip>Enviar por email</q-tooltip>
    </q-btn>

    <!-- Cancelar (nota AUT) e inutilizar (nota ERR) nunca coexistem: um botao so -->
    <q-btn
      v-if="acaoCancelamento"
      flat
      dense
      round
      :size="btnSize"
      :color="acaoCancelamento === 'cancelar' ? 'negative' : 'warning'"
      :icon="acaoCancelamento === 'cancelar' ? 'cancel' : 'block'"
      :loading="loadingCancelar"
      :disable="ocupado && !loadingCancelar"
      @click="cancelarOuInutilizar"
    >
      <q-tooltip>
        {{ acaoCancelamento === 'cancelar' ? 'Cancelar NFe' : 'Inutilizar NFe' }}
      </q-tooltip>
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
      :disable="ocupado && !loadingExcluir"
      @click="excluirNfe"
    >
      <q-tooltip>Excluir</q-tooltip>
    </q-btn>

    <q-btn
      v-if="showExtras && nota?.emitida"
      flat
      dense
      round
      :size="btnSize"
      color="grey-7"
      icon="edit_note"
      :loading="loadingStatus"
      :disable="ocupado && !loadingStatus"
      @click="statusDialog = true"
    >
      <q-tooltip>Alterar status manualmente</q-tooltip>
    </q-btn>

    <q-dialog v-model="statusDialog">
      <q-card flat style="width: 600px; max-width: 90vw">
        <q-card-section>
          <div class="text-h6">Alterar Status da NFe</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-banner class="bg-warning text-grey-8 rounded-borders q-mb-sm">
            <template v-slot:avatar>
              <q-icon name="warning" />
            </template>
            Não altere o status sem ter CERTEZA ABSOLUTA. A alteração pode levar à perda de dados.
            Somente confirme a operação se você tem as informações da nota fiscal para reparar em
            caso de erro.
          </q-banner>
          <div class="text-body2 q-mb-md">Selecione o novo status:</div>
          <div class="row q-col-gutter-sm">
            <div v-for="status in statusDisponiveis" :key="status.value" class="col-6 col-sm-4">
              <q-btn
                unelevated
                :color="status.color"
                class="full-width"
                stack
                @click="alterarStatus(status.value)"
              >
                <q-icon :name="status.icon" size="md" />
                <div class="text-caption q-mt-xs">{{ status.label }}</div>
              </q-btn>
            </div>
          </div>

          <!--
            Em lista, e nao na grade acima: sao acoes destrutivas cujo nome nao revela o
            efeito colateral (limpar autorizacao apaga tambem o cancelamento).
          -->
          <template v-if="limpezasDisponiveis.length">
            <div class="text-body2 q-mb-sm q-mt-md">Limpar protocolos:</div>
            <q-list bordered separator>
              <q-item
                v-for="limpeza in limpezasDisponiveis"
                :key="limpeza.chave"
                clickable
                @click="limparProtocolo(limpeza)"
              >
                <q-item-section avatar>
                  <q-icon name="clear" color="negative" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ limpeza.label }}</q-item-label>
                  <q-item-label caption>{{ limpeza.caption }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </template>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>
