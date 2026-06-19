<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { abrirPdf } from '@components/abrirPdf'
import { useCadastro } from 'src/composables/useCadastro'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'
import MgContratoForm from 'components/MgContratoForm.vue'
import MgContratoParcelasDialog from 'components/MgContratoParcelasDialog.vue'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const cod = Number(route.params.codcontrato)

const contratoCad = useCadastro('contrato', 'codcontrato', 'Contrato')
const fixCad = useCadastro(`contrato/${cod}/fixacao`, 'codfixacao', 'Fixação')
const pagCad = useCadastro(`contrato/${cod}/pagamento`, 'codpagamento', 'Pagamento')

const contrato = ref(null)
const carregando = ref(false)
const naturezas = ref([])

const moedas = [
  { label: 'R$', value: 'BRL' },
  { label: 'US$', value: 'USD' },
]
const corTipo = { FIXO: 'green-7', FIXAR: 'orange-8', BARTER: 'deep-purple-6' }

// Destino do voltar: a safra do contrato (centro de comando); fallback início.
const voltarTo = computed(() =>
  contrato.value?.codsafra
    ? { name: 'safra-detalhe', params: { codsafra: contrato.value.codsafra } }
    : { name: 'home' },
)
const ehFixo = computed(() => contrato.value?.tipo === 'FIXO')
// Cálculo do líquido (motor fiscal do agro) — vem do show do contrato.
const calculo = computed(() => contrato.value?.calculo || null)

function n(v) {
  return Number(v) || 0
}
function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}
function rs(v) {
  return 'R$ ' + fmt(v, 2)
}
function fmtData(d) {
  if (!d) return ''
  const [a, m, dia] = d.slice(0, 10).split('-')
  return `${dia}/${m}/${a}`
}

// ---- Totais / reconciliação ----
// Unidade de trabalho = KG. O contrato negocia em sacas (quantidade) + R$/saca;
// o embarque grava kg. Ponte: pesosaca da cultura (default 60). Dashboards
// mostram kg primário + sacas derivadas.
const pesosaca = computed(() => n(contrato.value?.pesosaca) || 60)
const volumeemaberto = computed(() => !!contrato.value?.volumeemaberto)
const contratado = computed(() => n(contrato.value?.quantidade)) // sc negociadas
const contratadokg = computed(() => n(contrato.value?.contratadokg))
const carregadokg = computed(() => n(contrato.value?.carregadokg))
const carregadosc = computed(() => n(contrato.value?.carregadosc))
// saldo em kg; null/sem teto quando contrato volumeemaberto (leva o saldo do silo).
const saldokg = computed(() =>
  volumeemaberto.value ? null : Math.max(0, contratadokg.value - carregadokg.value),
)
const fixado = computed(() => n(contrato.value?.fixado))
const afixar = computed(() => contratado.value - fixado.value)
const valornf = computed(() => n(contrato.value?.valornf))
// Parcelas: previsto = Σ valor; pago = Σ valor REalmente recebido.
const previsto = computed(() => pagamentos.value.reduce((s, p) => s + n(p.valor), 0))
const pago = computed(() => pagamentos.value.reduce((s, p) => s + n(p.valorrecebido), 0))

// Preço médio ponderado das fixações ativas. Com a normalização, o FIXO também
// tem fixação (espelho), então não há mais caso especial por tipo.
const precoMedio = computed(() => {
  const fx = fixacoes.value
  const q = fx.reduce((s, f) => s + n(f.quantidade), 0)
  const v = fx.reduce((s, f) => s + n(f.quantidade) * n(f.precoreal), 0)
  return q > 0 ? v / q : 0
})
// precoMedio é R$/saca; o carregado físico está em sacas derivadas (carregadosc).
const valorCarregado = computed(() => carregadosc.value * precoMedio.value)

// "Bate?" — valor carregado x NFs x pago (tolerância de centavos)
const difNf = computed(() => valornf.value - valorCarregado.value)
const difPago = computed(() => pago.value - valornf.value)
const bate = computed(
  () => Math.abs(difNf.value) < 1 && Math.abs(difPago.value) < 1 && valornf.value > 0,
)

const fixacoes = computed(() => (contrato.value?.ContratoFixacaoS || []).filter((f) => !f.inativo))
const pagamentos = computed(() =>
  (contrato.value?.ContratoPagamentoS || []).filter((p) => !p.inativo),
)
// Entregas = movimentos deste contrato no extrato (cada carga que o moveu).
const entregas = computed(() => contrato.value?.MovimentoGraoS || [])

async function recarregar() {
  carregando.value = true
  try {
    const { data } = await api.get(`v1/contrato/${cod}`)
    contrato.value = data.data ?? data
  } finally {
    carregando.value = false
  }
}

// ---- Fixação ----
function novaFixacao() {
  fixCad.abrirNovo({ data: new Date().toISOString().slice(0, 10), moeda: 'BRL' })
}
async function salvarFixacao() {
  await fixCad.salvar((f) => ({
    data: f.data,
    quantidade: f.quantidade,
    preco: f.preco,
    moeda: f.moeda,
    dolar: f.moeda === 'USD' ? f.dolar : null,
  }))
  await recarregar()
}
async function excluirFixacao(f) {
  fixCad.excluir(f)
  setTimeout(recarregar, 400)
}
const previewPrecoReal = computed(() => {
  const f = fixCad.form
  if (!f.preco) return null
  return f.moeda === 'USD' && f.dolar ? n(f.preco) * n(f.dolar) : n(f.preco)
})

// Líquido da fixação ao vivo (importante na negociação) — calcula o líquido do
// preço bruto travado, com a cultura/isenção/funrural do contrato.
const fixCalc = ref(null)
async function recalcularFix() {
  const bruto = n(previewPrecoReal.value)
  if (!contrato.value?.codcultura || bruto <= 0) {
    fixCalc.value = null
    return
  }
  try {
    const { data } = await api.get('v1/contrato/calculo', {
      params: {
        codcultura: contrato.value.codcultura,
        bruto,
        data: fixCad.form.data || undefined,
        isentofethab: contrato.value.isentofethab ? 1 : 0,
        funruralvenda: contrato.value.Filial?.funruralvenda ? 1 : 0,
      },
    })
    fixCalc.value = data
  } catch {
    fixCalc.value = null
  }
}
watch(
  () => [fixCad.form.preco, fixCad.form.dolar, fixCad.form.moeda, fixCad.form.data, fixCad.dialog],
  () => {
    if (fixCad.dialog) recalcularFix()
  },
)

// ---- Parcela / Pagamento ----
const modosParcela = [
  { label: 'Valor', value: 'VALOR' },
  { label: 'Sacas', value: 'SACAS' },
]
// Líquido médio/sc do contrato (motor) — base p/ sugerir o valor da parcela.
const liquidoSc = computed(() => n(calculo.value?.liquido))
function arred(v) {
  return Math.round(n(v) * 100) / 100
}
// O botão "+" das parcelas abre o gerador de várias parcelas de uma vez
// (MgContratoParcelasDialog). A edição de uma parcela existente segue no
// pagCad.dialog (form único).
const parcelasDialog = ref(false)
// Em modo SACAS, o valor previsto acompanha as sacas (× líquido/sc).
watch(
  () => [pagCad.form.sacas, pagCad.form.modo],
  () => {
    if (pagCad.dialog && pagCad.form.modo === 'SACAS') {
      pagCad.form.valor = arred(n(pagCad.form.sacas) * liquidoSc.value)
    }
  },
)
async function salvarPagamento() {
  await pagCad.salvar()
  await recarregar()
}
async function excluirPagamento(p) {
  pagCad.excluir(p)
  setTimeout(recarregar, 400)
}

// Confirmação de recebimento (valor real pode divergir do previsto).
const confirmDialog = ref(false)
const confirmSalvando = ref(false)
const confirmForm = ref({})
function abrirConfirmar(p) {
  confirmForm.value = {
    codcontratopagamento: p.codcontratopagamento,
    datarecebido: new Date().toISOString().slice(0, 10),
    valorrecebido: p.valor,
    codportador: p.codportador || contrato.value?.codportador || null,
  }
  confirmDialog.value = true
}
async function confirmarRecebimento() {
  if (confirmSalvando.value) return
  confirmSalvando.value = true
  try {
    const f = confirmForm.value
    await api.post(`v1/contrato/${cod}/pagamento/${f.codcontratopagamento}/confirmar`, {
      datarecebido: f.datarecebido,
      valorrecebido: f.valorrecebido,
      codportador: f.codportador,
    })
    notifySuccess('Recebimento confirmado!')
    confirmDialog.value = false
    await recarregar()
  } catch (e) {
    notifyError(e)
  } finally {
    confirmSalvando.value = false
  }
}

// ---- Anexos (PDFs) ----
const anexos = ref([])
const novoAnexo = ref(null)
const enviandoAnexo = ref(false)
async function carregarAnexos() {
  try {
    const { data } = await api.get(`v1/contrato/${cod}/anexo`)
    anexos.value = data
  } catch {
    anexos.value = []
  }
}
async function enviarAnexo() {
  if (!novoAnexo.value || enviandoAnexo.value) return
  enviandoAnexo.value = true
  try {
    const fd = new FormData()
    fd.append('arquivo', novoAnexo.value)
    fd.append('label', novoAnexo.value.name)
    await api.post(`v1/contrato/${cod}/anexo`, fd)
    notifySuccess('Anexo enviado!')
    novoAnexo.value = null
    await carregarAnexos()
  } catch (e) {
    notifyError(e)
  } finally {
    enviandoAnexo.value = false
  }
}
// Visualiza inline (mesmo método do negócios: abrirPdf abre num visualizador).
// PDF -> visualizador; imagem -> nova aba (abrirPdf força application/pdf).
async function visualizarAnexo(a) {
  const url = `v1/contrato/${cod}/anexo/${a.nome}/download`
  if (a.tipo === 'pdf' || /\.pdf$/i.test(a.nome)) {
    await abrirPdf(api, url, {}, { title: a.label || 'Anexo', size: 'a4' })
    return
  }
  try {
    const { data } = await api.get(url, { responseType: 'blob', skipLoading: true })
    const blobUrl = URL.createObjectURL(data)
    window.open(blobUrl, '_blank')
    setTimeout(() => URL.revokeObjectURL(blobUrl), 30000)
  } catch (e) {
    notifyError(e)
  }
}

async function baixarAnexo(a) {
  try {
    const { data } = await api.get(`v1/contrato/${cod}/anexo/${a.nome}/download`, {
      responseType: 'blob',
    })
    const url = URL.createObjectURL(data)
    const link = document.createElement('a')
    link.href = url
    link.download = a.label || a.nome
    link.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    notifyError(e)
  }
}
function excluirAnexo(a) {
  $q.dialog({
    title: 'Excluir anexo',
    message: `Excluir "${a.label}"?`,
    cancel: true,
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await api.delete(`v1/contrato/${cod}/anexo/${a.nome}`)
      notifySuccess('Excluído!')
      await carregarAnexos()
    } catch (e) {
      notifyError(e)
    }
  })
}

// ---- Contrato (edição/ativação/exclusão no cabeçalho do detalhe) ----
function editarContrato() {
  contratoCad.editar(contrato.value)
}
async function alternarInativoContrato() {
  await contratoCad.alternarInativo(contrato.value)
  await recarregar()
}
function excluirContrato() {
  $q.dialog({
    title: 'Excluir',
    message: `Excluir o contrato ${contrato.value?.contrato}?`,
    cancel: true,
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      const destino = voltarTo.value
      await api.delete(`v1/contrato/${cod}`)
      notifySuccess('Excluído!')
      router.push(destino)
    } catch (e) {
      notifyError(e)
    }
  })
}

onMounted(async () => {
  try {
    const { data } = await api.get('v1/natureza-operacao')
    naturezas.value = data.data ?? data
  } catch {
    // naturezas é opcional (só pro form fiscal)
  }
  await recarregar()
  await carregarAnexos()
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <!-- Cabeçalho -->
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center">
          <div class="col-12 col-sm row items-center no-wrap">
            <q-btn flat round size="sm" color="grey-7" icon="arrow_back" :to="voltarTo" />
            <q-avatar
              :color="corTipo[contrato?.tipo] || 'indigo-7'"
              text-color="white"
              icon="description"
              class="q-ml-sm"
            />
            <div class="col q-ml-md">
              <!-- Título: com quem o contrato foi feito (comprador) -->
              <div class="text-h6">
                {{ contrato?.Pessoa?.fantasia || contrato?.Pessoa?.pessoa || 'Contrato' }}
              </div>
              <div class="text-caption text-grey-7">
                {{ contrato?.Cultura?.cultura }}
                <span v-if="contrato?.Safra"> · {{ contrato.Safra.safra }}</span>
              </div>
            </div>
          </div>
          <div
            class="col-12 col-sm-auto row items-center justify-end no-wrap"
            :class="{ 'q-mt-sm': $q.screen.lt.sm }"
          >
            <MgInfoCriacao :registro="contrato" />
            <q-btn flat dense round size="sm" color="grey-7" icon="edit" @click="editarContrato">
              <q-tooltip>Editar contrato</q-tooltip>
            </q-btn>
            <q-btn
              flat
              dense
              round
              size="sm"
              color="grey-7"
              :icon="contrato?.inativo ? 'play_arrow' : 'pause'"
              @click="alternarInativoContrato"
            >
              <q-tooltip>{{ contrato?.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
            </q-btn>
            <q-btn flat dense round size="sm" color="grey-7" icon="delete" @click="excluirContrato">
              <q-tooltip>Excluir</q-tooltip>
            </q-btn>
            <q-btn
              flat
              color="green-7"
              icon="local_shipping"
              label="Pátio"
              :to="{ name: 'carga' }"
            />
          </div>
        </q-card-section>
      </q-card>

      <!-- Reconciliação físico / fiscal / financeiro -->
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-12 col-md-4">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="row items-center text-blue-grey-8">
                <q-icon name="local_shipping" class="q-mr-sm" /><span class="text-subtitle2"
                  >Físico</span
                >
              </div>
              <div class="text-h5 q-mt-sm row items-center">
                <div>
                  {{ fmt(carregadokg) }}
                  <span class="text-caption"
                    >/ {{ volumeemaberto ? '∞' : fmt(contratadokg) }} kg</span
                  >
                </div>
                <!-- Modo do contrato ao lado da quantidade -->
                <q-chip
                  v-if="contrato"
                  dense
                  square
                  :color="corTipo[contrato.tipo] || 'grey-7'"
                  text-color="white"
                  :label="contrato.tipo"
                  class="q-ml-sm q-my-none"
                />
              </div>
              <!-- Sacas derivadas (unidade comercial) -->
              <div class="text-caption text-grey-6">
                ≈ {{ fmt(carregadosc, 1) }} / {{ volumeemaberto ? '∞' : fmt(contratado) }} sc
              </div>
              <q-linear-progress
                :value="
                  !volumeemaberto && contratadokg ? Math.min(1, carregadokg / contratadokg) : 0
                "
                :indeterminate="volumeemaberto"
                color="green-6"
                track-color="grey-3"
                size="8px"
                rounded
                class="q-my-sm"
              />
              <div v-if="volumeemaberto" class="text-caption text-deep-purple-7">
                <q-icon name="all_inclusive" /> Sem limite — leva o saldo do silo
              </div>
              <div v-else class="text-caption text-grey-7">
                Saldo a embarcar: <b>{{ fmt(saldokg) }} kg</b>
                <span class="text-grey-6">(≈ {{ fmt(saldokg / pesosaca, 0) }} sc)</span>
              </div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-md-4">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="row items-center text-deep-orange-8">
                <q-icon name="receipt_long" class="q-mr-sm" /><span class="text-subtitle2"
                  >Fiscal (NFs)</span
                >
              </div>
              <div class="text-h5 q-mt-sm">{{ rs(valornf) }}</div>
              <div class="text-caption text-grey-7 q-mt-sm">
                Valor carregado: {{ rs(valorCarregado) }}
              </div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-md-4">
          <q-card flat bordered class="full-height">
            <q-card-section>
              <div class="row items-center text-teal-8">
                <q-icon name="payments" class="q-mr-sm" /><span class="text-subtitle2"
                  >Financeiro</span
                >
              </div>
              <div class="text-h5 q-mt-sm">{{ rs(pago) }}</div>
              <div class="text-caption text-grey-7 q-mt-sm">Pago pelo comprador</div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Bate? -->
      <q-banner v-if="bate" rounded class="bg-green-1 text-green-9 q-mb-md">
        <template #avatar><q-icon name="verified" color="green-7" /></template>
        Tudo confere: valor carregado, NFs emitidas e pagamentos batem.
      </q-banner>
      <q-banner v-else rounded class="bg-amber-1 text-amber-9 q-mb-md">
        <template #avatar><q-icon name="balance" color="amber-8" /></template>
        NFs − carregado: <b>{{ rs(difNf) }}</b> · Pago − NFs: <b>{{ rs(difPago) }}</b>
      </q-banner>

      <!-- Fixação de preço -->
      <q-card flat bordered class="q-mb-md">
        <q-item>
          <q-item-section>
            <q-item-label class="text-subtitle1">Fixação de preço</q-item-label>
            <q-item-label caption>
              Fixado {{ fmt(fixado) }} sc · A fixar {{ fmt(afixar) }} sc · Preço médio
              {{ rs(precoMedio) }}/sc
            </q-item-label>
          </q-item-section>
          <q-item-section v-if="!ehFixo" side>
            <q-btn flat round size="sm" color="primary" icon="add" @click="novaFixacao">
              <q-tooltip>Nova fixação</q-tooltip>
            </q-btn>
          </q-item-section>
        </q-item>
        <q-separator />
        <q-list separator>
          <q-item v-for="f in fixacoes" :key="f.codcontratofixacao">
            <q-item-section>
              <q-item-label>
                {{ fmt(f.quantidade) }} sc · {{ rs(f.precoreal) }}/sc
                <span v-if="f.precoliquido != null" class="text-green-8">
                  · líq {{ rs(f.precoliquido) }}</span
                >
                <q-badge
                  v-if="f.automatico"
                  color="blue-grey-5"
                  label="automática"
                  class="q-ml-xs"
                />
              </q-item-label>
              <q-item-label caption>
                {{ fmtData(f.data) }}
                <span v-if="f.moeda === 'USD'"
                  >· US$ {{ fmt(f.preco, 2) }} × {{ fmt(f.dolar, 4) }}</span
                >
              </q-item-label>
            </q-item-section>
            <q-item-section v-if="!ehFixo" side>
              <div class="row items-center no-wrap">
                <MgInfoCriacao :registro="f" />
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="edit"
                  @click="fixCad.editar(f)"
                />
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="delete"
                  @click="excluirFixacao(f)"
                />
              </div>
            </q-item-section>
          </q-item>
          <q-item v-if="ehFixo">
            <q-item-section class="text-grey-6">
              Contrato FIXO: preço travado no contrato (fixação automática).
            </q-item-section>
          </q-item>
          <q-item v-else-if="!fixacoes.length">
            <q-item-section class="text-grey-6">Nenhuma fixação lançada.</q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <!-- Parcelas de pagamento (previsto x recebido) -->
      <q-card flat bordered class="q-mb-md">
        <q-item>
          <q-item-section>
            <q-item-label class="text-subtitle1">Parcelas de pagamento</q-item-label>
            <q-item-label caption>
              Previsto {{ rs(previsto) }} · Recebido {{ rs(pago) }}
            </q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-btn flat round size="sm" color="primary" icon="add" @click="parcelasDialog = true">
              <q-tooltip>Novas parcelas</q-tooltip>
            </q-btn>
          </q-item-section>
        </q-item>
        <q-separator />
        <q-list separator>
          <q-item v-for="p in pagamentos" :key="p.codcontratopagamento">
            <q-item-section avatar>
              <q-avatar
                :color="p.datarecebido ? 'green-5' : 'grey-4'"
                :text-color="p.datarecebido ? 'white' : 'grey-8'"
                :icon="p.datarecebido ? 'check' : 'schedule'"
              />
            </q-item-section>
            <q-item-section>
              <q-item-label>
                {{ rs(p.valor) }}
                <span v-if="p.modo === 'SACAS' && p.sacas" class="text-caption text-grey-7">
                  ({{ fmt(p.sacas) }} sc)
                </span>
              </q-item-label>
              <q-item-label caption>
                Prev. {{ fmtData(p.data) }}
                <span v-if="p.datarecebido" class="text-green-8">
                  · Receb. {{ fmtData(p.datarecebido) }} {{ rs(p.valorrecebido) }}
                </span>
                <span v-if="p.observacao"> · {{ p.observacao }}</span>
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <div class="row items-center no-wrap">
                <q-btn
                  v-if="!p.datarecebido"
                  flat
                  dense
                  round
                  size="sm"
                  color="green-7"
                  icon="task_alt"
                  @click="abrirConfirmar(p)"
                >
                  <q-tooltip>Confirmar recebimento</q-tooltip>
                </q-btn>
                <MgInfoCriacao :registro="p" />
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="edit"
                  @click="pagCad.editar(p)"
                />
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="delete"
                  @click="excluirPagamento(p)"
                />
              </div>
            </q-item-section>
          </q-item>
          <q-item v-if="!pagamentos.length">
            <q-item-section class="text-grey-6">Nenhuma parcela lançada.</q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <!-- Entregas (extrato) -->
      <q-card flat bordered>
        <q-item>
          <q-item-section>
            <q-item-label class="text-subtitle1">Entregas</q-item-label>
            <q-item-label caption
              >{{ fmt(carregadokg) }} kg (≈ {{ fmt(carregadosc, 1) }} sc) entregue</q-item-label
            >
          </q-item-section>
        </q-item>
        <q-separator />
        <q-list separator>
          <q-item v-for="e in entregas" :key="e.codmovimentograo">
            <q-item-section avatar>
              <q-avatar
                :color="e.manual ? 'deep-purple-5' : 'green-6'"
                text-color="white"
                :icon="e.manual ? 'edit_note' : 'local_shipping'"
              />
            </q-item-section>
            <q-item-section>
              <q-item-label
                >{{ fmt(e.quantidadekg) }} kg
                <span class="text-caption text-grey-6">(≈ {{ fmt(e.quantidadesc, 1) }} sc)</span>
              </q-item-label>
              <q-item-label caption>
                {{ e.Carga?.placa || (e.manual ? 'Ajuste manual' : '—') }}
                <span v-if="e.data"> · {{ new Date(e.data).toLocaleDateString('pt-BR') }}</span>
              </q-item-label>
            </q-item-section>
          </q-item>
          <q-item v-if="!entregas.length">
            <q-item-section class="text-grey-6"
              >Nenhuma entrega ainda. Carregue no pátio.</q-item-section
            >
          </q-item>
        </q-list>
      </q-card>

      <!-- Anexos (PDFs) -->
      <q-card flat bordered class="q-mt-md">
        <q-item>
          <q-item-section avatar>
            <q-avatar color="blue-grey-1" text-color="blue-grey-8" icon="attach_file" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-subtitle1">Anexos</q-item-label>
            <q-item-label caption>Contratos, aditivos e documentos (PDF/imagem)</q-item-label>
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
              :loading="enviandoAnexo"
              @click="enviarAnexo"
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
                  @click="visualizarAnexo(a)"
                >
                  <q-tooltip>Visualizar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="download"
                  @click="baixarAnexo(a)"
                >
                  <q-tooltip>Baixar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  color="grey-7"
                  icon="delete"
                  @click="excluirAnexo(a)"
                >
                  <q-tooltip>Excluir</q-tooltip>
                </q-btn>
              </div>
            </q-item-section>
          </q-item>
          <q-item v-if="!anexos.length">
            <q-item-section class="text-grey-6">Nenhum anexo.</q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <!-- Dialog Fixação -->
      <q-dialog v-model="fixCad.dialog">
        <q-card flat style="width: 440px; max-width: 95vw">
          <q-form @submit="salvarFixacao">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">
                {{ fixCad.isNovo ? 'Nova fixação' : 'Editar fixação' }}
              </div>
            </q-card-section>
            <q-card-section class="q-pt-md">
              <div class="row q-col-gutter-md">
                <div class="col-12 col-sm-6">
                  <MgInputData v-model="fixCad.form.data" label="Data" type="date" autofocus />
                </div>
                <div class="col-12 col-sm-6">
                  <MgInputValor
                    v-model="fixCad.form.quantidade"
                    :decimals="0"
                    suffix="sc"
                    label="Quantidade"
                  />
                </div>
                <div class="col-12 col-sm-8">
                  <MgInputValor v-model="fixCad.form.preco" :decimals="2" label="Preço / saca" />
                </div>
                <div class="col-12 col-sm-4 self-center">
                  <q-btn-toggle
                    v-model="fixCad.form.moeda"
                    :options="moedas"
                    spread
                    no-caps
                    unelevated
                    toggle-color="primary"
                    color="grey-3"
                    text-color="grey-9"
                  />
                </div>
                <div v-if="fixCad.form.moeda === 'USD'" class="col-12">
                  <MgInputValor
                    v-model="fixCad.form.dolar"
                    :decimals="4"
                    prefix="R$"
                    label="Dólar travado"
                  />
                </div>
                <div v-if="fixCalc" class="col-12">
                  <q-banner rounded class="bg-green-1 text-green-10">
                    <template #avatar><q-icon name="savings" color="green-7" /></template>
                    <div class="row items-center justify-between">
                      <div>
                        Líquido <b>{{ rs(fixCalc.liquido) }}/sc</b>
                        <span class="text-caption">
                          (bruto {{ rs(fixCalc.bruto) }} − {{ rs(fixCalc.totaldeducao) }})
                        </span>
                      </div>
                      <div class="text-caption">
                        <span v-for="it in fixCalc.itens" :key="it.codtributo" class="q-ml-sm">
                          {{ it.codigo }} {{ fmt(it.valor, 2) }}
                        </span>
                      </div>
                    </div>
                  </q-banner>
                </div>
                <div v-else-if="previewPrecoReal" class="col-12">
                  <q-banner rounded class="bg-grey-2 text-grey-8">
                    Preço em reais: <b>{{ rs(previewPrecoReal) }}/sc</b>
                  </q-banner>
                </div>
              </div>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn type="submit" flat label="Salvar" color="primary" :loading="fixCad.salvando" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>

      <!-- Dialog Parcela (previsto) -->
      <q-dialog v-model="pagCad.dialog">
        <q-card flat style="width: 440px; max-width: 95vw">
          <q-form @submit="salvarPagamento">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">
                {{ pagCad.isNovo ? 'Nova parcela' : 'Editar parcela' }}
              </div>
            </q-card-section>
            <q-card-section class="q-pt-md">
              <div class="row q-col-gutter-md">
                <div class="col-12">
                  <q-btn-toggle
                    v-model="pagCad.form.modo"
                    :options="modosParcela"
                    spread
                    no-caps
                    unelevated
                    toggle-color="primary"
                    color="grey-3"
                    text-color="grey-9"
                  />
                </div>
                <div class="col-12 col-sm-6">
                  <MgInputData v-model="pagCad.form.data" label="Data prevista" type="date" />
                </div>
                <div v-if="pagCad.form.modo === 'SACAS'" class="col-12 col-sm-6">
                  <MgInputValor
                    v-model="pagCad.form.sacas"
                    :decimals="0"
                    suffix="sc"
                    label="Sacas"
                  />
                </div>
                <div class="col-12 col-sm-6">
                  <MgInputValor
                    v-model="pagCad.form.valor"
                    :decimals="2"
                    prefix="R$"
                    label="Valor previsto"
                  />
                </div>
                <div class="col-12">
                  <MgSelectPortador
                    v-model="pagCad.form.codportador"
                    label="Portador (conta que recebe)"
                  />
                </div>
                <div class="col-12">
                  <q-input v-model="pagCad.form.observacao" label="Observação" outlined />
                </div>
              </div>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn type="submit" flat label="Salvar" color="primary" :loading="pagCad.salvando" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>

      <!-- Gerador de várias parcelas -->
      <MgContratoParcelasDialog
        v-model="parcelasDialog"
        :cod="cod"
        :contrato="contrato"
        :liquido-sc="liquidoSc"
        @saved="recarregar"
      />

      <!-- Dialog Confirmar recebimento -->
      <q-dialog v-model="confirmDialog">
        <q-card flat style="width: 440px; max-width: 95vw">
          <q-form @submit="confirmarRecebimento">
            <q-card-section class="bg-primary text-white">
              <div class="text-h6">Confirmar recebimento</div>
            </q-card-section>
            <q-card-section class="q-pt-md">
              <div class="row q-col-gutter-md">
                <div class="col-12 col-sm-6">
                  <MgInputData
                    v-model="confirmForm.datarecebido"
                    label="Data recebida"
                    type="date"
                    autofocus
                  />
                </div>
                <div class="col-12 col-sm-6">
                  <MgInputValor
                    v-model="confirmForm.valorrecebido"
                    :decimals="2"
                    prefix="R$"
                    label="Valor recebido"
                  />
                </div>
                <div class="col-12">
                  <MgSelectPortador
                    v-model="confirmForm.codportador"
                    label="Portador (conta que recebeu)"
                  />
                </div>
              </div>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
              <q-btn
                type="submit"
                flat
                label="Confirmar"
                color="green-7"
                :loading="confirmSalvando"
              />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>

      <!-- Dialog Contrato (edição) — mesmo form do cadastro -->
      <MgContratoForm :cad="contratoCad" :naturezas="naturezas" @saved="recarregar" />
    </div>
  </q-page>
</template>
