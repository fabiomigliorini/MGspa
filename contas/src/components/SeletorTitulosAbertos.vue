<script setup>
import { ref, computed, watch } from 'vue'
import { api } from 'src/services/api'
import { formataNumero, formataDataSemHora } from '@components/formatters'
import SelectFilial from 'src/components/select/SelectFilial.vue'
import SelectPessoa from 'src/components/select/SelectPessoa.vue'
import SelectGrupoEconomico from 'src/components/select/SelectGrupoEconomico.vue'
import SelectTipoTitulo from 'src/components/select/SelectTipoTitulo.vue'
import SelectContaContabil from 'src/components/select/SelectContaContabil.vue'
import SelectPortador from 'src/components/select/SelectPortador.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  codpessoaInicial: { type: Number, default: null },
})

const emit = defineEmits([
  'update:modelValue',
  'update:codpessoa',
  'update:totalLiquido',
  'update:operacao',
])

// Cálculo de juros conforme legado MGsis/MGJuros.php
const parametros = ref({
  juros: 4, // % ao mês
  multa: 2, // %
  diasTolerancia: 3,
})

function diasAtraso(vencimento) {
  if (!vencimento) return 0
  const venc = new Date(String(vencimento).slice(0, 10) + 'T00:00:00')
  const hoje = new Date()
  hoje.setHours(0, 0, 0, 0)
  return Math.floor((hoje.getTime() - venc.getTime()) / 86400000)
}

function calcularJurosMulta(t) {
  const dias = diasAtraso(t.vencimento)
  const valor = Number(t.saldo) || 0
  // Juros e multa apenas para títulos a receber (DB)
  if (t.operacao === 'DB' && dias > parametros.value.diasTolerancia && valor > 0) {
    return {
      juros: round2(valor * (parametros.value.juros / 30 / 100) * dias),
      multa: round2(valor * (parametros.value.multa / 100)),
      dias,
    }
  }
  return { juros: 0, multa: 0, dias }
}

function maxTotal(t) {
  const { juros, multa } = calcularJurosMulta(t)
  return (Number(t.saldo) || 0) + juros + multa
}

function round2(n) {
  return Math.round(Number(n) * 100) / 100
}

// === Filtros ===
const filtros = ref({
  codfilial: null,
  codgrupoeconomico: null,
  codpessoa: props.codpessoaInicial,
  vencimento_de: null,
  vencimento_ate: null,
  credito: null,
  codtipotitulo: null,
  codcontacontabil: null,
  codportador: null,
})

watch(
  () => props.codpessoaInicial,
  (v) => {
    if (v && v !== filtros.value.codpessoa) filtros.value.codpessoa = v
  },
)

const operacaoOptions = [
  { label: 'Todos', value: null },
  { label: 'CR', value: 1 },
  { label: 'DB', value: 2 },
]

// === Estado da listagem ===
// Cada titulo: { ...t (dados do API), selecionado, capital, multa, juros, desconto, total }
// titulo.saldo = saldo original do API (imutável). titulo.capital = capital sendo pago.
const loading = ref(false)
const titulos = ref([])

const selecionados = computed(() => titulos.value.filter((r) => r.selecionado))

function novoRegistro(t) {
  const { juros, multa } = calcularJurosMulta(t)
  return {
    ...t,
    selecionado: false,
    capital: t.saldo,
    multa: multa || null,
    juros: juros || null,
    desconto: null,
    total: round2(t.saldo + multa + juros),
  }
}

function syncFromModel() {
  // pré-carrega seleção a partir do modelValue. Dados completos virão no buscar()
  titulos.value = props.modelValue.map((l) => ({
    ...l,
    capital: l.saldo, // o "saldo" do parent é o capital editado
    selecionado: true,
  }))
}
syncFromModel()

watch(
  () => props.modelValue,
  () => {
    if (props.modelValue.length === 0) {
      for (const r of titulos.value) r.selecionado = false
    }
  },
)

function emitModel() {
  const arr = selecionados.value.map((r) => ({
    codtitulo: r.codtitulo,
    codpessoa: r.codpessoa,
    codfilial: r.codfilial,
    operacao: r.operacao,
    saldo: r.capital, // parent espera "saldo" para o capital editado
    multa: r.multa,
    juros: r.juros,
    desconto: r.desconto,
    total: r.total,
  }))
  emit('update:modelValue', arr)

  let cr = 0
  let db = 0
  for (const l of arr) {
    if (l.operacao === 'CR') cr += Number(l.total) || 0
    else db += Number(l.total) || 0
  }
  const liq = db - cr
  emit('update:totalLiquido', Math.abs(liq))
  emit('update:operacao', liq < 0 ? 'CR' : 'DB')
}

// === Buscar títulos ===
async function buscar() {
  loading.value = true
  try {
    const params = {
      codpessoa: filtros.value.codpessoa,
      codgrupoeconomico: filtros.value.codgrupoeconomico,
      codfilial: filtros.value.codfilial,
      vencimento_de: filtros.value.vencimento_de,
      vencimento_ate: filtros.value.vencimento_ate,
      credito: filtros.value.credito,
      codtipotitulo: filtros.value.codtipotitulo,
      codcontacontabil: filtros.value.codcontacontabil,
      codportador: filtros.value.codportador,
    }
    const { data } = await api.get('v1/titulo/abertos-para-fechamento', { params })
    const novosTitulos = data.data || []
    // merge: mantém selecionados que sumiram no resultado novo + atualiza dados imutáveis dos que voltaram
    const novosCodigos = new Set(novosTitulos.map((t) => t.codtitulo))
    const existentesPorCod = new Map(titulos.value.map((r) => [r.codtitulo, r]))
    const faltando = titulos.value.filter((r) => r.selecionado && !novosCodigos.has(r.codtitulo))
    const novos = novosTitulos.map((t) => {
      const existente = existentesPorCod.get(t.codtitulo)
      if (existente) {
        return {
          ...t,
          selecionado: existente.selecionado,
          capital: existente.capital,
          multa: existente.multa,
          juros: existente.juros,
          desconto: existente.desconto,
          total: existente.total,
        }
      }
      return novoRegistro(t)
    })
    titulos.value = [...faltando, ...novos]
  } finally {
    loading.value = false
  }
}

watch(
  () => filtros.value.codpessoa,
  (v) => emit('update:codpessoa', v),
)

let debounceTimer = null
watch(
  () => ({ ...filtros.value }),
  () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(buscar, 400)
  },
  { deep: true, immediate: true },
)

// === Seleção e edição ===
function toggle(titulo) {
  titulo.selecionado = !titulo.selecionado
  emitModel()
}

function updateEd(titulo, campo, valor) {
  if (titulo[campo] === valor) return // sem mudança real → não auto-seleciona
  titulo[campo] = valor
  if (!titulo.selecionado) titulo.selecionado = true
  recalc(titulo, campo)
  emitModel()
}

// Recálculo proporcional (espelha lógica do legado)
function recalc(titulo, campo) {
  const { juros: jurosCalc, multa: multaCalc } = calcularJurosMulta(titulo)
  const saldoCalc = Number(titulo.saldo) || 0
  const totalCalc = round2(saldoCalc + jurosCalc + multaCalc)

  let capital = Number(titulo.capital) || 0
  let multa = Number(titulo.multa) || 0
  let juros = Number(titulo.juros) || 0
  let desconto = Number(titulo.desconto) || 0
  let total = Number(titulo.total) || 0

  if (campo === 'capital') {
    let perc = 1
    if (capital > saldoCalc) capital = saldoCalc
    else if (saldoCalc > 0) perc = capital / saldoCalc
    juros = round2(jurosCalc * perc)
    multa = round2(multaCalc * perc)
    desconto = null
  } else if (campo === 'total') {
    if (total > totalCalc) total = totalCalc
    // Escala juros/multa proporcionalmente considerando o desconto vigente
    let perc = totalCalc > 0 ? (total + desconto) / totalCalc : 1
    if (perc > 1) perc = 1
    if (perc < 0) perc = 0
    juros = round2(jurosCalc * perc)
    multa = round2(multaCalc * perc)
    capital = round2(total - multa - juros + desconto)
    if (capital > saldoCalc) capital = saldoCalc
    if (capital < 0) capital = 0
  } else if (campo === 'desconto') {
    if (desconto > capital + juros + multa) desconto = capital + juros + multa
  }

  total = round2(capital + multa + juros - desconto)
  titulo.capital = round2(capital)
  titulo.multa = multa || null
  titulo.juros = juros || null
  titulo.desconto = desconto ? round2(desconto) : null
  titulo.total = total
}

// === Seleção em massa ===
function selecionarVencidos() {
  for (const r of titulos.value) {
    r.selecionado = diasAtraso(r.vencimento) >= 0
  }
  emitModel()
}

function selecionarTodos() {
  for (const r of titulos.value) r.selecionado = true
  emitModel()
}

function deselecionarTodos() {
  for (const r of titulos.value) r.selecionado = false
  emitModel()
}

function limparJurosMulta() {
  for (const r of titulos.value) {
    r.multa = null
    r.juros = null
    r.total = round2((Number(r.capital) || 0) - (Number(r.desconto) || 0))
  }
  emitModel()
}

function recalcularJurosMulta() {
  for (const r of titulos.value) {
    const { juros, multa } = calcularJurosMulta(r)
    r.multa = multa || null
    r.juros = juros || null
    r.total = round2((Number(r.capital) || 0) + multa + juros - (Number(r.desconto) || 0))
  }
  emitModel()
}

// === Dialog de Recalcular (com parâmetros customizáveis) ===
const dialogRecalcular = ref(false)
const recalcForm = ref({ juros: 4, multa: 2, diasTolerancia: 3 })

function abrirDialogRecalcular() {
  recalcForm.value.juros = parametros.value.juros
  recalcForm.value.multa = parametros.value.multa
  recalcForm.value.diasTolerancia = parametros.value.diasTolerancia
  dialogRecalcular.value = true
}

function confirmarRecalcular() {
  parametros.value.juros = Number(recalcForm.value.juros) || 0
  parametros.value.multa = Number(recalcForm.value.multa) || 0
  parametros.value.diasTolerancia = Number(recalcForm.value.diasTolerancia) || 0
  recalcularJurosMulta()
  dialogRecalcular.value = false
}

// === Totais ===
const totalCapital = computed(() => somar('capital'))
const totalMulta = computed(() => somar('multa'))
const totalJuros = computed(() => somar('juros'))
const totalDesconto = computed(() => somar('desconto'))
const totalGeral = computed(() => somar('total'))

function somar(campo) {
  let cr = 0
  let db = 0
  for (const r of selecionados.value) {
    const v = Number(r[campo]) || 0
    if (r.operacao === 'CR') cr += v
    else db += v
  }
  const liq = db - cr
  return { valor: Math.abs(liq), operacao: liq < 0 ? 'CR' : 'DB' }
}

function classeOperacao(op) {
  return op === 'CR' ? 'text-orange' : 'text-green'
}


function classeVencimento(t) {
  const dias = diasAtraso(t.vencimento)
  if (dias <= 0) return 'text-green'
  if (dias <= parametros.value.diasTolerancia) return 'text-orange'
  return 'text-red'
}
</script>

<template>
  <div>
    <!-- Filtros -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section>
        <div class="text-grey-9 text-overline q-mb-sm">
          BUSCAR TITULOS
          <q-btn flat dense round size="sm" icon="autorenew" color="grey-8" @click="buscar">
            <q-tooltip>Atualizar Listagem</q-tooltip>
          </q-btn>
        </div>
        <div class="row q-col-gutter-md">
          <div class="col-xs-12 col-sm-3">
            <SelectGrupoEconomico
              v-model="filtros.codgrupoeconomico"
              class="text-caption"
              outlined
              clearable
              label="Grupo Econômico"
              :bottom-slots="false"
            />
          </div>
          <div class="col-xs-12 col-sm-4">
            <SelectPessoa
              class="text-caption"
              v-model="filtros.codpessoa"
              outlined
              clearable
              label="Pessoa"
              :bottom-slots="false"
              autofocus
            />
          </div>

          <div class="col-xs-4 col-sm-2">
            <q-select
              class="text-caption"
              v-model="filtros.credito"
              :options="operacaoOptions"
              emit-value
              map-options
              outlined
              label="Operação"
              :bottom-slots="false"
            />
          </div>
          <div class="col-xs-8 col-sm-3">
            <SelectFilial
              class="text-caption"
              v-model="filtros.codfilial"
              outlined
              clearable
              label="Filial"
              :bottom-slots="false"
            />
          </div>
          <div class="col-xs-6 col-sm-3">
            <div class="row q-col-gutter-md">
              <div class="col-6">
                <mgInputData
                  input-class="text-caption"
                  v-model="filtros.vencimento_de"
                  type="date"
                  label="Vencimento de"
                  stack-label
                  :year-digits="2"
                  :bottom-slots="false"
                />
              </div>
              <div class="col-6">
                <MgInputData
                  v-model="filtros.vencimento_ate"
                  input-class="text-caption"
                  type="date"
                  label="Até"
                  stack-label
                  :bottom-slots="false"
                />
              </div>
            </div>
          </div>
          <div class="col-xs-12 col-sm-3">
            <SelectTipoTitulo
              class="text-caption"
              v-model="filtros.codtipotitulo"
              outlined
              clearable
              label="Tipo de Título"
              :bottom-slots="false"
            />
          </div>
          <div class="col-xs-12 col-sm-3">
            <SelectContaContabil
              class="text-caption"
              v-model="filtros.codcontacontabil"
              outlined
              clearable
              label="Conta Contábil"
              :bottom-slots="false"
            />
          </div>
          <div class="col-xs-12 col-sm-3">
            <SelectPortador
              class="text-caption"
              v-model="filtros.codportador"
              outlined
              clearable
              label="Portador"
              :bottom-slots="false"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <q-inner-loading :showing="loading" color="primary" />

    <div v-if="!titulos.length" class="text-center text-grey q-ma-xl">
      Nenhum título encontrado para esses filtros.
    </div>
    <q-card v-else flat bordered class="text-caption">
      <!-- TOTAIS -->
      <q-card-section class="text-grey-9 text-overline">
        <div class="row q-col-gutter-md">
          <!-- NUMERO -->
          <div class="col-xs-12 col-sm-3">
            <div class="row">
              <q-btn
                flat
                round
                size="sm"
                icon="event_busy"
                color="red-7"
                @click="selecionarVencidos"
              >
                <q-tooltip>Selecionar somente títulos vencidos</q-tooltip>
              </q-btn>
              <q-btn flat round size="sm" icon="done_all" color="grey-8" @click="selecionarTodos">
                <q-tooltip>Selecionar todos</q-tooltip>
              </q-btn>
              <q-btn flat round size="sm" icon="block" color="grey-8" @click="deselecionarTodos">
                <q-tooltip>Limpar seleção</q-tooltip>
              </q-btn>
              <q-space />
              <q-btn flat round size="sm" icon="money_off" color="grey-8" @click="limparJurosMulta">
                <q-tooltip>Zerar juros e multa de todos os títulos</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                size="sm"
                icon="calculate"
                color="grey-8"
                @click="abrirDialogRecalcular"
              >
                <q-tooltip>Recalcular juros e multa de todos os títulos</q-tooltip>
              </q-btn>
            </div>
          </div>

          <!-- VALORES -->
          <div class="col-xs-12 col-sm-7 col-md-6">
            <div class="row q-col-gutter-md">
              <div class="col-3 text-right ellipsis">
                {{ formataNumero(totalCapital.valor) }}
              </div>
              <div class="col-2 text-right">
                {{ formataNumero(totalMulta.valor) }}
              </div>
              <div class="col-2 text-right ellipsis">
                {{ formataNumero(totalJuros.valor) }}
              </div>
              <div class="col-2 text-right ellipsis">
                {{ formataNumero(totalDesconto.valor) }}
              </div>
              <div class="col-3 text-right ellipsis" :class="classeOperacao(totalGeral.operacao)">
                {{ formataNumero(totalGeral.valor) }} {{ totalGeral.operacao }}
              </div>
            </div>
          </div>
          <div class="col gt-sm">
            {{ selecionados.length }} / {{ titulos.length }} título{{
              titulos.length === 1 ? '' : 's'
            }}
          </div>
        </div>
      </q-card-section>
      <q-separator />

      <!-- TITULOS -->
      <template v-for="(titulo, i) in titulos" :key="titulo.codtitulo">
        <q-card-section
          class="cursor-pointer q-py-sm"
          :class="{ 'bg-blue-2': titulo.selecionado }"
          @click="toggle(titulo)"
        >
          <div class="row q-col-gutter-md items-center">
            <!-- NUMERO -->
            <div class="col-xs-12 col-sm-3 ellipsis">
              <div class="row">
                <router-link
                  :to="'/titulo/' + titulo.codtitulo"
                  class="text-primary"
                  style="text-decoration: none"
                  @click.stop
                  target="_blank"
                  tabindex="-1"
                >
                  {{ titulo.numero }}
                  <q-icon name="launch" />
                </router-link>
                <q-space />
                <span :class="classeOperacao(titulo.operacao)" class="text-body2 text-weight-bold">
                  {{ formataNumero(titulo.saldo) }} {{ titulo.operacao }}
                </span>
              </div>
              <div class="row">
                <span class="text-grey-7" v-if="titulo.fatura"> {{ titulo.fatura }} </span>
                <q-space />
                <span :class="classeVencimento(titulo)">
                  {{ formataDataSemHora(titulo.vencimento) }}
                </span>
              </div>
            </div>

            <!-- VALORES -->
            <div class="col-xs-12 col-sm-7 col-md-6">
              <div class="row q-col-gutter-md">
                <div class="col-xs-4 col-sm-3" @click.stop>
                  <MgInputValor
                    input-class="text-caption"
                    label="Capital"
                    :bottom-slots="false"
                    :min="0"
                    :max="titulo.saldo"
                    :model-value="titulo.capital ?? null"
                    @update:model-value="(v) => updateEd(titulo, 'capital', v ?? 0)"
                  />
                </div>

                <div class="col-xs-4 col-sm-2" @click.stop>
                  <MgInputValor
                    input-class="text-caption"
                    label="Multa"
                    :bottom-slots="false"
                    :min="0"
                    :model-value="titulo.multa ?? null"
                    @update:model-value="(v) => updateEd(titulo, 'multa', v ?? 0)"
                  />
                </div>

                <div class="col-xs-4 col-sm-2" @click.stop>
                  <MgInputValor
                    input-class="text-caption"
                    label="Juros"
                    :bottom-slots="false"
                    :min="0"
                    :model-value="titulo.juros ?? null"
                    @update:model-value="(v) => updateEd(titulo, 'juros', v ?? 0)"
                  />
                </div>

                <div class="col-xs-4 col-sm-2" @click.stop>
                  <MgInputValor
                    input-class="text-caption"
                    label="Desconto"
                    :bottom-slots="false"
                    :min="0"
                    :max="
                      (Number(titulo.capital) || 0) +
                      (Number(titulo.multa) || 0) +
                      (Number(titulo.juros) || 0)
                    "
                    :model-value="titulo.desconto ?? null"
                    @update:model-value="(v) => updateEd(titulo, 'desconto', v ?? 0)"
                  />
                </div>

                <div class="col" @click.stop>
                  <MgInputValor
                    input-class="text-caption"
                    label="Total"
                    :bottom-slots="false"
                    :min="0"
                    :max="maxTotal(titulo) - (Number(titulo.desconto) || 0)"
                    :model-value="titulo.total ?? null"
                    @update:model-value="(v) => updateEd(titulo, 'total', v ?? 0)"
                  />
                </div>
              </div>
            </div>

            <div class="col ellipsis">
              <div class="text-grey-7 text-weight-bold">
                {{ titulo.fantasia }}
              </div>
              <div :class="titulo.gerencial ? 'text-orange' : 'text-green'">
                {{ titulo.filial }}
              </div>
            </div>
          </div>
        </q-card-section>
        <q-separator v-if="i < titulos.length - 1" />
      </template>
    </q-card>

    <!-- Dialog Recalcular Juros/Multa -->
    <q-dialog v-model="dialogRecalcular">
      <q-card bordered flat style="width: 500px; max-width: 90vw">
        <q-form @submit.prevent="confirmarRecalcular">
          <q-card-section class="row items-center q-pb-none">
            <div class="text-grey-9 text-overline">RECALCULAR JUROS/MULTA</div>
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-4">
                <MgInputValor
                  v-model="recalcForm.juros"
                  label="Juros Mensal"
                  suffix="%"
                  :decimals="1"
                  :min="0"
                  :bottom-slots="false"
                  autofocus
                />
              </div>
              <div class="col-4">
                <MgInputValor
                  v-model="recalcForm.multa"
                  label="Multa"
                  suffix="%"
                  :decimals="1"
                  :min="0"
                  :bottom-slots="false"
                />
              </div>
              <div class="col-4">
                <MgInputValor
                  v-model="recalcForm.diasTolerancia"
                  label="Tolerância"
                  :decimals="0"
                  :min="0"
                  :bottom-slots="false"
                  suffix="dias"
                />
              </div>
            </div>
          </q-card-section>
          <q-separator />
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn flat label="Recalcular" type="submit" color="primary" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </div>
</template>
