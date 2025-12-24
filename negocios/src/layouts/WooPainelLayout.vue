<script setup>
import MainLayout from "layouts/MainLayout.vue";
import UsuarioConectado from "components/UsuarioConectado.vue";
</script>
<template>
  <main-layout title="Woo">
    <!--Usuário-->
    <template #title>
      <usuario-conectado />
    </template>
    <!--Usuário-->
    <template #usuario>
      <usuario-conectado />
    </template>

    <!--WooPage-->
    <router-view />
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
