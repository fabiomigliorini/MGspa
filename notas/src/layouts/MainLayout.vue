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

const menuGroups = [
  {
    label: 'Emissões',
    items: [
      { label: 'Início', icon: 'home', color: 'brown', to: '/' },
      { label: 'Notas', icon: 'description', color: 'blue', to: '/nota' },
      { label: 'NFe Terceiro', icon: 'move_to_inbox', color: 'orange', to: '/nfe-terceiro' },
    ],
  },
  {
    label: 'Parametrização',
    items: [
      { label: 'Reforma', icon: 'account_balance', color: 'green', to: '/tributacao' },
      { label: 'Naturezas', icon: 'percent', color: 'green', to: '/natureza-operacao' },
      { label: 'Tributações', icon: 'receipt_long', color: 'teal', to: '/tributacao-cadastro' },
      { label: 'CFOPs', icon: 'compare_arrows', color: 'warning', to: '/cfop' },
      { label: 'Cidades', icon: 'location_city', color: 'purple', to: '/cidade' },
      { label: 'DFe', icon: 'sync', color: 'blue', to: '/dfe' },
      { label: 'Domínio', icon: 'file_download', color: 'deep-orange', to: '/dominio' },
    ],
  },
  {
    label: 'Transporte',
    items: [
      { label: 'MDFe', icon: 'receipt_long', color: 'teal', to: '/mdfe' },
      { label: 'Veículos', icon: 'local_shipping', color: 'indigo', to: '/veiculo' },
    ],
  },
]
</script>

<template>
  <q-layout view="hHh lpR fFf">
    <!-- Header -->
    <q-header bordered reveal class="bg-primary text-white">
      <q-toolbar>
        <!-- Hamburger ESQUERDO -->
        <q-btn
          v-if="$route.meta.leftDrawer"
          dense
          flat
          round
          icon="menu"
          @click="leftDrawerOpen = !leftDrawerOpen"
        />

        <MgPageTitle app-name="Notas & Documentos Fiscais" :home-route="{ name: 'home' }" />

        <!-- Menu do Usuário -->
        <MgUserMenu :auth="auth" />

        <MgAppsMenu :groups="menuGroups" />

        <!-- Hamburger DIREITO -->
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

    <!-- Conteúdo -->
    <q-page-container>
      <router-view />
    </q-page-container>

    <q-footer bordered reveal class="bg-primary text-blue-3 text-caption">
      <MgAppFooter app-name="Notas" />
    </q-footer>
  </q-layout>
</template>
