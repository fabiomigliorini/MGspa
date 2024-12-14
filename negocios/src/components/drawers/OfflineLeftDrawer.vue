<script setup>
import { ref } from "vue";
import OfflineLeftDrawerTabNegocios from "./OfflineLeftDrawerTabNegocios.vue";
import OfflineLeftDrawerTabPrancheta from "./OfflineLeftDrawerTabPrancheta.vue";
import OfflineLeftDrawerTabMercos from "./OfflineLeftDrawerTabMercos.vue";
const tab = ref("negocios");
</script>
<template>
  <q-tabs v-model="tab" dense active-color="primary" class="text-grey-8" align="left" inline-label>
    <q-tab name="negocios" icon="mdi-format-list-checkbox">
      <q-tooltip class="bg-accent">
        Listagem de Negócios
      </q-tooltip>
    </q-tab>
    <q-tab name="prancheta" icon="mdi-clipboard-text-outline">
      <q-tooltip class="bg-accent">
        Prancheta
      </q-tooltip>
    </q-tab>
    <q-tab name="mercos" icon="mdi-cloud-outline">
      <q-tooltip class="bg-accent">
        Vendas Mercos
      </q-tooltip>
    </q-tab>
  </q-tabs>

  <q-separator />
  <q-tab-panels v-model="tab" animated>
    <q-tab-panel name="negocios" class="q-pa-none">
      <offline-left-drawer-tab-negocios />
    </q-tab-panel>

    <q-tab-panel name="prancheta" class="q-pa-none">
      <offline-left-drawer-tab-prancheta />
    </q-tab-panel>

    <q-tab-panel name="mercos" class="q-pa-none">
      <offline-left-drawer-tab-mercos />
    </q-tab-panel>
  </q-tab-panels>
</template>
