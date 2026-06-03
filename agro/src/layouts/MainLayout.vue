<script setup>
import { ref } from 'vue'
import MgUserMenu from '@components/MgUserMenu.vue'
import MgAppFooter from '@components/MgAppFooter.vue'
import MgAppsMenu from '@components/MgAppsMenu.vue'
import MgPageTitle from '@components/MgPageTitle.vue'
import { useAuth } from 'src/composables/useAuth'

const leftDrawerOpen = ref(false)
const rightDrawerOpen = ref(false)

const auth = useAuth()

// Menu de telas internas do app (padrão do contas/estoque).
// Esqueleto: ainda sem telas internas — só o menu de apps externos.
const menuGroups = []
</script>

<template>
  <q-layout view="hHh lpR fFf">
    <q-header reveal bordered class="bg-primary text-white">
      <q-toolbar>
        <q-btn
          v-if="$route.meta.leftDrawer"
          dense
          flat
          round
          icon="menu"
          @click="leftDrawerOpen = !leftDrawerOpen"
        />

        <MgPageTitle app-name="Agro" :home-route="{ name: 'home' }" />

        <MgUserMenu :auth="auth" />
        <MgAppsMenu :groups="menuGroups" />

        <q-btn
          v-if="$route.meta.rightDrawer"
          dense
          flat
          round
          icon="menu"
          @click="rightDrawerOpen = !rightDrawerOpen"
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
      <q-scroll-area
        class="fit"
        :content-style="{ overflowX: 'hidden', width: '100%' }"
        :content-active-style="{ overflowX: 'hidden', width: '100%' }"
        :horizontal-thumb-style="{ display: 'none' }"
        :horizontal-bar-style="{ display: 'none' }"
      >
        <component :is="$route.meta.leftDrawer" />
      </q-scroll-area>
    </q-drawer>

    <q-page-container class="bg-grey-2">
      <router-view />
    </q-page-container>

    <q-footer bordered reveal class="bg-primary text-blue-3 text-caption">
      <MgAppFooter app-name="Agro" />
    </q-footer>
  </q-layout>
</template>
