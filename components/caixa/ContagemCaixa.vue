<script setup>
// Contagem do caixa (M13 doc-3): quantidade de cada cédula e moeda e o estoque dos itens de
// contagem (chips, ingressos) a valor de face. v-model = { contagem: { '200': 3, ... }, itens:
// { codcaixaitem: valor } }; o total soma tudo, que é o que fica na gaveta.
import { computed } from 'vue'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { formataNumero } from '@components/formatters'
import { CEDULAS, MOEDAS, totalContagem } from '@components/stores/caixaSessaoStore'

const model = defineModel({ type: Object, required: true })
const props = defineProps({
  // itens de contagem (modo C): [{ codcaixaitem, item }]
  itens: { type: Array, default: () => [] },
  autofocus: { type: Boolean, default: false },
})

const rotulo = (v) => (Number(v) >= 2 ? `R$ ${Number(v)}` : formataNumero(Number(v)))

const qtd = (v) => model.value.contagem?.[v] ?? null
const setQtd = (v, q) => {
  model.value.contagem = { ...model.value.contagem, [v]: q === '' || q === null ? null : Number(q) }
}

const dinheiro = computed(() => totalContagem(model.value.contagem))
const estoque = computed(() =>
  props.itens.reduce((s, i) => s + (Number(model.value.itens?.[i.codcaixaitem]) || 0), 0),
)
const total = computed(() => Math.round((dinheiro.value + estoque.value) * 100) / 100)

defineExpose({ total })
</script>

<template>
  <div>
    <div class="text-caption text-grey-7 q-mb-sm">Cédulas (quantidade)</div>
    <div class="row q-col-gutter-md q-mb-md">
      <div v-for="(v, i) in CEDULAS" :key="v" class="col-4 col-sm-3">
        <MgInput
          :model-value="qtd(v)"
          @update:model-value="(q) => setQtd(v, q)"
          :label="rotulo(v)"
          type="number"
          min="0"
          :autofocus="autofocus && i === 0"
        />
      </div>
    </div>
    <div class="text-caption text-grey-7 q-mb-sm">Moedas (quantidade)</div>
    <div class="row q-col-gutter-md q-mb-md">
      <div v-for="v in MOEDAS" :key="v" class="col-4 col-sm-3">
        <MgInput
          :model-value="qtd(v)"
          @update:model-value="(q) => setQtd(v, q)"
          :label="rotulo(v)"
          type="number"
          min="0"
        />
      </div>
    </div>
    <template v-if="itens.length">
      <div class="text-caption text-grey-7 q-mb-sm">Itens (valor de face)</div>
      <div class="row q-col-gutter-md q-mb-md">
        <div v-for="i in itens" :key="i.codcaixaitem" class="col-6 col-sm-4">
          <MgInputValor v-model="model.itens[i.codcaixaitem]" :label="i.item" />
        </div>
      </div>
    </template>
    <div class="row items-center">
      <div class="col text-caption text-grey-7">
        Dinheiro R$ {{ formataNumero(dinheiro) }}
        <template v-if="itens.length"> · itens R$ {{ formataNumero(estoque) }}</template>
      </div>
      <div class="text-h6">R$ {{ formataNumero(total) }}</div>
    </div>
  </div>
</template>
