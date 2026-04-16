<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import AppLauncher from 'src/components/AppLauncher.vue'
import UserMenu from 'src/components/UserMenu.vue'
import { version } from '../../package.json'

const router = useRouter()
const route = useRoute()
const leftDrawerOpen = ref(false)
const rightDrawerOpen = ref(false)

const pageTitle = computed(() => route.meta?.title || 'Contas')

const goToDashboard = () => {
  router.push({ name: 'home' })
}
</script>

<template>
  <q-layout view="hHh lpR fFf">
    <q-header reveal bordered class="bg-primary text-white">
      <q-toolbar>
        <q-btn
          dense
          flat
          round
          icon="menu"
          @click="leftDrawerOpen = !leftDrawerOpen"
          :disable="!$route.meta.leftDrawer"
        />

        <q-toolbar-title class="q-ml-sm">
          <q-avatar size="36px" class="q-mr-sm cursor-pointer" @click="goToDashboard">
            <img src="/MGPapelariaQuadrado.svg" alt="MG Papelaria" />
            <q-tooltip>Início</q-tooltip>
          </q-avatar>
          {{ pageTitle }}
        </q-toolbar-title>

        <div class="gt-xs q-mr-sm text-caption">v{{ version }}</div>

        <user-menu />

        <app-launcher />

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

    <q-drawer
      v-if="$route.meta.leftDrawer"
      v-model="leftDrawerOpen"
      show-if-above
      bordered
      class="bg-white"
      :width="280"
    >
      <q-scroll-area class="fit">
        <component :is="$route.meta.leftDrawer" />
      </q-scroll-area>
    </q-drawer>

    <q-page-container class="bg-grey-2">
      <router-view />
    </q-page-container>
  </q-layout>
</template>
