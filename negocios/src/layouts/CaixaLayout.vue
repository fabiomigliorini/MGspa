<script setup>
import MainLayout from 'layouts/MainLayout.vue'
import UsuarioConectado from 'components/UsuarioConectado.vue'
</script>
<template>
  <main-layout title="Caixa" back-to="/">
    <template #usuario>
      <usuario-conectado />
    </template>
    <template #content>
      <router-view />
    </template>
  </main-layout>
</template>
