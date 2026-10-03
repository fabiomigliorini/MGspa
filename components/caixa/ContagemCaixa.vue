<script setup>
// Contagem do caixa (M13 doc-3): quantidade de cada cédula e moeda, em duas colunas com o valor de
// cada campo no hint e o total da coluna, e o estoque dos itens de contagem (chips, ingressos) a
// valor de face. v-model = { contagem: { '200': 3, ... }, itens: { codcaixaitem: valor } }; o total
// geral soma tudo, que é o que fica no caixa.
import { computed } from 'vue'
import MgInputValor from '@components/MgInputValor.vue'
import { formataNumero } from '@components/formatters'
import { CEDULAS, MOEDAS } from '@components/stores/caixaSessaoStore'

const model = defineModel({ type: Object, required: true })
const props = defineProps({
  // itens de contagem (modo C): [{ codcaixaitem, item }]
  itens: { type: Array, default: () => [] },
  autofocus: { type: Boolean, default: false },
  // só consulta (caixa fechado ou sem permissão)
  disable: { type: Boolean, default: false },
})

const rotulo = (v) => (Number(v) >= 2 ? `R$ ${Number(v)}` : formataNumero(Number(v)))

const qtd = (v) => model.value.contagem?.[v] ?? null
const setQtd = (v, q) => {
  model.value.contagem = { ...model.value.contagem, [v]: q == null ? null : Number(q) }
}
// o valor do campo (quantidade × face), no hint
const valor = (v) => (Number(qtd(v)) > 0 ? formataNumero(qtd(v) * Number(v)) : undefined)

const soma = (faces) =>
  Math.round(faces.reduce((s, v) => s + (Number(qtd(v)) || 0) * Number(v), 0) * 100) / 100
const colunas = computed(() => [
  { titulo: 'Cédulas', faces: CEDULAS, total: soma(CEDULAS) },
  { titulo: 'Moedas', faces: MOEDAS, total: soma(MOEDAS) },
])
const estoque = computed(() =>
  props.itens.reduce((s, i) => s + (Number(model.value.itens?.[i.codcaixaitem]) || 0), 0),
)
const total = computed(() => Math.round((soma(CEDULAS) + soma(MOEDAS) + estoque.value) * 100) / 100)

defineExpose({ total })
</script>

<template>
  <div>
    <div class="row q-col-gutter-lg">
      <div v-for="(c, ci) in colunas" :key="c.titulo" class="col-12 col-sm-6 column">
        <div class="text-caption text-grey-7 q-mb-sm">{{ c.titulo }} (quantidade)</div>
        <div class="col row q-col-gutter-sm content-start">
          <div v-for="(v, i) in c.faces" :key="v" class="col-4">
            <MgInputValor
              :model-value="qtd(v)"
              @update:model-value="(q) => setQtd(v, q)"
              :label="rotulo(v)"
              :disable="disable"
              :decimals="0"
              :min="0"
              bottom-slots
              :autofocus="autofocus && ci === 0 && i === 0"
            >
              <template #hint>
                <div class="text-right">{{ valor(v) }}</div>
              </template>
            </MgInputValor>
          </div>
        </div>
        <div class="row items-center q-mt-sm">
          <div class="col text-caption text-grey-7">Total {{ c.titulo.toLowerCase() }}</div>
          <div class="text-subtitle1">R$ {{ formataNumero(c.total) }}</div>
        </div>
      </div>
    </div>
    <template v-if="itens.length">
      <q-separator class="q-my-md" />
      <div class="text-caption text-grey-7 q-mb-sm">Itens (valor de face)</div>
      <div class="row q-col-gutter-md">
        <div v-for="i in itens" :key="i.codcaixaitem" class="col-6 col-sm-4">
          <MgInputValor v-model="model.itens[i.codcaixaitem]" :label="i.item" :disable="disable" />
        </div>
      </div>
      <div class="row items-center q-mt-md">
        <div class="col text-caption text-grey-7">Total itens</div>
        <div class="text-subtitle1">R$ {{ formataNumero(estoque) }}</div>
      </div>
    </template>
    <q-separator class="q-my-md" />
    <div class="row items-center">
      <div class="col text-subtitle2">Total geral</div>
      <div class="text-h6">R$ {{ formataNumero(total) }}</div>
    </div>
  </div>
</template>
