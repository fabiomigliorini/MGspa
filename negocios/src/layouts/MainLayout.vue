<script setup>
import { ref } from "vue";
import LeftDrawer from "components/LeftDrawer.vue";
import RightDrawer from "components/RightDrawer.vue";
import ComputerSettings from "components/ComputerSettings.vue";
import UsuarioConectado from "components/UsuarioConectado.vue";

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
        <div class="gt-xs q-mr-sm">v0.5</div>

        <usuario-conectado />
        &nbsp;
        <computer-settings />
        &nbsp;

        <q-btn dense flat round icon="menu" @click="toggleRightDrawer" />
      </q-toolbar>
      <!-- <q-tabs align="left">
        <q-route-tab to="/page1" label="Page One" />
        <q-route-tab to="/page2" label="Page Two" />
        <q-route-tab to="/page3" label="Page Three" />
      </q-tabs> -->
    </q-header>

    <q-drawer v-model="leftDrawerOpen" show-if-above bordered>
      <LeftDrawer></LeftDrawer>
    </q-drawer>

    <q-drawer v-model="rightDrawerOpen" show-if-above bordered side="right">
      <RightDrawer></RightDrawer>
    </q-drawer>

    <q-page-container>
      <router-view :key="$route.fullPath" />
    </q-page-container>

    <!-- <q-footer reveal elevated class="bg-grey-8 text-white">
      <q-toolbar>
        <q-toolbar-title>
          <div></div>
        </q-toolbar-title>
      </q-toolbar>
    </q-footer> -->
  </q-layout>
</template>
