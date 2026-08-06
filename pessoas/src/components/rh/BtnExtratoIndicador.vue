<script setup>
// Botão que abre o extrato de lançamentos de um indicador.
// O @click.stop mora aqui: em tabela de linha clicável, esquecer dele no chamador
// fazia o clique navegar para a tela errada.
defineProps({
  codperiodo: { type: [Number, String], required: true },
  codindicador: { type: [Number, String], required: true },
  // Meu Painel usa a rota espelho, read-only.
  nomeRota: { type: String, default: 'rhIndicadorExtrato' },
  size: { type: String, default: 'sm' },
  color: { type: String, default: 'grey-7' },
})
</script>

<template>
  <q-btn
    flat
    round
    dense
    icon="receipt_long"
    :size="size"
    :color="color"
    @click.stop
    :to="{ name: nomeRota, params: { codperiodo, codindicador } }"
  >
    <q-tooltip>Ver Extrato</q-tooltip>
  </q-btn>
</template>
