<script setup>
import { computed } from "vue";
import MainLayout from "layouts/MainLayout.vue";
import OfflineLeftDrawer from "components/drawers/OfflineLeftDrawer.vue";
import OfflineRightDrawer from "components/drawers/OfflineRightDrawer.vue";
import UsuarioConectado from "components/UsuarioConectado.vue";
import DialogSincronizacao from "components/offline/DialogSincronizacao.vue";
import { sincronizacaoStore } from "stores/sincronizacao";
import moment from "moment";
moment.locale("pt-br");
const sSinc = sincronizacaoStore();
const btnSincronizarColor = computed({
  get() {
    if (!sSinc.ultimaSincronizacao.completa) {
      return "red-4";
    }
    if (
      moment(sSinc.ultimaSincronizacao.completa).isAfter(
        moment().subtract(4, "hours")
      )
    ) {
      return null;
    }
    return "red-4";
  },
});
</script>
<template>
  <main-layout title="PDV" left-drawer right-drawer>
    <template #botoes>
      <dialog-sincronizacao />
      <q-btn
        round
        dense
        flat
        icon="refresh"
        :loading="sSinc.importacao.rodando"
        :percentage="sSinc.importacao.progresso"
        @click="sSinc.sincronizar()"
        class="q-mr-sm"
        :color="btnSincronizarColor"
      >
        <template v-slot:loading>
          <q-spinner-dots />
        </template>
        <q-tooltip class="bg-accent">
          <template v-if="!sSinc.ultimaSincronizacao.completa">
            Sem Registro de Sincronização
          </template>
          <template v-else>
            Ultima Sincronização
            {{ moment(sSinc.ultimaSincronizacao.completa).fromNow() }}
          </template>
        </q-tooltip>
      </q-btn>
    </template>
    <template #usuario>
      <!-- USUARIO  -->
      <usuario-conectado />
    </template>
    <template #left-drawer>
      <offline-left-drawer />
    </template>
    <template #right-drawer>
      <offline-right-drawer />
    </template>
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
