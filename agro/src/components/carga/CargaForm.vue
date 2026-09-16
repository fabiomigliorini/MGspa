<script setup>
// Formulário da carga aberta (era o CargaDialog, agora inline no centro da
// tela como o negócio no PDV). Trabalha numa CÓPIA local da carga e emite
// salvar/avancar/cancelar — quem persiste é a página/store. As ações ficam em
// FABs no canto (padrão do PDV); a página dispara F3/F4 via defineExpose.
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useCargaStore } from 'src/stores/carga'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'
import { calcularCarga, sacas } from 'src/utils/desconto'
import {
  ETAPA_META,
  CONTATIPO_PADRAO,
  novoPonto,
  pontoCompleto,
  semearPontos,
  aplicarSentido,
  etapasDaCarga,
  proximaEtapa,
  cargaFinalizada,
  cargaPesada,
  agoraLocal,
  fmtNumero as fmt,
} from 'src/utils/carga'
import { imprimirTicket } from 'src/utils/ticket'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import CaminhaoDialog from 'components/CaminhaoDialog.vue'
import SelectContaTipo from 'components/SelectContaTipo.vue'
import SelectTalhao from 'components/SelectTalhao.vue'
import SelectUnidade from 'components/SelectUnidade.vue'
import SelectContrato from 'components/SelectContrato.vue'
import SelectSentido from './SelectSentido.vue'

const props = defineProps({
  carga: { type: Object, default: null },
  // true = carga nova (só "Registrar", entra na 1ª etapa sem avançar)
  novo: { type: Boolean, default: false },
})
const emit = defineEmits(['salvar', 'avancar', 'cancelar'])

const $q = useQuasar()
const store = useCargaStore()
const { culturaAtiva, safraAtiva, veiculosAtivos, unidadesAtivas } = storeToRefs(store)
const { online } = storeToRefs(useSincronizacaoStore())

const formRef = ref(null)

// Estado do autocomplete de placa — declarado antes do watcher imediato abaixo,
// que o zera ao trocar de carga.
const placaOptions = ref([])
const placaBusca = ref('')
const cadastroCaminhao = ref(false)

// Máximo do campo de chegada = agora (não deixa lançar no futuro). Precisa do
// timestamp completo: com só a data o clamp do MgInputData zeraria a hora (00:00).
const dataMax = agoraLocal()

// Cópia local — clonada só quando MUDA a carga (uuid). A store recarrega o
// Dexie em background (sync) e isso não pode apagar o que o operador está
// digitando; os campos que o servidor devolve (codcarga/sync) entram por
// watcher separado, sem tocar no resto.
const local = ref(null)
watch(
  () => props.carga?.uuid,
  () => {
    const c = props.carga
    if (!c) {
      local.value = null
      return
    }
    const carga = normalizarPontos(JSON.parse(JSON.stringify(c)))
    if (props.novo) semearPontos(carga)
    local.value = carga
    placaBusca.value = ''
  },
  { immediate: true },
)
watch(
  () => [props.carga?.codcarga, props.carga?.sincronizado, props.carga?.syncerro],
  ([codcarga, sincronizado, syncerro]) => {
    if (!local.value || !props.carga || props.carga.uuid !== local.value.uuid) return
    Object.assign(local.value, { codcarga, sincronizado, syncerro })
  },
)

// Compat: cargas antigas gravaram kg por ponto e não têm `percentual`. Reconstrói
// o % a partir do kg (proporção sobre o líquido) ou divide igualmente. Linha
// única → 100%. Cargas novas já vêm com percentual (não mexe).
function normalizarPontos(carga) {
  for (const papel of ['ORIGEM', 'DESTINO']) {
    const grupo = (carga.pontos || []).filter((p) => p.papel === papel)
    if (!grupo.length) continue
    if (!grupo.some((p) => p.percentual == null)) continue
    const liq = Number(carga.liquido)
    const temKg = liq > 0 && grupo.every((p) => Number(p.liquido) > 0)
    if (temKg) {
      grupo.forEach((p) => {
        p.percentual = Math.round((Number(p.liquido) / liq) * 1000) / 10
      })
    } else {
      distribuirPercentual(grupo)
    }
  }
  return carga
}

// Divide 100% igualmente entre as linhas do grupo (resto na última) — soma = 100.
function distribuirPercentual(grupo) {
  const n = grupo.length
  if (!n) return
  const base = Math.floor((100 / n) * 10) / 10
  let acumulado = 0
  grupo.forEach((p, idx) => {
    if (idx === n - 1) {
      p.percentual = Math.round((100 - acumulado) * 10) / 10
    } else {
      p.percentual = base
      acumulado += base
    }
  })
}

// ---- Sentido (tipo de romaneio) — decidido aqui, trava após a 1ª pesagem ----
const finalizada = computed(() => cargaFinalizada(local.value))
const podeTrocarSentido = computed(
  () => !!local.value && !cargaPesada(local.value) && !finalizada.value,
)
const sentidoSel = computed({
  get: () => local.value?.sentido,
  set: (s) => {
    if (!s || !local.value || s === local.value.sentido) return
    aplicarSentido(local.value, s)
  },
})

const ordem = computed(() => etapasDaCarga(local.value))
const idxEtapa = computed(() => ordem.value.indexOf(local.value?.etapa))
const etapaMeta = computed(() => ETAPA_META[local.value?.etapa] || {})
const mostrarPbt = computed(() => idxEtapa.value >= ordem.value.indexOf('PBT'))
const mostrarTara = computed(() => idxEtapa.value >= ordem.value.indexOf('TARA'))
const mostrarClassificacao = computed(
  () =>
    local.value?.sentido === 'ENTRADA' && idxEtapa.value >= ordem.value.indexOf('CLASSIFICACAO'),
)
const mostrarFiscal = computed(
  () => local.value?.sentido === 'SAIDA' && idxEtapa.value >= ordem.value.indexOf('FISCAL'),
)

// ---- Placa (autocomplete do cache de veículos, funciona offline) ----
function filtrarPlaca(val, update) {
  placaBusca.value = (val || '').toUpperCase()
  update(() => {
    const termo = placaBusca.value
    placaOptions.value = veiculosAtivos.value
      .filter((v) => (v.placa || '').toUpperCase().includes(termo))
      .slice(0, 50)
      .map((v) => ({ label: v.placa, value: v.placa }))
  })
}
function resolverPlaca(placa) {
  const p = (placa || '').toUpperCase() || null
  local.value.placa = p
  local.value.codveiculo = p
    ? veiculosAtivos.value.find((v) => (v.placa || '').toUpperCase() === p)?.codveiculo || null
    : null
}
function onPlacaBlur() {
  if (placaBusca.value && placaBusca.value !== local.value.placa) resolverPlaca(placaBusca.value)
}
async function onCaminhaoCriado(veiculo) {
  await store.adicionarVeiculo(veiculo)
  local.value.codveiculo = veiculo.codveiculo
  local.value.placa = veiculo.placa
}

// ---- Motorista (busca online via MgSelectPessoa; texto livre offline) ----
// Online usa o select padrão de pessoa (busca/filtra/pagina/cacheia). Offline —
// ou ao reabrir uma carga digitada offline (nome sem id) — cai num texto livre,
// pra não esconder o nome num select vazio. Limpar o texto reabilita a busca.
const motoristaTextoLivre = computed(
  () => !online.value || (!!local.value?.motorista && !local.value?.codpessoamotorista),
)
function onMotoristaSelect(opt) {
  local.value.motorista = opt?.label || null
}
function onMotoristaClear() {
  local.value.motorista = null
}

// ---- Pontos (origens / destinos) ----
const origens = computed(() => (local.value?.pontos || []).filter((p) => p.papel === 'ORIGEM'))
const destinos = computed(() => (local.value?.pontos || []).filter((p) => p.papel === 'DESTINO'))

function grupoDoPonto(p) {
  return p.papel === 'ORIGEM' ? origens.value : destinos.value
}
function addPonto(papel) {
  const contatipo = CONTATIPO_PADRAO[local.value.sentido]?.[papel] || 'UNIDADE'
  local.value.pontos.push(novoPonto(papel, contatipo))
  distribuirPercentual(papel === 'ORIGEM' ? origens.value : destinos.value)
}
function removerPonto(p) {
  const i = local.value.pontos.indexOf(p)
  if (i >= 0) local.value.pontos.splice(i, 1)
  distribuirPercentual(grupoDoPonto(p))
}

// Trocou o tipo (talhão/unidade/contrato): zera a seleção anterior. O select certo
// remonta; se virar UNIDADE única, o SelectUnidade preenche sozinho.
function onTipoChange(p) {
  p.codplantio = null
  p.codunidadearmazenadora = null
  p.codcontrato = null
  p.rotulo = null
}
function onEntidade(p, val) {
  if (p.contatipo === 'PLANTIO') p.codplantio = val
  else if (p.contatipo === 'UNIDADE') p.codunidadearmazenadora = val
  else if (p.contatipo === 'CONTRATO') p.codcontrato = val
  setRotuloPonto(p)
}
function rotuloPlantio(cod) {
  return store.plantioPorId(cod)?.rotulo || null
}
// Talhão escolhido no mapa pode ser de OUTRA safra (soja × milho): a carga
// segue a safra do talhão — é ela que define cultura, classificação e peso da
// saca. A página troca a safra ativa da listagem ao salvar.
function onPlantioSelecionado(plantio) {
  if (plantio?.codsafra && plantio.codsafra !== local.value.codsafra) {
    local.value.codsafra = plantio.codsafra
  }
}
function rotuloUnidade(cod) {
  return (
    unidadesAtivas.value.find((o) => o.codunidadearmazenadora === cod)?.unidadearmazenadora || null
  )
}
function setRotuloPonto(p) {
  if (p.contatipo === 'PLANTIO') p.rotulo = rotuloPlantio(p.codplantio)
  else if (p.contatipo === 'UNIDADE') p.rotulo = rotuloUnidade(p.codunidadearmazenadora)
  else if (p.contatipo === 'CONTRATO') p.rotulo = store.rotuloContrato(p.codcontrato)
}
function saldoContrato(cod) {
  return store.saldoContratoOffline(cod)
}

// ---- Classificação ----
// Parâmetros da cultura da safra da carga, na ordem da cascata.
const itensCarga = computed(() => store.parametrosDaCarga(local.value || {}))

// Estado vazio da classificação. Sem parâmetro cadastrado o desconto sairia 0 em
// silêncio e o líquido viria igual ao bruto — o operador precisa saber ONDE isso
// se resolve, não só que está vazio.
const avisoClassificacao = computed(() => {
  if (!local.value || itensCarga.value.length) return null
  return {
    titulo: `Nenhum parâmetro de classificação ativo para ${culturaAtiva.value?.cultura || 'a cultura desta safra'}.`,
    dica: 'Cadastre em Culturas › Classificação (umidade, impureza, avariados) — sem parâmetro não há desconto e o líquido sairia igual ao bruto.',
  }
})

// Trava do botão CLASSIFICAR. Só vale NA etapa CLASSIFICACAO: nas seguintes
// (TARA/FINALIZADO) a carga já passou por aqui, e travar de novo impediria
// corrigir um romaneio cujo parâmetro foi inativado depois.
const erroClassificacao = computed(() =>
  local.value?.etapa === 'CLASSIFICACAO' ? avisoClassificacao.value?.titulo || null : null,
)

const calc = computed(() => (local.value ? calcularCarga(local.value, itensCarga.value) : {}))

// Garante uma linha de leitura por parâmetro (preserva o já digitado).
watch(
  itensCarga,
  (itens) => {
    if (!local.value) return
    if (!Array.isArray(local.value.classificacao)) local.value.classificacao = []
    const existentes = new Set(local.value.classificacao.map((c) => c.codparametroclassificacao))
    for (const it of itens || []) {
      if (!existentes.has(it.codparametroclassificacao)) {
        local.value.classificacao.push({
          codparametroclassificacao: it.codparametroclassificacao,
          leitura: null,
          desconto: null,
        })
      }
    }
  },
  { immediate: true },
)
function linhaDe(codparam) {
  return (
    (local.value?.classificacao || []).find((c) => c.codparametroclassificacao === codparam) || {}
  )
}
function descontoParam(codparam) {
  return (
    (calc.value.classificacao || []).find((c) => c.codparametroclassificacao === codparam)
      ?.desconto || null
  )
}
function hintItem(item) {
  const partes = [`Tol. ${fmt(item.tolerancia, 1)}%`]
  if (item.metodo === 'FATOR' && Number(item.fator)) {
    partes.push(`fator ${fmt(item.fator, 1)}`)
  } else if (Number(item.desagio)) {
    partes.push(`deságio ${fmt(item.desagio, 1)}%`)
  }
  return partes.join(' · ')
}
function foraTolerancia(item) {
  const leitura = linhaDe(item.codparametroclassificacao)?.leitura
  if (leitura === null || leitura === undefined || leitura === '') return false
  return Number(leitura) > (Number(item.tolerancia) || 0)
}

const mostrarResultado = computed(() => calc.value.bruto !== null && calc.value.bruto !== undefined)
const pesosaca = computed(() => culturaAtiva.value?.pesosaca || 60)
const sacasLiquido = computed(() => sacas(calc.value.liquido, pesosaca.value))

const somaPercOrigens = computed(() =>
  origens.value.reduce((s, p) => s + (Number(p.percentual) || 0), 0),
)
const somaPercDestinos = computed(() =>
  destinos.value.reduce((s, p) => s + (Number(p.percentual) || 0), 0),
)
function somaPercBate(grupo) {
  const soma = grupo.reduce((s, p) => s + (Number(p.percentual) || 0), 0)
  return Math.abs(soma - 100) < 0.5
}
// kg estimado de um ponto — só depois de pesar (líquido da carga × %).
function kgDoPonto(p) {
  const liq = Number(calc.value.liquido)
  if (!(liq > 0)) return null
  return Math.round((liq * (Number(p.percentual) || 0)) / 100)
}

// ---- Validações de coleção (sem campo pra destacar) ----
function entradaValida() {
  if (!origens.value.length && !destinos.value.length) {
    $q.notify({ type: 'warning', message: 'Informe ao menos uma origem ou destino.' })
    return false
  }
  // Linha sem entidade seria DESCARTADA em silêncio (o filtro `pontoCompleto` do
  // store.salvar e o `contaDoPonto` do backend fazem o mesmo corte).
  if ((local.value?.pontos || []).some((p) => !pontoCompleto(p))) {
    $q.notify({
      type: 'negative',
      message: 'Selecione o talhão/unidade/contrato de cada origem e destino, ou remova a linha.',
    })
    return false
  }
  return true
}
function validarFinalizacao() {
  if (!origens.value.length || !destinos.value.length) {
    $q.notify({ type: 'negative', message: 'Informe ao menos uma origem e um destino.' })
    return false
  }
  if (!entradaValida()) return false
  if (!somaPercBate(origens.value) || !somaPercBate(destinos.value)) {
    $q.notify({ type: 'negative', message: 'A soma dos % de origem e de destino deve ser 100.' })
    return false
  }
  if (!(Number(calc.value.liquido) > 0)) {
    $q.notify({ type: 'negative', message: 'Peso líquido inválido (pbt − tara − desconto).' })
    return false
  }
  return true
}

// ---- Ações ----
function salvar() {
  if (finalizada.value ? !validarFinalizacao() : !entradaValida()) return
  emit('salvar', local.value)
}
// Salva na etapa ATUAL, sem avançar — pra corrigir um dado sem empurrar a carga.
function salvarSemAvancar() {
  if (!entradaValida()) return
  emit('salvar', local.value)
}
function cancelarCarga() {
  const sincronizada = !!local.value.codcarga || !!local.value.sincronizado
  $q.dialog({
    title: 'Cancelar carga',
    message: sincronizada
      ? `Cancelar a carga${local.value.codcarga ? ' #' + local.value.codcarga : ''}? Sai do pátio e estorna o estoque.`
      : 'Descartar esta carga pendente? Ela ainda não foi enviada ao servidor.',
    cancel: { label: 'Voltar', flat: true, color: 'grey-8' },
    ok: { label: 'Cancelar carga', flat: true, color: 'negative' },
    persistent: true,
  }).onOk(() => emit('cancelar', local.value))
}

const proxima = computed(() => proximaEtapa(local.value))
// Transição p/ FINALIZADO: ativa as :rules de "soma fecha" dos campos de líquido.
const finalizando = computed(() => proxima.value === 'FINALIZADO')

function avancar() {
  if (!entradaValida()) return
  if (erroClassificacao.value) {
    $q.notify({ type: 'negative', message: erroClassificacao.value })
    return
  }
  const prox = proxima.value
  if (!prox) return
  if (prox === 'FINALIZADO' && !validarFinalizacao()) return
  local.value.etapa = prox
  emit('avancar', local.value)
}

// Botão principal (FAB): registrar (nova), salvar (finalizada) ou avançar etapa.
function onSubmit() {
  if (props.novo || finalizada.value) salvar()
  else avancar()
}
const rotuloPrincipal = computed(() => {
  if (props.novo) return 'Registrar'
  if (finalizada.value) return 'Salvar'
  return etapaMeta.value.acao
})
const iconePrincipal = computed(() => {
  if (props.novo) return 'add'
  if (finalizada.value) return 'save'
  return etapaMeta.value.icon
})

function fazendaNome() {
  for (const p of origens.value) {
    if (p.contatipo === 'PLANTIO') {
      const f = store.plantioPorId(p.codplantio)?.Fazenda?.fazenda
      if (f) return f
    }
  }
  return 'MG Agro'
}

function imprimir() {
  if (!local.value || !finalizada.value) return
  const c = calc.value
  const veic = store.veiculoPorId(local.value.codveiculo)
  const itensFonte = local.value.sentido === 'SAIDA' ? destinos.value : origens.value
  const ok = imprimirTicket({
    titulo:
      local.value.sentido === 'SAIDA'
        ? 'ROMANEIO DE EXPEDIÇÃO'
        : local.value.sentido === 'TRANSFERENCIA'
          ? 'ROMANEIO DE TRANSFERÊNCIA'
          : 'ROMANEIO DE RECEBIMENTO',
    rotuloItens: local.value.sentido === 'SAIDA' ? 'Destinos' : 'Origens',
    assinaturas:
      local.value.sentido === 'SAIDA'
        ? ['Conferente', 'Motorista', 'Expedidor']
        : ['Classificador', 'Motorista', 'Recebedor'],
    numero: local.value.codcarga,
    data: local.value.data,
    fazenda: fazendaNome(),
    cultura: culturaAtiva.value?.cultura,
    safra: safraAtiva.value?.safra,
    placa: local.value.placa,
    placacarreta: local.value.placacarreta,
    veiculo: veic?.veiculo || null,
    motorista: local.value.motorista,
    itens: itensFonte.map((p) => ({ rotulo: p.rotulo, kg: kgDoPonto(p) })),
    pbt: local.value.pbt,
    tara: local.value.tara,
    bruto: c.bruto,
    classificacao: itensCarga.value
      .map((it) => ({
        nome: it.parametroclassificacao,
        leitura: linhaDe(it.codparametroclassificacao)?.leitura,
        desconto: descontoParam(it.codparametroclassificacao),
      }))
      .filter((x) => x.leitura != null && x.leitura !== ''),
    desconto: c.desconto,
    liquido: c.liquido,
    sacas: sacasLiquido.value,
    pesosaca: pesosaca.value,
  })
  if (!ok) $q.notify({ type: 'warning', message: 'Permita pop-ups para imprimir o romaneio.' })
}

// Atalhos da página (F3 = principal com validação do q-form; F4 = imprimir).
defineExpose({
  submit: () => formRef.value?.submit(),
  imprimir,
})
</script>

<template>
  <q-form v-if="local" ref="formRef" @submit.prevent="onSubmit">
    <div class="q-pa-md q-gutter-y-md carga-form">
      <!-- Tipo de romaneio + etapa -->
      <q-card flat bordered>
        <q-card-section class="row items-center q-col-gutter-sm">
          <div class="col-12 col-sm">
            <SelectSentido v-model="sentidoSel" :disable="!podeTrocarSentido" />
            <div v-if="!podeTrocarSentido && !finalizada" class="text-caption text-grey-6 q-mt-xs">
              Já pesado — o tipo de romaneio não muda mais.
            </div>
          </div>
          <div class="col-auto">
            <q-chip
              :color="etapaMeta.color"
              text-color="white"
              :icon="etapaMeta.icon"
              :label="etapaMeta.label"
            />
          </div>
        </q-card-section>
      </q-card>

      <!-- Identificação -->
      <q-card flat bordered>
        <q-card-section>
          <div class="text-subtitle2 text-grey-8 q-mb-sm">Caminhão</div>
          <div class="row q-col-gutter-x-md">
            <q-select
              :model-value="local.placa"
              :options="placaOptions"
              label="Placa"
              outlined
              use-input
              fill-input
              hide-selected
              clearable
              input-debounce="200"
              new-value-mode="add-unique"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              class="col-6 col-sm-3"
              autofocus
              lazy-rules
              :rules="[() => !!local.placa || 'Informe a placa.']"
              @filter="filtrarPlaca"
              @update:model-value="resolverPlaca"
              @blur="onPlacaBlur"
            >
              <template #no-option>
                <q-item v-if="placaBusca" clickable @click="cadastroCaminhao = true">
                  <q-item-section avatar><q-icon name="add" color="primary" /></q-item-section>
                  <q-item-section class="text-primary">Cadastrar “{{ placaBusca }}”</q-item-section>
                </q-item>
                <q-item v-else>
                  <q-item-section class="text-grey-6">Digite a placa…</q-item-section>
                </q-item>
              </template>
            </q-select>

            <q-input
              v-model="local.placacarreta"
              label="Carreta"
              outlined
              class="col-6 col-sm-3"
              @update:model-value="local.placacarreta = ($event || '').toUpperCase()"
            />

            <MgSelectPessoa
              v-if="!motoristaTextoLivre"
              v-model="local.codpessoamotorista"
              label="Motorista"
              clearable
              :bottom-slots="false"
              class="col-12 col-sm-3"
              @select="onMotoristaSelect"
              @clear="onMotoristaClear"
            />
            <q-input
              v-else
              v-model="local.motorista"
              label="Motorista"
              hint="Offline — texto livre"
              outlined
              clearable
              class="col-12 col-sm-3"
              @update:model-value="local.codpessoamotorista = null"
            />

            <MgInputData
              v-model="local.data"
              type="timestamp"
              label="Chegada"
              :max="dataMax"
              class="col-12 col-sm-3"
            />
          </div>
        </q-card-section>
      </q-card>

      <!-- Origens / Destinos -->
      <q-card flat bordered>
        <q-card-section>
          <div class="row q-col-gutter-lg">
            <div class="col-12 col-md-6">
              <div class="text-subtitle2 text-grey-8 q-mb-xs">Origem do grão</div>
              <div
                v-for="(p, i) in origens"
                :key="'o' + i"
                class="row q-col-gutter-sm items-center q-mb-xs"
              >
                <SelectContaTipo
                  v-model="p.contatipo"
                  papel="ORIGEM"
                  label="Origem"
                  class="col-4"
                  @update:model-value="onTipoChange(p)"
                />
                <SelectTalhao
                  v-if="p.contatipo === 'PLANTIO'"
                  :model-value="p.codplantio"
                  :codsafra="local.codsafra"
                  class="col"
                  lazy-rules
                  :rules="[(v) => !!v || 'Selecione o talhão.']"
                  @update:model-value="(v) => onEntidade(p, v)"
                  @select="onPlantioSelecionado"
                />
                <SelectUnidade
                  v-else-if="p.contatipo === 'UNIDADE'"
                  :model-value="p.codunidadearmazenadora"
                  class="col"
                  lazy-rules
                  :rules="[(v) => !!v || 'Selecione a unidade.']"
                  @update:model-value="(v) => onEntidade(p, v)"
                />
                <SelectContrato
                  v-else
                  :model-value="p.codcontrato"
                  operacao="compra"
                  class="col"
                  lazy-rules
                  :rules="[(v) => !!v || 'Selecione o contrato.']"
                  @update:model-value="(v) => onEntidade(p, v)"
                />
                <MgInputValor
                  v-model="p.percentual"
                  :decimals="1"
                  suffix="%"
                  :min="0"
                  :max="100"
                  label="%"
                  :readonly="origens.length === 1"
                  class="col-3"
                  lazy-rules
                  :rules="[
                    () => !finalizando || somaPercBate(origens) || 'Soma dos % deve ser 100',
                  ]"
                />
                <!-- Sempre visível: uma linha semeada e impreenchível (ex.: safra sem
                     talhão) travaria o registro sem saída. Remover é a válvula. -->
                <q-btn
                  flat
                  round
                  color="grey-7"
                  icon="close"
                  class="col-auto"
                  @click="removerPonto(p)"
                />
              </div>
              <div
                v-if="origens.length"
                class="text-caption q-mb-xs"
                :class="somaPercBate(origens) ? 'text-grey-7' : 'text-orange-8'"
              >
                Soma: {{ fmt(somaPercOrigens, 1) }}%
                <span v-if="calc.liquido"> · líquido {{ fmt(calc.liquido) }} kg</span>
              </div>
              <q-btn
                flat
                dense
                color="primary"
                icon="add"
                label="Origem"
                @click="addPonto('ORIGEM')"
              />
            </div>

            <div class="col-12 col-md-6">
              <div class="text-subtitle2 text-grey-8 q-mb-xs">Destino do grão</div>
              <div v-for="(p, i) in destinos" :key="'d' + i" class="q-mb-sm">
                <div class="row q-col-gutter-sm items-center">
                  <SelectContaTipo
                    v-model="p.contatipo"
                    papel="DESTINO"
                    label="Destino"
                    class="col-4"
                    @update:model-value="onTipoChange(p)"
                  />
                  <SelectUnidade
                    v-if="p.contatipo === 'UNIDADE'"
                    :model-value="p.codunidadearmazenadora"
                    class="col"
                    lazy-rules
                    :rules="[(v) => !!v || 'Selecione a unidade.']"
                    @update:model-value="(v) => onEntidade(p, v)"
                  />
                  <SelectContrato
                    v-else
                    :model-value="p.codcontrato"
                    operacao="venda"
                    class="col"
                    lazy-rules
                    :rules="[(v) => !!v || 'Selecione o contrato.']"
                    @update:model-value="(v) => onEntidade(p, v)"
                  />
                  <MgInputValor
                    v-model="p.percentual"
                    :decimals="1"
                    suffix="%"
                    :min="0"
                    :max="100"
                    label="%"
                    :readonly="destinos.length === 1"
                    class="col-3"
                    lazy-rules
                    :rules="[
                      () => !finalizando || somaPercBate(destinos) || 'Soma dos % deve ser 100',
                    ]"
                  />
                  <q-btn
                    flat
                    round
                    color="grey-7"
                    icon="close"
                    class="col-auto"
                    @click="removerPonto(p)"
                  />
                </div>
                <div
                  v-if="p.contatipo === 'CONTRATO' && p.codcontrato"
                  class="text-caption q-pl-sm"
                >
                  <span v-if="saldoContrato(p.codcontrato) === Infinity" class="text-deep-purple-7">
                    <q-icon name="all_inclusive" /> Volume em aberto
                  </span>
                  <span
                    v-else
                    :class="
                      (kgDoPonto(p) || 0) > saldoContrato(p.codcontrato) + 1
                        ? 'text-negative text-weight-medium'
                        : 'text-grey-6'
                    "
                  >
                    Saldo a entregar: {{ fmt(saldoContrato(p.codcontrato)) }} kg
                    <span v-if="kgDoPonto(p)"> · esta carga ≈ {{ fmt(kgDoPonto(p)) }} kg</span>
                  </span>
                </div>
                <div
                  v-if="mostrarFiscal && p.contatipo === 'CONTRATO'"
                  class="row q-col-gutter-sm q-mt-xs"
                >
                  <q-input v-model="p.numeronf" label="Nº NF" outlined class="col" />
                  <MgInputValor
                    v-model="p.valornf"
                    :decimals="2"
                    prefix="R$"
                    label="Valor NF"
                    class="col"
                  />
                </div>
              </div>
              <div
                v-if="destinos.length"
                class="text-caption q-mb-xs"
                :class="somaPercBate(destinos) ? 'text-grey-7' : 'text-orange-8'"
              >
                Soma: {{ fmt(somaPercDestinos, 1) }}%
              </div>
              <q-btn
                flat
                dense
                color="primary"
                icon="add"
                label="Destino"
                @click="addPonto('DESTINO')"
              />
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Pesagem (ordem por sentido) -->
      <q-card v-if="mostrarPbt || mostrarTara" flat bordered>
        <q-card-section>
          <div class="text-subtitle2 text-grey-8 q-mb-sm">Pesagem</div>
          <div class="row q-col-gutter-md">
            <MgInputValor
              v-if="mostrarPbt"
              v-model="local.pbt"
              :decimals="0"
              suffix="kg"
              label="Peso bruto total (caminhão + carga)"
              class="col-12 col-sm-6"
              lazy-rules
              :rules="[
                (v) => novo || local.etapa !== 'PBT' || v > 0 || 'Informe o peso bruto (PBT).',
              ]"
            />
            <MgInputValor
              v-if="mostrarTara"
              v-model="local.tara"
              :decimals="0"
              suffix="kg"
              label="Tara (caminhão vazio)"
              class="col-12 col-sm-6"
              lazy-rules
              :rules="[
                (v) => novo || local.etapa !== 'TARA' || v > 0 || 'Informe a tara.',
                // Cruzadas: cobradas assim que os DOIS pesos existem.
                (v) =>
                  v == null ||
                  local.pbt == null ||
                  v < Number(local.pbt) ||
                  'A tara deve ser menor que o PBT.',
                () =>
                  local.pbt == null ||
                  local.tara == null ||
                  Number(calc.liquido) > 0 ||
                  'Líquido (PBT − tara − desconto) deve ser maior que zero.',
              ]"
            />
          </div>

          <!-- Prévia do resultado (ao vivo; o gravado fica no resumo à direita) -->
          <div v-if="mostrarResultado" class="row text-center bg-grey-1 rounded-borders q-pa-sm">
            <div class="col">
              <div class="text-caption text-grey-7">Bruto</div>
              <div class="text-weight-medium">{{ fmt(calc.bruto) }} kg</div>
            </div>
            <div class="col">
              <div class="text-caption text-grey-7">Desconto</div>
              <div class="text-weight-medium text-orange-9">{{ fmt(calc.desconto) }} kg</div>
            </div>
            <div class="col">
              <div class="text-caption text-grey-7">Líquido</div>
              <div class="text-weight-medium text-green-9">{{ fmt(calc.liquido) }} kg</div>
            </div>
            <div class="col">
              <div class="text-caption text-grey-7">Sacas</div>
              <div class="text-weight-medium">{{ fmt(sacasLiquido, 1) }}</div>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Classificação (só recebimento, a partir da etapa) -->
      <q-card v-if="mostrarClassificacao" flat bordered>
        <q-card-section>
          <div class="text-subtitle2 text-grey-8 q-mb-sm">Classificação</div>
          <q-banner
            v-if="avisoClassificacao"
            dense
            rounded
            class="bg-orange-1 text-orange-9 q-mb-sm"
          >
            <template #avatar><q-icon name="warning" color="orange-8" /></template>
            {{ avisoClassificacao.titulo }}
            <div class="text-caption">{{ avisoClassificacao.dica }}</div>
          </q-banner>
          <div class="row q-col-gutter-md">
            <div
              v-for="item in itensCarga"
              :key="item.codparametroclassificacao"
              class="col-6 col-sm-4 col-md-3"
            >
              <MgInputValor
                v-model="linhaDe(item.codparametroclassificacao).leitura"
                :decimals="1"
                suffix="%"
                :label="`${item.ordem}. ${item.parametroclassificacao}`"
                :hint="hintItem(item)"
                lazy-rules
                :rules="[
                  (v) => v == null || (v >= 0 && v <= 100) || 'Leitura deve ficar entre 0 e 100%.',
                ]"
              />
              <div v-if="foraTolerancia(item)" class="text-caption text-orange-9 q-pl-sm">
                acima da tolerância
              </div>
              <div
                v-if="descontoParam(item.codparametroclassificacao)"
                class="text-caption text-orange-8 q-pl-sm"
              >
                − {{ fmt(descontoParam(item.codparametroclassificacao)) }} kg
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <q-input
        v-model="local.observacao"
        label="Observação"
        type="textarea"
        autogrow
        outlined
        bg-color="white"
      />

      <!-- Espaço pros FABs não cobrirem o último campo -->
      <div class="q-py-xl" />
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <div class="row items-center q-gutter-sm">
        <q-btn v-if="!novo" fab icon="delete" color="negative" @click="cancelarCarga">
          <q-tooltip>Cancelar carga</q-tooltip>
        </q-btn>
        <q-btn v-if="finalizada" fab icon="print" color="accent" @click="imprimir">
          <q-tooltip>Imprimir romaneio (F4)</q-tooltip>
        </q-btn>
        <q-btn v-if="!novo && !finalizada" fab icon="save" color="grey-7" @click="salvarSemAvancar">
          <q-tooltip>Salvar sem avançar</q-tooltip>
        </q-btn>
        <q-btn type="submit" fab :icon="iconePrincipal" :label="rotuloPrincipal" color="primary">
          <q-tooltip>F3</q-tooltip>
        </q-btn>
      </div>
    </q-page-sticky>
  </q-form>

  <CaminhaoDialog v-model="cadastroCaminhao" :placa="placaBusca" @criado="onCaminhaoCriado" />
</template>

<style scoped>
.carga-form {
  max-width: 1000px;
  margin: 0 auto;
}
</style>
