<script setup>
import MainLayout from 'layouts/MainLayout.vue'
import UsuarioConectado from 'components/UsuarioConectado.vue'
import ValeEmitidosLeftDrawer from 'components/drawers/ValeEmitidosLeftDrawer.vue'
</script>
<template>
  <main-layout title="Vales Emitidos" back-to="/vale-modelo" left-drawer>
    <template #usuario>
      <usuario-conectado />
    </template>
    <template #left-drawer>
      <vale-emitidos-left-drawer />
    </template>
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
