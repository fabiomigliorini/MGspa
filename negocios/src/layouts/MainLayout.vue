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
        &nbsp;
        <q-btn to="/config/padrao" round icon="settings" flat />
        &nbsp;

        <q-btn
          dense
          flat
          round
          icon="attach_money"
          @click="toggleRightDrawer"
        />
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
