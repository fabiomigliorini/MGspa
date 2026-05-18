<script setup>
import { ref } from 'vue'
import MgUserMenu from '@components/MgUserMenu.vue'
import MgAppFooter from '@components/MgAppFooter.vue'
import MgAppsMenu from '@components/MgAppsMenu.vue'
import MgScreensMenu from '@components/MgScreensMenu.vue'
import MgPageTitle from '@components/MgPageTitle.vue'
import { useAuth } from 'src/composables/useAuth'

const leftDrawerOpen = ref(false)
const rightDrawerOpen = ref(false)

const auth = useAuth()

const menuGroups = [
  {
    label: 'Movimento',
    items: [
      { label: 'Títulos', icon: 'request_quote', color: 'indigo-7', to: { name: 'titulo' } },
      { label: 'Pix', icon: 'pix', color: 'teal-7', to: { name: 'pix' } },
      {
        label: 'Saldos',
        icon: 'account_balance_wallet',
        color: 'amber-8',
        to: { name: 'portador-saldos' },
      },
      {
        label: 'Boletos',
        icon: 'receipt',
        color: 'red-7',
        to: { name: 'boleto-abertos', query: { tipo: 'vencer7' } },
      },
      {
        label: 'Agrupamentos',
        icon: 'receipt_long',
        color: 'indigo-7',
        to: { name: 'agrupamento' },
      },
      {
        label: 'Liquidações',
        icon: 'paid',
        color: 'green-7',
        to: { name: 'liquidacao-titulo' },
      },
    ],
  },
  {
    label: 'Cadastros',
    items: [
      { label: 'Bancos', icon: 'account_balance', color: 'red-8', to: { name: 'banco' } },
      {
        label: 'Contas Contábeis',
        icon: 'account_tree',
        color: 'indigo-8',
        to: { name: 'conta-contabil' },
      },
      { label: 'Tipos de Título', icon: 'receipt_long', color: 'teal-8', to: { name: 'tipo-titulo' } },
      {
        label: 'Tipos de Movimento',
        icon: 'sync_alt',
        color: 'deep-purple-8',
        to: { name: 'tipo-movimento-titulo' },
      },
      { label: 'Portadores', icon: 'credit_card', color: 'cyan-8', to: { name: 'portador' } },
      {
        label: 'Formas de Pagamento',
        icon: 'payments',
        color: 'green-8',
        to: { name: 'forma-pagamento' },
      },
    ],
  },
]
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

        <MgPageTitle app-name="Contas" :home-route="{ name: 'pix' }" />

        <MgUserMenu :auth="auth" />
        <MgAppsMenu />
        <MgScreensMenu :groups="menuGroups" />

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
      <MgAppFooter app-name="Contas" />
    </q-footer>
  </q-layout>
</template>
