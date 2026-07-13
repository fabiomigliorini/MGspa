<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { api } from 'src/services/api'
import { notifyError } from 'src/utils/notify'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectMoeda from '@components/MgSelectMoeda.vue'

// Modal de valores + impostos da fixação. O operador informa o preço (na moeda),
// o vencimento e AJUSTA as alíquotas/UPF de cada tributo (config congelada na
// fixação). Em BRL o líquido é calculado ao vivo; em moeda estrangeira o R$/
// líquido só nasce ao TRAVAR o câmbio (aqui só se declara a config de tributos).
// FETHAB não tem checkbox: isenção = zerar a UPF do tributo do grupo FETHAB.
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  cod: { type: [Number, String], required: true },
  contrato: { type: Object, default: () => ({}) },
  fixacao: { type: Object, default: null },
  // Saldo a fixar (sc) do contrato. null = sem info (não bloqueia).
  afixar: { type: Number, default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const aberto = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const form = ref({
  data: '',
  datavencimento: '',
  quantidade: null,
  codmoeda: null,
  preco: null,
})
const tributos = ref([])
const salvando = ref(false)
const carregando = ref(false)

// Lista de moedas (p/ derivar o iso do codmoeda selecionado e o default Real).
const moedas = ref([])
const SIMBOLOS_MOEDA = { BRL: 'R$', USD: 'US$', EUR: '€' }
function isoDe(codmoeda) {
  return moedas.value.find((m) => Number(m.codmoeda) === Number(codmoeda))?.iso || 'BRL'
}
async function carregarMoedas() {
  if (moedas.value.length) return
  try {
    const { data } = await api.get('v1/select/moeda')
    moedas.value = data || []
  } catch {
    moedas.value = []
  }
}

const editando = computed(() => !!props.fixacao?.codcontratofixacao)
const pesosaca = computed(() => Number(props.contrato?.pesosaca) || 60)

// Teto de quantidade (contrato com volume definido).
const semTeto = computed(() => !!props.contrato?.volumeemaberto)
const disponivelFixar = computed(() =>
  props.afixar == null
    ? null
    : Number(props.afixar) + (editando.value ? n(props.fixacao?.quantidade) : 0),
)
const maxQuantidade = computed(() =>
  semTeto.value || disponivelFixar.value == null ? null : disponivelFixar.value,
)
const dataContrato = computed(() => (props.contrato?.datacontrato || '').slice(0, 10))
const hojeIso = computed(() => new Date().toISOString().slice(0, 10))
// O MgInputData passa às :rules o valor de DISPLAY (BR "dd/mm/aaaa"), não o ISO —
// então a FAIXA (não antes do contrato / não no futuro) é garantida pelo :min/
// :max (clamp), e aqui só exigimos o preenchimento.
const dataRules = [(v) => !!v || 'Informe a data']

// "É moeda estrangeira (≠ Real)?" — deriva do iso do codmoeda escolhido.
const estrangeira = computed(() => isoDe(form.value.codmoeda) !== 'BRL')
const simboloMoeda = computed(
  () => SIMBOLOS_MOEDA[isoDe(form.value.codmoeda)] ?? isoDe(form.value.codmoeda),
)

function n(v) {
  return Number(v) || 0
}
function fmt(v, dec = 2) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}
function arred4(v) {
  return Math.round(n(v) * 10000) / 10000
}

// Valor de cada tributo, na unidade que JÁ dá pra saber sem o câmbio:
//   UNIDADE (FETHAB/IAGRO) = %/100 × UPF × pesosaca/1000  → sempre R$ (independe do câmbio)
//   VALOR   (SENAR/FUNRURAL) = %/100 × preço              → na MOEDA (é % do preço)
function valorTributo(tributo) {
  if (tributo.base === 'UNIDADE') {
    return arred4((n(tributo.percentual) / 100) * n(tributo.upf) * (pesosaca.value / 1000))
  }
  return arred4((n(tributo.percentual) / 100) * n(form.value.preco))
}
// Símbolo do valor de um tributo: UNIDADE é R$; VALOR segue a moeda da fixação.
function prefixoTributo(tributo) {
  return tributo.base === 'UNIDADE' ? 'R$' : simboloMoeda.value
}
// Líquido/saca só fecha em BRL (unidades homogêneas em R$). Em moeda estrangeira
// as deduções são mistas (R$ do FETHAB + US$ do SENAR) — não somam num líquido só.
const totalDeducao = computed(() =>
  estrangeira.value ? 0 : arred4(tributos.value.reduce((s, t) => s + valorTributo(t), 0)),
)
const liquido = computed(() => arred4(n(form.value.preco) - totalDeducao.value))
const percentualDeducao = computed(() =>
  n(form.value.preco) > 0 ? (totalDeducao.value / n(form.value.preco)) * 100 : 0,
)

// Total (BRL): quantidade × líquido/sc. Estrangeira: quantidade × preço (na moeda).
const totalDisplay = computed(() =>
  estrangeira.value
    ? n(form.value.quantidade) * n(form.value.preco)
    : n(form.value.quantidade) * liquido.value,
)

function mapTributo(it) {
  return {
    codtributo: it.codtributo ?? null,
    codigo: it.codigo,
    descricao: it.descricao,
    base: it.base,
    percentual: n(it.percentual),
    upf: it.base === 'UNIDADE' ? n(it.upf) : null,
    grupofethab: !!it.grupofethab,
  }
}

// Config de tributos da cultura (alíquotas + UPF da competência) p/ pré-preencher.
async function carregarPadrao() {
  if (!props.contrato?.codcultura) return
  carregando.value = true
  try {
    const { data } = await api.get('v1/contrato/calculo', {
      params: {
        codcultura: props.contrato.codcultura,
        bruto: n(form.value.preco) || 0,
        data: form.value.data || undefined,
        funruralvenda: props.contrato.Filial?.funruralvenda ? 1 : 0,
      },
    })
    tributos.value = (data.itens || []).map((it) => ({
      ...mapTributo(it),
      upf: it.base === 'UNIDADE' ? n(it.upf ?? data.unidade?.valor) : null,
    }))
  } catch (e) {
    notifyError(e, 'Não foi possível carregar os tributos da cultura.')
  } finally {
    carregando.value = false
  }
}

async function abrir() {
  await carregarMoedas()
  if (editando.value) {
    const f = props.fixacao
    form.value = {
      data: (f.data || '').slice(0, 10),
      datavencimento: (f.datavencimento || '').slice(0, 10),
      quantidade: n(f.quantidade),
      codmoeda: f.codmoeda,
      preco: n(f.preco),
    }
    if (Array.isArray(f.tributos) && f.tributos.length) {
      tributos.value = f.tributos.map(mapTributo)
    } else {
      carregarPadrao()
    }
    return
  }
  const real = moedas.value.find((m) => m.iso === 'BRL')
  form.value = {
    data: dataContrato.value || hojeIso.value,
    datavencimento: '',
    quantidade: maxQuantidade.value,
    codmoeda: real?.codmoeda ?? null,
    preco: null,
  }
  tributos.value = []
  carregarPadrao()
}

onMounted(carregarMoedas)
watch(
  () => props.modelValue,
  (v) => {
    if (v) abrir()
  },
)

async function salvar() {
  if (salvando.value) return
  salvando.value = true
  try {
    const payload = {
      data: form.value.data,
      datavencimento: form.value.datavencimento || null,
      quantidade: form.value.quantidade,
      codmoeda: form.value.codmoeda,
      preco: form.value.preco,
      // Config fiscal congelada (declarada mesmo em moeda estrangeira). O líquido
      // em R$ é derivado das travas de câmbio no backend, não daqui.
      tributos: tributos.value.map((t) => ({
        codtributo: t.codtributo,
        codigo: t.codigo,
        descricao: t.descricao,
        base: t.base,
        percentual: n(t.percentual),
        upf: t.base === 'UNIDADE' ? n(t.upf) : null,
        grupofethab: !!t.grupofethab,
      })),
    }
    if (editando.value) {
      await api.put(`v1/contrato/${props.cod}/fixacao/${props.fixacao.codcontratofixacao}`, payload)
    } else {
      await api.post(`v1/contrato/${props.cod}/fixacao`, payload)
    }
    emit('saved')
    aberto.value = false
  } catch (e) {
    notifyError(e, 'Erro ao salvar a fixação.')
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <q-dialog v-model="aberto">
    <q-card flat style="width: 560px; max-width: 96vw">
      <q-form @submit.prevent="salvar">
        <q-card-section class="bg-primary text-white q-py-sm">
          <div class="text-h6">{{ editando ? 'Editar fixação' : 'Nova fixação' }}</div>
        </q-card-section>

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-4">
              <MgInputData
                v-model="form.data"
                label="Data da fixação"
                type="date"
                :min="dataContrato || null"
                :max="hojeIso"
                lazy-rules
                :rules="dataRules"
              />
            </div>
            <div class="col-4">
              <MgInputValor
                v-model="form.quantidade"
                :decimals="0"
                :max="maxQuantidade"
                suffix="sc"
                label="Quantidade"
                lazy-rules
                :rules="[(v) => v > 0 || 'Informe a quantidade']"
              />
            </div>
            <div class="col-4">
              <MgSelectMoeda v-model="form.codmoeda" />
            </div>

            <div class="col-4">
              <MgInputValor
                v-model="form.preco"
                :decimals="2"
                :prefix="simboloMoeda"
                label="Preço / saca"
                lazy-rules
                :rules="[(v) => v > 0 || 'Informe o preço']"
              />
            </div>
            <div class="col-4">
              <MgInputData v-model="form.datavencimento" label="Vencimento" type="date" />
            </div>
            <div class="col-4">
              <MgInputValor
                :model-value="totalDisplay"
                :decimals="estrangeira ? 2 : 0"
                :prefix="simboloMoeda"
                label="Total"
                bg-color="green-1"
                input-class="text-green-10"
                readonly
              />
            </div>

            <!-- Aviso p/ moeda estrangeira: cada imposto na sua unidade; o líquido
                 final em R$ só fecha ao travar o câmbio. -->
            <div v-if="estrangeira" class="col-12 text-caption text-blue-grey-7">
              <q-icon name="info" size="14px" class="q-mr-xs" />
              Em {{ isoDe(form.codmoeda) }}: FETHAB/IAGRO já em R$ (independem do câmbio) e
              SENAR/FUNRURAL na moeda (% do preço). O líquido total em R$ fecha ao travar o câmbio.
            </div>

            <!-- Tributos (config): alíquota + UPF + valor em qualquer moeda
                 (FETHAB/IAGRO em R$; SENAR/FUNRURAL na moeda). O líquido combinado
                 em R$ só fecha em BRL. -->
            <template v-for="(tributo, i) in tributos" :key="i">
              <div class="col-4">
                <MgInputValor
                  v-model="tributo.percentual"
                  :decimals="2"
                  suffix="%"
                  :label="'Alíquota ' + tributo.codigo"
                />
              </div>
              <div class="col-4">
                <MgInputValor
                  v-if="tributo.base === 'UNIDADE'"
                  v-model="tributo.upf"
                  :decimals="2"
                  prefix="R$"
                  label="UPF"
                />
              </div>
              <div class="col-4">
                <MgInputValor
                  :model-value="valorTributo(tributo)"
                  :decimals="2"
                  :prefix="prefixoTributo(tributo)"
                  :label="tributo.codigo"
                  readonly
                  bg-color="grey-2"
                  input-class="text-red"
                />
              </div>
            </template>

            <!-- Líquido/deduções só em BRL. -->
            <template v-if="!estrangeira">
              <div class="col-8 text-weight-medium text-grey-8">
                Líquido ({{ fmt(100 - percentualDeducao, 2) }}%)
                <q-btn
                  flat
                  dense
                  round
                  no-caps
                  color="primary"
                  icon="refresh"
                  :loading="carregando"
                  @click="carregarPadrao"
                >
                  <q-tooltip>Recalcular Tributos</q-tooltip>
                </q-btn>
              </div>
              <div class="col-4">
                <MgInputValor
                  :model-value="liquido"
                  :decimals="2"
                  prefix="R$"
                  label="Líquido"
                  readonly
                  bg-color="green-1"
                  input-class="text-green-10"
                />
              </div>
            </template>
            <div v-else class="col-12">
              <q-btn
                flat
                dense
                no-caps
                color="primary"
                icon="refresh"
                label="Recarregar tributos da cultura"
                :loading="carregando"
                @click="carregarPadrao"
              />
            </div>
          </div>

          <div v-if="!tributos.length && !carregando" class="text-grey-6 q-py-sm">
            Nenhum tributo configurado para esta cultura.
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn type="submit" flat label="Salvar" color="primary" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
