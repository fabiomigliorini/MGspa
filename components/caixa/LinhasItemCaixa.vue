<script setup>
// As linhas de um item do caixa na contagem (doc-4, "Itens do caixa"): o item conta como cédula,
// um campo de quantidade por preço (com a descrição no rótulo) que já passou pelo caixa. Preço
// novo só entra pela entrada do item (ItemCaixaDialog).
// v-model = [{ preco, descricao, quantidade }]
import MgInputValor from '@components/MgInputValor.vue'
import { formataNumero } from '@components/formatters'

const linhas = defineModel({ type: Array, required: true })
defineProps({
  autofocus: { type: Boolean, default: false },
  disable: { type: Boolean, default: false },
})

const rotulo = (l) => `R$ ${formataNumero(l.preco)}` + (l.descricao ? ` ${l.descricao}` : '')
const valor = (l) =>
  Number(l.quantidade) > 0 && Number(l.preco) > 0
    ? formataNumero(Number(l.quantidade) * Number(l.preco))
    : undefined
</script>

<template>
  <div class="row q-col-gutter-sm">
    <div v-for="(l, i) in linhas" :key="i" class="col-4">
      <MgInputValor
        v-model="l.quantidade"
        :label="rotulo(l)"
        :decimals="0"
        :min="0"
        :disable="disable"
        :autofocus="autofocus && i === 0"
        bottom-slots
      >
        <template #hint>
          <div class="text-right">{{ valor(l) }}</div>
        </template>
      </MgInputValor>
    </div>
  </div>
</template>
