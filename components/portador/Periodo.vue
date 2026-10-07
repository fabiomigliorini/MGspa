<script setup>
// O período do portador (doc-4), igual no contas (/portador/{cod}/{codperiodo}) e no caixa do PDV
// (negocios /caixa): cabeçalho e resumo à esquerda, lançamentos à direita (uma coluna no celular)
// e os diálogos de movimento, todos do periodoStore. O que cada tela mostra quem decide é o store
// (no PDV, o contexto com o codpdv). O slot entra embaixo dos lançamentos.
import PeriodoCabecalho from '@components/portador/PeriodoCabecalho.vue'
import PeriodoLancamentos from '@components/portador/PeriodoLancamentos.vue'
import TransferirCaixaDialog from '@components/caixa/TransferirCaixaDialog.vue'
import AvulsoCaixaDialog from '@components/caixa/AvulsoCaixaDialog.vue'
import ItemCaixaDialog from '@components/caixa/ItemCaixaDialog.vue'
import MaquinetaCaixaDialog from '@components/caixa/MaquinetaCaixaDialog.vue'
</script>

<template>
  <div class="row q-col-gutter-md q-mb-md">
    <div class="col-12 col-md-5">
      <PeriodoCabecalho />
    </div>
    <div class="col-12 col-md-7">
      <PeriodoLancamentos />
      <slot />
    </div>
  </div>

  <TransferirCaixaDialog />
  <AvulsoCaixaDialog />
  <ItemCaixaDialog />
  <MaquinetaCaixaDialog />
</template>
