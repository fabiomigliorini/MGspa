<script setup>
import { ref } from "vue";
import OfflineLeftDrawerTabNegocios from "./OfflineLeftDrawerTabNegocios.vue";
import OfflineLeftDrawerTabPrancheta from "./OfflineLeftDrawerTabPrancheta.vue";
const tab = ref("negocios");
</script>
<template>
  <q-tabs v-model="tab" dense active-color="primary">
    <q-tab name="negocios" label="Negocios" />
    <q-tab name="prancheta" label="Prancheta" />
  </q-tabs>

  <q-separator />
  <q-tab-panels v-model="tab" animated>
    <q-tab-panel name="negocios" class="q-pa-none">
      <offline-left-drawer-tab-negocios />
    </q-tab-panel>

    <q-tab-panel name="prancheta" class="q-pa-none">
      <offline-left-drawer-tab-prancheta />
    </q-tab-panel>
  </q-tab-panels>
</template>
