<script setup>
import MainLayout from "layouts/MainLayout.vue";
import UsuarioConectado from "components/UsuarioConectado.vue";
</script>
<template>
  <main-layout title="Comanda de Vendedor" back-to="/">
    <template #usuario>
      <!-- USUARIO  -->
      <usuario-conectado />
    </template>
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
