<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import {
  useCargaStore,
  ETAPAS_POR_SENTIDO,
  CONTATIPO_PADRAO,
  novoPonto,
  pontoCompleto,
} from 'src/stores/carga'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'
import { calcularCarga, sacas } from 'src/utils/desconto'
import { imprimirTicket } from 'src/utils/ticket'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import CaminhaoDialog from 'components/CaminhaoDialog.vue'
import SelectContaTipo from 'components/SelectContaTipo.vue'
import SelectTalhao from 'components/SelectTalhao.vue'
import SelectUnidade from 'components/SelectUnidade.vue'
import SelectContrato from 'components/SelectContrato.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  carga: { type: Object, default: null },
  // true = carga nova (só "Registrar", entra na 1ª etapa sem avançar)
  novo: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'salvar', 'avancar'])

const $q = useQuasar()
const store = useCargaStore()
const { plantiosDaSafra, faixasDaSafra, culturaAtiva, safraAtiva, veiculosAtivos, unidadesAtivas } =
  storeToRefs(store)
const { online } = storeToRefs(useSincronizacaoStore())

const local = ref(null)
watch(
  () => props.carga,
  (c) => {
    if (!c) {
      local.value = null
      return
    }
    const carga = normalizarPontos(JSON.parse(JSON.stringify(c)))
    if (props.novo) semearPontos(carga)
    local.value = carga
  },
  { immediate: true },
)

// Carga nova já abre com 1 origem + 1 destino no tipo padrão do sentido (a
// unidade única é pré-selecionada pelo SelectUnidade; o talhão/contrato o
// operador escolhe). Só semeia o que faltar.
function semearPontos(carga) {
  const s = carga.sentido
  if (!carga.pontos.some((p) => p.papel === 'ORIGEM')) {
    carga.pontos.push(novoPonto('ORIGEM', CONTATIPO_PADRAO[s]?.ORIGEM || 'UNIDADE'))
  }
  if (!carga.pontos.some((p) => p.papel === 'DESTINO')) {
    carga.pontos.push(novoPonto('DESTINO', CONTATIPO_PADRAO[s]?.DESTINO || 'UNIDADE'))
  }
}

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

const show = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const ordem = computed(() => ETAPAS_POR_SENTIDO[local.value?.sentido] || [])
const idxEtapa = computed(() => ordem.value.indexOf(local.value?.etapa))
const mostrarPbt = computed(() => idxEtapa.value >= ordem.value.indexOf('PBT'))
const mostrarTara = computed(() => idxEtapa.value >= ordem.value.indexOf('TARA'))
const mostrarClassificacao = computed(
  () =>
    local.value?.sentido === 'ENTRADA' && idxEtapa.value >= ordem.value.indexOf('CLASSIFICACAO'),
)
const mostrarFiscal = computed(
  () => local.value?.sentido === 'SAIDA' && idxEtapa.value >= ordem.value.indexOf('FISCAL'),
)

const labelEtapa = {
  PBT: 'Peso bruto total',
  TARA: 'Tara',
  CLASSIFICACAO: 'Classificação',
  FISCAL: 'Nota Fiscal',
  FINALIZADO: 'Finalizado',
}
const rotuloAcao = {
  PBT: 'Pesar bruto',
  TARA: 'Pesar tara',
  CLASSIFICACAO: 'Classificar',
  FISCAL: 'Notas fiscais',
  FINALIZADO: 'Imprimir romaneio',
}

// ---- Placa (autocomplete do cache de veículos, funciona offline) ----
const placaOptions = ref([])
const placaBusca = ref('')
const cadastroCaminhao = ref(false)

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

// Grupo (origens/destinos) do ponto — pra travar o % em 100 quando é linha única.
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
// Escolheu a entidade (talhão/unidade/contrato): grava no campo certo + rótulo.
function onEntidade(p, val) {
  if (p.contatipo === 'PLANTIO') p.codplantio = val
  else if (p.contatipo === 'UNIDADE') p.codunidadearmazenadora = val
  else if (p.contatipo === 'CONTRATO') p.codcontrato = val
  setRotuloPonto(p)
}
function rotuloPlantio(cod) {
  return plantiosDaSafra.value.find((o) => o.codplantio === cod)?.rotulo || null
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

const calc = computed(() => (local.value ? calcularCarga(local.value, faixasDaSafra.value) : {}))
const mostrarResultado = computed(() => calc.value.bruto !== null && calc.value.bruto !== undefined)
const pesosaca = computed(() => culturaAtiva.value?.pesosaca || 60)
const sacasLiquido = computed(() => sacas(calc.value.liquido, pesosaca.value))

const somaPercOrigens = computed(() =>
  origens.value.reduce((s, p) => s + (Number(p.percentual) || 0), 0),
)
const somaPercDestinos = computed(() =>
  destinos.value.reduce((s, p) => s + (Number(p.percentual) || 0), 0),
)
// Soma dos % de um grupo fecha em 100 (tolerância de arredondamento).
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

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}

// Trava de coleção (sem campo pra destacar): exige ao menos uma origem/destino.
// Placa, pbt, tara e o "soma fecha" são :rules nos campos do q-form.
function entradaValida() {
  if (!origens.value.length && !destinos.value.length) {
    $q.notify({ type: 'warning', message: 'Informe ao menos uma origem ou destino.' })
    return false
  }
  return true
}

// Travas de finalização (origem+destino completos, % fecha 100, líquido > 0).
// Usadas ao avançar p/ FINALIZADO e ao salvar uma carga já finalizada (edição).
function validarFinalizacao() {
  if (!origens.value.length || !destinos.value.length) {
    $q.notify({ type: 'negative', message: 'Informe ao menos uma origem e um destino.' })
    return false
  }
  if (!origens.value.every(pontoCompleto) || !destinos.value.every(pontoCompleto)) {
    $q.notify({
      type: 'negative',
      message: 'Selecione o talhão/unidade/contrato de cada origem e destino.',
    })
    return false
  }
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

// Salvar (carga nova OU já finalizada). Numa carga finalizada mantém as travas de
// finalização pra não regravar incompleta; nas demais, exige ao menos origem/destino.
function salvar() {
  if (local.value.etapa === 'FINALIZADO' ? !validarFinalizacao() : !entradaValida()) return
  emit('salvar', local.value)
}

// Botão principal do rodapé: registrar (nova), salvar (finalizada) ou avançar etapa.
function onSubmit() {
  if (props.novo || local.value.etapa === 'FINALIZADO') salvar()
  else avancar()
}
const rotuloPrincipal = computed(() => {
  if (props.novo) return 'Registrar'
  if (local.value?.etapa === 'FINALIZADO') return 'Salvar'
  return rotuloAcao[local.value?.etapa]
})

const proxima = computed(() => {
  const i = idxEtapa.value
  return i >= 0 && i < ordem.value.length - 1 ? ordem.value[i + 1] : null
})
// Transição p/ FINALIZADO: ativa as :rules de "soma fecha" dos campos de líquido.
const finalizando = computed(() => proxima.value === 'FINALIZADO')

function avancar() {
  if (!entradaValida()) return
  const prox = proxima.value
  if (!prox) return
  // Antes de finalizar: origem+destino completos, % fecha 100 e líquido > 0.
  if (prox === 'FINALIZADO' && !validarFinalizacao()) return
  local.value.etapa = prox
  emit('avancar', local.value)
}

function fazendaNome() {
  for (const p of origens.value) {
    if (p.contatipo === 'PLANTIO') {
      const f = plantiosDaSafra.value.find((o) => o.codplantio === p.codplantio)?.Fazenda?.fazenda
      if (f) return f
    }
  }
  return 'MG Agro'
}

function imprimir() {
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
    umidade: local.value.umidade,
    impureza: local.value.impureza,
    avariados: local.value.avariados,
    desconto: c.desconto,
    liquido: c.liquido,
    sacas: sacasLiquido.value,
    pesosaca: pesosaca.value,
  })
  if (!ok) $q.notify({ type: 'warning', message: 'Permita pop-ups para imprimir o romaneio.' })
}
</script>

<template>
  <q-dialog v-model="show" :maximized="$q.screen.lt.sm">
    <q-card v-if="local" flat style="width: 620px; max-width: 95vw">
      <q-form @submit.prevent="onSubmit">
        <q-card-section class="row items-center bg-primary text-white">
          <div class="text-h6">{{ local.placa || 'Nova carga' }}</div>
          <q-space />
          <q-chip color="white" text-color="primary" :label="labelEtapa[local.etapa]" />
          <q-btn flat round icon="close" v-close-popup tabindex="-1" />
        </q-card-section>

        <q-card-section class="q-gutter-y-md scroll" style="max-height: 72vh">
          <!-- Identificação -->
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
              class="col-6 col-sm-4"
              autofocus
              lazy-rules
              :rules="[() => !!local.placa]"
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
              class="col-6 col-sm-4"
              @update:model-value="local.placacarreta = ($event || '').toUpperCase()"
            />

            <MgSelectPessoa
              v-if="!motoristaTextoLivre"
              v-model="local.codpessoamotorista"
              label="Motorista"
              clearable
              :bottom-slots="false"
              class="col-12 col-sm-4"
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
              class="col-12 col-sm-4"
              @update:model-value="local.codpessoamotorista = null"
            />
          </div>

          <!-- Origens -->
          <div>
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
                class="col-3"
                @update:model-value="onTipoChange(p)"
              />
              <SelectTalhao
                v-if="p.contatipo === 'PLANTIO'"
                :model-value="p.codplantio"
                class="col"
                @update:model-value="(v) => onEntidade(p, v)"
              />
              <SelectUnidade
                v-else-if="p.contatipo === 'UNIDADE'"
                :model-value="p.codunidadearmazenadora"
                class="col"
                @update:model-value="(v) => onEntidade(p, v)"
              />
              <SelectContrato
                v-else
                :model-value="p.codcontrato"
                operacao="compra"
                class="col"
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
                :rules="[() => !finalizando || somaPercBate(origens) || 'Soma dos % deve ser 100']"
              />
              <q-btn
                v-if="origens.length > 1"
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
            <div>
              <q-btn flat dense color="primary" icon="add" @click="addPonto('ORIGEM')" />
            </div>
          </div>

          <q-separator />

          <!-- Destinos -->
          <div>
            <div class="text-subtitle2 text-grey-8 q-mb-xs">Destino do grão</div>
            <div v-for="(p, i) in destinos" :key="'d' + i" class="q-mb-sm">
              <div class="row q-col-gutter-sm items-center">
                <SelectContaTipo
                  v-model="p.contatipo"
                  papel="DESTINO"
                  label="Destino"
                  class="col-3"
                  @update:model-value="onTipoChange(p)"
                />
                <SelectUnidade
                  v-if="p.contatipo === 'UNIDADE'"
                  :model-value="p.codunidadearmazenadora"
                  class="col"
                  @update:model-value="(v) => onEntidade(p, v)"
                />
                <SelectContrato
                  v-else
                  :model-value="p.codcontrato"
                  operacao="venda"
                  class="col"
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
                  v-if="destinos.length > 1"
                  flat
                  round
                  color="grey-7"
                  icon="close"
                  class="col-auto"
                  @click="removerPonto(p)"
                />
              </div>
              <div v-if="p.contatipo === 'CONTRATO' && p.codcontrato" class="text-caption q-pl-sm">
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
            <div>
              <q-btn flat color="primary" icon="add" dense @click="addPonto('DESTINO')" />
            </div>
          </div>

          <q-separator />

          <!-- Pesos (ordem por sentido) -->
          <MgInputValor
            v-if="mostrarPbt"
            v-model="local.pbt"
            :decimals="0"
            suffix="kg"
            label="Peso bruto total (caminhão + carga)"
            lazy-rules
            :rules="[() => novo || local.etapa !== 'PBT' || !!local.pbt]"
          />
          <MgInputValor
            v-if="mostrarTara"
            v-model="local.tara"
            :decimals="0"
            suffix="kg"
            label="Tara (caminhão vazio)"
            lazy-rules
            :rules="[() => novo || local.etapa !== 'TARA' || !!local.tara]"
          />

          <!-- Classificação (só recebimento, a partir da etapa) -->
          <div v-if="mostrarClassificacao" class="row q-col-gutter-x-md">
            <MgInputValor
              v-model="local.umidade"
              :decimals="1"
              suffix="%"
              label="Umidade"
              class="col-4"
            />
            <MgInputValor
              v-model="local.impureza"
              :decimals="1"
              suffix="%"
              label="Impureza"
              class="col-4"
            />
            <MgInputValor
              v-model="local.avariados"
              :decimals="1"
              suffix="%"
              label="Avariados"
              class="col-4"
            />
          </div>

          <!-- Resultado -->
          <q-card v-if="mostrarResultado" flat bordered class="bg-grey-1">
            <q-card-section class="q-pa-sm row text-center">
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
            </q-card-section>
          </q-card>

          <q-input
            v-model="local.observacao"
            label="Observação"
            type="textarea"
            autogrow
            outlined
          />
        </q-card-section>

        <q-separator />

        <q-card-actions align="right">
          <q-btn
            v-if="local.etapa === 'FINALIZADO'"
            flat
            color="primary"
            icon="print"
            label="Imprimir romaneio"
            class="q-mr-auto"
            tabindex="-1"
            @click="imprimir()"
          />
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn type="submit" flat color="primary" :label="rotuloPrincipal" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <CaminhaoDialog v-model="cadastroCaminhao" :placa="placaBusca" @criado="onCaminhaoCriado" />
</template>
