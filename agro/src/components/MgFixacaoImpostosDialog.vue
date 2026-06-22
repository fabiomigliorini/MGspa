<script setup>
import { ref, computed, watch } from 'vue'
import { api } from 'src/services/api'
import { notifyError } from 'src/utils/notify'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'

// Modal de valores + impostos da fixação. O operador informa o preço bruto e
// AJUSTA as alíquotas/UPF de cada tributo (default vindo da config da cultura).
// O líquido é recalculado ao vivo e GRAVADO junto da fixação (snapshot) — o
// backend revalida as linhas e trava o líquido (precoliquido/totaldeducao).
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  cod: { type: [Number, String], required: true },
  contrato: { type: Object, default: () => ({}) },
  fixacao: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const moedas = [
  { label: 'R$', value: 'BRL' },
  { label: 'US$', value: 'USD' },
]

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
  isentofethab: false,
})
const linhas = ref([])
const salvando = ref(false)
const carregando = ref(false)

const editando = computed(() => !!props.fixacao?.codcontratofixacao)
const pesosaca = computed(() => Number(props.contrato?.pesosaca) || 60)

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
function rs(v) {
  return 'R$ ' + fmt(v, 2)
}
function arred4(v) {
  return Math.round(n(v) * 10000) / 10000
}

// Preço bruto em R$/sc (USD trava com o dólar informado).
const precoreal = computed(() =>
  form.value.moeda === 'USD' && form.value.dolar
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

// Busca a config de tributos da cultura (alíquotas + UPF da competência) pra
// pré-preencher as linhas editáveis.
async function carregarPadrao() {
  if (!props.contrato?.codcultura) return
  carregando.value = true
  try {
    const { data } = await api.get('v1/contrato/calculo', {
      params: {
        codcultura: props.contrato.codcultura,
        bruto: precoreal.value || 0,
        data: form.value.data || undefined,
        // isenção de FETHAB vive na fixação: isento => o padrão já vem sem as
        // linhas do grupo FETHAB.
        isentofethab: form.value.isentofethab ? 1 : 0,
        funruralvenda: props.contrato.Filial?.funruralvenda ? 1 : 0,
      },
    })
    linhas.value = (data.itens || []).map((it) => ({
      codtributo: it.codtributo ?? null,
      codigo: it.codigo,
      descricao: it.descricao,
      base: it.base,
      percentual: n(it.percentual),
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
      isentofethab: !!f.isentofethab,
    }
    if (Array.isArray(f.tributos) && f.tributos.length) {
      linhas.value = f.tributos.map((it) => ({
        codtributo: it.codtributo ?? null,
        codigo: it.codigo,
        descricao: it.descricao,
        base: it.base,
        percentual: n(it.percentual),
        upf: it.base === 'UNIDADE' ? n(it.upf) : null,
      }))
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
    isentofethab: false,
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
  salvando.value = true
  try {
    const payload = {
      data: form.value.data,
      quantidade: form.value.quantidade,
      preco: form.value.preco,
      moeda: form.value.moeda,
      dolar: form.value.moeda === 'USD' ? form.value.dolar : null,
      isentofethab: !!form.value.isentofethab,
      tributos: linhas.value.map((l) => ({
        codtributo: l.codtributo,
        codigo: l.codigo,
        descricao: l.descricao,
        base: l.base,
        percentual: n(l.percentual),
        upf: l.base === 'UNIDADE' ? n(l.upf) : null,
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
            <div class="col-12 col-sm-4">
              <MgInputData v-model="form.data" label="Data" type="date" autofocus />
            </div>
            <div class="col-12 col-sm-4">
              <MgInputValor
                v-model="form.quantidade"
                :decimals="0"
                suffix="sc"
                label="Quantidade"
              />
            </div>
            <div class="col-12 col-sm-4 self-center">
              <q-btn-toggle
                v-model="form.moeda"
                :options="moedas"
                spread
                no-caps
                unelevated
                toggle-color="primary"
                color="grey-3"
                text-color="grey-9"
              />
            </div>
            <div class="col-12 col-sm-6">
              <MgInputValor v-model="form.preco" :decimals="2" label="Preço bruto / saca" />
            </div>
            <div v-if="form.moeda === 'USD'" class="col-12 col-sm-6">
              <MgInputValor v-model="form.dolar" :decimals="4" prefix="R$" label="Dólar travado" />
            </div>
            <div class="col-12">
              <q-checkbox
                v-model="form.isentofethab"
                label="Isento de FETHAB"
                @update:model-value="carregarPadrao"
              />
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

          <div class="row text-caption text-grey-7 q-px-xs q-mb-xs">
            <div class="col-4">Tributo</div>
            <div class="col-3 text-center">Alíquota</div>
            <div class="col-3 text-center">UPF</div>
            <div class="col-2 text-right">R$/sc</div>
          </div>

          <div v-for="(l, i) in linhas" :key="i" class="row q-col-gutter-sm items-center q-py-xs">
            <div class="col-4">
              <div class="text-weight-medium">{{ l.codigo }}</div>
              <div class="text-caption text-grey-6">
                {{ l.base === 'UNIDADE' ? 'UPF × pesosaca' : 'sobre o bruto' }}
              </div>
            </div>
            <div class="col-3">
              <MgInputValor v-model="l.percentual" :decimals="2" suffix="%" />
            </div>
            <div class="col-3">
              <MgInputValor v-if="l.base === 'UNIDADE'" v-model="l.upf" :decimals="2" prefix="R$" />
              <div v-else class="text-center text-grey-5">—</div>
            </div>
            <div class="col-2 text-right text-weight-medium">{{ fmt(valorLinha(l), 2) }}</div>
          </div>

          <div v-if="!linhas.length && !carregando" class="text-grey-6 q-py-sm">
            Nenhum tributo configurado para esta cultura.
          </div>

          <q-banner rounded class="bg-green-1 text-green-10 q-mt-md">
            <template #avatar><q-icon name="savings" color="green-7" /></template>
            <div class="text-caption">
              Bruto {{ rs(precoreal) }}/sc · Deduções {{ rs(totalDeducao) }} ({{
                fmt(percentualDeducao, 2)
              }}%)
            </div>
            <div class="text-h6">Líquido {{ rs(liquido) }}/sc</div>
          </q-banner>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn type="submit" flat label="Salvar" color="primary" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
