<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useNfeTerceiroStore } from '../stores/nfeTerceiroStore'
import nfeTerceiroService from '../services/nfeTerceiroService'
import {
  formatCnpjCpf,
  formatDateTime,
  formatDate,
  formatCurrency,
  formatDecimal,
  formatChave,
} from 'src/utils/formatters'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const nfeTerceiroStore = useNfeTerceiroStore()

const pessoasUrl = process.env.PESSOAS_URL

const tab = ref('geral')
const loadingAction = ref(false)
const barrasInput = ref('')
const tipoProdutoSelecionado = ref(null)
const icmsStData = ref(null)
const loadingIcmsSt = ref(false)

const tipoProdutoOptions = [
  { label: 'Uso e Consumo', value: 7 },
  { label: 'Imobilizado', value: 8 },
  { label: 'Outros Insumos', value: 10 },
]

const nfe = computed(() => nfeTerceiroStore.currentNfeTerceiro)
const loading = computed(() => nfeTerceiroStore.loading.nfeTerceiro)
const itens = computed(() => nfeTerceiroStore.itens)
const duplicatas = computed(() => nfeTerceiroStore.duplicatas)
const pagamentos = computed(() => nfeTerceiroStore.pagamentos)
const todosConferidos = computed(
  () => itens.value.length > 0 && itens.value.every((i) => i.conferencia),
)

const manifestacaoLabel = (indmanifestacao) => {
  switch (indmanifestacao) {
    case 210200:
      return { label: 'Operacao Realizada', color: 'green', icon: 'check_circle' }
    case 210210:
      return { label: 'Ciencia da Operacao', color: 'orange', icon: 'info' }
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
      return 'Autorizada'
    case 2:
      return 'Denegada'
    case 3:
      return 'Cancelada'
    default:
      return '-'
  }
}

const manifestacaoOptions = [
  { label: 'Ciencia da Operacao', value: 210210 },
  { label: 'Operacao Realizada', value: 210200 },
  { label: 'Desconhecida', value: 210220 },
  { label: 'Nao Realizada', value: 210240 },
]

const handleManifestacao = (indmanifestacao) => {
  if (indmanifestacao === 210240) {
    $q.dialog({
      title: 'Justificativa',
      message: 'Informe a justificativa (minimo 15 caracteres):',
      prompt: {
        model: '',
        type: 'text',
      },
      cancel: { label: 'Cancelar', flat: true },
      ok: { label: 'Enviar', color: 'primary' },
    }).onOk((justificativa) => {
      enviarManifestacao(indmanifestacao, justificativa)
    })
  } else {
    $q.dialog({
      title: 'Confirmar Manifestacao',
      message: `Enviar "${manifestacaoOptions.find((o) => o.value === indmanifestacao)?.label}" a SEFAZ? Esta acao nao pode ser desfeita.`,
      cancel: { label: 'Cancelar', flat: true },
      ok: { label: 'Confirmar', color: 'primary' },
    }).onOk(() => {
      enviarManifestacao(indmanifestacao, null)
    })
  }
}

const enviarManifestacao = async (indmanifestacao, justificativa) => {
  loadingAction.value = true
  try {
    const data = { indmanifestacao }
    if (justificativa) {
      data.justificativa = justificativa
    }
    await nfeTerceiroStore.manifestacao(nfe.value.codnfeterceiro, data)
    $q.notify({ type: 'positive', message: 'Manifestacao enviada' })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao manifestar',
      caption: error.response?.data?.message || error.message,
    })
  } finally {
    loadingAction.value = false
  }
}

const handleDownload = async () => {
  $q.dialog({
    title: 'Download NFe',
    message: 'Efetuar o download da NFe na SEFAZ?',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Download', color: 'primary' },
  }).onOk(async () => {
    loadingAction.value = true
    try {
      await nfeTerceiroStore.downloadNfe(nfe.value.codnfeterceiro)
      $q.notify({ type: 'positive', message: 'Download efetuado' })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao efetuar download',
        caption: error.response?.data?.message || error.message,
      })
    } finally {
      loadingAction.value = false
    }
  })
}

const handleRevisao = () => {
  $q.dialog({
    title: 'Confirmar',
    message: nfe.value.revisao ? 'Desmarcar revisao?' : 'Marcar como revisada?',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar', color: 'primary' },
  }).onOk(async () => {
    try {
      await nfeTerceiroStore.toggleRevisao(nfe.value.codnfeterceiro)
      $q.notify({ type: 'positive', message: 'Revisao atualizada' })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao alterar revisao',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

const handleConferencia = () => {
  $q.dialog({
    title: 'Confirmar',
    message: nfe.value.conferencia ? 'Desmarcar conferencia?' : 'Marcar como conferida?',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar', color: 'primary' },
  }).onOk(async () => {
    try {
      await nfeTerceiroStore.toggleConferencia(nfe.value.codnfeterceiro)
      $q.notify({ type: 'positive', message: 'Conferencia atualizada' })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao alterar conferencia',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

const handleImportar = async () => {
  try {
    const validacao = await nfeTerceiroStore.validarImportacao(nfe.value.codnfeterceiro)
    if (!validacao.podeImportar) {
      $q.dialog({
        title: 'Nao pode importar',
        message: validacao.erros.join('<br>'),
        html: true,
        ok: { label: 'OK', flat: true },
      })
      return
    }
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Erro ao validar', caption: error.message })
    return
  }

  $q.dialog({
    title: 'Importar NFe de Terceiro',
    message: 'Importar esta NFe para o sistema? Sera criada Nota Fiscal, Negocio e Titulos.',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Importar', color: 'primary' },
  }).onOk(async () => {
    loadingAction.value = true
    try {
      await nfeTerceiroStore.importar(nfe.value.codnfeterceiro)
      $q.notify({ type: 'positive', message: 'NFe importada com sucesso' })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao importar',
        caption: error.response?.data?.message || error.message,
      })
    } finally {
      loadingAction.value = false
    }
  })
}

const handleCarregarIcmsSt = async () => {
  loadingIcmsSt.value = true
  try {
    icmsStData.value = await nfeTerceiroService.icmsst(nfe.value.codnfeterceiro)
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Erro ao carregar ICMS-ST', caption: error.message })
  } finally {
    loadingIcmsSt.value = false
  }
}

const handleGerarGuiaSt = () => {
  const defaultValor = icmsStData.value?.totais?.diferenca || 0
  const defaultVencimento = new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0]

  $q.dialog({
    title: 'Gerar Guia ST',
    message: 'Informe o valor e vencimento:',
    prompt: { model: String(defaultValor.toFixed(2)), type: 'number', label: 'Valor' },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Gerar', color: 'primary' },
  }).onOk(async (valor) => {
    $q.dialog({
      title: 'Vencimento',
      prompt: { model: defaultVencimento, type: 'date', label: 'Data de Vencimento' },
      cancel: { label: 'Cancelar', flat: true },
      ok: { label: 'Confirmar', color: 'primary' },
    }).onOk(async (vencimento) => {
      loadingAction.value = true
      try {
        await nfeTerceiroService.gerarGuiaSt(nfe.value.codnfeterceiro, {
          valor: parseFloat(valor),
          vencimento,
        })
        $q.notify({ type: 'positive', message: 'Guia ST gerada com sucesso' })
        await handleCarregarIcmsSt()
      } catch (error) {
        $q.notify({
          type: 'negative',
          message: 'Erro ao gerar Guia ST',
          caption: error.response?.data?.message || error.message,
        })
      } finally {
        loadingAction.value = false
      }
    })
  })
}

const handleOpenXml = () => {
  window.open(nfeTerceiroService.xml(nfe.value.codnfeterceiro), '_blank')
}

const handleOpenDanfe = () => {
  window.open(nfeTerceiroService.danfe(nfe.value.codnfeterceiro), '_blank')
}

const handleOpenGuiaStPdf = (codtitulonfeterceiro) => {
  window.open(
    nfeTerceiroService.guiaStPdfUrl(nfe.value.codnfeterceiro, codtitulonfeterceiro),
    '_blank'
  )
}

// ==================== OPERAÇÕES SOBRE ITENS ====================

const handleBuscarItem = async () => {
  const barras = barrasInput.value?.trim()
  if (!barras) {
    $q.notify({ type: 'warning', message: 'Preencha o codigo de barras ou referencia' })
    return
  }
  try {
    const items = await nfeTerceiroStore.buscarItem(nfe.value.codnfeterceiro, barras)
    barrasInput.value = ''
    if (items.length === 0) {
      $q.notify({ type: 'negative', message: `Nao localizei "${barras}"` })
    } else if (items.length === 1) {
      $q.notify({ type: 'positive', message: `Item encontrado: ${items[0].xprod}` })
    } else {
      $q.notify({ type: 'info', message: `${items.length} itens encontrados` })
    }
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Erro ao buscar item', caption: error.message })
  }
}

const handleConferenciaItem = async (item) => {
  try {
    await nfeTerceiroStore.toggleConferenciaItem(nfe.value.codnfeterceiro, item.codnfeterceiroitem)
    $q.notify({ type: 'positive', message: 'Conferencia do item atualizada' })
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Erro ao alterar conferencia', caption: error.message })
  }
}

const handleDividirItem = (item) => {
  $q.dialog({
    title: 'Dividir Item',
    message: `Dividir "${item.xprod}" em quantas partes?`,
    options: {
      model: 2,
      items: [
        { label: '2 partes (55/45)', value: 2 },
        { label: '3 partes (39/33/28)', value: 3 },
        { label: '4 partes (30/27/23/20)', value: 4 },
        { label: '5 partes', value: 5 },
        { label: '6 partes', value: 6 },
        { label: '10 partes', value: 10 },
      ],
    },
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Dividir', color: 'primary' },
  }).onOk(async (parcelas) => {
    try {
      await nfeTerceiroStore.dividirItem(
        nfe.value.codnfeterceiro,
        item.codnfeterceiroitem,
        parcelas
      )
      $q.notify({ type: 'positive', message: `Item dividido em ${parcelas} partes` })
    } catch (error) {
      $q.notify({ type: 'negative', message: 'Erro ao dividir item', caption: error.message })
    }
  })
}

const handleMarcarTipoProduto = async () => {
  if (!tipoProdutoSelecionado.value) {
    $q.notify({ type: 'warning', message: 'Selecione o tipo de produto' })
    return
  }
  $q.dialog({
    title: 'Confirmar',
    message: 'Marcar todos os itens com o tipo selecionado?',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar', color: 'primary' },
  }).onOk(async () => {
    try {
      await nfeTerceiroStore.marcarTipoProduto(
        nfe.value.codnfeterceiro,
        tipoProdutoSelecionado.value
      )
      $q.notify({ type: 'positive', message: 'Itens marcados' })
    } catch (error) {
      $q.notify({ type: 'negative', message: 'Erro ao marcar itens', caption: error.message })
    }
  })
}

const handleConferirTodos = () => {
  const desmarcando = todosConferidos.value
  $q.dialog({
    title: 'Confirmar',
    message: desmarcando
      ? 'Desmarcar todos os itens como conferidos?'
      : 'Marcar todos os itens como conferidos?',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Confirmar', color: 'primary' },
  }).onOk(async () => {
    try {
      await nfeTerceiroStore.conferirTodos(nfe.value.codnfeterceiro)
      $q.notify({
        type: 'positive',
        message: desmarcando ? 'Conferência desmarcada' : 'Todos os itens conferidos',
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Erro ao alterar conferência',
        caption: error.response?.data?.message || error.message,
      })
    }
  })
}

onMounted(async () => {
  try {
    await nfeTerceiroStore.fetchNfeTerceiro(route.params.codnfeterceiro)
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao carregar NFe de terceiro',
      caption: error.response?.data?.message || error.message,
    })
    router.push({ name: 'nfe-terceiro' })
  }
})
</script>

<template>
  <q-page class="q-pa-md">
    <!-- Loading -->
    <div v-if="loading && !nfe" class="row justify-center q-py-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <template v-else-if="nfe">
      <!-- Cabecalho -->
      <div class="row items-center q-mb-md" style="flex-wrap: nowrap">
        <q-btn
          flat
          dense
          round
          icon="arrow_back"
          :to="{ name: 'nfe-terceiro' }"
          class="q-mr-sm"
          style="flex-shrink: 0"
        >
          <q-tooltip>Voltar</q-tooltip>
        </q-btn>

        <div class="text-h5 ellipsis" style="flex: 1; min-width: 0">
          NFe {{ nfe.numero }} - Série {{ nfe.serie }}
        </div>

        <!-- Manifestacao -->
        <q-btn-dropdown
          flat
          :color="manifestacaoLabel(nfe.indmanifestacao).color"
          :icon="manifestacaoLabel(nfe.indmanifestacao).icon"
          :loading="loadingAction"
          class="q-mr-sm"
        >
          <q-tooltip>Manifestação: {{ manifestacaoLabel(nfe.indmanifestacao).label }}</q-tooltip>
          <q-list>
            <q-item
              v-for="opt in manifestacaoOptions"
              :key="opt.value"
              clickable
              v-close-popup
              @click="handleManifestacao(opt.value)"
            >
              <q-item-section>{{ opt.label }}</q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>

        <!-- Revisao -->
        <q-btn
          flat
          icon="rate_review"
          :color="nfe.revisao ? 'green' : 'grey-7'"
          class="q-mr-sm"
          @click="handleRevisao"
        >
          <q-tooltip>{{ nfe.revisao ? 'Revisada' : 'Marcar como revisada' }}</q-tooltip>
        </q-btn>

        <!-- Conferencia -->
        <q-btn
          flat
          icon="task_alt"
          :color="nfe.conferencia ? 'green' : 'grey-7'"
          @click="handleConferencia"
        >
          <q-tooltip>{{ nfe.conferencia ? 'Conferida' : 'Marcar como conferida' }}</q-tooltip>
        </q-btn>
      </div>

      <!-- Linha 1: Dados da nota / Valores / NFe -->
      <div class="row q-col-gutter-md q-mb-md">
        <!-- Card: Dados da Nota -->
        <div class="col-12 col-md-4">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="text-body2">
                <q-icon name="receipt_long" size="1.5em" class="q-mr-sm" />
                Dados da Nota
              </div>
            </q-card-section>
            <q-card-section>
              <div class="text-caption text-grey-7">Série / Número</div>
              <div class="text-subtitle1 text-weight-bold">{{ nfe.serie }} / {{ nfe.numero }}</div>

              <div class="text-caption text-grey-7 q-mt-sm">Natureza</div>
              <div class="text-body2">{{ nfe.natureza }}</div>

              <template v-if="nfe.naturezaOperacao">
                <div class="text-caption text-grey-7 q-mt-sm">Nossa Natureza</div>
                <div class="text-body2">{{ nfe.naturezaOperacao.naturezaoperacao }}</div>
              </template>

              <div class="text-caption text-grey-7 q-mt-sm">Emissão</div>
              <div class="text-body2">{{ formatDateTime(nfe.emissao) }}</div>

              <div class="text-caption text-grey-7 q-mt-sm">Entrada</div>
              <div class="text-body2">{{ nfe.entrada ? formatDateTime(nfe.entrada) : '-' }}</div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Card: Valores -->
        <div class="col-12 col-md-4">
          <q-card flat bordered class="full-height">
            <q-card-section class="bg-primary text-white">
              <div class="text-body2">
                <q-icon name="attach_money" size="1.5em" class="q-mr-sm" />
                Totais
              </div>
            </q-card-section>
            <q-card-section>
              <div class="text-caption text-grey-7">Valor Total</div>
              <div class="text-subtitle1 text-weight-bold text-primary">
                R$ {{ formatCurrency(nfe.valortotal) }}
              </div>

              <div class="row q-col-gutter-sm q-mt-sm">
                <div class="col-6">
                  <div class="text-caption text-grey-7">Produtos</div>
                  <div class="text-body2">R$ {{ formatCurrency(nfe.valorprodutos) }}</div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-7">Frete</div>
                  <div class="text-body2">R$ {{ formatCurrency(nfe.valorfrete) }}</div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-7">Desconto</div>
                  <div class="text-body2">R$ {{ formatCurrency(nfe.valordesconto) }}</div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-7">Outras / Seguro</div>
                  <div class="text-body2">
                    R$ {{ formatCurrency(nfe.valoroutras + nfe.valorseguro) }}
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-7">ICMS</div>
                  <div class="text-body2">R$ {{ formatCurrency(nfe.icmsvalor) }}</div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-7">ICMS ST</div>
                  <div class="text-body2">R$ {{ formatCurrency(nfe.icmsstvalor) }}</div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-7">IPI</div>
                  <div class="text-body2">R$ {{ formatCurrency(nfe.ipivalor) }}</div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Card: NFe -->
        <div class="col-12 col-md-4">
          <q-card flat bordered class="full-height flex column">
            <q-card-section class="bg-primary text-white">
              <div class="text-body2">
                <q-icon name="description" size="1.5em" class="q-mr-sm" />
                NFe
              </div>
            </q-card-section>

            <q-card-section class="col-grow">
              <div class="text-caption text-grey-7">Chave</div>
              <div class="text-caption" style="font-family: monospace">
                {{ formatChave(nfe.nfechave) }}
              </div>

              <div class="text-caption text-grey-7 q-mt-sm">Status</div>
              <div>
                <q-badge v-if="nfe.codnotafiscal" color="green" class="text-subtitle2">
                  <q-icon name="check_circle" size="sm" class="q-mr-xs" />
                  Importada (NF #{{ nfe.codnotafiscal }})
                </q-badge>
                <q-badge v-else color="orange" class="text-subtitle2">Pendente Importação</q-badge>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-actions align="right">
              <q-btn dense round flat color="grey-7" icon="cloud_download" @click="handleDownload">
                <q-tooltip>Download NFe</q-tooltip>
              </q-btn>
              <q-btn dense round flat color="grey-7" icon="code" @click="handleOpenXml">
                <q-tooltip>XML</q-tooltip>
              </q-btn>
              <q-btn dense round flat color="grey-7" icon="picture_as_pdf" @click="handleOpenDanfe">
                <q-tooltip>DANFE</q-tooltip>
              </q-btn>
              <q-btn
                v-if="!nfe.codnotafiscal"
                dense
                round
                flat
                color="positive"
                icon="check_circle"
                :loading="loadingAction"
                @click="handleImportar"
              >
                <q-tooltip>Importar</q-tooltip>
              </q-btn>
            </q-card-actions>
          </q-card>
        </div>
      </div>

      <!-- Linha 2: Emitente -->
      <div class="row q-mb-md">
        <div class="col-12">
          <q-card flat bordered>
            <q-card-section class="bg-primary text-white">
              <div class="text-body2">
                <q-icon
                  :name="nfe.pessoa?.fisica === true ? 'person' : 'business'"
                  size="1.5em"
                  class="q-mr-sm"
                />
                Emitente
              </div>
            </q-card-section>

            <q-card-section>
              <div class="row q-col-gutter-sm">
                <!-- NOME -->
                <div class="col-12 col-sm-8 col-md-5">
                  <div class="text-caption text-grey-7">Nome | Razão Social</div>
                  <div class="text-body1 text-weight-bold text-primary ellipsis">
                    <a
                      v-if="nfe.pessoa?.codpessoa"
                      :href="`${pessoasUrl}/pessoa/${nfe.pessoa.codpessoa}`"
                      target="_blank"
                      class="text-primary text-weight-bold"
                      style="text-decoration: none"
                    >
                      {{ nfe.pessoa?.fantasia || '-' }}
                      <span class="text-grey-7">
                        | {{ nfe.pessoa?.pessoa || nfe.emitente || '-' }}
                      </span>
                    </a>
                    <template v-else>
                      {{ nfe.emitente || '-' }}
                    </template>
                  </div>
                </div>

                <!-- CNPJ / CPF -->
                <div class="col-12 col-sm-4 col-md-2">
                  <div class="text-caption text-grey-7">
                    {{ nfe.pessoa?.fisica ? 'CPF' : 'CNPJ' }}
                  </div>
                  <div class="text-body1 text-weight-bold text-primary ellipsis">
                    {{ formatCnpjCpf(nfe.pessoa?.cnpj || nfe.cnpj, nfe.pessoa?.fisica) }}
                  </div>
                </div>

                <!-- IE -->
                <div class="col-6 col-sm-8 col-md-2" v-if="nfe.pessoa?.ie || nfe.ie">
                  <div class="text-caption text-grey-7">Inscrição Estadual</div>
                  <div class="text-body2 ellipsis">{{ nfe.pessoa?.ie || nfe.ie }}</div>
                </div>

                <!-- EMAIL -->
                <div class="col-12 col-sm-4 col-md-3" v-if="nfe.pessoa?.email">
                  <div class="text-caption text-grey-7">E-MAIL</div>
                  <div class="text-body2 ellipsis">{{ nfe.pessoa.email }}</div>
                </div>

                <!-- ENDEREÇO -->
                <div class="col-12 col-sm-8 col-md-3" v-if="nfe.pessoa?.endereco">
                  <div class="text-caption text-grey-7">Endereço Fiscal</div>
                  <div class="text-body2 ellipsis">
                    {{ nfe.pessoa.endereco }}, {{ nfe.pessoa.numero }}, {{ nfe.pessoa.cep }}
                  </div>
                </div>

                <!-- BAIRRO -->
                <div class="col-12 col-sm-4 col-md-2" v-if="nfe.pessoa?.bairro">
                  <div class="text-caption text-grey-7">Bairro</div>
                  <div class="text-body2 ellipsis">{{ nfe.pessoa.bairro }}</div>
                </div>

                <!-- CIDADE -->
                <div class="col-12 col-sm-5 col-md-2" v-if="nfe.pessoa?.cidade">
                  <div class="text-caption text-grey-7">Cidade</div>
                  <div class="text-body2 ellipsis">
                    {{ nfe.pessoa.cidade }} / {{ nfe.pessoa.uf }}
                  </div>
                </div>

                <!-- TELEFONE -->
                <div class="col-6 col-sm-4 col-md-2" v-if="nfe.pessoa?.telefone1">
                  <div class="text-caption text-grey-7">Telefone</div>
                  <div class="text-body2 ellipsis">{{ nfe.pessoa.telefone1 }}</div>
                </div>

                <!-- FILIAL DESTINO -->
                <div class="col-6 col-sm-4 col-md-2" v-if="nfe.filial?.filial">
                  <div class="text-caption text-grey-7">Filial Destino</div>
                  <div class="text-body2 ellipsis">{{ nfe.filial.filial }}</div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Tabs: Itens / Duplicatas / Pagamentos / Detalhes -->
      <q-card flat bordered>
        <q-tabs v-model="tab" align="left" active-color="primary" indicator-color="primary">
          <q-tab name="geral" label="Itens" :badge="itens.length || undefined" />
          <q-tab name="duplicatas" label="Duplicatas" :badge="duplicatas.length || undefined" />
          <q-tab name="pagamentos" label="Pagamentos" :badge="pagamentos.length || undefined" />
          <q-tab name="icmsst" label="ICMS ST" @click="!icmsStData && handleCarregarIcmsSt()" />
          <q-tab name="detalhes" label="Detalhes" />
        </q-tabs>

        <q-separator />

        <q-tab-panels v-model="tab" animated>
          <!-- Itens -->
          <q-tab-panel name="geral">
            <!-- Toolbar: Busca + Marcar tipo -->
            <div class="row q-gutter-sm q-mb-md items-center justify-end">
              <form @submit.prevent="handleBuscarItem">
                <q-input
                  v-model="barrasInput"
                  label="Barras / Referencia"
                  outlined
                  :bottom-slots="false"
                  style="width: 200px"
                >
                  <template v-slot:prepend>
                    <q-icon name="qr_code_scanner" />
                  </template>
                </q-input>
              </form>
              <q-select
                v-model="tipoProdutoSelecionado"
                :options="tipoProdutoOptions"
                label="Tipo Produto"
                stack-label
                outlined
                clearable
                emit-value
                map-options
                :bottom-slots="false"
                style="width: 160px"
              />
              <q-btn flat dense icon="check" color="primary" @click="handleMarcarTipoProduto">
                <q-tooltip>Marcar todos itens</q-tooltip>
              </q-btn>
              <q-separator vertical class="gt-xs" />
              <q-btn
                flat
                dense
                :icon="todosConferidos ? 'task_alt' : 'radio_button_unchecked'"
                :color="todosConferidos ? 'green' : 'grey-7'"
                @click="handleConferirTodos"
              >
                <q-tooltip>
                  {{ todosConferidos ? 'Desmarcar todos' : 'Marcar todos como conferidos' }}
                </q-tooltip>
              </q-btn>
            </div>

            <q-list separator v-if="itens.length > 0">
              <q-item
                v-for="item in itens"
                :key="item.codnfeterceiroitem"
                clickable
                :class="item.conferencia ? 'bg-green-1' : ''"
                :to="{
                  name: 'nfe-terceiro-item-view',
                  params: {
                    codnfeterceiro: nfe.codnfeterceiro,
                    codnfeterceiroitem: item.codnfeterceiroitem,
                  },
                }"
              >
                <q-item-section top>
                  <q-item-label lines="1">
                    <span class="text-weight-medium">{{ item.xprod }}</span>
                    <q-badge v-if="item.conferencia" color="green" class="q-ml-sm">
                      Conferido
                    </q-badge>
                    <q-badge v-if="!item.codprodutobarra" color="red" class="q-ml-sm">
                      Sem Produto
                    </q-badge>
                  </q-item-label>
                  <q-item-label caption>
                    EAN: {{ item.cean || '-' }} | Cod: {{ item.cprod }} | NCM: {{ item.ncm }} |
                    CFOP: {{ item.cfop }}
                  </q-item-label>
                  <q-item-label caption v-if="item.produtoBarra">
                    <q-badge color="blue" class="q-mr-xs">
                      {{ item.produtoBarra.produto?.produto }}
                    </q-badge>
                    <span class="text-grey-7">{{ item.produtoBarra.variacao?.variacao }}</span>
                  </q-item-label>
                  <q-item-label caption v-if="item.complemento">
                    Complemento: R$ {{ formatCurrency(item.complemento) }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side top>
                  <q-item-label>{{ formatDecimal(item.qcom, 4) }} {{ item.ucom }}</q-item-label>
                  <q-item-label class="text-weight-bold">
                    R$ {{ formatCurrency(item.vprod) }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side top>
                  <q-btn-group flat>
                    <q-btn
                      flat
                      dense
                      round
                      size="sm"
                      :icon="item.conferencia ? 'check_circle' : 'radio_button_unchecked'"
                      :color="item.conferencia ? 'green' : 'grey'"
                      @click.stop.prevent="handleConferenciaItem(item)"
                    >
                      <q-tooltip>
                        {{ item.conferencia ? 'Desmarcar conferencia' : 'Marcar conferido' }}
                      </q-tooltip>
                    </q-btn>
                    <q-btn
                      flat
                      dense
                      round
                      size="sm"
                      icon="call_split"
                      color="primary"
                      @click.stop.prevent="handleDividirItem(item)"
                    >
                      <q-tooltip>Dividir item</q-tooltip>
                    </q-btn>
                  </q-btn-group>
                </q-item-section>
              </q-item>
            </q-list>
            <div v-else class="text-center text-grey-6 q-pa-lg">Nenhum item</div>
          </q-tab-panel>

          <!-- Duplicatas -->
          <q-tab-panel name="duplicatas">
            <q-list separator v-if="duplicatas.length > 0">
              <q-item v-for="dup in duplicatas" :key="dup.codnfeterceiroduplicata">
                <q-item-section>
                  <q-item-label>Parcela {{ dup.ndup }}</q-item-label>
                  <q-item-label caption>Vencimento: {{ formatDate(dup.dvenc) }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-item-label class="text-weight-bold">
                    R$ {{ formatCurrency(dup.vdup) }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
            <div v-else class="text-center text-grey-6 q-pa-lg">Nenhuma duplicata</div>
          </q-tab-panel>

          <!-- Pagamentos -->
          <q-tab-panel name="pagamentos">
            <q-list separator v-if="pagamentos.length > 0">
              <q-item v-for="pag in pagamentos" :key="pag.codnfeterceiropagamento">
                <q-item-section>
                  <q-item-label>Forma {{ pag.tpag }}</q-item-label>
                  <q-item-label caption>Indicador: {{ pag.indpag }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-item-label class="text-weight-bold">
                    R$ {{ formatCurrency(pag.vpag) }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
            <div v-else class="text-center text-grey-6 q-pa-lg">Nenhum pagamento</div>
          </q-tab-panel>

          <!-- ICMS ST -->
          <q-tab-panel name="icmsst">
            <div v-if="loadingIcmsSt" class="row justify-center q-pa-lg">
              <q-spinner color="primary" size="2em" />
            </div>
            <template v-else-if="icmsStData">
              <!-- Tabela de itens -->
              <q-markup-table flat bordered dense separator="cell" class="q-mb-md">
                <thead>
                  <tr>
                    <th>Produto</th>
                    <th>NCM Nota</th>
                    <th>NCM Produto</th>
                    <th>CEST</th>
                    <th class="text-right">MVA %</th>
                    <th class="text-right">Valor</th>
                    <th class="text-right">Reducao</th>
                    <th class="text-right">ICMS</th>
                    <th class="text-right">ST Nota</th>
                    <th class="text-right">ST Calc.</th>
                    <th class="text-right">Diferenca</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in icmsStData.itens" :key="item.codnfeterceiroitem">
                    <td>{{ item.xprod }}</td>
                    <td>{{ item.ncmnota }}</td>
                    <td>{{ item.ncmproduto }}</td>
                    <td :class="item.cestnota !== item.cestproduto ? 'text-red' : ''">
                      {{ item.cestproduto || item.cestnota }}
                    </td>
                    <td class="text-right">
                      {{ item.mva ? ((item.mva - 1) * 100).toFixed(2) : '-' }}
                    </td>
                    <td class="text-right">{{ formatCurrency(item.valor) }}</td>
                    <td class="text-right">
                      {{ item.reducao !== 1 ? (item.reducao * 100).toFixed(2) + '%' : '-' }}
                    </td>
                    <td class="text-right">{{ formatCurrency(item.vicms) }}</td>
                    <td class="text-right">{{ formatCurrency(item.vicmsst) }}</td>
                    <td class="text-right">{{ formatCurrency(item.vicmsstcalculado) }}</td>
                    <td
                      class="text-right"
                      :class="item.diferenca > 0.01 ? 'text-red text-weight-bold' : ''"
                    >
                      {{ formatCurrency(item.diferenca) }}
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="text-weight-bold">
                    <td colspan="8" class="text-right">Totais:</td>
                    <td class="text-right">{{ formatCurrency(icmsStData.totais.vicmsst) }}</td>
                    <td class="text-right">
                      {{ formatCurrency(icmsStData.totais.vicmsstcalculado) }}
                    </td>
                    <td
                      class="text-right"
                      :class="icmsStData.totais.diferenca > 0.01 ? 'text-red' : ''"
                    >
                      {{ formatCurrency(icmsStData.totais.diferenca) }}
                    </td>
                  </tr>
                </tfoot>
              </q-markup-table>

              <!-- Guias ST geradas -->
              <div v-if="icmsStData.guias?.length > 0" class="q-mb-md">
                <div class="text-subtitle2 q-mb-sm">Guias ST Geradas</div>
                <q-list separator bordered>
                  <q-item v-for="guia in icmsStData.guias" :key="guia.codtitulo">
                    <q-item-section>
                      <q-item-label>{{ guia.numero }}</q-item-label>
                      <q-item-label caption>
                        Vencimento: {{ formatDate(guia.vencimento) }}
                      </q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      <q-item-label class="text-weight-bold">
                        R$ {{ formatCurrency(guia.credito) }}
                      </q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      <q-btn
                        flat
                        dense
                        icon="picture_as_pdf"
                        color="red"
                        @click="handleOpenGuiaStPdf(guia.codtitulonfeterceiro)"
                      >
                        <q-tooltip>Ver PDF</q-tooltip>
                      </q-btn>
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>

              <!-- Botao gerar guia -->
              <q-btn
                v-if="icmsStData.totais.diferenca > 0.01"
                color="primary"
                icon="receipt_long"
                label="Gerar Guia ST"
                :loading="loadingAction"
                @click="handleGerarGuiaSt"
              />
            </template>
            <div v-else class="text-center text-grey-6 q-pa-lg">
              Clique para carregar dados de ICMS ST
            </div>
          </q-tab-panel>

          <!-- Detalhes -->
          <q-tab-panel name="detalhes">
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-list dense>
                  <q-item>
                    <q-item-section>
                      <q-item-label caption>Codigo</q-item-label>
                      <q-item-label>#{{ nfe.codnfeterceiro }}</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item>
                    <q-item-section>
                      <q-item-label caption>Situacao</q-item-label>
                      <q-item-label>{{ situacaoLabel(nfe.indsituacao) }}</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item>
                    <q-item-section>
                      <q-item-label caption>Autorizacao</q-item-label>
                      <q-item-label>
                        {{ nfe.nfedataautorizacao ? formatDateTime(nfe.nfedataautorizacao) : '-' }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item>
                    <q-item-section>
                      <q-item-label caption>NSU</q-item-label>
                      <q-item-label>{{ nfe.nsu || '-' }}</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item>
                    <q-item-section>
                      <q-item-label caption>Ignorada</q-item-label>
                      <q-item-label>{{ nfe.ignorada ? 'Sim' : 'Nao' }}</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item v-if="nfe.justificativa">
                    <q-item-section>
                      <q-item-label caption>Justificativa</q-item-label>
                      <q-item-label>{{ nfe.justificativa }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>
              <div class="col-12 col-md-6">
                <q-list dense>
                  <q-item v-if="nfe.informacoes">
                    <q-item-section>
                      <q-item-label caption>Informacoes Complementares</q-item-label>
                      <q-item-label>{{ nfe.informacoes }}</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item v-if="nfe.observacoes">
                    <q-item-section>
                      <q-item-label caption>Observacoes</q-item-label>
                      <q-item-label>{{ nfe.observacoes }}</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item>
                    <q-item-section>
                      <q-item-label caption>Nota Fiscal</q-item-label>
                      <q-item-label>{{ nfe.codnotafiscal || 'Vazio' }}</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item>
                    <q-item-section>
                      <q-item-label caption>Negocio</q-item-label>
                      <q-item-label>{{ nfe.codnegocio || 'Vazio' }}</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item v-if="nfe.criacao">
                    <q-item-section>
                      <q-item-label caption>Criacao</q-item-label>
                      <q-item-label>{{ formatDateTime(nfe.criacao) }}</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item v-if="nfe.alteracao">
                    <q-item-section>
                      <q-item-label caption>Ultima Alteracao</q-item-label>
                      <q-item-label>
                        {{ formatDateTime(nfe.alteracao) }}
                        <span v-if="nfe.usuarioAlteracao" class="text-grey-7">
                          por {{ nfe.usuarioAlteracao.usuario }}
                        </span>
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>
            </div>
          </q-tab-panel>
        </q-tab-panels>
      </q-card>
    </template>
  </q-page>
</template>
