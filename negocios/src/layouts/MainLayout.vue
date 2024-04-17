<script setup>
import { ref } from "vue";
import UsuarioConectado from "components/UsuarioConectado.vue";
import { version } from "../../package.json";

const props = defineProps({
  backTo: {
    type: String,
    default: null,
  },
  leftDrawer: {
    type: Boolean,
    default: true,
  },
  rightDrawer: {
    type: Boolean,
    default: true,
  },
  title: {
    type: String,
    default: "Negócios",
  },
});

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
        <q-btn
          dense
          flat
          round
          icon="menu"
          @click="toggleLeftDrawer"
          v-if="leftDrawer"
        />
        <q-btn dense flat round icon="arrow_back" :to="backTo" v-if="backTo" />
        <q-toolbar-title>
          <q-avatar class="q-mr-sm">
            <img src="MGPapelariaQuadrado.svg" />
          </q-avatar>
          {{ title }}
        </q-toolbar-title>
        <div class="gt-xs q-mr-sm text-caption">v{{ version }}</div>

        <!-- TODO: criar componente para usar nos dois layouts -->
        <usuario-conectado />

        <q-btn round icon="apps" flat>
          <q-menu>
            <q-list style="min-width: 100px">
              <q-item clickable v-close-popup to="/">
                <q-item-section avatar>
                  <q-icon name="point_of_sale" />
                </q-item-section>
                <q-item-section>PDV</q-item-section>
              </q-item>
              <q-item clickable v-close-popup to="/listagem">
                <q-item-section avatar>
                  <q-icon name="checklist_rtl" />
                </q-item-section>
                <q-item-section>Listagem Negocios</q-item-section>
              </q-item>
              <q-item clickable v-close-popup to="/conferencia">
                <q-item-section avatar>
                  <q-icon name="check" />
                </q-item-section>
                <q-item-section>Conferência</q-item-section>
              </q-item>

              <!--
              <q-item clickable v-close-popup to="/liquidacao">
                <q-item-section avatar>
                  <q-icon name="mdi-checkbook" />
                </q-item-section>
                <q-item-section>Liquidações</q-item-section>
              </q-item>
               -->
              <q-separator />
              <q-item clickable v-close-popup to="/config/padrao">
                <q-item-section avatar>
                  <q-icon name="settings" />
                </q-item-section>
                <q-item-section>Configurações</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>

        <q-btn
          dense
          flat
          round
          icon="menu"
          @click="toggleRightDrawer"
          :disable="!rightDrawer"
        />
      </q-toolbar>
    </q-header>

    <q-drawer v-model="leftDrawerOpen" show-if-above bordered v-if="leftDrawer">
      <slot name="left-drawer" />
    </q-drawer>

    <q-drawer
      v-model="rightDrawerOpen"
      show-if-above
      bordered
      side="right"
      v-if="rightDrawer"
    >
      <slot name="right-drawer" />
    </q-drawer>

    <q-page-container>
      <q-page>
        <slot></slot>
      </q-page>
    </q-page-container>
  </q-layout>
</template>
