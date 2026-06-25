<script setup>
import { ref, computed, watch } from 'vue'
import { api } from 'src/services/api'
import { notifyError } from 'src/utils/notify'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectMoeda from '@components/MgSelectMoeda.vue'

// Modal de valores + impostos da fixação. O operador informa o preço bruto e
// AJUSTA as alíquotas/UPF de cada tributo (default vindo da config da cultura).
// O líquido é recalculado ao vivo e GRAVADO junto da fixação (snapshot) — o
// backend revalida os tributos e trava o líquido (precoliquido/totaldeducao).
// FETHAB não tem mais checkbox: a isenção é IMPLÍCITA — basta zerar a UPF do
// tributo do grupo FETHAB que aquela transação fica isenta (o backend deriva o
// flag isentofethab a partir disso).
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  cod: { type: [Number, String], required: true },
  contrato: { type: Object, default: () => ({}) },
  fixacao: { type: Object, default: null },
  // Saldo a fixar (sc) do contrato. Espelho da trava do backend: null = sem info
  // (não bloqueia); contrato volume em aberto também não tem teto.
  afixar: { type: Number, default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const aberto = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const form = ref({
  data: '',
  quantidade: null,
  preco: null,
  moeda: 'BRL',
  dolar: null,
})
const tributos = ref([])
const salvando = ref(false)
const carregando = ref(false)

const editando = computed(() => !!props.fixacao?.codcontratofixacao)
const pesosaca = computed(() => Number(props.contrato?.pesosaca) || 60)

// Teto de quantidade: contrato sem volume em aberto. Disponível p/ esta fixação =
// saldo a fixar + a própria quantidade (na edição, ela volta pro saldo).
const semTeto = computed(() => !!props.contrato?.volumeemaberto)
const disponivelFixar = computed(() =>
  props.afixar == null
    ? null
    : Number(props.afixar) + (editando.value ? n(props.fixacao?.quantidade) : 0),
)
// Teto p/ o campo quantidade (clamp do MgInputValor): null = sem limite.
const maxQuantidade = computed(() =>
  semTeto.value || disponivelFixar.value == null ? null : disponivelFixar.value,
)
// Qualquer moeda != BRL é estrangeira e exige cotação em R$ pra travar o preço.
const estrangeira = computed(() => form.value.moeda && form.value.moeda !== 'BRL')

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

// Preço bruto em R$/sc (moeda estrangeira trava com a cotação informada).
const precoreal = computed(() =>
  estrangeira.value && form.value.dolar
    ? n(form.value.preco) * n(form.value.dolar)
    : n(form.value.preco),
)

// Mesma fórmula do motor fiscal (ContratoCalculoService): UNIDADE = %/100 × UPF
// × pesosaca/1000; VALOR = %/100 × bruto.
function valorTributo(tributo) {
  if (tributo.base === 'UNIDADE') {
    return arred4((n(tributo.percentual) / 100) * n(tributo.upf) * (pesosaca.value / 1000))
  }
  return arred4((n(tributo.percentual) / 100) * precoreal.value)
}
const totalDeducao = computed(() =>
  arred4(tributos.value.reduce((s, tributo) => s + valorTributo(tributo), 0)),
)
const liquido = computed(() => arred4(precoreal.value - totalDeducao.value))
const percentualDeducao = computed(() =>
  precoreal.value > 0 ? (totalDeducao.value / precoreal.value) * 100 : 0,
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

// Busca a config de tributos da cultura (alíquotas + UPF da competência) pra
// pré-preencher os tributos editáveis. SEMPRE traz o FETHAB; isenção = operador
// zera a UPF desse tributo.
async function carregarPadrao() {
  if (!props.contrato?.codcultura) return
  carregando.value = true
  try {
    const { data } = await api.get('v1/contrato/calculo', {
      params: {
        codcultura: props.contrato.codcultura,
        bruto: precoreal.value || 0,
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

function abrir() {
  if (editando.value) {
    const f = props.fixacao
    form.value = {
      data: (f.data || '').slice(0, 10),
      quantidade: n(f.quantidade),
      preco: n(f.preco),
      moeda: f.moeda || 'BRL',
      dolar: f.dolar || null,
    }
    if (Array.isArray(f.tributos) && f.tributos.length) {
      tributos.value = f.tributos.map(mapTributo)
    } else {
      carregarPadrao()
    }
    return
  }
  form.value = {
    data: new Date().toISOString().slice(0, 10),
    // Pré-preenche com o saldo a fixar (default = fixar tudo); operador ajusta.
    quantidade: maxQuantidade.value,
    preco: null,
    moeda: 'BRL',
    dolar: null,
  }
  tributos.value = []
  carregarPadrao()
}

watch(
  () => props.modelValue,
  (v) => {
    if (v) abrir()
  },
)

// Validação fica nos :rules dos campos (q-form valida no submit antes de chamar
// salvar); o teto de quantidade é o :max do MgInputValor (clamp).
async function salvar() {
  if (salvando.value) return
  salvando.value = true
  try {
    const payload = {
      data: form.value.data,
      quantidade: form.value.quantidade,
      preco: form.value.preco,
      moeda: form.value.moeda,
      dolar: estrangeira.value ? form.value.dolar : null,
      tributos: tributos.value.map((tributo) => ({
        codtributo: tributo.codtributo,
        codigo: tributo.codigo,
        descricao: tributo.descricao,
        base: tributo.base,
        percentual: n(tributo.percentual),
        upf: tributo.base === 'UNIDADE' ? n(tributo.upf) : null,
        grupofethab: !!tributo.grupofethab,
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
        <q-card-section class="bg-primary text-white">
          <div class="text-h6">{{ editando ? 'Editar fixação' : 'Nova fixação' }}</div>
          <div class="text-caption">Valores e impostos · {{ contrato.Cultura?.cultura || '' }}</div>
        </q-card-section>

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-4">
              <MgInputData v-model="form.data" label="Data" type="date" />
            </div>
            <div class="col-4">
              <MgSelectMoeda v-model="form.moeda" />
            </div>
            <div class="col-4">
              <MgInputValor
                v-model="form.preco"
                :decimals="2"
                label="Preço bruto / saca"
                lazy-rules
                :rules="[() => precoreal > 0]"
              />
            </div>

            <!-- Uma linha por tributo: rótulo · alíquota · UPF (só indexado) · custo. -->
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
                <!-- Tributo indexado: UPF editável; de valor não tem UPF. -->
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
                  prefix="R$"
                  :label="tributo.codigo"
                  readonly
                  bg-color="grey-2"
                  input-class="text-red"
                />
              </div>
            </template>
            <!-- <div class="col-9 text-weight-medium text-grey-8">
              Deduções
            </div>
            <div class="col-3">
              <MgInputValor
                :model-value="totalDeducao"
                :decimals="2"
                prefix="R$"
                label="Total deduções"
                readonly
                bg-color="grey-2"
                input-class="text-red"
              />
            </div> -->

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
            <div class="col-4 text-weight-medium text-grey-8">Total</div>
            <div class="col-4">
              <MgInputValor
                v-model="form.quantidade"
                :decimals="0"
                :max="maxQuantidade"
                suffix="sc"
                label="Quantidade"
                lazy-rules
                :rules="[(v) => v > 0]"
              />
            </div>
            <div class="col-4">
              <MgInputValor
                :model-value="form.quantidade * liquido"
                :decimals="0"
                :max="maxQuantidade"
                prefix="R$"
                label="Quantidade"
                bg-color="green-1"
                input-class="text-green-10"
                readonly
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
