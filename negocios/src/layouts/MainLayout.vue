<script setup>
import { ref } from "vue";
import LeftDrawer from "components/LeftDrawer.vue";
import RightDrawer from "components/RightDrawer.vue";
import UsuarioConectado from "components/UsuarioConectado.vue";
import { version } from "../../package.json";

const leftDrawerOpen = ref(false);
const rightDrawerOpen = ref(false);

const toggleLeftDrawer = () => {
  leftDrawerOpen.value = !leftDrawerOpen.value;
};
const toggleRightDrawer = () => {
  rightDrawerOpen.value = !rightDrawerOpen.value;
};
</script>

<template>
  <q-layout view="hHh lpr fFf">
    <q-header reveal elevated height-hint="98">
      <q-toolbar>
        <q-btn dense flat round icon="menu" @click="toggleLeftDrawer" />

        <q-toolbar-title>
          <q-avatar>
            <img src="MGPapelariaQuadrado.svg" />
          </q-avatar>
          Negócios
        </q-toolbar-title>
        <div class="gt-xs q-mr-sm">v{{ version }}</div>

        <!-- TODO: criar componente para usar nos dois layouts -->
        <usuario-conectado />
        <q-btn to="/config/padrao" round icon="settings" flat>
          <q-tooltip class="bg-accent"> Configurações </q-tooltip>
        </q-btn>

        <q-btn to="/conferencia" round icon="check" flat>
          <q-tooltip class="bg-accent"> Conferência </q-tooltip>
        </q-btn>

        <q-btn dense flat round icon="attach_money" @click="toggleRightDrawer">
          <q-tooltip class="bg-accent"> Totais </q-tooltip>
        </q-btn>
      </q-toolbar>
    </q-header>

    <q-drawer v-model="leftDrawerOpen" show-if-above bordered>
      <LeftDrawer />
    </q-drawer>

    <q-drawer v-model="rightDrawerOpen" show-if-above bordered side="right">
      <RightDrawer />
    </q-drawer>

    <q-page-container>
      <router-view :key="$route.fullPath" />
    </q-page-container>
  </q-layout>
</template>
