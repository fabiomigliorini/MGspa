<script setup>
import { computed } from 'vue'
import { formataReal, formataNumero } from '@components/formatters'

// Recibo do cálculo fiscal (bruto → impostos → líquido), SEMPRE em R$ — usado
// onde o bruto em R$ já é conhecido: card de fixação (parte travada) e diálogo
// de travar câmbio (a fatia). A conta mora aqui (fonte única):
//   UNIDADE (FETHAB/IAGRO) = %/100 × UPF × pesosaca/1000 × sacas
//   VALOR   (SENAR/FUNRURAL) = %/100 × bruto
const props = defineProps({
  bruto: { type: Number, default: 0 }, // R$ bruto (travado, ou preço×qtd em BRL)
  sacas: { type: Number, default: 0 },
  tributos: { type: Array, default: () => [] },
  pesosaca: { type: Number, default: 60 },
  // Líquido GRAVADO pelo servidor (card da fixação). Sem ele (prévia nos
  // diálogos) vale a conta local, com o mesmo arredondamento do servidor.
  liquido: { type: Number, default: null },
})

const rs = formataReal
function n(v) {
  return Number(v) || 0
}
function arred(v) {
  return Math.round(n(v) * 100) / 100
}
function arred4(v) {
  return Math.round(n(v) * 10000) / 10000
}

// Como o servidor (ContratoCalculoService): cada tributo por SACA em 4 casas,
// líquido por saca em 4 casas, × sacas, 2 casas.
const brutoSc = computed(() => (n(props.sacas) > 0 ? n(props.bruto) / n(props.sacas) : 0))

const linhas = computed(() =>
  (props.tributos || []).map((t) => {
    const unidade = t.base === 'UNIDADE'
    const porSc = arred4(
      unidade
        ? (n(t.percentual) / 100) * n(t.upf) * (n(props.pesosaca) / 1000)
        : (n(t.percentual) / 100) * brutoSc.value,
    )
    const detalhe = unidade
      ? `${formataNumero(props.sacas, 0)} sc`
      : `${formataNumero(t.percentual, 2)}%`
    return { codigo: t.codigo, detalhe, porSc, valor: arred(porSc * n(props.sacas)) }
  }),
)
const liquidoLocal = computed(() =>
  arred(arred4(brutoSc.value - linhas.value.reduce((s, l) => s + l.porSc, 0)) * n(props.sacas)),
)
const liquidoFinal = computed(() => props.liquido ?? liquidoLocal.value)
// Líquido por saca (o que sobra por sc, R$) — leitura direta pro produtor.
const liquidoPorSc = computed(() =>
  n(props.sacas) > 0 ? liquidoFinal.value / n(props.sacas) : 0,
)
</script>

<template>
  <!-- Moeda estrangeira ainda não travada: sem bruto em R$, nada a calcular. -->
  <div v-if="bruto <= 0" class="text-caption text-grey-6" style="font-style: italic">
    Câmbio não travado — o líquido em R$ é definido ao travar.
  </div>
  <div v-else>
    <div class="row items-baseline justify-between text-caption">
      <span class="text-grey-7">Bruto</span>
      <span class="text-grey-9 text-weight-medium">{{ rs(bruto) }}</span>
    </div>
    <div
      v-for="l in linhas"
      :key="l.codigo"
      class="row items-baseline justify-between text-caption"
    >
      <span class="text-grey-6"
        >{{ l.codigo }} <span class="text-grey-5">· {{ l.detalhe }}</span></span
      >
      <span class="text-red-6">− {{ rs(l.valor) }}</span>
    </div>
    <q-separator class="q-my-xs" />
    <div class="row items-baseline justify-between">
      <span class="text-grey-8 text-weight-medium">
        Líquido
        <span class="text-caption text-grey-5">{{ rs(liquidoPorSc) }}/sc</span>
      </span>
      <span class="text-subtitle1 text-weight-bold text-green-8">{{ rs(liquidoFinal) }}</span>
    </div>
  </div>
</template>
