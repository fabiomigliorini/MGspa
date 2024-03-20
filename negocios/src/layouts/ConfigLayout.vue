<script setup>
import { ref } from "vue";
import ConfigLeftDrawer from "components/ConfigLeftDrawer.vue";
import UsuarioConectado from "components/UsuarioConectado.vue";
import { version } from "../../package.json";

const leftDrawerOpen = ref(false);

const toggleLeftDrawer = () => {
  leftDrawerOpen.value = !leftDrawerOpen.value;
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
          Configurações
        </q-toolbar-title>
        <div class="gt-xs q-mr-sm">v{{ version }}</div>

        <usuario-conectado />
        &nbsp;
        <q-btn to="/" round icon="point_of_sale" flat />
        &nbsp;

        <!-- <q-btn dense flat round icon="menu" @click="toggleRightDrawer" /> -->
      </q-toolbar>
    </q-header>

    <q-drawer v-model="leftDrawerOpen" show-if-above bordered>
      <config-left-drawer />
    </q-drawer>

    <q-page-container>
      <router-view :key="$route.fullPath" />
    </q-page-container>
  </q-layout>
</template>
