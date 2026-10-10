<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import MainLayout from 'layouts/MainLayout.vue'
import UsuarioConectado from 'components/UsuarioConectado.vue'
import { sincronizacaoStore } from 'stores/sincronizacao'

const route = useRoute()
const sSinc = sincronizacaoStore()

// o proprio dispositivo (Meu Dispositivo) volta para o PDV; os outros, para a lista
const voltar = computed(() =>
  !route.params.codpdv || Number(route.params.codpdv) === sSinc.pdv.codpdv ? '/' : '/dispositivo',
)
</script>
<template>
  <main-layout title="Dispositivo" :back-to="voltar">
    <template #usuario>
      <usuario-conectado />
    </template>
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
