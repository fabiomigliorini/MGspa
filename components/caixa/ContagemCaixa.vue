<script setup>
// Contagem do caixa (M13 doc-3): quantidade de cada cédula e moeda, em duas colunas com o valor de
// cada campo no hint e o total da coluna, e os itens (chips, ingressos), que contam como cédula
// (doc-4, "Itens do caixa"): um bloco por item que está no caixa, só um campo de quantidade por
// preço (como cédula); preço novo só entra pela entrada do item.
// v-model = { contagem: { '200': 3 }, itens: { codcaixaitem: [{ preco, descricao, quantidade }] } };
// o total geral soma tudo, que é o que fica no caixa.
// `copia` = { titulo, contagem, itens } no mesmo formato: cada bloco (cédulas, moedas, cada item)
// tem um botão que troca o bloco pelo que está nela (quem chama decide de onde vem).
import { computed } from 'vue'
import MgInputValor from '@components/MgInputValor.vue'
import LinhasItemCaixa from '@components/caixa/LinhasItemCaixa.vue'
import { formataNumero } from '@components/formatters'
import { CEDULAS, MOEDAS, totalLinhas } from '@components/stores/periodoStore'

const model = defineModel({ type: Object, required: true })
const props = defineProps({
  // os itens do caixa, para o nome: [{ codcaixaitem, item }]; vazio = sem bloco de itens (PDV)
  itens: { type: Array, default: () => [] },
  autofocus: { type: Boolean, default: false },
  // só consulta (caixa fechado ou sem permissão)
  disable: { type: Boolean, default: false },
  // de onde copiar, bloco a bloco: { titulo, contagem, itens }; null = sem botão
  copia: { type: Object, default: null },
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
// os itens na contagem (os blocos)
const blocos = computed(() => model.value.itens || {})
const nome = (cod) =>
  props.itens.find((i) => String(i.codcaixaitem) === String(cod))?.item ?? 'Item'
const estoque = computed(() =>
  Object.values(blocos.value).reduce((s, linhas) => s + totalLinhas(linhas), 0),
)

// copia o bloco: o que não está na cópia fica vazio
const copiarFaces = (faces) => {
  const c = props.copia?.contagem || {}
  model.value.contagem = {
    ...model.value.contagem,
    ...Object.fromEntries(faces.map((v) => [v, Number(c[v]) > 0 ? Number(c[v]) : null])),
  }
}
const copiarItem = (cod) => {
  const c = props.copia?.itens?.[cod] || []
  model.value.itens[cod] = model.value.itens[cod].map((l) => {
    const q = c.find(
      (x) => Number(x.preco) === Number(l.preco) && (x.descricao || null) === (l.descricao || null),
    )?.quantidade
    return { ...l, quantidade: Number(q) > 0 ? Number(q) : null }
  })
}

const total = computed(() => Math.round((soma(CEDULAS) + soma(MOEDAS) + estoque.value) * 100) / 100)

defineExpose({ total })
</script>

<template>
  <div>
    <div class="row q-col-gutter-lg">
      <div v-for="(c, ci) in colunas" :key="c.titulo" class="col-12 col-sm-6 column">
        <div class="row items-center q-mb-sm">
          <div class="col text-caption text-grey-7">{{ c.titulo }} (quantidade)</div>
          <q-btn
            v-if="copia && !disable"
            flat
            round
            size="sm"
            color="grey-7"
            icon="content_copy"
            tabindex="-1"
            @click="copiarFaces(c.faces)"
          >
            <q-tooltip>{{ copia.titulo }}</q-tooltip>
          </q-btn>
        </div>
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
      <div v-for="cod in Object.keys(blocos)" :key="cod" class="q-mb-sm">
        <div class="row items-center q-mb-sm">
          <div class="col text-caption text-grey-7">{{ nome(cod) }} (quantidade)</div>
          <q-btn
            v-if="copia && !disable"
            flat
            round
            size="sm"
            color="grey-7"
            icon="content_copy"
            tabindex="-1"
            @click="copiarItem(cod)"
          >
            <q-tooltip>{{ copia.titulo }}</q-tooltip>
          </q-btn>
        </div>
        <LinhasItemCaixa v-model="model.itens[cod]" :disable="disable" />
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
