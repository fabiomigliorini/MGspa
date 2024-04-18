<script setup>
import MainLayout from "layouts/MainLayout.vue";
import ListagemLeftDrawer from "components/drawers/ListagemLeftDrawer.vue";
import UsuarioConectado from "components/UsuarioConectado.vue";
</script>
<template>
  <main-layout title="Devolução" left-drawer>
    <template #usuario>
      <!-- USUARIO  -->
      <usuario-conectado />
    </template>
    <template #left-drawer>
      <listagem-left-drawer />
    </template>
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
