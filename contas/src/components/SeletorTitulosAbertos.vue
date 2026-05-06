<script setup>
import { ref, computed, watch } from 'vue'
import { date } from 'quasar'
import { api } from 'src/services/api'
import { formataNumero } from 'src/utils/formatters.js'
import SelectFilial from 'src/components/select/SelectFilial.vue'
import SelectPessoa from 'src/components/select/SelectPessoa.vue'
import SelectGrupoEconomico from 'src/components/select/SelectGrupoEconomico.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] }, // linhas selecionadas
  codpessoaInicial: { type: Number, default: null },
})

const emit = defineEmits([
  'update:modelValue',
  'update:codpessoa',
  'update:totalLiquido',
  'update:operacao',
])

// === Filtros locais ===
const filtros = ref({
  codfilial: null,
  codgrupoeconomico: null,
  codpessoa: props.codpessoaInicial,
  vencimento_de: null,
  vencimento_ate: null,
  credito: null, // 1 = crédito; 2 = débito
})

watch(
  () => props.codpessoaInicial,
  (v) => {
    if (v && v !== filtros.value.codpessoa) filtros.value.codpessoa = v
  },
)

const operacaoOptions = [
  { label: 'Todos', value: null },
  { label: 'Crédito', value: 1 },
  { label: 'Débito', value: 2 },
]

// === Estado da listagem ===
const loading = ref(false)
const titulos = ref([])

// Mapa de linhas selecionadas: codtitulo -> { saldo, multa, juros, desconto, total, operacao, calculado }
const linhas = ref(new Map())

// Inicializa a partir do modelValue (em mounts/edits)
function syncFromModel() {
  const novo = new Map()
  for (const l of props.modelValue) {
    novo.set(l.codtitulo, { ...l })
  }
  linhas.value = novo
}
syncFromModel()

watch(
  () => props.modelValue,
  () => {
    if (props.modelValue.length === 0) linhas.value = new Map()
  },
)

function emitModel() {
  const arr = Array.from(linhas.value.values()).map((l) => ({
    codtitulo: l.codtitulo,
    saldo: l.saldo ?? 0,
    multa: l.multa ?? 0,
    juros: l.juros ?? 0,
    desconto: l.desconto ?? 0,
    total: l.total ?? 0,
    operacao: l.operacao,
  }))
  emit('update:modelValue', arr)

  // total e operação
  let totalCR = 0
  let totalDB = 0
  for (const l of arr) {
    if (l.operacao === 'CR') totalCR += Number(l.total) || 0
    else totalDB += Number(l.total) || 0
  }
  const liquido = totalDB - totalCR
  emit('update:totalLiquido', Math.abs(liquido))
  emit('update:operacao', liquido < 0 ? 'CR' : 'DB')
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
      codtitulos: Array.from(linhas.value.keys()),
    }
    const { data } = await api.get('v1/titulo/abertos-para-fechamento', { params })
    titulos.value = data.data || []
  } finally {
    loading.value = false
  }
}

watch(
  () => filtros.value.codpessoa,
  (v) => emit('update:codpessoa', v),
)

// debounce simples
let debounceTimer = null
watch(
  () => ({ ...filtros.value }),
  () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(buscar, 400)
  },
  { deep: true, immediate: true },
)

// === Helpers ===
function isSel(codtitulo) {
  return linhas.value.has(codtitulo)
}

function defaultLinha(t) {
  return {
    codtitulo: t.codtitulo,
    operacao: t.operacao,
    saldo: t.saldo,
    multa: 0,
    juros: 0,
    desconto: 0,
    total: t.saldo,
  }
}

function toggle(t) {
  if (linhas.value.has(t.codtitulo)) {
    linhas.value.delete(t.codtitulo)
  } else {
    linhas.value.set(t.codtitulo, defaultLinha(t))
  }
  linhas.value = new Map(linhas.value)
  emitModel()
}

function getLinha(codtitulo) {
  return linhas.value.get(codtitulo) || null
}

function setLinha(t, patch) {
  if (!linhas.value.has(t.codtitulo)) {
    linhas.value.set(t.codtitulo, defaultLinha(t))
  }
  const l = linhas.value.get(t.codtitulo)
  Object.assign(l, patch)
  linhas.value = new Map(linhas.value)
  emitModel()
}

function recalc(t, campo) {
  const tit = titulos.value.find((x) => x.codtitulo === t.codtitulo)
  if (!tit) return
  const l = getLinha(t.codtitulo)
  if (!l) return

  const saldoMax = tit.saldo
  let saldo = Number(l.saldo) || 0
  let multa = Number(l.multa) || 0
  let juros = Number(l.juros) || 0
  let desconto = Number(l.desconto) || 0

  if (campo === 'saldo') {
    if (saldo > saldoMax) saldo = saldoMax
  } else if (campo === 'desconto') {
    const lim = saldo + juros + multa
    if (desconto > lim) desconto = lim
  }

  const total = round2(saldo + multa + juros - desconto)
  setLinha(t, { saldo: round2(saldo), multa: round2(multa), juros: round2(juros), desconto: round2(desconto), total })
}

function round2(n) {
  return Math.round(Number(n) * 100) / 100
}

// === Totais ===
const totalSaldo = computed(() => somar('saldo'))
const totalMulta = computed(() => somar('multa'))
const totalJuros = computed(() => somar('juros'))
const totalDesconto = computed(() => somar('desconto'))
const totalGeral = computed(() => somar('total', true))

function somar(campo, comSinal = false) {
  let cr = 0
  let db = 0
  for (const l of linhas.value.values()) {
    const v = Number(l[campo]) || 0
    if (l.operacao === 'CR') cr += v
    else db += v
  }
  if (comSinal) {
    const liq = db - cr
    return { valor: Math.abs(liq), operacao: liq < 0 ? 'CR' : 'DB' }
  }
  const liq = db - cr
  return { valor: Math.abs(liq), operacao: liq < 0 ? 'CR' : 'DB' }
}

function classeOperacao(op) {
  return op === 'CR' ? 'text-orange' : 'text-green'
}

function formatVcto(v) {
  return v ? date.formatDate(v, 'DD/MM/YYYY') : ''
}

function classeVencimento(t, l) {
  if (!l) return 'text-grey-7'
  const venc = t.vencimento ? new Date(String(t.vencimento).slice(0, 10)) : null
  if (!venc) return 'text-grey-7'
  if (venc < new Date()) return 'text-red'
  return 'text-green'
}
</script>

<template>
  <div>
    <!-- Filtros -->
    <q-card flat bordered class="q-pa-md q-mb-md">
      <div class="row q-col-gutter-sm">
        <div class="col-xs-12 col-sm-3">
          <SelectFilial
            v-model="filtros.codfilial"
            outlined
            clearable
            label="Filial"
            :bottom-slots="false"
          />
        </div>
        <div class="col-xs-12 col-sm-3">
          <q-select
            v-model="filtros.credito"
            :options="operacaoOptions"
            emit-value
            map-options
            outlined
            label="Operação"
            :bottom-slots="false"
          />
        </div>
        <div class="col-xs-12 col-sm-6">
          <SelectGrupoEconomico
            v-model="filtros.codgrupoeconomico"
            outlined
            clearable
            label="Grupo Econômico"
            :bottom-slots="false"
          />
        </div>
        <div class="col-xs-12 col-sm-6">
          <SelectPessoa
            v-model="filtros.codpessoa"
            outlined
            clearable
            label="Pessoa"
            :bottom-slots="false"
          />
        </div>
        <div class="col-xs-6 col-sm-3">
          <MgInputData
            v-model="filtros.vencimento_de"
            type="date"
            label="Vencimento de"
            stack-label
            :bottom-slots="false"
          />
        </div>
        <div class="col-xs-6 col-sm-3">
          <MgInputData
            v-model="filtros.vencimento_ate"
            type="date"
            label="Até"
            stack-label
            :bottom-slots="false"
          />
        </div>
      </div>
    </q-card>

    <!-- Totais -->
    <q-card flat bordered class="q-pa-sm q-mb-sm">
      <div class="row items-center q-gutter-md">
        <div class="text-weight-bold">Total Selecionado</div>
        <q-space />
        <div class="text-caption text-grey-7">Capital</div>
        <div class="text-weight-bold">{{ formataNumero(totalSaldo.valor) }}</div>
        <div class="text-caption text-grey-7">Multa</div>
        <div class="text-weight-bold">{{ formataNumero(totalMulta.valor) }}</div>
        <div class="text-caption text-grey-7">Juros</div>
        <div class="text-weight-bold">{{ formataNumero(totalJuros.valor) }}</div>
        <div class="text-caption text-grey-7">Desconto</div>
        <div class="text-weight-bold">{{ formataNumero(totalDesconto.valor) }}</div>
        <q-separator vertical />
        <div class="text-caption text-grey-7">Total</div>
        <div class="text-h6" :class="classeOperacao(totalGeral.operacao)">
          {{ formataNumero(totalGeral.valor) }} {{ totalGeral.operacao }}
        </div>
      </div>
    </q-card>

    <!-- Tabela -->
    <q-card flat bordered>
      <q-inner-loading :showing="loading" color="primary" />
      <div v-if="!titulos.length && !loading" class="text-center text-grey q-pa-xl">
        Nenhum título encontrado para esses filtros.
      </div>

      <q-list separator v-else>
        <q-item v-for="t in titulos" :key="t.codtitulo" dense>
          <q-item-section avatar style="min-width: 40px">
            <q-checkbox :model-value="isSel(t.codtitulo)" @update:model-value="toggle(t)" />
          </q-item-section>

          <q-item-section style="flex: 0 0 220px; min-width: 0">
            <q-item-label class="ellipsis text-weight-medium">{{ t.numero }}</q-item-label>
            <q-item-label caption class="ellipsis">
              {{ t.fatura }}
            </q-item-label>
            <q-item-label caption :class="classeVencimento(t, getLinha(t.codtitulo))">
              {{ formatVcto(t.vencimento) }} ·
              <span class="text-weight-bold">
                {{ formataNumero(t.saldo) }} {{ t.operacao }}
              </span>
            </q-item-label>
          </q-item-section>

          <q-item-section style="flex: 0 0 110px">
            <MgInputData
              v-if="false"
              :model-value="null"
              label="?"
            />
            <MgInputValor
              dense
              :bottom-slots="false"
              :model-value="getLinha(t.codtitulo)?.saldo ?? null"
              @update:model-value="(v) => { setLinha(t, { saldo: v ?? 0 }); recalc(t, 'saldo') }"
              label="Capital"
              stack-label
            />
          </q-item-section>

          <q-item-section style="flex: 0 0 100px">
            <MgInputValor
              dense
              :bottom-slots="false"
              :model-value="getLinha(t.codtitulo)?.multa ?? null"
              @update:model-value="(v) => { setLinha(t, { multa: v ?? 0 }); recalc(t, 'multa') }"
              label="Multa"
              stack-label
            />
          </q-item-section>

          <q-item-section style="flex: 0 0 100px">
            <MgInputValor
              dense
              :bottom-slots="false"
              :model-value="getLinha(t.codtitulo)?.juros ?? null"
              @update:model-value="(v) => { setLinha(t, { juros: v ?? 0 }); recalc(t, 'juros') }"
              label="Juros"
              stack-label
            />
          </q-item-section>

          <q-item-section style="flex: 0 0 100px">
            <MgInputValor
              dense
              :bottom-slots="false"
              :model-value="getLinha(t.codtitulo)?.desconto ?? null"
              @update:model-value="(v) => { setLinha(t, { desconto: v ?? 0 }); recalc(t, 'desconto') }"
              label="Desc."
              stack-label
            />
          </q-item-section>

          <q-item-section style="flex: 0 0 110px">
            <MgInputValor
              dense
              :bottom-slots="false"
              :model-value="getLinha(t.codtitulo)?.total ?? null"
              @update:model-value="(v) => { setLinha(t, { total: v ?? 0 }) }"
              label="Total"
              stack-label
            />
          </q-item-section>

          <q-item-section side class="gt-sm">
            <q-item-label caption :class="t.gerencial ? 'text-orange' : 'text-green'">
              {{ t.filial }}
            </q-item-label>
            <q-item-label caption class="ellipsis" style="max-width: 180px">
              {{ t.fantasia }}
            </q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </q-card>
  </div>
</template>
