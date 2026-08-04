<script setup>
// Banner de nova versão fica aqui (e não num layout) pra cobrir toda rota do
// app, inclusive telas sem menu de usuário. Ver components/pwaAtualizacao.js.
import MgBannerNovaVersao from '@components/MgBannerNovaVersao.vue'
</script>

<template>
  <MgBannerNovaVersao />
  <router-view />
</template>
