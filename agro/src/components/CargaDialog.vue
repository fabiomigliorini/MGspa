<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useCargaStore } from 'src/stores/carga'
import { calcularCarga, sacas } from 'src/utils/desconto'
import { imprimirTicket as imprimir } from 'src/utils/ticket'
import MgInputValor from '@components/MgInputValor.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  carga: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue', 'salvar'])

const $q = useQuasar()
const store = useCargaStore()
const { plantiosDaSafra, faixasDaSafra, culturaAtiva, safraAtiva } = storeToRefs(store)

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

const proxima = {
  PATIO: 'BRUTO',
  BRUTO: 'CLASSIFICACAO',
  CLASSIFICACAO: 'TARA',
  TARA: 'FINALIZADO',
  FINALIZADO: null,
}
const rotuloAvancar = {
  PATIO: 'Pesar bruto',
  BRUTO: 'Classificar',
  CLASSIFICACAO: 'Pesar tara',
  TARA: 'Finalizar',
  FINALIZADO: 'Imprimir ticket',
}
const labelEtapa = {
  PATIO: 'Pátio',
  BRUTO: 'Peso Bruto',
  CLASSIFICACAO: 'Classificação',
  TARA: 'Tara',
  FINALIZADO: 'Finalizado',
}

const calc = computed(() => (local.value ? calcularCarga(local.value, faixasDaSafra.value) : {}))
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

function addPlantio() {
  local.value.plantios.push({ codplantio: null, percentual: null, rotulo: null })
}
function setRotulo(p) {
  p.rotulo = plantiosDaSafra.value.find((o) => o.codplantio === p.codplantio)?.rotulo || null
}
function removePlantio(i) {
  local.value.plantios.splice(i, 1)
}

function salvar() {
  emit('salvar', local.value)
}

function avancar() {
  const prox = proxima[local.value.etapa]
  if (!prox) return imprimirTicket()

  if (local.value.etapa === 'PATIO' && (!local.value.placa || !local.value.plantios.length)) {
    return $q.notify({ type: 'warning', message: 'Informe a placa e ao menos um talhão.' })
  }
  if (local.value.etapa === 'BRUTO' && !local.value.pesobruto) {
    return $q.notify({ type: 'warning', message: 'Informe o peso bruto.' })
  }
  if (local.value.etapa === 'TARA' && !local.value.tara) {
    return $q.notify({ type: 'warning', message: 'Informe a tara.' })
  }

  local.value.etapa = prox
  emit('salvar', local.value)
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
  const ok = imprimir({
    numero: local.value.codcargacolheita,
    data: local.value.data,
    fazenda: fazendaNome(),
    cultura: culturaAtiva.value?.cultura,
    safra: safraAtiva.value?.safra,
    placa: local.value.placa,
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

      <q-card-section class="q-gutter-md scroll" style="max-height: 70vh">
        <!-- Identificação -->
        <div class="row q-col-gutter-md">
          <q-input
            v-model="local.placa"
            label="Placa"
            outlined
            class="col-12 col-sm-6"
            @update:model-value="local.placa = ($event || '').toUpperCase()"
          />
          <q-input v-model="local.motorista" label="Motorista" outlined class="col-12 col-sm-6" />
        </div>

        <!-- Talhões (rateio %) -->
        <div>
          <div class="text-subtitle2 text-grey-8 q-mb-xs">Talhões da carga</div>
          <div
            v-for="(p, i) in local.plantios"
            :key="i"
            class="row q-col-gutter-sm items-center q-mb-xs"
          >
            <q-select
              v-model="p.codplantio"
              :options="plantiosDaSafra"
              option-value="codplantio"
              option-label="rotulo"
              emit-value
              map-options
              outlined
              label="Talhão / variedade"
              class="col"
              @update:model-value="setRotulo(p)"
            />
            <MgInputValor v-model="p.percentual" :decimals="0" suffix="%" label="%" class="col-3" />
            <q-btn flat round color="grey-7" icon="close" class="col-auto" @click="removePlantio(i)" />
          </div>
          <div class="row items-center justify-between">
            <q-btn flat color="primary" icon="add" label="Talhão" @click="addPlantio" />
            <div v-if="local.plantios.length" class="text-caption" :class="somaPercentual === 100 ? 'text-grey-7' : 'text-orange-8'">
              Soma: {{ somaPercentual }}%
            </div>
          </div>
        </div>

        <q-separator />

        <!-- Pesagem -->
        <div class="row q-col-gutter-md">
          <MgInputValor v-model="local.pesobruto" :decimals="0" suffix="kg" label="Peso bruto" class="col-6" />
          <MgInputValor v-model="local.tara" :decimals="0" suffix="kg" label="Tara" class="col-6" />
        </div>

        <!-- Classificação -->
        <div class="row q-col-gutter-md">
          <MgInputValor v-model="local.umidade" :decimals="1" suffix="%" label="Umidade" class="col-4" />
          <MgInputValor v-model="local.impureza" :decimals="1" suffix="%" label="Impureza" class="col-4" />
          <MgInputValor v-model="local.avariados" :decimals="1" suffix="%" label="Avariados" class="col-4" />
        </div>

        <!-- Resultado (calculado offline) -->
        <q-card flat bordered class="bg-grey-1">
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
                <div class="text-weight-medium text-green-9">{{ fmt(calc.pesoliquidoseco) }} kg</div>
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
        <q-btn flat label="Salvar" color="primary" @click="salvar" />
        <q-btn flat :label="rotuloAvancar[local.etapa]" color="primary" @click="avancar" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
