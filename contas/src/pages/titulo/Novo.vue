<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { date } from 'quasar'
import { api } from 'src/services/api'
import { formataNumero, formataDataSemHora, formataDataIso } from "@components/formatters"
import { notifySuccess, notifyError } from 'src/utils/notify'
import { useSelectCacheStore } from 'src/stores/selectCacheStore'
import PessoaInfo from 'src/components/PessoaInfo.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'

const route = useRoute()
const router = useRouter()
const cache = useSelectCacheStore()

const STEP = {
  FILIAL: 1,
  GERENCIAL: 2,
  PORTADOR: 3,
  TIPO: 4,
  CONTA: 5,
  PESSOA: 6,
  VALORES: 7,
  OBSERVACAO: 8,
}
const TOTAL = 8

const TITULOS_ETAPA = {
  [STEP.FILIAL]: 'Filial',
  [STEP.GERENCIAL]: 'Fiscal ou Gerencial',
  [STEP.PORTADOR]: 'Portador',
  [STEP.TIPO]: 'Tipo de Título',
  [STEP.CONTA]: 'Conta Contábil',
  [STEP.PESSOA]: 'Pessoa',
  [STEP.VALORES]: 'Valor e Datas',
  [STEP.OBSERVACAO]: 'Observação',
}

const step = ref(STEP.FILIAL)
const carregando = ref(true)
const saving = ref(false)

const hoje = () => formataDataIso(new Date())
const hojeMais30 = () => formataDataIso(date.addToDate(new Date(), { days: 30 }))

const model = ref({
  codfilial: null,
  gerencial: null,
  codportador: null,
  codtipotitulo: null,
  codcontacontabil: null,
  codpessoa: null,
  valor: null,
  emissao: hoje(),
  vencimento: hojeMais30(),
  observacao: '',
})

const labels = ref({
  filial: '',
  gerencial: '',
  portador: '',
  tipotitulo: '',
  contacontabil: '',
  pessoa: '',
})

// === Listas (cache) com filtro local por etapa ===
const buscaFilial = ref('')
const buscaPortador = ref('')
const buscaTipo = ref('')

const filiaisFiltradas = computed(() => {
  const t = buscaFilial.value.trim().toLowerCase()
  if (!t) return cache.filial.items
  return cache.filial.items.filter((f) => (f.label || '').toLowerCase().includes(t))
})

const portadoresFiltrados = computed(() => {
  if (!model.value.codfilial) return []
  const base = cache.portador.items.filter(
    (p) => !p.inativo && (p.codfilial == null || p.codfilial === model.value.codfilial),
  )
  const t = buscaPortador.value.trim().toLowerCase()
  if (!t) return base
  return base.filter(
    (p) =>
      (p.portador || '').toLowerCase().includes(t) || (p.banco || '').toLowerCase().includes(t),
  )
})

const tiposTituloFiltrados = computed(() => {
  const t = buscaTipo.value.trim().toLowerCase()
  if (!t) return cache.tipoTitulo.items
  return cache.tipoTitulo.items.filter((tt) => (tt.tipotitulo || '').toLowerCase().includes(t))
})

// === Conta contábil (busca remota) ===
const contaBusca = ref('')
const contas = ref([])
const contaLoading = ref(false)
let contaTimer = null

watch(contaBusca, () => {
  clearTimeout(contaTimer)
  contaTimer = setTimeout(pesquisarConta, 300)
})

async function pesquisarConta() {
  const t = (contaBusca.value || '').trim()
  contaLoading.value = true
  try {
    const ret = await api.get('v1/select/conta-contabil', { params: { busca: t } })
    contas.value = Array.isArray(ret.data) ? ret.data : ret.data.data || []
  } catch (e) {
    notifyError(e, 'Erro ao buscar conta contábil')
    contas.value = []
  } finally {
    contaLoading.value = false
  }
}

// === Pessoa (busca remota) ===
const buscaPessoa = ref('')
const pessoas = ref([])
const pessoaLoading = ref(false)
let pessoaTimer = null

watch(buscaPessoa, () => {
  clearTimeout(pessoaTimer)
  pessoaTimer = setTimeout(pesquisarPessoa, 300)
})

watch(step, async (s) => {
  if (s === STEP.CONTA && contas.value.length === 0) {
    pesquisarConta()
  }
  if (s === STEP.PESSOA && pessoas.value.length === 0) {
    pesquisarPessoa()
  }
  if (s === STEP.GERENCIAL) {
    await nextTick()
    listaGerencialRef.value?.$el?.focus?.()
  }
})

async function pesquisarPessoa() {
  const t = (buscaPessoa.value || '').trim()
  pessoaLoading.value = true
  try {
    const ret = await api.get('v1/select/pessoa', {
      params: { pessoa: t || ' ', somenteAtivos: false },
    })
    pessoas.value = Array.isArray(ret.data) ? ret.data : ret.data.data || []
  } catch (e) {
    notifyError(e, 'Erro ao buscar pessoa')
    pessoas.value = []
  } finally {
    pessoaLoading.value = false
  }
}

// === Seleções ===
function selecionarFilial(f) {
  if (model.value.codfilial !== f.value) {
    // troca de filial limpa portador se não for compatível
    const port = cache.portador.items.find((p) => p.codportador === model.value.codportador)
    if (port && port.codfilial != null && port.codfilial !== f.value) {
      model.value.codportador = null
      labels.value.portador = ''
    }
  }
  model.value.codfilial = f.value
  labels.value.filial = f.label
  step.value = STEP.GERENCIAL
}

function selecionarGerencial(valor) {
  model.value.gerencial = valor
  labels.value.gerencial = valor ? 'Gerencial' : 'Fiscal'
  step.value = STEP.PORTADOR
}

function selecionarPortador(p) {
  if (p == null) {
    model.value.codportador = null
    labels.value.portador = 'Sem portador'
  } else {
    model.value.codportador = p.codportador
    labels.value.portador = p.portador
  }
  step.value = STEP.TIPO
}

function selecionarTipo(t) {
  model.value.codtipotitulo = t.codtipotitulo
  labels.value.tipotitulo = t.tipotitulo
  step.value = STEP.CONTA
}

function selecionarConta(c) {
  model.value.codcontacontabil = c.codcontacontabil
  labels.value.contacontabil = c.contacontabil
  step.value = STEP.PESSOA
}

function selecionarPessoa(p) {
  model.value.codpessoa = p.codpessoa
  labels.value.pessoa = p.fantasia
  step.value = STEP.VALORES
}

// === Navegação por teclado nas listagens ===
const highlightedIndex = ref(0)
const listaGerencialRef = ref(null)

const listaAtiva = computed(() => {
  switch (step.value) {
    case STEP.FILIAL:
      return filiaisFiltradas.value.map((item) => ({ tipo: 'filial', item }))
    case STEP.GERENCIAL:
      return [
        { tipo: 'gerencial', item: false },
        { tipo: 'gerencial', item: true },
      ]
    case STEP.PORTADOR:
      return [
        { tipo: 'portador', item: null },
        ...portadoresFiltrados.value.map((item) => ({ tipo: 'portador', item })),
      ]
    case STEP.TIPO:
      return tiposTituloFiltrados.value.map((item) => ({ tipo: 'tipo', item }))
    case STEP.CONTA:
      return contas.value.map((item) => ({ tipo: 'conta', item }))
    case STEP.PESSOA:
      return pessoas.value.map((item) => ({ tipo: 'pessoa', item }))
    default:
      return []
  }
})

watch([listaAtiva, step], () => {
  highlightedIndex.value = 0
})

watch(highlightedIndex, async () => {
  await nextTick()
  const el = document.querySelector('.wizard-item-ativo')
  if (el) el.scrollIntoView({ block: 'nearest' })
})

function navegarLista(delta) {
  const total = listaAtiva.value.length
  if (total === 0) return
  let n = highlightedIndex.value + delta
  if (n < 0) n = total - 1
  if (n >= total) n = 0
  highlightedIndex.value = n
}

function selecionarHighlighted() {
  const ent = listaAtiva.value[highlightedIndex.value]
  if (!ent) return
  switch (ent.tipo) {
    case 'filial':
      selecionarFilial(ent.item)
      break
    case 'gerencial':
      selecionarGerencial(ent.item)
      break
    case 'portador':
      selecionarPortador(ent.item)
      break
    case 'tipo':
      selecionarTipo(ent.item)
      break
    case 'conta':
      selecionarConta(ent.item)
      break
    case 'pessoa':
      selecionarPessoa(ent.item)
      break
  }
}

function onKeydownLista(e) {
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    navegarLista(1)
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    navegarLista(-1)
  } else if (e.key === 'Enter') {
    e.preventDefault()
    selecionarHighlighted()
  }
}

function avancarValores() {
  if (!model.value.valor || Number(model.value.valor) <= 0) return
  if (!model.value.emissao || !model.value.vencimento) return
  step.value = STEP.OBSERVACAO
}

function voltar() {
  if (step.value > STEP.FILIAL) {
    step.value--
  } else {
    router.push({ name: 'titulo' })
  }
}

function irParaEtapa(s) {
  if (s < step.value) step.value = s
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
      numero: null,
      fatura: null,
      valor: model.value.valor,
      emissao: model.value.emissao,
      transacao: model.value.emissao,
      vencimento: model.value.vencimento,
      vencimentooriginal: model.value.vencimento,
      gerencial: !!model.value.gerencial,
      boleto: false,
      observacao: model.value.observacao || null,
    }
    const { data } = await api.post('v1/titulo', payload)
    notifySuccess('Título criado')
    router.replace({ name: 'titulo-detalhe', params: { codtitulo: data.data.codtitulo } })
  } catch (e) {
    notifyError(e, 'Erro ao salvar')
  } finally {
    saving.value = false
  }
}

// === Duplicar (pré-preenchimento) ===
async function carregarDuplicar(id) {
  try {
    const { data } = await api.get(`v1/titulo/${id}`)
    const t = data.data
    model.value = {
      codfilial: t.codfilial,
      gerencial: !!t.gerencial,
      codportador: t.codportador,
      codtipotitulo: t.codtipotitulo,
      codcontacontabil: t.codcontacontabil,
      codpessoa: t.codpessoa,
      valor: t.valor != null ? Math.abs(t.valor) : null,
      emissao: hoje(),
      vencimento: hojeMais30(),
      observacao: t.observacao || '',
    }
    labels.value = {
      filial: t.filial || '',
      gerencial: t.gerencial ? 'Gerencial' : 'Fiscal',
      portador: t.portador || (t.codportador ? '' : 'Sem portador'),
      tipotitulo: t.tipotitulo || '',
      contacontabil: t.contacontabil || '',
      pessoa: t.fantasia || '',
    }
  } catch (e) {
    notifyError(e, 'Erro ao duplicar')
  }
}

// === Inicialização ===
onMounted(async () => {
  carregando.value = true
  try {
    await Promise.all([
      cache.loadList('filial', 'v1/select/filial', (d) => (Array.isArray(d) ? d : d.data || [])),
      cache.loadList('portador', 'v1/select/portador', (d) =>
        Array.isArray(d) ? d : d.data || [],
      ),
      cache.loadList('tipoTitulo', 'v1/select/tipo-titulo', (d) =>
        Array.isArray(d) ? d : d.data || [],
      ),
    ])
    if (route.query.duplicar) {
      await carregarDuplicar(Number(route.query.duplicar))
    }
  } finally {
    carregando.value = false
  }
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 500px; margin: auto">
      <q-item class="q-pb-md q-px-none">
        <q-item-section avatar>
          <q-btn flat dense round icon="arrow_back" @click="voltar" aria-label="Voltar" />
        </q-item-section>
        <q-item-section>
          <div class="text-h4 text-grey-9 ellipsis">Novo Título</div>
          <div class="text-h6 text-grey-7 ellipsis">
            Etapa {{ step }}/{{ TOTAL }}: {{ TITULOS_ETAPA[step] }}
          </div>
        </q-item-section>
      </q-item>

      <q-card bordered flat>
        <q-linear-progress :value="step / TOTAL" color="primary" size="2px" />

        <!-- Resumo do que já foi escolhido -->
        <q-card-section v-if="step > STEP.FILIAL" class="q-pa-sm bg-grey-1">
          <div class="row q-gutter-xs items-center">
            <q-chip
              v-if="labels.filial"
              dense
              clickable
              color="white"
              text-color="grey-9"
              icon="store"
              :label="labels.filial"
              @click="irParaEtapa(STEP.FILIAL)"
            />
            <q-chip
              v-if="step > STEP.GERENCIAL && labels.gerencial"
              dense
              clickable
              color="white"
              :text-color="model.gerencial ? 'orange-9' : 'green-9'"
              :icon="model.gerencial ? 'insights' : 'gavel'"
              :label="labels.gerencial"
              @click="irParaEtapa(STEP.GERENCIAL)"
            />
            <q-chip
              v-if="step > STEP.PORTADOR && labels.portador"
              dense
              clickable
              color="white"
              text-color="grey-9"
              icon="account_balance"
              :label="labels.portador"
              @click="irParaEtapa(STEP.PORTADOR)"
            />
            <q-chip
              v-if="step > STEP.TIPO && labels.tipotitulo"
              dense
              clickable
              color="white"
              text-color="grey-9"
              icon="receipt_long"
              :label="labels.tipotitulo"
              @click="irParaEtapa(STEP.TIPO)"
            />
            <q-chip
              v-if="step > STEP.CONTA && labels.contacontabil"
              dense
              clickable
              color="white"
              text-color="grey-9"
              icon="account_tree"
              :label="labels.contacontabil"
              @click="irParaEtapa(STEP.CONTA)"
            />
            <q-chip
              v-if="step > STEP.PESSOA && labels.pessoa"
              dense
              clickable
              color="white"
              text-color="grey-9"
              icon="person"
              :label="labels.pessoa"
              @click="irParaEtapa(STEP.PESSOA)"
            />
            <q-chip
              v-if="step > STEP.VALORES && model.valor"
              dense
              clickable
              color="white"
              text-color="grey-9"
              icon="attach_money"
              :label="formataNumero(model.valor)"
              @click="irParaEtapa(STEP.VALORES)"
            />
            <q-chip
              v-if="step > STEP.VALORES"
              dense
              clickable
              color="white"
              text-color="grey-9"
              icon="event"
              :label="`Venc. ${formataDataSemHora(model.vencimento)}`"
              @click="irParaEtapa(STEP.VALORES)"
            />
          </div>
        </q-card-section>

        <q-separator />

        <!-- Loading inicial -->
        <q-card-section v-if="carregando" class="text-center q-py-xl">
          <q-spinner-dots color="primary" size="40px" />
        </q-card-section>

        <!-- Etapa 1: Filial -->
        <div v-else-if="step === STEP.FILIAL">
          <q-card-section class="q-pb-none">
            <q-input
              v-model="buscaFilial"
              outlined
              label="Filtrar filial"
              clearable
              autofocus
              @keydown="onKeydownLista"
            >
              <template #prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </q-card-section>
          <q-list separator class="q-mt-sm">
            <q-item
              v-for="(f, idx) in filiaisFiltradas"
              :key="f.value"
              clickable
              v-ripple
              :active="highlightedIndex === idx"
              active-class="bg-blue-1 wizard-item-ativo"
              @click="selecionarFilial(f)"
              @mouseover="highlightedIndex = idx"
            >
              <q-item-section avatar>
                <q-icon name="store" color="grey-7" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ f.label }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-icon name="chevron_right" color="grey-6" />
              </q-item-section>
            </q-item>
            <q-item v-if="filiaisFiltradas.length === 0">
              <q-item-section class="text-grey-7">Nenhuma filial encontrada</q-item-section>
            </q-item>
          </q-list>
        </div>

        <!-- Etapa 2: Fiscal ou Gerencial -->
        <q-list
          v-else-if="step === STEP.GERENCIAL"
          ref="listaGerencialRef"
          separator
          tabindex="0"
          style="outline: none"
          @keydown="onKeydownLista"
        >
          <q-item
            clickable
            v-ripple
            :active="highlightedIndex === 0"
            active-class="bg-blue-1 wizard-item-ativo"
            @click="selecionarGerencial(false)"
            @mouseover="highlightedIndex = 0"
          >
            <q-item-section avatar>
              <q-icon name="gavel" color="green-7" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-green-9">Fiscal</q-item-label>
              <q-item-label caption>Título com efeito contábil/fiscal</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-icon name="chevron_right" color="grey-6" />
            </q-item-section>
          </q-item>
          <q-item
            clickable
            v-ripple
            :active="highlightedIndex === 1"
            active-class="bg-blue-1 wizard-item-ativo"
            @click="selecionarGerencial(true)"
            @mouseover="highlightedIndex = 1"
          >
            <q-item-section avatar>
              <q-icon name="insights" color="orange-7" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-orange-9">Gerencial</q-item-label>
              <q-item-label caption>Título apenas para controle interno</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-icon name="chevron_right" color="grey-6" />
            </q-item-section>
          </q-item>
        </q-list>

        <!-- Etapa 3: Portador -->
        <div v-else-if="step === STEP.PORTADOR">
          <q-card-section class="q-pb-none">
            <q-input
              v-model="buscaPortador"
              outlined
              label="Filtrar portador"
              clearable
              autofocus
              @keydown="onKeydownLista"
            >
              <template #prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </q-card-section>
          <q-list separator class="q-mt-sm">
            <q-item
              clickable
              v-ripple
              :active="highlightedIndex === 0"
              active-class="bg-blue-1 wizard-item-ativo"
              @click="selecionarPortador(null)"
              @mouseover="highlightedIndex = 0"
            >
              <q-item-section avatar>
                <q-icon name="block" color="grey-6" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-grey-7">Sem portador</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-icon name="chevron_right" color="grey-6" />
              </q-item-section>
            </q-item>
            <q-item
              v-for="(p, idx) in portadoresFiltrados"
              :key="p.codportador"
              clickable
              v-ripple
              :active="highlightedIndex === idx + 1"
              active-class="bg-blue-1 wizard-item-ativo"
              @click="selecionarPortador(p)"
              @mouseover="highlightedIndex = idx + 1"
            >
              <q-item-section avatar>
                <q-icon name="account_balance" color="grey-7" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ p.portador }}</q-item-label>
                <q-item-label v-if="p.banco" caption>{{ p.banco }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-icon name="chevron_right" color="grey-6" />
              </q-item-section>
            </q-item>
            <q-item v-if="portadoresFiltrados.length === 0">
              <q-item-section class="text-grey-7"> Nenhum portador encontrado </q-item-section>
            </q-item>
          </q-list>
        </div>

        <!-- Etapa 3: Tipo de Título -->
        <div v-else-if="step === STEP.TIPO">
          <q-card-section class="q-pb-none">
            <q-input
              v-model="buscaTipo"
              outlined
              label="Filtrar tipo de título"
              clearable
              autofocus
              @keydown="onKeydownLista"
            >
              <template #prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </q-card-section>
          <q-list separator class="q-mt-sm">
            <q-item
              v-for="(t, idx) in tiposTituloFiltrados"
              :key="t.codtipotitulo"
              clickable
              v-ripple
              :active="highlightedIndex === idx"
              active-class="bg-blue-1 wizard-item-ativo"
              @click="selecionarTipo(t)"
              @mouseover="highlightedIndex = idx"
            >
              <q-item-section avatar>
                <q-icon name="receipt_long" color="grey-7" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ t.tipotitulo }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-icon name="chevron_right" color="grey-6" />
              </q-item-section>
            </q-item>
            <q-item v-if="tiposTituloFiltrados.length === 0">
              <q-item-section class="text-grey-7">
                Nenhum tipo de título encontrado
              </q-item-section>
            </q-item>
          </q-list>
        </div>

        <!-- Etapa 4: Conta Contábil -->
        <div v-else-if="step === STEP.CONTA">
          <q-card-section class="q-pb-none">
            <q-input
              v-model="contaBusca"
              outlined
              label="Filtrar conta contábil"
              clearable
              autofocus
              @keydown="onKeydownLista"
            >
              <template #prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </q-card-section>
          <q-list separator class="q-mt-sm">
            <q-item v-if="contaLoading">
              <q-item-section class="text-center">
                <q-spinner-dots color="primary" size="32px" />
              </q-item-section>
            </q-item>
            <q-item
              v-for="(c, idx) in contas"
              :key="c.codcontacontabil"
              clickable
              v-ripple
              :active="highlightedIndex === idx"
              active-class="bg-blue-1 wizard-item-ativo"
              @click="selecionarConta(c)"
              @mouseover="highlightedIndex = idx"
            >
              <q-item-section avatar>
                <q-icon name="account_tree" color="grey-7" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ c.contacontabil }}</q-item-label>
                <q-item-label v-if="c.numero" caption>{{ c.numero }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-icon name="chevron_right" color="grey-6" />
              </q-item-section>
            </q-item>
            <q-item v-if="!contaLoading && contas.length === 0">
              <q-item-section class="text-grey-7">Nenhuma conta encontrada</q-item-section>
            </q-item>
          </q-list>
        </div>

        <!-- Etapa 5: Pessoa -->
        <div v-else-if="step === STEP.PESSOA">
          <q-card-section class="q-pb-none">
            <q-input
              v-model="buscaPessoa"
              outlined
              label="Filtrar pessoa"
              clearable
              autofocus
              @keydown="onKeydownLista"
            >
              <template #prepend>
                <q-icon name="search" />
              </template>
              <template #append>
                <q-spinner v-if="pessoaLoading" color="primary" size="20px" />
              </template>
            </q-input>
          </q-card-section>
          <q-list separator class="q-mt-sm">
            <q-item
              v-for="(p, idx) in pessoas"
              :key="p.codpessoa"
              clickable
              v-ripple
              :active="highlightedIndex === idx"
              active-class="bg-blue-1 wizard-item-ativo"
              @click="selecionarPessoa(p)"
              @mouseover="highlightedIndex = idx"
            >
              <q-item-section avatar>
                <q-icon name="person" color="grey-7" />
              </q-item-section>
              <q-item-section>
                <PessoaInfo :pessoa="p" />
              </q-item-section>
              <q-item-section side>
                <q-icon name="chevron_right" color="grey-6" />
              </q-item-section>
            </q-item>
            <q-item v-if="!pessoaLoading && pessoas.length === 0">
              <q-item-section class="text-grey-7">Nenhuma pessoa encontrada</q-item-section>
            </q-item>
          </q-list>
        </div>

        <!-- Etapa 6: Valor e Datas -->
        <q-card-section v-else-if="step === STEP.VALORES">
          <div class="row q-col-gutter-md">
            <div class="col-xs-12 col-sm-4">
              <MgInputValor
                v-model="model.valor"
                :min="0.01"
                label="Valor"
                prefix="R$"
                autofocus
                @keyup.enter="avancarValores"
              />
            </div>
            <div class="col-xs-12 col-sm-4">
              <MgInputData
                v-model="model.emissao"
                type="date"
                label="Emissão"
                stack-label
                @keyup.enter="avancarValores"
              />
            </div>
            <div class="col-xs-12 col-sm-4">
              <MgInputData
                v-model="model.vencimento"
                type="date"
                label="Vencimento"
                stack-label
                @keyup.enter="avancarValores"
              />
            </div>
          </div>
          <div class="text-right q-mt-md">
            <q-btn
              label="Próximo"
              color="primary"
              icon-right="arrow_forward"
              :disable="
                !model.valor || Number(model.valor) <= 0 || !model.emissao || !model.vencimento
              "
              @click="avancarValores"
            />
          </div>
        </q-card-section>

        <!-- Etapa 8: Observação + Salvar -->
        <q-card-section v-else-if="step === STEP.OBSERVACAO">
          <q-input
            v-model="model.observacao"
            outlined
            type="textarea"
            label="Observação (opcional)"
            maxlength="255"
            autogrow
            autofocus
          />
          <div class="text-right q-mt-md">
            <q-btn
              label="Salvar"
              color="primary"
              icon-right="check"
              :loading="saving"
              @click="salvar"
            />
          </div>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>
