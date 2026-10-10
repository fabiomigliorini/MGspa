<script setup>
import MainLayout from 'layouts/MainLayout.vue'
import UsuarioConectado from 'components/UsuarioConectado.vue'
import DispositivoLeftDrawer from 'components/drawers/DispositivoLeftDrawer.vue'
</script>
<template>
  <main-layout title="Dispositivos" left-drawer>
    <template #usuario>
      <usuario-conectado />
    </template>
    <template #left-drawer>
      <dispositivo-left-drawer />
    </template>
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
