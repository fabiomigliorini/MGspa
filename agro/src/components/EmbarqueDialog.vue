<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useEmbarqueStore } from 'src/stores/embarque'
import { sacas } from 'src/utils/desconto'
import { imprimirTicket } from 'src/utils/ticket'
import MgInputValor from '@components/MgInputValor.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  embarque: { type: Object, default: null },
  // true = embarque novo (só "Registrar", entra na coluna Tara sem avançar)
  novo: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'salvar'])

const $q = useQuasar()
const store = useEmbarqueStore()
const { contratosAtivos, plantiosTalhao, culturas } = storeToRefs(store)

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

const proxima = {
  TARA: 'CLASSIFICACAO',
  CLASSIFICACAO: 'BRUTO',
  BRUTO: 'FISCAL',
  FISCAL: 'DESPACHADO',
  DESPACHADO: null,
}
// Rótulo da ação = etapa ATUAL (o que se faz na coluna do card), não a próxima.
// Tara pesa a tara; Classificação classifica; Peso Bruto pesa o bruto;
// Nota Fiscal emite as NFs; Despachado fecha/imprime o ticket.
const rotuloAvancar = {
  TARA: 'Pesar tara',
  CLASSIFICACAO: 'Classificar',
  BRUTO: 'Pesar bruto',
  FISCAL: 'Notas fiscais',
  DESPACHADO: 'Despachar',
}
const labelEtapa = {
  TARA: 'Tara',
  CLASSIFICACAO: 'Classificação',
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
const sacasSeco = computed(() => sacas(calc.value.pesoliquidoseco, pesosacaCultura.value))

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

function aprovar() {
  local.value.aprovado = local.value.aprovado ? null : new Date().toISOString()
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
  imprimirTicket({
    titulo: 'TICKET DE EXPEDIÇÃO',
    rotuloItens: 'Contratos',
    numero: local.value.codembarque,
    data: local.value.data,
    placa: local.value.placa,
    motorista: local.value.motorista,
    talhoes: local.value.contratos.map((ct) => ({
      rotulo: ct.rotulo,
      percentual: null,
      sc: ct.quantidade,
    })),
    pesobruto: local.value.pesobruto,
    tara: local.value.pesotara,
    pesoliquido: c.pesoliquido,
    umidade: local.value.umidade,
    impureza: local.value.impureza,
    avariados: local.value.avariados,
    descontoumidade: c.descontoumidade,
    descontoimpureza: c.descontoimpureza,
    descontoavariados: c.descontoavariados,
    pesoliquidoseco: c.pesoliquidoseco,
    sacas: sacasSeco.value,
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
    <q-card v-if="local" style="width: 600px; max-width: 96vw">
      <q-card-section class="row items-center bg-green-8 text-white">
        <div class="text-h6">{{ local.placa || 'Novo caminhão' }}</div>
        <q-space />
        <q-chip color="white" text-color="green-9" :label="labelEtapa[local.etapa]" />
        <q-btn flat round icon="close" v-close-popup tabindex="-1" />
      </q-card-section>

      <q-card-section class="q-gutter-md scroll" style="max-height: 72vh">
        <!-- Identificação -->
        <div class="row q-col-gutter-md">
          <q-input
            v-model="local.placa"
            label="Placa"
            outlined
            class="col-6 col-sm-4"
            @update:model-value="local.placa = ($event || '').toUpperCase()"
          />
          <q-input
            v-model="local.placacarreta"
            label="Carreta"
            outlined
            class="col-6 col-sm-4"
            @update:model-value="local.placacarreta = ($event || '').toUpperCase()"
          />
          <q-input v-model="local.motorista" label="Motorista" outlined class="col-12 col-sm-4" />
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
                suffix="sc"
                label="Qtd"
                class="col-3"
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
              suffix="sc"
              label="Qtd"
              class="col-3"
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

        <!-- Pesagem (tara primeiro na expedição) -->
        <div class="row q-col-gutter-md">
          <MgInputValor
            v-model="local.pesotara"
            :decimals="0"
            suffix="kg"
            label="Tara (vazio)"
            class="col-6"
          />
          <MgInputValor
            v-model="local.pesobruto"
            :decimals="0"
            suffix="kg"
            label="Bruto (carregado)"
            class="col-6"
          />
        </div>

        <!-- Classificação + aprovação -->
        <div class="row q-col-gutter-md items-center">
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
        <q-btn
          :outline="!local.aprovado"
          :unelevated="!!local.aprovado"
          :color="local.aprovado ? 'green-7' : 'grey-7'"
          :icon="local.aprovado ? 'verified' : 'how_to_reg'"
          :label="local.aprovado ? 'Comprador aprovou' : 'Comprador aprovou?'"
          @click="aprovar"
        />

        <!-- Resultado -->
        <q-card flat bordered class="bg-grey-1">
          <q-card-section class="q-pa-sm row text-center">
            <div class="col">
              <div class="text-caption text-grey-7">Líquido</div>
              <div class="text-weight-medium">{{ fmt(calc.pesoliquido) }} kg</div>
            </div>
            <div class="col">
              <div class="text-caption text-grey-7">Líquido seco</div>
              <div class="text-weight-medium text-green-9">{{ fmt(calc.pesoliquidoseco) }} kg</div>
            </div>
            <div class="col">
              <div class="text-caption text-grey-7">Sacas</div>
              <div class="text-weight-medium">{{ fmt(sacasSeco, 1) }}</div>
            </div>
          </q-card-section>
        </q-card>

        <q-input v-model="local.observacao" label="Observação" type="textarea" autogrow outlined />
      </q-card-section>

      <q-separator />

      <q-card-actions align="right">
        <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
        <!-- Ação única = próxima movimentação do kanban. Embarque novo entra na
             coluna Pátio (Registrar); os demais avançam de etapa. -->
        <q-btn
          unelevated
          :color="novo ? 'primary' : 'green-7'"
          :label="novo ? 'Registrar' : rotuloAvancar[local.etapa]"
          @click="novo ? salvar() : avancar()"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
