<script setup>
import { ref } from "vue";
import LeftDrawer from "components/LeftDrawer.vue";
import RightDrawer from "components/RightDrawer.vue";
import ComputerSettings from "components/ComputerSettings.vue";

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
  <q-layout view="hHh lpR fFf">
    <q-header reveal elevated height-hint="98">
      <q-toolbar>
        <q-btn dense flat round icon="menu" @click="toggleLeftDrawer" />

        <q-toolbar-title>
          <q-avatar>
            <img src="MGPapelariaQuadrado.svg" />
          </q-avatar>
          Negócios
        </q-toolbar-title>
        <div class="gt-xs">Quasar v{{ $q.version }}</div>

        <!-- <q-btn dense flat round icon="settings" @click="toggleSettings" /> -->
        <!--
        <q-btn-dropdown dense flat icon="settings">
          <computer-settings></computer-settings>
          <div class="row no-wrap q-pa-md justify-center">
            <div class="column items-center">
              <q-avatar size="72px">
                <img src="https://cdn.quasar.dev/img/boy-avatar.png" />
              </q-avatar>
              <div class="text-subtitle1 q-mt-md q-mb-xs">{{ user }}</div>

              <q-btn
                color="primary"
                label="Sair"
                push
                size="sm"
                v-close-popup
                @click="Deslogar"
              />
            </div>
          </div>
        </q-btn-dropdown>
-->
        <ComputerSettings></ComputerSettings>

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
