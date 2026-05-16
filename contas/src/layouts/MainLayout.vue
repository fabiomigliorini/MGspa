<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import AppLauncher from 'src/components/AppLauncher.vue'
import MgUserMenu from '@components/MgUserMenu.vue'
import { version } from '../../package.json'
import { formataTimestampIso } from '@components/formatters'

const route = useRoute()
const leftDrawerOpen = ref(false)
const rightDrawerOpen = ref(false)

const pageTitle = computed(() => route.meta?.title || 'Contas')

const buildDate = formataTimestampIso(process.env.BUILD_DATE)
const commitNumber = process.env.COMMIT_NUMBER
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
          <router-link :to="{ name: 'pix' }" class="text-white" style="text-decoration: none">
            <q-avatar size="36px" class="q-mr-sm cursor-pointer">
              <img src="/MGPapelariaQuadrado.svg" alt="MG Papelaria" />
              <q-tooltip>Início</q-tooltip>
            </q-avatar>
          </router-link>
          {{ pageTitle }}
        </q-toolbar-title>

        <MgUserMenu />

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
      <div class="q-ma-xs text-center">
        <span class="gt-xs"> App Contas | MG Papelaria &copy; | </span>
        <span class="gt-xs"> v{{ version }} | </span>
        <span v-if="commitNumber"> #{{ commitNumber }}</span>
        <span v-if="buildDate"> | {{ buildDate }}</span>
      </div>
    </q-footer>
  </q-layout>
</template>
