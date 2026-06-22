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
// backend revalida as linhas e trava o líquido (precoliquido/totaldeducao).
// FETHAB não tem mais checkbox: a isenção é IMPLÍCITA — basta zerar a UPF da
// linha do grupo FETHAB que aquela transação fica isenta (o backend deriva o
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
const linhas = ref([])
const salvando = ref(false)
const carregando = ref(false)

const editando = computed(() => !!props.fixacao?.codcontratofixacao)
const pesosaca = computed(() => Number(props.contrato?.pesosaca) || 60)

// Teto de quantidade: contrato sem volume em aberto. Disponível p/ esta fixação =
// saldo a fixar + a própria quantidade (na edição, ela volta pro saldo).
const semTeto = computed(() => !!props.contrato?.volumeemaberto)
const disponivelFixar = computed(() =>
  props.afixar == null ? null : Number(props.afixar) + (editando.value ? n(props.fixacao?.quantidade) : 0),
)
const hintFixar = computed(() =>
  semTeto.value || disponivelFixar.value == null
    ? undefined
    : `Saldo a fixar: ${fmt(disponivelFixar.value, 0)} sc`,
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
function valorLinha(l) {
  if (l.base === 'UNIDADE') {
    return arred4((n(l.percentual) / 100) * n(l.upf) * (pesosaca.value / 1000))
  }
  return arred4((n(l.percentual) / 100) * precoreal.value)
}
const totalDeducao = computed(() => arred4(linhas.value.reduce((s, l) => s + valorLinha(l), 0)))
const liquido = computed(() => arred4(precoreal.value - totalDeducao.value))
const percentualDeducao = computed(() =>
  precoreal.value > 0 ? (totalDeducao.value / precoreal.value) * 100 : 0,
)

function mapLinha(it) {
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
// pré-preencher as linhas editáveis. SEMPRE traz o FETHAB; isenção = operador
// zera a UPF dessa linha.
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
    linhas.value = (data.itens || []).map((it) => ({
      ...mapLinha(it),
      upf: it.base === 'UNIDADE' ? n(it.upf ?? data.unidade?.valor) : null,
    }))
  } catch (e) {
    notifyError(e, 'Não foi possível carregar os impostos da cultura.')
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
      linhas.value = f.tributos.map(mapLinha)
    } else {
      carregarPadrao()
    }
    return
  }
  form.value = {
    data: new Date().toISOString().slice(0, 10),
    quantidade: null,
    preco: null,
    moeda: 'BRL',
    dolar: null,
  }
  linhas.value = []
  carregarPadrao()
}

watch(
  () => props.modelValue,
  (v) => {
    if (v) abrir()
  },
)

async function salvar() {
  if (salvando.value) return
  if (precoreal.value <= 0) {
    notifyError(null, 'Informe o preço bruto da fixação.')
    return
  }
  if (n(form.value.quantidade) <= 0) {
    notifyError(null, 'Informe a quantidade fixada (sacas).')
    return
  }
  // Espelho da trava do backend: não fixar além do saldo (contratos com teto).
  if (
    !semTeto.value &&
    disponivelFixar.value != null &&
    n(form.value.quantidade) > disponivelFixar.value + 1e-6
  ) {
    notifyError(null, `Excede o saldo a fixar do contrato (${fmt(disponivelFixar.value, 0)} sc).`)
    return
  }
  salvando.value = true
  try {
    const payload = {
      data: form.value.data,
      quantidade: form.value.quantidade,
      preco: form.value.preco,
      moeda: form.value.moeda,
      dolar: estrangeira.value ? form.value.dolar : null,
      tributos: linhas.value.map((l) => ({
        codtributo: l.codtributo,
        codigo: l.codigo,
        descricao: l.descricao,
        base: l.base,
        percentual: n(l.percentual),
        upf: l.base === 'UNIDADE' ? n(l.upf) : null,
        grupofethab: !!l.grupofethab,
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

        <q-card-section class="q-pt-md">
          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-6">
              <MgInputData v-model="form.data" label="Data" type="date" />
            </div>
            <div class="col-12 col-sm-6">
              <MgInputValor
                v-model="form.quantidade"
                :decimals="0"
                suffix="sc"
                label="Quantidade"
                :hint="hintFixar"
                autofocus
              />
            </div>
            <div class="col-12 col-sm-6" :class="estrangeira ? 'col-sm-6' : ''">
              <MgInputValor v-model="form.preco" :decimals="2" label="Preço bruto / saca" />
            </div>
            <div v-if="estrangeira" class="col-12 col-sm-6">
              <MgInputValor v-model="form.dolar" :decimals="4" prefix="R$" label="Cotação R$" />
            </div>
            <div class="col-12 col-sm-6">
              <MgSelectMoeda v-model="form.moeda" />
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="row items-center justify-between q-mb-sm">
            <div class="text-subtitle2">Impostos / deduções</div>
            <q-btn
              flat
              no-caps
              size="sm"
              color="primary"
              icon="refresh"
              label="Recarregar padrão"
              :loading="carregando"
              @click="carregarPadrao"
            />
          </div>

          <div v-for="(l, i) in linhas" :key="i" class="row q-col-gutter-sm items-center q-py-xs">
            <div class="col-4">
              <MgInputValor v-model="l.percentual" :decimals="2" suffix="%" :label="l.codigo" />
            </div>
            <div class="col-4">
              <!-- Col 2: tributo indexado mostra a UPF (editável); tributo de
                   valor mostra o próprio custo por saca (readonly). -->
              <MgInputValor
                v-if="l.base === 'UNIDADE'"
                v-model="l.upf"
                :decimals="2"
                prefix="R$"
                label="UPF"
              />
              <MgInputValor
                v-else
                :model-value="valorLinha(l)"
                :decimals="2"
                prefix="R$"
                label="Custo"
                readonly
                bg-color="grey-2"
                input-class="text-red"
              />
            </div>
            <div class="col-4">
              <!-- Col 3: última linha mostra o líquido geral da saca; nas demais,
                   só os indexados mostram o custo aqui (o de valor já está na col2). -->
              <MgInputValor
                v-if="i === linhas.length - 1"
                :model-value="liquido"
                :decimals="2"
                prefix="R$"
                label="Líquido"
                readonly
                bg-color="green-1"
                input-class="text-green-10"
              />
              <MgInputValor
                v-else-if="l.base === 'UNIDADE'"
                :model-value="valorLinha(l)"
                :decimals="2"
                prefix="R$"
                label="Custo"
                readonly
                bg-color="grey-2"
                input-class="text-red"
              />
            </div>
          </div>
          <div class="text-caption">Deduções ({{ fmt(percentualDeducao, 2) }}%)</div>

          <div v-if="!linhas.length && !carregando" class="text-grey-6 q-py-sm">
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
