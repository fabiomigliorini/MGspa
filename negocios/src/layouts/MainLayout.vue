<script setup>
import { ref } from "vue";
import { version } from "../../package.json";

const props = defineProps({
  backTo: {
    type: String,
    default: null,
  },
  leftDrawer: {
    type: Boolean,
    default: false,
  },
  rightDrawer: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: "*** Título ***",
  },
});

const apps = ref([
  {
    label: "PDV",
    apps: [
      {
        icon: "point_of_sale",
        label: "PDV",
        to: "/",
      },
      {
        icon: "checklist_rtl",
        label: "Listagem",
        to: "/listagem",
      },
      {
        icon: "check",
        label: "Conferência",
        to: "/conferencia",
      },
    ],
  },
  {
    label: "Liquidações",
    apps: [
      {
        icon: "mdi-checkbook",
        label: "Liquidações",
        to: "/liquidacao",
      },
    ],
  },
  {
    label: "Configurações",
    apps: [
      {
        icon: "settings",
        label: "Config",
        to: "/config/padrao",
      },
    ],
  },
]);

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
        <!-- HAMBURQUER ESQUERDO -->
        <q-btn
          dense
          flat
          round
          icon="menu"
          @click="toggleLeftDrawer"
          :disable="!leftDrawer"
          v-if="!backTo"
        />
        <q-btn dense flat round icon="arrow_back" :to="backTo" v-if="backTo" />

        <!-- TITULO -->
        <q-toolbar-title>
          <q-avatar class="q-mr-sm">
            <img src="MGPapelariaQuadrado.svg" />
          </q-avatar>
          {{ title }}
        </q-toolbar-title>

        <!-- BOTOES ADICIONAIS -->
        <slot name="botoes" />

        <!-- VERSAO  -->
        <div class="gt-xs q-mr-sm text-caption">v{{ version }}</div>

        <!-- USUARIO  -->
        <slot name="usuario" />

        <!-- MENU -->
        <q-btn round icon="apps" flat>
          <q-menu style="width: 300px" class="q-pa-sm">
            <template v-for="(appBlock, iAppBlock) in apps" :key="iAppBlock">
              <!-- <q-item-label header>
                {{ appBlock.label }}
              </q-item-label> -->
              <div class="row">
                <template v-for="(app, iApp) in appBlock.apps" :key="iApp">
                  <q-item
                    class="col-4 text-grey-8"
                    :to="app.to"
                    clickable
                    active-class="bg-teal-1 text-grey-8"
                  >
                    <q-item-section class="flex-center">
                      <q-icon :name="app.icon" size="40px" />
                      <q-item-label class="text-caption ellipsis">
                        {{ app.label }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </template>
              </div>
              <q-separator
                v-if="iAppBlock != apps.length - 1"
                class="q-my-sm"
              />
            </template>
          </q-menu>
        </q-btn>

        <!-- HAMBURGER DIREITO -->
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

    <q-drawer v-model="leftDrawerOpen" bordered show-if-above v-if="leftDrawer">
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
      <slot name="content" />
    </q-page-container>
  </q-layout>
</template>
