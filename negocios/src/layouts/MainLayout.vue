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
    label: 'Ponto de Venda',
    items: [
      { label: 'PDV', icon: 'point_of_sale', color: 'secondary', to: '/' },
      { label: 'Consulta de Preços', icon: 'price_check', color: 'teal', to: '/quiosque' },
      { label: 'Confissão de Dívida', icon: 'photo_camera', color: 'negative', to: '/confissao' },
      { label: 'Comandas', icon: 'mdi-barcode', color: 'indigo', to: '/comanda-vendedor' },
      { label: 'Conferência', icon: 'check', color: 'orange', to: '/conferencia' },
      { label: 'WOO', icon: 'mdi-list-box-outline', color: 'purple', to: '/woo/painel' },
    ],
  },
  {
    label: 'Configurações',
    items: [{ label: 'Config', icon: 'settings', color: 'grey-8', to: '/config/padrao' }],
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
        <q-btn v-if="leftDrawer" dense flat round icon="menu" @click="toggleLeftDrawer" />
        <q-btn dense flat round icon="arrow_back" :to="backTo" v-if="backTo" />

        <!-- TITULO -->
        <MgPageTitle app-name="Negócios" :title="title" home-route="/" />

        <!-- BOTOES ADICIONAIS -->
        <slot name="botoes" />

        <!-- USUARIO  -->
        <slot name="usuario" />

        <MgAppsMenu :groups="menuGroups" />

        <!-- HAMBURGER DIREITO -->
        <q-btn v-if="rightDrawer" dense flat round icon="menu" @click="toggleRightDrawer" />
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
