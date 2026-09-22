<script setup>
import { ref } from 'vue'
import MgUserMenu from '@components/MgUserMenu.vue'
import MgAppFooter from '@components/MgAppFooter.vue'
import MgAppsMenu from '@components/MgAppsMenu.vue'
import MgPageTitle from '@components/MgPageTitle.vue'
import { useAuth } from 'src/composables/useAuth'

const leftDrawerOpen = ref(false)
const rightDrawerOpen = ref(false)

// Drawers estreitos (300px): por padrão o conteúdo do q-scroll-area cresce até a
// largura NATURAL (um número grande, uma fila de chips) e o que passa disso é
// cortado pelo drawer, sem barra visível pra rolar. Prender em 100% obriga o
// conteúdo a caber e quebrar linha. Vale para os dois lados.
const scrollAreaFit = {
  contentStyle: { overflowX: 'hidden', width: '100%' },
  contentActiveStyle: { overflowX: 'hidden', width: '100%' },
  horizontalThumbStyle: { display: 'none' },
  horizontalBarStyle: { display: 'none' },
}

const auth = useAuth()

// Menu de telas internas do app (padrão do contas/estoque).
const menuGroups = [
  {
    label: 'Operação',
    items: [
      { label: 'Pátio de Cargas', icon: 'local_shipping', color: 'green-7', to: { name: 'carga' } },
      { label: 'Romaneios', icon: 'fact_check', color: 'blue-grey-7', to: { name: 'cargas' } },
      {
        label: 'Estoque & Extrato',
        icon: 'inventory_2',
        color: 'amber-8',
        to: { name: 'extrato' },
      },
    ],
  },
  {
    label: 'Safra',
    items: [
      { label: 'Safras', icon: 'eco', color: 'light-green-8', to: { name: 'safras' } },
      { label: 'Fazendas', icon: 'agriculture', color: 'green-7', to: { name: 'fazendas' } },
    ],
  },
  {
    label: 'Cadastros',
    items: [
      { label: 'Culturas', icon: 'category', color: 'blue-grey-7', to: { name: 'culturas' } },
      {
        label: 'Unidades Armazenadoras',
        icon: 'warehouse',
        color: 'amber-8',
        to: { name: 'unidades-armazenadoras' },
      },
    ],
  },
]
</script>

<template>
  <q-layout view="hHh lpr fFf">
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
      :width="300"
    >
      <q-scroll-area class="fit" v-bind="scrollAreaFit">
        <component :is="$route.meta.leftDrawer" />
      </q-scroll-area>
    </q-drawer>

    <q-drawer
      v-if="$route.meta.rightDrawer"
      v-model="rightDrawerOpen"
      side="right"
      show-if-above
      bordered
      class="bg-white"
      :width="300"
    >
      <q-scroll-area class="fit" v-bind="scrollAreaFit">
        <component :is="$route.meta.rightDrawer" />
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
