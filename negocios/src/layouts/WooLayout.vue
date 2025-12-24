<script setup>
import MainLayout from "layouts/MainLayout.vue";
import WooLeftDrawer from "components/drawers/WooLeftDrawer.vue";
import UsuarioConectado from "components/UsuarioConectado.vue";
</script>
<template>
  <main-layout title="Woo" left-drawer back-to="/woo/painel">
    <!--Usuário-->
    <template #usuario>
      <usuario-conectado />
    </template>

    <!--barra lateral-->
    <template #left-drawer>
      <woo-left-drawer />
    </template>

    <!--WooPage-->
    <router-view />
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
