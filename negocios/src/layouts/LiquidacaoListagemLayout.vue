<script setup>
import MainLayout from "layouts/MainLayout.vue";
import LiquidacaoListagemLeftDrawer from "components/drawers/LiquidacaoListagemLeftDrawer.vue";
import UsuarioConectado from "components/UsuarioConectado.vue";
</script>
<template>
  <main-layout title="Listagem de Liquidações" left-drawer>
    <template #usuario>
      <!-- USUARIO  -->
      <usuario-conectado />
    </template>
    <template #left-drawer>
      <liquidacao-listagem-left-drawer />
    </template>
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
