<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { formataNumero, formataDataSemHora } from '@components/formatters'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { useAuthStore } from 'src/stores/auth'
import { PERMISSOES } from 'src/constants/permissoes'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { ESTADO_COBRANCA } from 'src/constants/tituloBoleto'
import SelectFilial from 'src/components/select/SelectFilial.vue'
import SelectPortador from 'src/components/select/SelectPortador.vue'
import SelectTipoTitulo from 'src/components/select/SelectTipoTitulo.vue'
import SelectContaContabil from 'src/components/select/SelectContaContabil.vue'
import SelectPessoa from 'src/components/select/SelectPessoa.vue'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const auth = useAuthStore()

// Permissão para criar/alterar/estornar e mexer em boletos.
// Visualização (carregar/PDF) é livre para qualquer usuário autenticado.
const podeMutar = computed(() =>
  auth.hasAnyPermission([PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.COBRANCA]),
)

const titulo = ref(null)
const loading = ref(false)
const saving = ref(false)

const codtitulo = computed(() => (route.params.codtitulo ? Number(route.params.codtitulo) : null))

// === Dialog de edição/duplicação ===
const dialogOpen = ref(false)
const duplicando = ref(false)
const model = ref({})

// Travas (legado MGsis): só Numero e Valor são bloqueados.
// Quando duplicando, geradoAuto/liquidado retornam false porque o novo registro
// não herda codnegocio/codtituloagrupamento e nasce com saldo positivo.
const geradoAuto = computed(
  () =>
    !duplicando.value &&
    !!(titulo.value?.codnegocioformapagamento || titulo.value?.codtituloagrupamento),
)
// Quando o título foi LIQUIDADO por um agrupamento, o vínculo está no movimento
// (não em titulo.codtituloagrupamento, que indica que ele foi GERADO por agrupamento).
const codtituloagrupamentoLiquidacao = computed(() => {
  const movs = titulo.value?.movimentos
  if (!Array.isArray(movs)) return null
  const m = movs.find((x) => x.codtituloagrupamento)
  return m?.codtituloagrupamento ?? null
})
const liquidado = computed(() => !duplicando.value && Number(titulo.value?.saldo ?? 1) === 0)
const estornado = computed(() => !!titulo.value?.estornado)

// Estornar segue a mesma regra de geradoAuto: títulos gerados automaticamente
// (vinculados a negócio ou agrupamento) só podem ser estornados pela origem.
const podeEstornar = computed(
  () =>
    podeMutar.value &&
    titulo.value &&
    !estornado.value &&
    !geradoAuto.value &&
    Number(titulo.value.saldo) !== 0,
)

const podeEditar = computed(() => podeMutar.value && titulo.value && !estornado.value)

// === Helpers ===
const formatCodigo = (v) => (v ? '#' + String(v).padStart(8, '0') : '')

function toDateInput(v) {
  return v ? String(v).slice(0, 10) : null
}

function modelFromTitulo(t) {
  return {
    codtitulo: t.codtitulo,
    codfilial: t.codfilial,
    codpessoa: t.codpessoa,
    codtipotitulo: t.codtipotitulo,
    codcontacontabil: t.codcontacontabil,
    codportador: t.codportador,
    numero: t.numero || '',
    fatura: t.fatura || '',
    valor: t.valor != null ? Math.abs(t.valor) : null,
    emissao: toDateInput(t.emissao),
    transacao: toDateInput(t.transacao),
    vencimento: toDateInput(t.vencimento),
    vencimentooriginal: toDateInput(t.vencimentooriginal),
    gerencial: !!t.gerencial,
    observacao: t.observacao || '',
  }
}

function abrirDialogEditar() {
  if (!titulo.value) return
  duplicando.value = false
  model.value = modelFromTitulo(titulo.value)
  dialogOpen.value = true
}

function abrirDialogDuplicar() {
  if (!titulo.value) return
  duplicando.value = true
  // Copia dados do título atual mas zera o codtitulo (será POST/criação).
  // Mantém numero, fatura, datas etc. (mesmo padrão do legado MGsis).
  model.value = {
    ...modelFromTitulo(titulo.value),
    codtitulo: null,
  }
  dialogOpen.value = true
}

// === Carregar registro ===
async function carregar() {
  if (!codtitulo.value) return
  loading.value = true
  try {
    const { data } = await api.get(`v1/titulo/${codtitulo.value}`)
    titulo.value = data.data
  } catch (e) {
    notifyError(e, 'Erro ao carregar título')
    titulo.value = null
  } finally {
    loading.value = false
  }
}

// === Salvar ===
async function salvar() {
  saving.value = true
  try {
    const payload = {
      codfilial: model.value.codfilial,
      codpessoa: model.value.codpessoa,
      codtipotitulo: model.value.codtipotitulo,
      codcontacontabil: model.value.codcontacontabil,
      codportador: model.value.codportador || null,
      numero: model.value.numero || null,
      fatura: model.value.fatura || null,
      valor: model.value.valor,
      emissao: model.value.emissao,
      transacao: model.value.transacao,
      vencimento: model.value.vencimento,
      vencimentooriginal: model.value.vencimentooriginal,
      gerencial: !!model.value.gerencial,
      observacao: model.value.observacao || null,
    }
    if (duplicando.value) {
      const { data } = await api.post('v1/titulo', payload)
      notifySuccess('Título criado')
      dialogOpen.value = false
      duplicando.value = false
      router.replace({ name: 'titulo-detalhe', params: { codtitulo: data.data.codtitulo } })
    } else {
      const { data } = await api.put(`v1/titulo/${model.value.codtitulo}`, payload)
      titulo.value = data.data
      notifySuccess('Título atualizado')
      dialogOpen.value = false
    }
  } catch (e) {
    notifyError(e, 'Erro ao salvar')
  } finally {
    saving.value = false
  }
}

// === Boleto BB ===
function bbCriar() {
  $q.dialog({
    title: 'Novo Boleto',
    message: 'Deseja criar um novo boleto para este título?',
    cancel: true,
  }).onOk(async () => {
    try {
      await api.post(`v1/titulo/${codtitulo.value}/boleto-bb`)
      notifySuccess('Boleto criado')
      await carregar()
    } catch (e) {
      notifyError(e, 'Erro ao criar boleto')
    }
  })
}

async function bbConsultar(b) {
  try {
    await api.post(`v1/titulo/${codtitulo.value}/boleto-bb/${b.codtituloboleto}/consultar`)
    notifySuccess('Boleto consultado')
    await carregar()
  } catch (e) {
    notifyError(e, 'Erro ao consultar boleto')
  }
}

function bbBaixar(b) {
  $q.dialog({
    title: 'Baixar Boleto',
    message: 'Confirma baixar (cancelar) este boleto no banco?',
    cancel: true,
  }).onOk(async () => {
    try {
      await api.post(`v1/titulo/${codtitulo.value}/boleto-bb/${b.codtituloboleto}/baixar`)
      notifySuccess('Boleto baixado')
      await carregar()
    } catch (e) {
      notifyError(e, 'Erro ao baixar boleto')
    }
  })
}

function bbAbrirPdf(b) {
  const url = `${process.env.API_URL}v1/titulo/${codtitulo.value}/boleto-bb/${b.codtituloboleto}/pdf`
  window.open(url, '_blank')
}

async function abrirDanfe(codnotafiscal) {
  try {
    const { data } = await api.get(`v1/nota-fiscal/${codnotafiscal}/danfe`, {
      responseType: 'blob',
    })
    const url = URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
    window.open(url, '_blank')
  } catch (e) {
    notifyError(e, 'Erro ao abrir DANFE')
  }
}

function bbEstadoCor(estado) {
  if (estado === 6) return 'green-7' // LIQUIDADO
  if (estado === 7) return 'red-7' // BAIXADO
  if ([14, 80].includes(estado)) return 'orange-7' // EM LIQUIDACAO / PROCESSAMENTO
  return 'blue-7'
}

// === Outras ações ===
function estornar() {
  $q.dialog({
    title: 'Estornar',
    message: 'Confirma estornar este título?',
    cancel: true,
  }).onOk(async () => {
    try {
      const { data } = await api.post(`v1/titulo/${codtitulo.value}/estornar`)
      titulo.value = data.data
      notifySuccess('Título estornado')
    } catch (e) {
      notifyError(e, 'Erro ao estornar')
    }
  })
}

const urlPessoa = (codpessoa) =>
  codpessoa ? `${process.env.PESSOAS_URL}/pessoa/${codpessoa}` : null
const urlNegocio = (codnegocio) =>
  codnegocio ? `${process.env.NEGOCIOS_URL}/negocio/${codnegocio}` : null
const urlAgrupamento = (codtituloagrupamento) =>
  codtituloagrupamento ? `/agrupamento/${codtituloagrupamento}` : null

// === Estilos derivados ===
// Parseia YYYY-MM-DD como data local (evita o offset UTC do `new Date(string)`).
function parseDateLocal(s) {
  if (!s) return null
  const [y, m, d] = String(s).slice(0, 10).split('-').map(Number)
  return new Date(y, m - 1, d)
}

const movimentosOrdenados = computed(() => {
  const lista = titulo.value?.movimentos
  if (!lista) return []
  return [...lista].sort((a, b) => {
    const ta = a.transacao || ''
    const tb = b.transacao || ''
    if (ta !== tb) return ta < tb ? -1 : 1
    return (a.codmovimentotitulo ?? 0) - (b.codmovimentotitulo ?? 0)
  })
})

const classeVencimento = computed(() => {
  if (!titulo.value || Number(titulo.value.saldo) === 0) return 'text-grey-6'
  const venc = parseDateLocal(titulo.value.vencimento)
  if (!venc) return 'text-grey-6'
  const hojeD = new Date()
  hojeD.setHours(0, 0, 0, 0)
  const atraso = Math.floor((hojeD - venc) / 86400000)
  if (atraso > 5) return 'text-red'
  if (atraso > 0) return 'text-orange'
  return 'text-green'
})

onMounted(carregar)
watch(() => route.fullPath, carregar)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1000px; margin: auto">
      <template v-if="titulo">
        <!-- Cabeçalho: voltar + número + fatura -->
        <q-item class="q-pb-md q-px-none">
          <q-item-section avatar>
            <q-btn
              flat
              dense
              round
              icon="arrow_back"
              :to="{ name: 'titulo' }"
              aria-label="Voltar"
            />
          </q-item-section>
          <q-item-section>
            <div class="text-h4 text-grey-9 ellipsis">
              {{ titulo.numero }}
            </div>
            <div class="text-h6 text-grey-7 ellipsis" v-if="titulo.fatura">
              {{ titulo.fatura }}
            </div>
          </q-item-section>
        </q-item>

        <!-- Cards resumo -->
        <div class="row q-col-gutter-md q-mb-md">
          <!-- FILIAL -->
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Filial</div>
              <div class="text-h6" :class="titulo.gerencial ? 'text-orange' : 'text-green'">
                {{ titulo.filial }}
              </div>
              <div class="text-caption text-grey-7">
                {{ titulo.gerencial ? 'Gerencial' : 'Fiscal' }}
              </div>
            </q-card>
          </div>

          <!-- EMISSAO -->
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Emissão</div>
              <div class="text-h6 text-grey-7">
                {{ formataDataSemHora(titulo.emissao) }}
              </div>
              <div class="text-caption text-grey-7">
                Transação
                {{ formataDataSemHora(titulo.transacao) }}
              </div>
            </q-card>
          </div>

          <!-- VENCIMENTO -->
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Vencimento</div>
              <div class="text-h6" :class="classeVencimento">
                {{ formataDataSemHora(titulo.vencimento) }}
              </div>
              <div class="text-caption text-grey-7">
                Original
                {{ formataDataSemHora(titulo.vencimentooriginal) }}
              </div>
            </q-card>
          </div>

          <!-- SALDO / VALOR -->
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Saldo</div>
              <div class="text-h6" :class="classeVencimento">
                <template v-if="Number(titulo.saldo) !== 0">
                  {{ formataNumero(Math.abs(titulo.saldo)) }} {{ titulo.operacaosaldo }}
                </template>
                <span v-else-if="estornado" class="text-negative">Estornado</span>
                <span v-else class="text-grey-6">Liquidado</span>
              </div>

              <div
                class="text-caption text-grey-7"
                v-if="titulo.vencimentooriginal !== titulo.vencimento - 1"
              >
                Original
                {{ formataNumero(Math.abs(titulo.valor)) }} {{ titulo.operacao }}
              </div>
            </q-card>
          </div>
        </div>

        <!-- Card Detalhes + Saldo -->
        <q-card bordered flat>
          <q-card-section class="text-grey-9 text-overline row items-center">
            DETALHES DO TÍTULO
            <q-space />
            <q-btn
              v-if="podeEditar"
              flat
              round
              dense
              icon="edit"
              size="sm"
              color="grey-7"
              @click="abrirDialogEditar"
            >
              <q-tooltip>Editar</q-tooltip>
            </q-btn>
            <q-btn
              flat
              round
              dense
              icon="content_copy"
              size="sm"
              color="grey-7"
              @click="abrirDialogDuplicar"
            >
              <q-tooltip>Duplicar</q-tooltip>
            </q-btn>
            <q-btn
              v-if="podeEstornar"
              flat
              round
              dense
              icon="undo"
              size="sm"
              color="grey-7"
              @click="estornar"
            >
              <q-tooltip>Estornar</q-tooltip>
            </q-btn>
            <MgInfoCriacao
              :usuariocriacao="titulo.usuariocriacao"
              :criacao="titulo.criacao"
              :usuarioalteracao="titulo.usuarioalteracao"
              :alteracao="titulo.alteracao"
            />
          </q-card-section>

          <!-- Detalhes -->
          <div class="row q-col-gutter-lg q-pa-md">
            <!-- ID -->
            <div class="col-xs-6 col-sm-4 col-md-2">
              <div class="text-overline text-grey-7">#</div>
              <div class="text-body2">
                {{ formatCodigo(titulo.codtitulo) }}
              </div>
            </div>

            <!-- TIPO -->
            <div class="col-xs-6 col-sm-4 col-md-2">
              <div class="text-overline text-grey-7">Tipo de Título</div>
              <div class="text-body2">
                {{ titulo.tipotitulo }}
              </div>
            </div>

            <!-- PESSOA -->
            <div class="col-xs-6 col-sm-4 col-md-2 ellipsis">
              <div class="text-overline text-grey-7">Pessoa</div>
              <div class="text-body2">
                <q-btn
                  flat
                  dense
                  no-caps
                  padding="0"
                  color="primary"
                  :label="titulo.fantasia"
                  :href="urlPessoa(titulo.codpessoa)"
                  target="_blank"
                />
              </div>
            </div>

            <!-- CONTA -->
            <div class="col-xs-6 col-sm-4 col-md-2">
              <div class="text-overline text-grey-7">Conta Contábil</div>
              <div class="text-body2">
                {{ titulo.contacontabil }}
              </div>
            </div>

            <!-- PORTADOR -->
            <div class="col-xs-6 col-sm-4 col-md-2">
              <div class="text-overline text-grey-7">Portador</div>
              <div class="text-body2">
                {{ titulo.portador }}
              </div>
            </div>

            <!-- LIQUIDACAO -->
            <div class="col-xs-6 col-sm-4 col-md-2" v-if="titulo.transacaoliquidacao">
              <div class="text-overline text-grey-7">Liquidação</div>
              <div class="text-body2">{{ formataDataSemHora(titulo.transacaoliquidacao) }}</div>
            </div>
          </div>
        </q-card>

        <q-banner v-if="geradoAuto" class="bg-blue-1 text-blue-9 q-mt-md" flat bordered rounded>
          <template #avatar><q-icon name="info" /></template>
          Título gerado automaticamente!
          <q-btn
            v-if="titulo?.codnegocio"
            flat
            dense
            no-caps
            padding="0 4px"
            color="primary"
            :href="urlNegocio(titulo.codnegocio)"
            target="_blank"
            :label="`Negócio ${formatCodigo(titulo.codnegocio)}`"
          />
          <q-btn
            v-if="titulo?.codtituloagrupamento"
            flat
            dense
            no-caps
            padding="0 4px"
            color="primary"
            :to="{ name: 'agrupamento-detalhe', params: { id: titulo.codtituloagrupamento } }"
            :label="`Agrupamento ${formatCodigo(titulo.codtituloagrupamento)}`"
          />
        </q-banner>

        <q-banner
          v-if="codtituloagrupamentoLiquidacao && !titulo?.codtituloagrupamento"
          class="bg-blue-1 text-blue-9 q-mt-md"
          flat
          bordered
          rounded
        >
          <template #avatar><q-icon name="info" /></template>
          Título liquidado por agrupamento!
          <q-btn
            flat
            dense
            no-caps
            padding="0 4px"
            color="primary"
            :to="{
              name: 'agrupamento-detalhe',
              params: { id: codtituloagrupamentoLiquidacao },
            }"
            :label="`Agrupamento ${formatCodigo(codtituloagrupamentoLiquidacao)}`"
          />
        </q-banner>

        <!-- OBSERVACAOES -->
        <template v-if="titulo.observacao">
          <q-card bordered flat class="q-mt-md">
            <div class="row q-col-gutter-sm q-pa-md">
              <div class="col-12">
                <div class="text-overline text-grey-7">Observações</div>
                <div
                  class="text-body2 bg-grey-2 rounded-borders q-pa-sm"
                  style="white-space: pre-line"
                >
                  {{ titulo.observacao }}
                </div>
              </div>
            </div>
          </q-card>
        </template>

        <div class="row q-col-gutter-md">
          <!-- Card Movimentos -->
          <div class="col-xs-12 col-sm-6">
            <q-card bordered flat class="q-mt-md">
              <q-card-section class="text-grey-9 text-overline">
                MOVIMENTOS ({{ titulo.movimentos.length }})
              </q-card-section>

              <q-list separator>
                <q-item v-for="m in movimentosOrdenados" :key="m.codmovimentotitulo">
                  <!-- Transacao -->
                  <q-item-section style="flex: 0 0 70px; min-width: 0">
                    <q-item-label caption>{{ formataDataSemHora(m.transacao) }}</q-item-label>
                    <q-item-label caption>
                      {{ m.tipomovimentotitulo }}
                    </q-item-label>
                  </q-item-section>

                  <!-- VALOR -->
                  <q-item-section class="text-right">
                    <q-item-label
                      class="text-weight-bold"
                      :class="m.operacao === 'CR' ? 'text-orange' : 'text-green'"
                    >
                      {{ formataNumero(Math.abs(m.valor)) }} {{ m.operacao }}
                    </q-item-label>
                    <q-item-label caption class="ellipsis">
                      <q-btn
                        v-if="m.codnegocio"
                        flat
                        dense
                        no-caps
                        size="sm"
                        padding="0 4px"
                        color="primary"
                        :href="urlNegocio(m.codnegocio)"
                        target="_blank"
                        :label="`Negócio ${formatCodigo(m.codnegocio)}`"
                      />
                      <q-btn
                        v-if="m.codliquidacaotitulo"
                        flat
                        dense
                        no-caps
                        size="sm"
                        padding="0 4px"
                        color="primary"
                        :to="{
                          name: 'liquidacao-titulo-detalhe',
                          params: { id: m.codliquidacaotitulo },
                        }"
                        :label="`Liquidação ${formatCodigo(m.codliquidacaotitulo)}`"
                      />
                      <q-btn
                        v-if="m.codtituloagrupamento"
                        flat
                        dense
                        no-caps
                        size="sm"
                        padding="0 4px"
                        color="primary"
                        :href="urlAgrupamento(m.codtituloagrupamento)"
                        target="_blank"
                        :label="`Agrupamento ${formatCodigo(m.codtituloagrupamento)}`"
                      />
                      <span v-if="m.codboletoretorno">Retorno Boleto</span>
                      <span v-if="m.codcobranca">Cobrança</span>
                    </q-item-label>
                    <q-item-label v-if="m.portador" caption>{{ m.portador }}</q-item-label>
                  </q-item-section>

                  <q-item-section side>
                    <MgInfoCriacao :usuariocriacao="m.usuariocriacao" :criacao="m.criacao" />
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <!-- Card Boleto BB -->
          <div class="col-xs-12 col-sm-6">
            <q-card bordered flat class="q-mt-md">
              <q-card-section class="text-grey-9 text-overline row items-center">
                BOLETO BB ({{ titulo.boletos?.length || 0 }})
                <q-space />
                <q-btn
                  v-if="podeMutar"
                  flat
                  round
                  dense
                  icon="add"
                  size="sm"
                  color="primary"
                  @click="bbCriar"
                >
                  <q-tooltip>Novo Boleto</q-tooltip>
                </q-btn>
              </q-card-section>

              <q-list separator v-if="titulo.boletos?.length">
                <q-item
                  v-for="b in titulo.boletos"
                  :key="b.codtituloboleto"
                  :class="{ 'bg-grey-2': !!b.inativo }"
                >
                  <q-item-section>
                    <!-- NOSSO NUMERO/STATUS -->
                    <q-item-label>
                      <span class="text-weight-medium">{{ b.nossonumero }}</span>
                      &nbsp;
                      <q-badge :color="bbEstadoCor(b.estadotitulocobranca)">
                        {{ ESTADO_COBRANCA[b.estadotitulocobranca] || b.estadotitulocobranca }}
                      </q-badge>
                    </q-item-label>

                    <!-- PORTADOR -->
                    <q-item-label caption>
                      {{ b.portador }}
                    </q-item-label>

                    <!-- VENCIMENTO -->
                    <q-item-label caption>
                      Vencimento {{ formataDataSemHora(b.vencimento) }}
                    </q-item-label>

                    <!-- REGISTRO -->
                    <q-item-label caption v-if="b.dataregistro">
                      Registro {{ formataDataSemHora(b.dataregistro) }}
                    </q-item-label>

                    <!-- RECEBIMENTO -->
                    <q-item-label caption v-if="b.datarecebimento">
                      Recebimento {{ formataDataSemHora(b.datarecebimento) }}
                    </q-item-label>

                    <!-- CREDITO -->
                    <q-item-label caption v-if="b.datacredito">
                      Crédito {{ formataDataSemHora(b.datacredito) }}
                    </q-item-label>

                    <!-- BAIXA -->
                    <q-item-label caption v-if="b.databaixaautomatica">
                      Baixa {{ formataDataSemHora(b.databaixaautomatica) }}
                    </q-item-label>

                    <!-- VALOR ORIGINAL -->
                    <q-item-label caption>
                      Original {{ formataNumero(b.valororiginal) }}
                    </q-item-label>

                    <!-- VALOR ATUAL -->
                    <q-item-label caption> Atual {{ formataNumero(b.valoratual) }} </q-item-label>

                    <!-- VALOR PAGO -->
                    <q-item-label caption v-if="b.valorpago > 0" class="text-green">
                      Pago {{ formataNumero(b.valorpago) }}
                    </q-item-label>

                    <!-- VALOR JUROS MORA -->
                    <q-item-label caption v-if="b.valorjuromora > 0" class="text-orange">
                      Juros {{ formataNumero(b.valorjuromora) }}
                    </q-item-label>

                    <!-- VALOR MULTA -->
                    <q-item-label caption v-if="b.valormulta > 0" class="text-orange">
                      Multa {{ formataNumero(b.valormulta) }}
                    </q-item-label>

                    <!-- VALOR DESCONTO -->
                    <q-item-label caption v-if="b.valordesconto > 0" class="text-blue">
                      Desconto {{ formataNumero(b.valordesconto) }}
                    </q-item-label>

                    <!-- VALOR OUTRO -->
                    <q-item-label caption v-if="b.valoroutro > 0">
                      Outro {{ formataNumero(b.valoroutro) }}
                    </q-item-label>
                  </q-item-section>

                  <!-- Ações -->
                  <q-item-section side>
                    <div class="row q-gutter-xs">
                      <q-btn
                        flat
                        dense
                        round
                        size="sm"
                        icon="picture_as_pdf"
                        color="primary"
                        @click="bbAbrirPdf(b)"
                      >
                        <q-tooltip>Abrir Boleto</q-tooltip>
                      </q-btn>
                      <q-btn
                        v-if="podeMutar"
                        flat
                        dense
                        round
                        size="sm"
                        icon="refresh"
                        color="grey-7"
                        @click="bbConsultar(b)"
                      >
                        <q-tooltip>Consultar</q-tooltip>
                      </q-btn>
                      <q-btn
                        v-if="podeMutar"
                        flat
                        dense
                        round
                        size="sm"
                        icon="block"
                        color="red-7"
                        @click="bbBaixar(b)"
                      >
                        <q-tooltip>Baixar</q-tooltip>
                      </q-btn>
                    </div>
                  </q-item-section>
                </q-item>
              </q-list>
              <q-card-section v-else class="text-center text-grey-6 q-pa-md">
                Nenhum boleto emitido
              </q-card-section>
            </q-card>

            <!-- Card Notas Fiscais -->
            <q-card v-if="titulo.notas?.length" bordered flat class="q-mt-md">
              <q-card-section class="text-grey-9 text-overline">
                NOTAS FISCAIS ({{ titulo.notas.length }})
              </q-card-section>
              <q-list separator>
                <q-item
                  v-for="n in titulo.notas"
                  :key="n.codnotafiscal"
                  clickable
                  v-ripple
                  @click="abrirDanfe(n.codnotafiscal)"
                >
                  <q-item-section avatar>
                    <q-icon name="picture_as_pdf" color="red-7" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>
                      Nota {{ n.numero }} — Mod {{ n.modelo }} / Sér {{ n.serie }}
                    </q-item-label>
                    <q-item-label caption>
                      {{ n.filial }} • Emissão {{ formataDataSemHora(n.emissao) }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-item-label class="text-weight-bold">
                      {{ formataNumero(n.valortotal) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>
        </div>

        <template v-if="titulo.movimentos?.length"> </template>
      </template>
    </div>

    <q-inner-loading :showing="loading" color="primary" />

    <!-- Dialog de duplicar / editar -->
    <q-dialog v-model="dialogOpen">
      <q-card bordered flat style="width: 700px; max-width: 95vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ duplicando ? 'DUPLICAR TÍTULO' : 'EDITAR TÍTULO' }}
        </q-card-section>
        <q-form @submit.prevent="salvar">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <!-- FILIAL -->
              <div class="col-7 col-sm-4">
                <SelectFilial
                  v-model="model.codfilial"
                  outlined
                  label="Filial"
                  :rules="[(v) => !!v || 'Obrigatório']"
                  autofocus
                />
              </div>

              <!-- GERENAICL -->
              <div class="col-5 col-sm-3">
                <q-select
                  v-model="model.gerencial"
                  outlined
                  label="Gerencial"
                  :options="[
                    { label: 'Gerencial', value: true },
                    { label: 'Fiscal', value: false },
                  ]"
                  emit-value
                  map-options
                />
              </div>

              <!-- TIPO -->
              <div class="col-12 col-sm-5">
                <SelectTipoTitulo
                  v-model="model.codtipotitulo"
                  outlined
                  label="Tipo de Título"
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>

              <!-- NUMERO -->
              <div class="col-6 col-sm-4">
                <q-input
                  v-model="model.numero"
                  outlined
                  label="Número"
                  maxlength="20"
                  :bg-color="geradoAuto ? 'grey-3' : ''"
                  :readonly="geradoAuto"
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>

              <!-- FATURA -->
              <div class="col-6 col-sm-5">
                <q-input v-model="model.fatura" outlined label="Fatura" maxlength="50" />
              </div>

              <!-- VALOR -->
              <div class="col-12 col-sm-3">
                <MgInputValor
                  v-model="model.valor"
                  label="Valor"
                  prefix="R$"
                  :bg-color="geradoAuto ? 'grey-3' : ''"
                  :readonly="geradoAuto || liquidado"
                  :rules="[(v) => (v && Number(v) > 0) || 'Obrigatório']"
                />
              </div>

              <!-- EMISSAO -->
              <div class="col-6 col-sm-3">
                <MgInputData
                  v-model="model.emissao"
                  type="date"
                  label="Emissão"
                  stack-label
                  :bg-color="geradoAuto ? 'grey-3' : ''"
                  :readonly="geradoAuto"
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>

              <!-- TRANSACAO -->
              <div class="col-6 col-sm-3">
                <MgInputData
                  v-model="model.transacao"
                  type="date"
                  label="Transação"
                  stack-label
                  :bg-color="geradoAuto ? 'grey-3' : ''"
                  :readonly="geradoAuto"
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>

              <!-- VENCIMENTO ORIGINAL -->
              <div class="col-6 col-sm-3">
                <MgInputData
                  v-model="model.vencimentooriginal"
                  type="date"
                  label="Vencimento Original"
                  stack-label
                  :bg-color="geradoAuto ? 'grey-3' : ''"
                  :readonly="geradoAuto"
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>

              <!-- VENCIMENTO -->
              <div class="col-6 col-sm-3">
                <MgInputData
                  v-model="model.vencimento"
                  type="date"
                  label="Vencimento"
                  stack-label
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>

              <!-- PESSOA -->
              <div class="col-12">
                <SelectPessoa
                  v-model="model.codpessoa"
                  outlined
                  label="Pessoa"
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>

              <!-- CONTA -->
              <div class="col-12 col-sm-6">
                <SelectContaContabil
                  v-model="model.codcontacontabil"
                  outlined
                  label="Conta Contábil"
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>

              <!-- PORTADOR -->
              <div class="col-12 col-sm-6">
                <SelectPortador v-model="model.codportador" outlined clearable label="Portador" />
              </div>

              <!-- OBSERVACOES -->
              <div class="col-12">
                <q-input
                  v-model="model.observacao"
                  outlined
                  type="textarea"
                  label="Observação"
                  maxlength="255"
                  autogrow
                />
              </div>
            </div>
          </q-card-section>
          <q-separator inset />
          <q-card-actions align="right" class="text-primary">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn flat label="Salvar" type="submit" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
