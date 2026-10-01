<script setup>
import MainLayout from 'layouts/MainLayout.vue'
import UsuarioConectado from 'components/UsuarioConectado.vue'
import PagamentoLeftDrawer from 'components/drawers/PagamentoLeftDrawer.vue'
import { pagamentoListaStore } from '@components/stores/pagamentoListaStore'
import { sincronizacaoStore } from 'stores/sincronizacao'

// a listagem única de pagamentos, travada no PDV (o servidor força o PDV do dispositivo)
pagamentoListaStore().configurar({
  endpoint: 'v1/pdv/pagamento',
  fixos: { pdv: sincronizacaoStore().pdv.uuid },
  travadoPdv: true,
})
</script>
<template>
  <main-layout title="Pagamentos" back-to="/" left-drawer>
    <template #usuario>
      <usuario-conectado />
    </template>
    <template #left-drawer>
      <pagamento-left-drawer />
    </template>
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
