<script setup>
import { ref } from 'vue'
import MgAppFooter from '@components/MgAppFooter.vue'
import MgAppsMenu from '@components/MgAppsMenu.vue'
import MgPageTitle from '@components/MgPageTitle.vue'

defineProps({
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
    default: '*** Título ***',
  },
})

const menuGroups = [
  {
    label: 'PDV',
    items: [
      { label: 'PDV', icon: 'point_of_sale', color: 'grey-8', to: '/' },
      { label: 'WOO', icon: 'mdi-list-box-outline', color: 'grey-8', to: '/woo/painel' },
      { label: 'Conferência', icon: 'check', color: 'grey-8', to: '/conferencia' },
      { label: 'Conf', icon: 'photo_camera', color: 'grey-8', to: '/confissao' },
    ],
  },
  {
    label: 'Comandas',
    items: [
      { label: 'Comandas', icon: 'mdi-barcode', color: 'grey-8', to: '/comanda-vendedor' },
    ],
  },
  {
    label: 'Configurações',
    items: [
      { label: 'Config', icon: 'settings', color: 'grey-8', to: '/config/padrao' },
    ],
  },
]

const leftDrawerOpen = ref(false)
const rightDrawerOpen = ref(false)

const toggleLeftDrawer = () => {
  leftDrawerOpen.value = !leftDrawerOpen.value
}
const toggleRightDrawer = () => {
  rightDrawerOpen.value = !rightDrawerOpen.value
}
</script>

<template>
  <q-layout view="hHh lpr fFf">
    <q-header reveal bordered height-hint="98">
      <q-toolbar>
        <!-- HAMBURQUER ESQUERDO -->
        <q-btn
          dense
          flat
          round
          icon="menu"
          @click="toggleLeftDrawer"
          :disable="!leftDrawer"
          v-if="leftDrawer"
        />
        <q-btn dense flat round icon="arrow_back" :to="backTo" v-if="backTo" />

        <!-- TITULO -->
        <MgPageTitle app-name="Negócios" :title="title" home-route="/" />

        <!-- BOTOES ADICIONAIS -->
        <slot name="botoes" />

        <!-- USUARIO  -->
        <slot name="usuario" />

        <MgAppsMenu :groups="menuGroups" />

        <!-- HAMBURGER DIREITO -->
        <q-btn
          dense
          flat
          round
          icon="menu"
          @click="toggleRightDrawer"
          :disable="!rightDrawer"
          v-if="rightDrawer"
        />
      </q-toolbar>
    </q-header>

    <q-drawer v-model="leftDrawerOpen" bordered show-if-above v-if="leftDrawer">
      <slot name="left-drawer" />
    </q-drawer>

    <q-drawer v-model="rightDrawerOpen" show-if-above bordered side="right" v-if="rightDrawer">
      <slot name="right-drawer" />
    </q-drawer>

    <q-page-container>
      <slot name="content" />
    </q-page-container>

    <q-footer bordered reveal class="bg-primary text-blue-3 text-caption">
      <MgAppFooter app-name="Negócios" />
    </q-footer>
  </q-layout>
</template>
