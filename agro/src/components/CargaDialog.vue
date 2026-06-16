<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { api } from 'src/services/api'
import { useCargaStore } from 'src/stores/carga'
import { calcularCarga, sacas } from 'src/utils/desconto'
import { imprimirTicket as imprimir } from 'src/utils/ticket'
import MgInputValor from '@components/MgInputValor.vue'
import CaminhaoDialog from 'components/CaminhaoDialog.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  carga: { type: Object, default: null },
  // true = registro de caminhão novo (só "Salvar", sem avançar — fica no Peso Bruto)
  novo: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'salvar', 'avancar'])

const $q = useQuasar()
const store = useCargaStore()
const { plantiosDaSafra, faixasDaSafra, culturaAtiva, safraAtiva, veiculosAtivos } =
  storeToRefs(store)

const local = ref(null)
watch(
  () => props.carga,
  (c) => {
    local.value = c ? JSON.parse(JSON.stringify(c)) : null
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

const proxima = {
  BRUTO: 'CLASSIFICACAO',
  CLASSIFICACAO: 'TARA',
  TARA: 'FINALIZADO',
  FINALIZADO: null,
}
// Rótulo da ação = etapa ATUAL (o que se faz na tela aberta), não a próxima.
// Peso Bruto/Classificação ainda estão classificando; Tara pesa a tara; o
// Finalizado fecha/imprime o ticket.
const rotuloAvancar = {
  BRUTO: 'Classificar',
  CLASSIFICACAO: 'Classificar',
  TARA: 'Pesar tara',
  FINALIZADO: 'Finalizar',
}
const labelEtapa = {
  BRUTO: 'Peso Bruto',
  CLASSIFICACAO: 'Classificação',
  TARA: 'Tara',
  FINALIZADO: 'Finalizado',
}

// Ordem das etapas — controla a divulgação progressiva dos campos: a chegada
// (Peso Bruto) mostra só o essencial; classificação/tara/resultado surgem na vez.
const ORDEM = ['BRUTO', 'CLASSIFICACAO', 'TARA', 'FINALIZADO']
const idxEtapa = computed(() => ORDEM.indexOf(local.value?.etapa))
const mostrarClassificacao = computed(() => idxEtapa.value >= ORDEM.indexOf('CLASSIFICACAO'))
const mostrarTara = computed(() => idxEtapa.value >= ORDEM.indexOf('TARA'))
// % de rateio só faz sentido quando a carga mistura 2+ talhões.
const mostrarPercentual = computed(() => (local.value?.plantios?.length || 0) > 1)

const calc = computed(() => (local.value ? calcularCarga(local.value, faixasDaSafra.value) : {}))
const mostrarResultado = computed(
  () => calc.value.pesoliquido !== null && calc.value.pesoliquido !== undefined,
)
const pesosaca = computed(() => culturaAtiva.value?.pesosaca || 60)
const sacasSeco = computed(() => sacas(calc.value.pesoliquidoseco, pesosaca.value))
const somaDescontos = computed(
  () =>
    (Number(calc.value.descontoumidade) || 0) +
    (Number(calc.value.descontoimpureza) || 0) +
    (Number(calc.value.descontoavariados) || 0),
)
const somaPercentual = computed(() =>
  (local.value?.plantios || []).reduce((s, p) => s + (Number(p.percentual) || 0), 0),
)

// Talhões selecionados (multi-select) — só os códigos que estão na carga.
const talhoesSelecionados = computed(() => (local.value?.plantios || []).map((p) => p.codplantio))

// Sincroniza a seleção do multi-select com local.plantios, preservando o %
// (rateio) dos que continuam e criando os novos. Talhão único => 100%.
function onTalhoesChange(cods) {
  const atuais = local.value.plantios || []
  const novos = (cods || []).map((cod) => {
    const existente = atuais.find((p) => p.codplantio === cod)
    if (existente) return existente
    const opt = plantiosDaSafra.value.find((o) => o.codplantio === cod)
    return { codplantio: cod, percentual: null, rotulo: opt?.rotulo || null }
  })
  if (novos.length === 1) {
    novos[0].percentual = 100
  } else if (novos.length === 2 && novos.some((p) => p.percentual == null)) {
    // Virou mistura de dois talhões: começa meio a meio; a partir daí, editar
    // um campo ajusta o outro pra sempre fechar 100% (ver ajustarPercentual).
    novos[0].percentual = 50
    novos[1].percentual = 50
  }
  local.value.plantios = novos
}

// Rateio de DOIS talhões sempre fecha 100%: ao editar um campo, o outro vira o
// complemento automaticamente. Com 3+ talhões o ajuste segue manual (a soma é
// só indicada, ficando laranja enquanto não bate 100%).
function ajustarPercentual(plantio, valor) {
  const v = Math.min(100, Math.max(0, Number(valor) || 0))
  plantio.percentual = v
  const ps = local.value.plantios || []
  if (ps.length === 2) {
    const outro = ps.find((p) => p.codplantio !== plantio.codplantio)
    if (outro) outro.percentual = 100 - v
  }
}

// Toda entrada exige placa, ao menos um talhão e o peso bruto (a etapa inicial
// é "Peso Bruto"). Vale pra salvar e pra avançar.
function entradaValida() {
  if (!local.value.placa || !local.value.plantios.length || !local.value.pesobruto) {
    $q.notify({
      type: 'warning',
      message: 'Informe a placa, ao menos um talhão e o peso bruto.',
    })
    return false
  }
  return true
}

// Salva na etapa atual e fecha (registra/atualiza sem mover de coluna).
function salvar() {
  if (!entradaValida()) return
  emit('salvar', local.value)
}

// Avança pra próxima etapa SEM fechar — revela os campos da etapa seguinte pra
// continuar na mesma sessão. Sem próxima etapa => imprime o ticket.
function avancar() {
  if (!entradaValida()) return
  if (local.value.etapa === 'TARA' && !local.value.tara) {
    return $q.notify({ type: 'warning', message: 'Informe a tara.' })
  }
  const prox = proxima[local.value.etapa]
  if (!prox) return imprimirTicket()
  local.value.etapa = prox
  emit('avancar', local.value)
}

function fazendaNome() {
  for (const p of local.value.plantios || []) {
    const f = plantiosDaSafra.value.find((o) => o.codplantio === p.codplantio)?.Fazenda?.fazenda
    if (f) return f
  }
  return 'MG Agro'
}

function imprimirTicket() {
  const c = calc.value
  // Dados completos do caminhão (cadastro) — só aparecem no ticket.
  const veic = store.veiculoPorId(local.value.codveiculo)
  const ok = imprimir({
    numero: local.value.codcargacolheita,
    data: local.value.data,
    fazenda: fazendaNome(),
    cultura: culturaAtiva.value?.cultura,
    safra: safraAtiva.value?.safra,
    placa: local.value.placa,
    veiculo: veic?.veiculo || null,
    renavam: veic?.renavam || null,
    motorista: local.value.motorista,
    talhoes: local.value.plantios,
    pesobruto: local.value.pesobruto,
    tara: local.value.tara,
    pesoliquido: c.pesoliquido,
    umidade: local.value.umidade,
    impureza: local.value.impureza,
    avariados: local.value.avariados,
    descontoumidade: c.descontoumidade,
    descontoimpureza: c.descontoimpureza,
    descontoavariados: c.descontoavariados,
    pesoliquidoseco: c.pesoliquidoseco,
    sacas: sacasSeco.value,
    pesosaca: pesosaca.value,
  })
  if (!ok) $q.notify({ type: 'warning', message: 'Permita pop-ups para imprimir o ticket.' })
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
            class="col-12 col-sm-6"
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
            class="col-12 col-sm-6"
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

        <!-- Talhões da carga: multi-select (mínimo 1); % rateio só com 2+ -->
        <div>
          <q-select
            :model-value="talhoesSelecionados"
            :options="plantiosDaSafra"
            option-value="codplantio"
            option-label="rotulo"
            multiple
            use-chips
            emit-value
            map-options
            outlined
            label="Talhão / variedade"
            @update:model-value="onTalhoesChange"
          >
            <template #no-option>
              <q-item>
                <q-item-section class="text-grey-6"
                  >Nenhum talhão plantado nesta safra</q-item-section
                >
              </q-item>
            </template>
          </q-select>

          <!-- Rateio entre talhões (mistura de 2+) -->
          <div v-if="mostrarPercentual" class="q-mt-sm">
            <div class="text-caption text-grey-7 q-mb-xs">Rateio entre talhões</div>
            <div
              v-for="p in local.plantios"
              :key="p.codplantio"
              class="row items-center q-col-gutter-sm q-mb-xs"
            >
              <div class="col text-body2">{{ p.rotulo }}</div>
              <MgInputValor
                :model-value="p.percentual"
                :decimals="0"
                :min="0"
                :max="100"
                suffix="%"
                label="%"
                class="col-3"
                @update:model-value="(v) => ajustarPercentual(p, v)"
              />
            </div>
            <div
              class="text-caption"
              :class="somaPercentual === 100 ? 'text-grey-7' : 'text-orange-8'"
            >
              Soma: {{ somaPercentual }}%
            </div>
          </div>
        </div>

        <q-separator />

        <!-- Peso bruto (sempre visível desde a chegada) -->
        <MgInputValor v-model="local.pesobruto" :decimals="0" suffix="kg" label="Peso bruto" />

        <!-- Tara (a partir da etapa de tara) -->
        <MgInputValor
          v-if="mostrarTara"
          v-model="local.tara"
          :decimals="0"
          suffix="kg"
          label="Tara"
        />

        <!-- Classificação (a partir da etapa de classificação) -->
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

        <!-- Resultado (calculado offline, quando já há peso líquido) -->
        <q-card v-if="mostrarResultado" flat bordered class="bg-grey-1">
          <q-card-section class="q-pa-sm">
            <div class="row text-center">
              <div class="col">
                <div class="text-caption text-grey-7">Líquido</div>
                <div class="text-weight-medium">{{ fmt(calc.pesoliquido) }} kg</div>
              </div>
              <div class="col">
                <div class="text-caption text-grey-7">Desconto</div>
                <div class="text-weight-medium text-orange-9">{{ fmt(somaDescontos) }} kg</div>
              </div>
              <div class="col">
                <div class="text-caption text-grey-7">Líquido seco</div>
                <div class="text-weight-medium text-green-9">
                  {{ fmt(calc.pesoliquidoseco) }} kg
                </div>
              </div>
              <div class="col">
                <div class="text-caption text-grey-7">Sacas</div>
                <div class="text-weight-medium">{{ fmt(sacasSeco, 1) }}</div>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <q-input v-model="local.observacao" label="Observação" type="textarea" autogrow outlined />
      </q-card-section>

      <q-separator />

      <q-card-actions align="right">
        <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
        <!-- Ação única = próxima movimentação do kanban. Caminhão novo entra na
             coluna Peso Bruto (Registrar); os demais avançam de etapa. -->
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
