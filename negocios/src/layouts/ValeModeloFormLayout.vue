<script setup>
import MainLayout from 'layouts/MainLayout.vue'
import UsuarioConectado from 'components/UsuarioConectado.vue'
</script>
<template>
  <main-layout title="Modelo de Vale" back-to="/vale-modelo">
    <template #usuario>
      <usuario-conectado />
    </template>
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
