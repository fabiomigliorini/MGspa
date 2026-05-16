<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import AppLauncher from 'src/components/AppLauncher.vue'
import MgUserMenu from '@components/MgUserMenu.vue'
import { version } from '../../package.json'
import { formataTimestampCompleto } from '@components/formatters'

const route = useRoute()
const leftDrawerOpen = ref(false)
const rightDrawerOpen = ref(false)

const pageTitle = computed(() => route.meta?.title || 'Notas & Documentos Fiscais')

const buildDate = formataTimestampCompleto(process.env.BUILD_DATE)
const commitNumber = process.env.COMMIT_NUMBER
</script>

<template>
  <q-layout view="hHh lpR fFf">
    <!-- Header -->
    <q-header bordered reveal class="bg-primary text-white">
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
          <router-link :to="{ name: 'home' }" class="text-white" style="text-decoration: none">
            <q-avatar size="36px" class="q-mr-sm cursor-pointer">
              <img src="/MGPapelariaQuadrado.svg" alt="MG Papelaria" />
              <q-tooltip>Inicio</q-tooltip>
            </q-avatar>
          </router-link>
          {{ pageTitle }}
        </q-toolbar-title>

        <!-- Menu do Usuário -->
        <MgUserMenu />

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
    <!-- <q-drawer
      v-if="$route.meta.rightDrawer"
      v-model="rightDrawerOpen"
      side="right"
      bordered
      overlay
      behavior="mobile"
      class="bg-grey-2"
      :width="350"
    > -->
    <!-- <q-scroll-area class="fit">
        <component :is="$route.meta.rightDrawer" />
      </q-scroll-area>
    </q-drawer> -->

    <!-- Conteúdo -->
    <q-page-container>
      <router-view />
    </q-page-container>

    <q-footer bordered reveal class="bg-primary text-blue-3 text-caption">
      <div class="q-ma-xs text-center">
        <span class="gt-xs"> App Notas | MG Papelaria &copy; | </span>
        v{{ version }}
        <span v-if="commitNumber"> | commit #{{ commitNumber }}</span>
        <span class="gt-xs" v-if="buildDate"> | {{ buildDate }}</span>
      </div>
    </q-footer>
  </q-layout>
</template>
