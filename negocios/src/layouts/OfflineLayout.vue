<script setup>
import MainLayout from "layouts/MainLayout.vue";
import OfflineLeftDrawer from "components/drawers/OfflineLeftDrawer.vue";
import OfflineRightDrawer from "components/drawers/OfflineRightDrawer.vue";
import UsuarioConectado from "components/UsuarioConectado.vue";
import BtnSincronizacao from "components/offline/BtnSincronizacao.vue";
</script>
<template>
  <main-layout title="PDV" left-drawer right-drawer>
    <template #botoes>
      <btn-sincronizacao />
    </template>
    <template #usuario>
      <!-- USUARIO  -->
      <usuario-conectado />
    </template>
    <template #left-drawer>
      <offline-left-drawer />
    </template>
    <template #right-drawer>
      <offline-right-drawer />
    </template>
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
