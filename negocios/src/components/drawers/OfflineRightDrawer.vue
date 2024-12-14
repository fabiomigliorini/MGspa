<script setup>
import TotalNegocio from "components/offline/TotalNegocio.vue";
import DetalheNegocio from "components/offline/DetalheNegocio.vue";
import MercosPedido from "components/offline/MercosPedido.vue";
</script>
<template>
  <!-- TOTAIS -->
  <total-negocio />
  <mercos-pedido />
  <q-separator spaced />
  <detalhe-negocio />
</template>
