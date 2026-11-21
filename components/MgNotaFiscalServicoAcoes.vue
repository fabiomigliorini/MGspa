<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { useQuasar } from 'quasar'
import { abrirPdf } from '@components/abrirPdf'
import { abrirXml } from '@components/abrirXml'
import MgInput from '@components/MgInput.vue'
import MgSelect from '@components/MgSelect.vue'
import {
  NFSE_MOTIVOS_SUBSTITUICAO,
  descricaoMotivoSubstituicao,
} from '@components/nfseSubstituicao'

// Acoes da nota fiscal de servico no Sistema Nacional da NFS-e (TASK-279). Espelha o
// MgNotaFiscalAcoes da NF-e, bem mais simples: a emissao e sincrona (o POST ja devolve a
// NFS-e autorizada ou a rejeicao), nao ha transmissao em fila nem contingencia.
const props = defineProps({
  nota: { type: Object, required: true },
  api: { type: [Function, Object], required: true },
  compact: { type: Boolean, default: false },
  // XML, e-mail, duplicar e substituir: so no detalhe
  showExtras: { type: Boolean, default: false },
  // Exibe o botao Excluir para notas em digitacao (status DIG)
  mostrarExcluir: { type: Boolean, default: false },
  // Abrir o DANFSe ao fim do Enviar autorizado. Quando null, segue o padrao !compact
  abrirDanfseAposEnviar: { type: Boolean, default: null },
})

const emit = defineEmits(['action-completed'])

const $q = useQuasar()

const loadingEnviar = ref(false)
const loadingConsultar = ref(false)
const loadingCancelar = ref(false)
const loadingEmail = ref(false)
const loadingDanfse = ref(false)
const loadingXml = ref(false)
const loadingDuplicar = ref(false)
const loadingSubstituir = ref(false)
const loadingExcluir = ref(false)

const cancelarDialog = ref(false)
const cancelamento = ref({ motivo: 1, justificativa: '' })

const substituirDialog = ref(false)
const substituicao = ref({ motivo: 99, justificativa: '' })

const MOTIVOS_CANCELAMENTO = [
  { label: 'Erro na emissão', value: 1 },
  { label: 'Serviço não prestado', value: 2 },
  { label: 'Outros', value: 9 },
]

// A chamada ao Sistema Nacional pode demorar; o timeout global do axios e 15s. Abortar
// antes do backend terminar deixaria o PHP rodando e a tela sem saber o resultado.
const TIMEOUT_SEFIN = 150000

let desmontado = false
onUnmounted(() => {
  desmontado = true
})

const cod = computed(() => props.nota?.codnotafiscalservico)
const status = computed(() => props.nota?.status)

// Uma acao de cada vez: a clicada gira, as outras desabilitam
const ocupado = computed(
  () =>
    loadingEnviar.value ||
    loadingConsultar.value ||
    loadingCancelar.value ||
    loadingEmail.value ||
    loadingDanfse.value ||
    loadingXml.value ||
    loadingDuplicar.value ||
    loadingSubstituir.value ||
    loadingExcluir.value,
)

const podeEnviar = computed(() => ['DIG', 'ERR'].includes(status.value))
// A consulta e pelo identificador da DPS, que existe desde o primeiro envio (numero)
const podeConsultar = computed(() => ['ERR', 'AUT'].includes(status.value) && !!props.nota?.numero)
const podeCancelar = computed(() => status.value === 'AUT')
// Substituir (TASK-279 #5): cria a substituta em digitacao, ligada a esta
const podeSubstituir = computed(() => status.value === 'AUT')
const podeEnviarEmail = computed(() => status.value === 'AUT')
const podeAbrirDanfse = computed(() => ['AUT', 'CAN'].includes(status.value))
const xmlsDisponiveis = computed(() =>
  [
    props.nota?.numero && { label: 'DPS assinada (enviada)', value: 'dps' },
    props.nota?.nfsechave && { label: 'NFS-e autorizada', value: 'nfse' },
    status.value === 'CAN' && { label: 'Cancelamento', value: 'cancelamento' },
  ].filter(Boolean),
)
const podeAbrirXml = computed(() => xmlsDisponiveis.value.length > 0)
const podeExcluir = computed(() => status.value === 'DIG')

const deveAbrirDanfse = computed(() => props.abrirDanfseAposEnviar ?? !props.compact)
const btnSize = computed(() => (props.compact ? 'sm' : undefined))

function stop(event) {
  if (event) {
    event.preventDefault?.()
    event.stopPropagation?.()
  }
}

function mensagemErro(error, fallback) {
  return error?.response?.data?.message || error?.message || fallback
}

function notificarResultado(r, fallback) {
  $q.notify({
    color: r?.sucesso ? 'green-5' : 'red-5',
    icon: r?.sucesso ? 'done' : 'error',
    message: r?.mensagem || fallback,
    multiLine: true,
    timeout: r?.sucesso ? 5000 : 10000,
  })
}

function notificarErro(error, fallback) {
  $q.notify({ color: 'red-5', icon: 'error', message: mensagemErro(error, fallback) })
}

async function enviar(event) {
  stop(event)
  loadingEnviar.value = true
  let nota = null
  try {
    const { data } = await props.api.post(
      `/v1/nota-fiscal-servico/${cod.value}/enviar`,
      {},
      { timeout: TIMEOUT_SEFIN },
    )
    nota = data?.nota ?? null
    notificarResultado(data?.resultado, 'NFS-e enviada')
    if (nota && !desmontado) emit('action-completed', 'enviar', nota)
  } catch (error) {
    notificarErro(error, 'Erro ao enviar a NFS-e')
  } finally {
    loadingEnviar.value = false
  }
  if (nota?.status === 'AUT' && deveAbrirDanfse.value && !desmontado) {
    await abrirDanfse(null, nota)
  }
}

async function consultar(event) {
  stop(event)
  loadingConsultar.value = true
  try {
    const { data } = await props.api.post(
      `/v1/nota-fiscal-servico/${cod.value}/consultar`,
      {},
      { timeout: TIMEOUT_SEFIN },
    )
    notificarResultado(data?.resultado, 'Consulta concluída')
    if (data?.nota && !desmontado) emit('action-completed', 'consultar', data.nota)
  } catch (error) {
    notificarErro(error, 'Erro ao consultar a NFS-e')
  } finally {
    loadingConsultar.value = false
  }
}

function abrirCancelar(event) {
  stop(event)
  cancelamento.value = { motivo: 1, justificativa: '' }
  cancelarDialog.value = true
}

async function cancelar() {
  loadingCancelar.value = true
  try {
    const { data } = await props.api.post(
      `/v1/nota-fiscal-servico/${cod.value}/cancelar`,
      { motivo: cancelamento.value.motivo, justificativa: cancelamento.value.justificativa },
      { timeout: TIMEOUT_SEFIN },
    )
    notificarResultado(data?.resultado, 'Cancelamento enviado')
    if (data?.resultado?.sucesso) cancelarDialog.value = false
    if (data?.nota && !desmontado) emit('action-completed', 'cancelar', data.nota)
  } catch (error) {
    notificarErro(error, 'Erro ao cancelar a NFS-e')
  } finally {
    loadingCancelar.value = false
  }
}

function enviarEmail(event) {
  stop(event)
  $q.dialog({
    title: 'Enviar por e-mail',
    message: 'E-mail do tomador (vários separados por vírgula)',
    prompt: {
      // Os e-mails marcados "Envio de NFe" no cadastro da pessoa
      model: props.nota?.pessoa?.emailnfe || '',
      type: 'text',
      outlined: true,
    },
    cancel: { label: 'Cancelar', flat: true, color: 'grey-8' },
    ok: { label: 'Enviar', flat: true, color: 'primary' },
  }).onOk(async (destinatario) => {
    loadingEmail.value = true
    try {
      const { data } = await props.api.post(`/v1/nota-fiscal-servico/${cod.value}/mail`, {
        destinatario: destinatario || null,
      })
      notificarResultado(data, 'E-mail enviado')
    } catch (error) {
      notificarErro(error, 'Erro ao enviar o e-mail')
    } finally {
      loadingEmail.value = false
    }
  })
}

async function abrirDanfse(event, nota = null) {
  stop(event)
  const codigo = nota?.codnotafiscalservico ?? cod.value
  loadingDanfse.value = true
  try {
    await abrirPdf(
      props.api,
      `/v1/nota-fiscal-servico/${codigo}/danfse`,
      {},
      { title: 'DANFSe', size: 'a4' },
    )
  } finally {
    loadingDanfse.value = false
  }
}

function abrirXmlDoTipo(tipo) {
  return abrirXml(
    props.api,
    `/v1/nota-fiscal-servico/${cod.value}/xml/${tipo}`,
    {},
    {
      titulo: 'XML da NFS-e',
      nomeArquivo: `nfse-${cod.value}-${tipo}.xml`,
      erroFallback: 'Erro ao abrir o XML',
    },
  )
}

async function verXml(event) {
  stop(event)
  const xmls = xmlsDisponiveis.value
  if (xmls.length === 1) {
    loadingXml.value = true
    try {
      await abrirXmlDoTipo(xmls[0].value)
    } finally {
      loadingXml.value = false
    }
    return
  }
  $q.dialog({
    title: 'XMLs da NFS-e',
    options: { type: 'radio', model: xmls[xmls.length - 1].value, items: xmls },
    cancel: { label: 'Cancelar', flat: true, color: 'grey-8' },
    ok: { label: 'Abrir', flat: true, color: 'primary' },
  }).onOk(async (tipo) => {
    loadingXml.value = true
    try {
      await abrirXmlDoTipo(tipo)
    } finally {
      loadingXml.value = false
    }
  })
}

function duplicar(event) {
  stop(event)
  $q.dialog({
    title: 'Duplicar nota de serviço',
    message:
      'Tem certeza que quer duplicar esta nota de serviço? Será criada uma nota nova, em digitação.',
    cancel: { label: 'Cancelar', flat: true, color: 'grey-8' },
    ok: { label: 'Duplicar', flat: true, color: 'primary' },
  }).onOk(async () => {
    loadingDuplicar.value = true
    try {
      const { data } = await props.api.post(`/v1/nota-fiscal-servico/${cod.value}/duplicar`)
      $q.notify({ color: 'green-5', icon: 'done', message: 'Nota de serviço duplicada' })
      if (!desmontado) emit('action-completed', 'duplicar', data?.data ?? data)
    } catch (error) {
      notificarErro(error, 'Erro ao duplicar a nota de serviço')
    } finally {
      loadingDuplicar.value = false
    }
  })
}

function abrirSubstituir(event) {
  stop(event)
  substituicao.value = { motivo: 99, justificativa: '' }
  substituirDialog.value = true
}

// Cria a substituta (copia desta, em digitacao) e devolve para a tela abrir o formulario
// dela. Nada vai ao Sistema Nacional aqui: a substituicao acontece no Enviar da substituta.
async function substituir() {
  if (loadingSubstituir.value) return
  loadingSubstituir.value = true
  try {
    const { data } = await props.api.post(`/v1/nota-fiscal-servico/${cod.value}/substituir`, {
      motivo: substituicao.value.motivo,
      justificativa: substituicao.value.justificativa?.trim() || null,
    })
    substituirDialog.value = false
    $q.notify({ color: 'green-5', icon: 'done', message: 'Nota substituta criada' })
    if (!desmontado) emit('action-completed', 'substituir', data?.data ?? data)
  } catch (error) {
    notificarErro(error, 'Erro ao criar a nota substituta')
  } finally {
    loadingSubstituir.value = false
  }
}

function excluir(event) {
  stop(event)
  $q.dialog({
    title: 'Excluir nota de serviço',
    message: 'Confirma a exclusão desta nota de serviço?',
    cancel: { label: 'Cancelar', flat: true, color: 'grey-8' },
    ok: { label: 'Excluir', flat: true, color: 'negative' },
  }).onOk(async () => {
    loadingExcluir.value = true
    try {
      await props.api.delete(`/v1/nota-fiscal-servico/${cod.value}`)
      $q.notify({ color: 'green-5', icon: 'done', message: 'Nota de serviço excluída' })
      emit('action-completed', 'excluir', props.nota)
    } catch (error) {
      notificarErro(error, 'Erro ao excluir a nota de serviço')
    } finally {
      loadingExcluir.value = false
    }
  })
}

defineExpose({ enviar, podeEnviar })
</script>

<template>
  <div class="row no-wrap q-gutter-xs items-center">
    <q-btn
      v-if="podeEnviar"
      flat
      round
      :size="btnSize"
      color="grey-7"
      icon="send"
      :loading="loadingEnviar"
      :disable="ocupado && !loadingEnviar"
      @click="enviar"
    >
      <q-tooltip>Enviar ao Sistema Nacional da NFS-e</q-tooltip>
    </q-btn>

    <q-btn
      v-if="podeConsultar"
      flat
      round
      :size="btnSize"
      color="grey-7"
      icon="refresh"
      :loading="loadingConsultar"
      :disable="ocupado && !loadingConsultar"
      @click="consultar"
    >
      <q-tooltip>Consultar no Sistema Nacional</q-tooltip>
    </q-btn>

    <q-btn
      v-if="podeAbrirDanfse"
      flat
      round
      :size="btnSize"
      color="grey-7"
      icon="picture_as_pdf"
      :loading="loadingDanfse"
      :disable="ocupado && !loadingDanfse"
      @click="abrirDanfse"
    >
      <q-tooltip>Abrir DANFSe</q-tooltip>
    </q-btn>

    <q-btn
      v-if="showExtras && podeAbrirXml"
      flat
      round
      :size="btnSize"
      color="grey-7"
      icon="code"
      :loading="loadingXml"
      :disable="ocupado && !loadingXml"
      @click="verXml"
    >
      <q-tooltip>Abrir XML</q-tooltip>
    </q-btn>

    <q-btn
      v-if="showExtras && podeEnviarEmail"
      flat
      round
      :size="btnSize"
      color="grey-7"
      icon="email"
      :loading="loadingEmail"
      :disable="ocupado && !loadingEmail"
      @click="enviarEmail"
    >
      <q-tooltip>Enviar por e-mail</q-tooltip>
    </q-btn>

    <q-btn
      v-if="showExtras"
      flat
      round
      :size="btnSize"
      color="grey-7"
      icon="content_copy"
      :loading="loadingDuplicar"
      :disable="ocupado && !loadingDuplicar"
      @click="duplicar"
    >
      <q-tooltip>Duplicar</q-tooltip>
    </q-btn>

    <q-btn
      v-if="showExtras && podeSubstituir"
      flat
      round
      :size="btnSize"
      color="grey-7"
      icon="find_replace"
      :loading="loadingSubstituir"
      :disable="ocupado && !loadingSubstituir"
      @click="abrirSubstituir"
    >
      <q-tooltip>Substituir NFS-e</q-tooltip>
    </q-btn>

    <q-btn
      v-if="podeCancelar"
      flat
      round
      :size="btnSize"
      color="grey-7"
      icon="cancel"
      :loading="loadingCancelar"
      :disable="ocupado && !loadingCancelar"
      @click="abrirCancelar"
    >
      <q-tooltip>Cancelar NFS-e</q-tooltip>
    </q-btn>

    <q-btn
      v-if="mostrarExcluir && podeExcluir"
      flat
      round
      :size="btnSize"
      color="grey-7"
      icon="delete"
      :loading="loadingExcluir"
      :disable="ocupado && !loadingExcluir"
      @click="excluir"
    >
      <q-tooltip>Excluir</q-tooltip>
    </q-btn>

    <q-dialog v-model="substituirDialog">
      <q-card flat style="width: 600px; max-width: 90vw">
        <q-form @submit.prevent="substituir">
          <q-card-section>
            <div class="text-h6">Substituir NFS-e {{ nota.nfsenumero }}</div>
            <div class="text-caption text-grey-7">
              Cria uma cópia em digitação para você corrigir. Quando ela for autorizada, o Sistema
              Nacional cancela esta.
            </div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <MgSelect
                  v-model="substituicao.motivo"
                  :options="NFSE_MOTIVOS_SUBSTITUICAO"
                  label="Motivo"
                  emit-value
                  map-options
                  autofocus
                />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="substituicao.justificativa"
                  label="Descrição do motivo (opcional)"
                  maxlength="255"
                  counter
                  :rules="[descricaoMotivoSubstituicao]"
                />
              </div>
            </div>
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat label="Voltar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn
              flat
              label="Substituir"
              color="primary"
              type="submit"
              :loading="loadingSubstituir"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <q-dialog v-model="cancelarDialog">
      <q-card flat style="width: 600px; max-width: 90vw">
        <q-form @submit.prevent="cancelar">
          <q-card-section>
            <div class="text-h6">Cancelar NFS-e</div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <div class="text-caption text-grey-7">Motivo</div>
                <q-option-group
                  v-model="cancelamento.motivo"
                  :options="MOTIVOS_CANCELAMENTO"
                  type="radio"
                  inline
                />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="cancelamento.justificativa"
                  label="Justificativa"
                  autofocus
                  maxlength="255"
                  counter
                  :rules="[(v) => (v && v.trim().length >= 15) || 'Informe ao menos 15 caracteres']"
                />
              </div>
            </div>
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat label="Voltar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn
              flat
              label="Cancelar NFS-e"
              color="negative"
              type="submit"
              :loading="loadingCancelar"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </div>
</template>
