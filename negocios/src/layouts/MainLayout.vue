<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import MgAppFooter from '@components/MgAppFooter.vue'
import MgAppsMenu from '@components/MgAppsMenu.vue'
import MgPageTitle from '@components/MgPageTitle.vue'
import BtnSincronizacao from 'components/offline/BtnSincronizacao.vue'
import UsuarioConectado from 'components/UsuarioConectado.vue'

const route = useRoute()

const menuGroups = [
  {
    label: 'Ponto de Venda',
    items: [
      { label: 'PDV', icon: 'point_of_sale', color: 'secondary', to: '/' },
      { label: 'Pagamentos', icon: 'payments', color: 'indigo', to: '/pagamento' },
      { label: 'Caixa', icon: 'savings', color: 'green-8', to: '/caixa' },
      { label: 'Confissão de Dívida', icon: 'photo_camera', color: 'negative', to: '/confissao' },
      {
        label: 'Meu Dispositivo',
        icon: 'phonelink_setup',
        color: 'blue-grey',
        to: '/dispositivo/meu',
      },
    ],
  },
  {
    label: 'Consultas',
    items: [{ label: 'Consulta de Preços', icon: 'price_check', color: 'teal', to: '/quiosque' }],
  },
  {
    label: 'Administração',
    items: [
      { label: 'Modelos de Vale', icon: 'card_giftcard', color: 'pink', to: '/vale-modelo' },
      { label: 'Comandas', icon: 'mdi-barcode', color: 'indigo', to: '/comanda-vendedor' },
      { label: 'WOO', icon: 'mdi-list-box-outline', color: 'purple', to: '/woo/painel' },
      { label: 'Dispositivos', icon: 'devices', color: 'blue-grey', to: '/dispositivo' },
      { label: 'PagarMe', icon: 'mdi-printer-pos-outline', color: 'primary', to: '/pagar-me' },
      {
        label: 'Prancheta',
        icon: 'mdi-clipboard-text-outline',
        color: 'primary',
        to: '/prancheta',
      },
    ],
  },
]

const leftDrawerOpen = ref(false)
const rightDrawerOpen = ref(false)

// meta.backTo: caminho fixo, ou funcao da rota quando o destino depende dela
const backTo = computed(() => {
  const b = route.meta.backTo
  return typeof b === 'function' ? b(route) : b
})
</script>

<template>
  <q-layout view="hHh lpr fFf">
    <q-header reveal bordered height-hint="98">
      <q-toolbar>
        <!-- HAMBURQUER ESQUERDO -->
        <q-btn
          v-if="$route.meta.leftDrawer"
          dense
          flat
          round
          icon="menu"
          @click="leftDrawerOpen = !leftDrawerOpen"
        />
        <q-btn dense flat round icon="arrow_back" :to="backTo" v-if="backTo" />

        <!-- TITULO -->
        <MgPageTitle app-name="Negócios" home-route="/" />

        <!-- SINCRONIZACAO: em todas as telas -->
        <btn-sincronizacao />

        <!-- USUARIO  -->
        <usuario-conectado />

        <MgAppsMenu :groups="menuGroups" />

        <!-- HAMBURGER DIREITO -->
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

    <q-drawer v-model="leftDrawerOpen" bordered show-if-above v-if="$route.meta.leftDrawer">
      <component :is="$route.meta.leftDrawer" />
    </q-drawer>

    <q-drawer
      v-model="rightDrawerOpen"
      show-if-above
      bordered
      side="right"
      v-if="$route.meta.rightDrawer"
    >
      <component :is="$route.meta.rightDrawer" />
    </q-drawer>

    <q-page-container class="bg-grey-2">
      <router-view :key="$route.fullPath" />
    </q-page-container>

    <q-footer bordered reveal class="bg-primary text-blue-3 text-caption">
      <MgAppFooter app-name="Negócios" />
    </q-footer>
  </q-layout>
</template>
