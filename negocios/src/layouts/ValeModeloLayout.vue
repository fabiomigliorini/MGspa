<script setup>
import MainLayout from 'layouts/MainLayout.vue'
import UsuarioConectado from 'components/UsuarioConectado.vue'
import ValeModeloLeftDrawer from 'components/drawers/ValeModeloLeftDrawer.vue'
</script>
<template>
  <main-layout title="Modelos de Vale" back-to="/" left-drawer>
    <template #usuario>
      <usuario-conectado />
    </template>
    <template #left-drawer>
      <vale-modelo-left-drawer />
    </template>
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
