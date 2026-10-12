<script setup>
// Contagem do caixa (M13 doc-3; doc-4, "Itens do caixa"), um bloco por vez: as moedas, as cédulas
// ou um item do caixa (chips, ingressos), que conta como cédula. Uma linha por face (no item, por
// preço, com a descrição): a quantidade, a face e o total dela; mudar um recalcula o outro. Preço
// novo de item só entra pela entrada do item. Na saída do item (ItemCaixaDialog), a linha traz o
// `disponivel`: teto da quantidade.
// v-model = { contagem: { '200': 3 }, itens: { codcaixaitem: [{ preco, descricao, quantidade }] } },
// a contagem inteira: só o bloco muda, quem chama salva tudo.
// `copia` = { titulo, contagem, itens } no mesmo formato: copiarBloco() troca o bloco pelo dela.
import { computed } from 'vue'
import MgInputValor from '@components/MgInputValor.vue'
import { formataNumero } from '@components/formatters'
import { CEDULAS, MOEDAS } from '@components/stores/periodoStore'

const model = defineModel({ type: Object, required: true })
const props = defineProps({
  // 'cedulas', 'moedas' ou o codcaixaitem de um item
  bloco: { type: [String, Number], required: true },
  // o nome do bloco, no total: Moedas, Cédulas, Chips de celular
  nome: { type: String, required: true },
  autofocus: { type: Boolean, default: false },
  // só consulta (caixa fechado ou sem permissão)
  disable: { type: Boolean, default: false },
  // de onde copiar: { titulo, contagem, itens }; null = sem cópia
  copia: { type: Object, default: null },
})

const FACES = { cedulas: CEDULAS, moedas: MOEDAS }
const faces = computed(() => FACES[props.bloco] ?? null)
const coluna = computed(() =>
  props.bloco === 'moedas' ? 'Moeda' : props.bloco === 'cedulas' ? 'Cédula' : 'Preço',
)
const centavos = (v) => Math.round(v * 100) / 100

// as linhas do bloco: a face (ou o preço e a descrição do item), a quantidade e como trocá-la
const linhas = computed(() =>
  faces.value
    ? faces.value.map((v) => ({
        chave: v,
        preco: Number(v),
        descricao: null,
        quantidade: model.value.contagem?.[v] ?? null,
        set: (q) => {
          model.value.contagem = { ...model.value.contagem, [v]: q == null ? null : Number(q) }
        },
      }))
    : (model.value.itens?.[props.bloco] ?? []).map((l, i) => ({
        chave: i,
        preco: Number(l.preco),
        descricao: l.descricao,
        quantidade: l.quantidade ?? null,
        max: l.disponivel ?? null,
        set: (q) => {
          l.quantidade = q == null ? null : Number(q)
        },
      })),
)

// o total da linha (quantidade × face), editável; mudar o total recalcula a quantidade
const totalLinha = (l) => (l.quantidade == null ? null : centavos(l.quantidade * l.preco))
const setTotal = (l, t) => l.set(t == null ? null : Math.round(t / l.preco))
const total = computed(() =>
  centavos(linhas.value.reduce((s, l) => s + (Number(l.quantidade) || 0) * l.preco, 0)),
)

// troca o bloco pelo da cópia: o que não está nela fica vazio
function copiarBloco() {
  if (faces.value) {
    const c = props.copia?.contagem || {}
    model.value.contagem = {
      ...model.value.contagem,
      ...Object.fromEntries(faces.value.map((v) => [v, Number(c[v]) > 0 ? Number(c[v]) : null])),
    }
    return
  }
  const c = props.copia?.itens?.[props.bloco] || []
  model.value.itens[props.bloco] = model.value.itens[props.bloco].map((l) => {
    const q = c.find(
      (x) => Number(x.preco) === Number(l.preco) && (x.descricao || null) === (l.descricao || null),
    )?.quantidade
    return { ...l, quantidade: Number(q) > 0 ? Number(q) : null }
  })
}

defineExpose({ copiarBloco })
</script>

<template>
  <div>
    <div class="row q-col-gutter-sm text-caption text-grey-7 q-mb-xs">
      <div class="col-3">Quantidade</div>
      <div class="col-5 text-center">{{ coluna }}</div>
      <div class="col-4 text-right">Total</div>
    </div>
    <div v-for="(l, i) in linhas" :key="l.chave" class="row q-col-gutter-sm items-center q-mb-sm">
      <div class="col-3">
        <MgInputValor
          :model-value="l.quantidade"
          @update:model-value="(q) => l.set(q)"
          :disable="disable"
          :decimals="0"
          :min="0"
          :max="l.max"
          :autofocus="autofocus && i === 0"
          dense
          align="center"
        />
      </div>
      <div class="col-5 text-center ellipsis">
        <span v-if="l.descricao" class="text-grey-7 q-mr-sm">{{ l.descricao }}</span>
        <span class="text-subtitle1">{{ formataNumero(l.preco) }}</span>
      </div>
      <div class="col-4">
        <MgInputValor
          :model-value="totalLinha(l)"
          @update:model-value="(t) => setTotal(l, t)"
          :disable="disable || !(l.preco > 0)"
          :min="0"
          :max="l.max == null ? null : centavos(l.max * l.preco)"
          :step="l.preco"
          dense
        />
      </div>
    </div>
    <div class="row items-center q-mt-sm">
      <div class="col text-caption text-grey-9 text-weight-bold">
        Total {{ faces ? nome.toLowerCase() : nome }}
      </div>
      <div class="text-subtitle1 text-weight-bold">R$ {{ formataNumero(total) }}</div>
    </div>
  </div>
</template>
