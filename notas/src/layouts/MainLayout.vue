<script setup>
import { ref } from 'vue'
import AppLauncher from 'src/components/AppLauncher.vue'
import UserMenu from 'src/components/UserMenu.vue'

const leftDrawerOpen = ref(false)
const rightDrawerOpen = ref(false)
</script>

<template>
  <q-layout view="hHh lpR fFf">
    <!-- Header -->
    <q-header elevated class="bg-primary text-white">
      <q-toolbar>
        <!-- Hamburger ESQUERDO -->
        <q-btn
          dense
          flat
          round
          icon="menu"
          @click="leftDrawerOpen = !leftDrawerOpen"
          :disable="!$route.meta.leftDrawer"
        />

        <q-toolbar-title class="q-ml-sm">
          <q-avatar size="36px" class="q-mr-sm">
            <img src="/MGPapelariaQuadrado.svg" alt="MG Papelaria" />
          </q-avatar>
          Notas & Documentos Fiscais
        </q-toolbar-title>

        <!-- Menu do Usuário -->
        <user-menu />

        <!-- App Launcher -->
        <app-launcher />

        <!-- Hamburger DIREITO -->
        <q-btn
          dense
          flat
          round
          icon="menu"
          @click="rightDrawerOpen = !rightDrawerOpen"
          :disable="!$route.meta.rightDrawer"
        />
      </q-toolbar>
    </q-header>

    <!-- Drawer ESQUERDA -->
    <q-drawer
      v-if="$route.meta.leftDrawer"
      v-model="leftDrawerOpen"
      show-if-above
      bordered
      class="bg-grey-2"
      :width="280"
    >
      <q-scroll-area class="fit">
        <component :is="$route.meta.leftDrawer" />
      </q-scroll-area>
    </q-drawer>

    <!-- Drawer DIREITA -->
    <q-drawer
      v-if="$route.meta.rightDrawer"
      v-model="rightDrawerOpen"
      side="right"
      bordered
      overlay
      behavior="mobile"
      class="bg-grey-2"
      :width="350"
    >
      <q-scroll-area class="fit">
        <component :is="$route.meta.rightDrawer" />
      </q-scroll-area>
    </q-drawer>

    <!-- Conteúdo -->
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>
