<script setup>
import MainLayout from 'layouts/MainLayout.vue'
import UsuarioConectado from 'components/UsuarioConectado.vue'
import { useRoute } from 'vue-router'
import ValeModeloLeftDrawer from 'components/drawers/ValeModeloLeftDrawer.vue'
import ValeEmitidosLeftDrawer from 'components/drawers/ValeEmitidosLeftDrawer.vue'

const route = useRoute()
</script>
<template>
  <main-layout
    :title="route.path === '/vale-modelo' ? 'Modelos de Vale' : 'Vales Emitidos'"
    :back-to="route.path === '/vale-modelo' ? '/' : '/vale-modelo'"
    left-drawer
  >
    <template #usuario>
      <usuario-conectado />
    </template>
    <template #left-drawer>
      <vale-modelo-left-drawer v-if="route.path === '/vale-modelo'" />
      <vale-emitidos-left-drawer v-else-if="route.path === '/vale-modelo/emitidos'" />
    </template>
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
