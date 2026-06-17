<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { api } from 'src/services/api'
import { useEmbarqueStore } from 'src/stores/embarque'
import { sacas } from 'src/utils/desconto'
import { imprimirTicket } from 'src/utils/ticket'
import MgInputValor from '@components/MgInputValor.vue'
import CaminhaoDialog from 'components/CaminhaoDialog.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  embarque: { type: Object, default: null },
  // true = embarque novo (só "Registrar", entra na coluna Tara sem avançar)
  novo: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'salvar'])

const $q = useQuasar()
const store = useEmbarqueStore()
const { contratosAtivos, plantiosTalhao, culturas, veiculosAtivos } = storeToRefs(store)

const local = ref(null)
watch(
  () => props.embarque,
  (e) => {
    local.value = e ? JSON.parse(JSON.stringify(e)) : null
  },
  { immediate: true },
)

const show = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

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

// ---- Motorista (busca online em pessoas; texto livre como fallback offline) ----
const motoristaOptions = ref([])
const motoristaBusca = ref('')

function filtrarMotorista(val, update, abort) {
  motoristaBusca.value = (val || '').trim()
  const termo = motoristaBusca.value
  if (termo.length < 2) {
    update(() => {
      motoristaOptions.value = []
    })
    return
  }
  api
    .get('v1/select/pessoa', { params: { pessoa: termo, somenteAtivos: true }, skipLoading: true })
    .then(({ data }) =>
      update(() => {
        motoristaOptions.value = data.map((p) => ({
          label: p.fantasia || p.pessoa,
          value: p.fantasia || p.pessoa,
          codpessoa: p.codpessoa,
        }))
      }),
    )
    .catch(() => abort()) // offline: segue como texto livre
}
function resolverMotorista(nome) {
  const n = nome || null
  local.value.motorista = n
  local.value.codpessoamotorista = n
    ? motoristaOptions.value.find((o) => o.value === n)?.codpessoa || null
    : null
}
function onMotoristaBlur() {
  if (motoristaBusca.value && motoristaBusca.value !== local.value.motorista) {
    resolverMotorista(motoristaBusca.value)
  }
}

// Divulgação progressiva dos campos de peso: a expedição começa pela Tara
// (caminhão vazio) e só revela o Bruto quando chega na etapa Peso Bruto.
const ORDEM = ['TARA', 'BRUTO', 'FISCAL', 'DESPACHADO']
const idxEtapa = computed(() => ORDEM.indexOf(local.value?.etapa))
const mostrarBruto = computed(() => idxEtapa.value >= ORDEM.indexOf('BRUTO'))
const mostrarResultado = computed(
  () => calc.value.pesoliquido !== null && calc.value.pesoliquido !== undefined,
)

const proxima = {
  TARA: 'BRUTO',
  BRUTO: 'FISCAL',
  FISCAL: 'DESPACHADO',
  DESPACHADO: null,
}
// Rótulo da ação = etapa ATUAL (o que se faz na coluna do card), não a próxima.
// Saída de grãos não classifica: Tara pesa a tara; Peso Bruto pesa o bruto;
// Nota Fiscal emite as NFs; Despachado fecha/imprime o romaneio.
const rotuloAvancar = {
  TARA: 'Pesar tara',
  BRUTO: 'Pesar bruto',
  FISCAL: 'Notas fiscais',
  DESPACHADO: 'Despachar',
}
const labelEtapa = {
  TARA: 'Tara',
  BRUTO: 'Peso Bruto',
  FISCAL: 'Nota Fiscal',
  DESPACHADO: 'Despachado',
}

const opcoesContrato = computed(() =>
  contratosAtivos.value.map((c) => ({
    value: c.codcontrato,
    label: `${c.contrato} — ${c.Pessoa?.fantasia || c.Pessoa?.pessoa || ''}`,
    saldo: (Number(c.quantidade) || 0) - (Number(c.carregado) || 0),
    rotulo: `${c.contrato} — ${c.Pessoa?.fantasia || c.Pessoa?.pessoa || ''}`,
  })),
)

const calc = computed(() => (local.value ? store.calcular(local.value) : {}))
const pesosacaCultura = computed(() => {
  const cod = local.value?.contratos?.[0]?.codcontrato
  const ct = store.contratos.find((c) => c.codcontrato === cod)
  return culturas.value.find((c) => c.codcultura === ct?.codcultura)?.pesosaca || 60
})
const sacasLiquido = computed(() => sacas(calc.value.pesoliquido, pesosacaCultura.value))

// Soma dos quilos rateados entre contratos / origens — deve fechar com o
// líquido da carga (bruto - tara), igual ao rateio de talhões no recebimento.
const somaContratos = computed(() =>
  (local.value?.contratos || []).reduce((s, c) => s + (Number(c.quantidade) || 0), 0),
)
const somaOrigens = computed(() =>
  (local.value?.origens || []).reduce((s, o) => s + (Number(o.quantidade) || 0), 0),
)
function bateComLiquido(soma) {
  const liq = Number(calc.value.pesoliquido)
  if (!liq) return true // sem líquido ainda: não cobra fechamento
  return Math.abs(soma - liq) < 1
}

function addContrato() {
  local.value.contratos.push({
    codcontrato: null,
    quantidade: null,
    rotulo: null,
    numeronf: null,
    valornf: null,
  })
}
function setRotuloContrato(c) {
  c.rotulo = opcoesContrato.value.find((o) => o.value === c.codcontrato)?.rotulo || null
}
function addOrigem(tipo) {
  local.value.origens.push({ tipo, codplantio: null, quantidade: null })
}

function salvar() {
  // "Registrar" (embarque novo) entra na Tara: exige placa e ao menos um contrato.
  if (!local.value.placa || !local.value.contratos.length) {
    return $q.notify({ type: 'warning', message: 'Informe a placa e ao menos um contrato.' })
  }
  emit('salvar', local.value)
}

function avancar() {
  const prox = proxima[local.value.etapa]
  if (!prox) return imprimir()

  if (local.value.etapa === 'TARA' && !local.value.pesotara) {
    return $q.notify({ type: 'warning', message: 'Informe a tara.' })
  }
  if (local.value.etapa === 'BRUTO' && !local.value.pesobruto) {
    return $q.notify({ type: 'warning', message: 'Informe o peso bruto.' })
  }
  local.value.etapa = prox
  emit('salvar', local.value)
}

function imprimir() {
  const c = calc.value
  // Romaneio de saída: sem classificação; quilos por contrato; líquido = bruto - tara.
  imprimirTicket({
    titulo: 'ROMANEIO DE EXPEDIÇÃO',
    rotuloItens: 'Contratos',
    assinaturas: ['Conferente', 'Motorista', 'Expedidor'],
    numero: local.value.codembarque,
    data: local.value.data,
    placa: local.value.placa,
    motorista: local.value.motorista,
    talhoes: local.value.contratos.map((ct) => ({
      rotulo: ct.rotulo,
      percentual: null,
      kg: ct.quantidade,
    })),
    pesobruto: local.value.pesobruto,
    tara: local.value.pesotara,
    pesoliquido: c.pesoliquido,
    pesoliquidoseco: c.pesoliquido,
    sacas: sacasLiquido.value,
    pesosaca: pesosacaCultura.value,
  })
}

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}
</script>

<template>
  <q-dialog v-model="show" :maximized="$q.screen.lt.sm">
    <q-card v-if="local" style="width: 560px; max-width: 95vw">
      <q-card-section class="row items-center bg-primary text-white">
        <div class="text-h6">{{ local.placa || 'Novo caminhão' }}</div>
        <q-space />
        <q-chip color="white" text-color="primary" :label="labelEtapa[local.etapa]" />
        <q-btn flat round icon="close" v-close-popup tabindex="-1" />
      </q-card-section>

      <q-card-section class="q-gutter-y-md scroll" style="max-height: 70vh">
        <!-- Identificação: placa (cadastro de veículo) e motorista (pessoa) -->
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

          <q-select
            :model-value="local.motorista"
            :options="motoristaOptions"
            label="Motorista"
            outlined
            use-input
            fill-input
            hide-selected
            clearable
            input-debounce="350"
            new-value-mode="add-unique"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            class="col-12 col-sm-4"
            @filter="filtrarMotorista"
            @update:model-value="resolverMotorista"
            @blur="onMotoristaBlur"
          >
            <template #no-option>
              <q-item>
                <q-item-section class="text-grey-6">Digite o nome do motorista…</q-item-section>
              </q-item>
            </template>
          </q-select>
        </div>

        <!-- Contratos -->
        <div>
          <div class="text-subtitle2 text-grey-8 q-mb-xs">Contratos carregados</div>
          <div v-for="(c, i) in local.contratos" :key="i" class="q-mb-sm">
            <div class="row q-col-gutter-sm items-center">
              <q-select
                v-model="c.codcontrato"
                :options="opcoesContrato"
                emit-value
                map-options
                outlined
                label="Contrato"
                class="col"
                @update:model-value="setRotuloContrato(c)"
              />
              <MgInputValor
                v-model="c.quantidade"
                :decimals="0"
                suffix="kg"
                label="Quilos"
                class="col-4"
              />
              <q-btn
                flat
                round
                color="grey-7"
                icon="close"
                class="col-auto"
                @click="local.contratos.splice(i, 1)"
              />
            </div>
            <div
              v-if="local.etapa === 'FISCAL' || local.etapa === 'DESPACHADO'"
              class="row q-col-gutter-sm q-mt-xs"
            >
              <q-input v-model="c.numeronf" label="Nº NF" outlined class="col" />
              <MgInputValor
                v-model="c.valornf"
                :decimals="2"
                prefix="R$"
                label="Valor NF"
                class="col"
              />
            </div>
          </div>
          <div
            v-if="local.contratos.length"
            class="text-caption q-mb-xs"
            :class="bateComLiquido(somaContratos) ? 'text-grey-7' : 'text-orange-8'"
          >
            Soma dos contratos: {{ fmt(somaContratos) }} kg<span v-if="calc.pesoliquido">
              · líquido {{ fmt(calc.pesoliquido) }} kg</span
            >
          </div>
          <q-btn flat color="primary" icon="add" label="Contrato" @click="addContrato" />
        </div>

        <!-- Origem -->
        <div>
          <div class="text-subtitle2 text-grey-8 q-mb-xs">Origem do grão</div>
          <div
            v-for="(o, i) in local.origens"
            :key="i"
            class="row q-col-gutter-sm items-center q-mb-xs"
          >
            <q-chip
              :color="o.tipo === 'SILO' ? 'amber-7' : 'brown-5'"
              text-color="white"
              :label="o.tipo"
            />
            <q-select
              v-if="o.tipo === 'TALHAO'"
              v-model="o.codplantio"
              :options="plantiosTalhao"
              option-value="codplantio"
              option-label="rotulo"
              emit-value
              map-options
              outlined
              label="Talhão"
              class="col"
            />
            <div v-else class="col text-grey-7 q-pl-sm">Silo / armazém</div>
            <MgInputValor
              v-model="o.quantidade"
              :decimals="0"
              suffix="kg"
              label="Quilos"
              class="col-4"
            />
            <q-btn
              flat
              round
              color="grey-7"
              icon="close"
              class="col-auto"
              @click="local.origens.splice(i, 1)"
            />
          </div>
          <div
            v-if="local.origens.length"
            class="text-caption q-mb-xs"
            :class="bateComLiquido(somaOrigens) ? 'text-grey-7' : 'text-orange-8'"
          >
            Soma das origens: {{ fmt(somaOrigens) }} kg
          </div>
          <div class="q-gutter-sm">
            <q-btn
              flat
              color="amber-8"
              icon="warehouse"
              label="Do silo"
              @click="addOrigem('SILO')"
            />
            <q-btn
              flat
              color="brown-6"
              icon="grass"
              label="Do talhão"
              @click="addOrigem('TALHAO')"
            />
          </div>
        </div>

        <q-separator />

        <!-- Tara (sempre visível desde a chegada do caminhão vazio) -->
        <MgInputValor v-model="local.pesotara" :decimals="0" suffix="kg" label="Tara (vazio)" />

        <!-- Bruto (só a partir da etapa Peso Bruto, com o caminhão carregado) -->
        <MgInputValor
          v-if="mostrarBruto"
          v-model="local.pesobruto"
          :decimals="0"
          suffix="kg"
          label="Bruto (carregado)"
        />

        <!-- Resultado (saída de grãos não tem classificação/desconto) -->
        <q-card v-if="mostrarResultado" flat bordered class="bg-grey-1">
          <q-card-section class="q-pa-sm row text-center">
            <div class="col">
              <div class="text-caption text-grey-7">Líquido</div>
              <div class="text-weight-medium text-green-9">{{ fmt(calc.pesoliquido) }} kg</div>
            </div>
            <div class="col">
              <div class="text-caption text-grey-7">Sacas</div>
              <div class="text-weight-medium">{{ fmt(sacasLiquido, 1) }}</div>
            </div>
          </q-card-section>
        </q-card>

        <q-input v-model="local.observacao" label="Observação" type="textarea" autogrow outlined />
      </q-card-section>

      <q-separator />

      <q-card-actions align="right">
        <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
        <!-- Ação única = próxima movimentação do kanban. Embarque novo entra na
             coluna Tara (Registrar); os demais avançam de etapa. -->
        <q-btn
          unelevated
          color="primary"
          :label="novo ? 'Registrar' : rotuloAvancar[local.etapa]"
          @click="novo ? salvar() : avancar()"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>

  <CaminhaoDialog v-model="cadastroCaminhao" :placa="placaBusca" @criado="onCaminhaoCriado" />
</template>
