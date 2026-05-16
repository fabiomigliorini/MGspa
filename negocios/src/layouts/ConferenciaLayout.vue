<script setup>
import MainLayout from 'layouts/MainLayout.vue'
import ConferenciaLeftDrawer from 'components/drawers/ConferenciaLeftDrawer.vue'
import UsuarioConectado from 'components/UsuarioConectado.vue'
</script>
<template>
  <main-layout title="Conferência" left-drawer>
    <template #usuario>
      <!-- USUARIO  -->
      <usuario-conectado />
    </template>
    <template #left-drawer>
      <conferencia-left-drawer />
    </template>
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
