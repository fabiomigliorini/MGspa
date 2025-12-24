<script setup>
import TotalNegocio from "components/offline/TotalNegocio.vue";
import DetalheNegocio from "components/offline/DetalheNegocio.vue";
import MercosPedido from "components/offline/MercosPedido.vue";
import WooPedido from "components/offline/WooPedido.vue";
</script>
<template>
  <!-- TOTAIS -->
  <total-negocio />
  <woo-pedido />
  <mercos-pedido />
  <q-separator spaced />
  <detalhe-negocio />
</template>
