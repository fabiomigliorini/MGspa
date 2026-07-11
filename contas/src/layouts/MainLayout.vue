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
    label: 'Movimento',
    items: [
      { label: 'Pix Recebidos', icon: 'pix', color: 'teal-7', to: { name: 'pix' } },
      {
        label: 'Liquidações',
        icon: 'paid',
        color: 'indigo-7',
        to: { name: 'liquidacao-titulo' },
      },
      {
        label: 'Agrupamentos',
        icon: 'receipt_long',
        color: 'amber-8',
        to: { name: 'agrupamento' },
      },
      { label: 'Títulos', icon: 'request_quote', color: 'indigo-7', to: { name: 'titulo' } },
      {
        label: 'Saldos',
        icon: 'account_balance_wallet',
        color: 'amber-8',
        to: { name: 'portador-saldos' },
      },
      {
        label: 'Boletos Emitidos',
        icon: 'receipt',
        color: 'red-7',
        to: { name: 'boleto-abertos', query: { tipo: 'vencer7' } },
      },
      { label: 'Cheques', icon: 'payments', color: 'teal-8', to: { name: 'cheque' } },
      {
        label: 'Repasse de Cheques',
        icon: 'move_up',
        color: 'deep-purple-7',
        to: { name: 'cheque-repasse' },
      },
    ],
  },
  {
    label: 'Cadastros',
    items: [
      { label: 'Bancos', icon: 'account_balance', color: 'red-8', to: { name: 'banco' } },
      { label: 'Moedas', icon: 'currency_exchange', color: 'purple-8', to: { name: 'moeda' } },
      {
        label: 'Contas Contábeis',
        icon: 'account_tree',
        color: 'indigo-8',
        to: { name: 'conta-contabil' },
      },
      {
        label: 'Unidades de Referência (UPF)',
        icon: 'straighten',
        color: 'blue-8',
        to: { name: 'unidade-referencia' },
      },
      {
        label: 'Tipos de Título',
        icon: 'receipt_long',
        color: 'teal-8',
        to: { name: 'tipo-titulo' },
      },
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
      {
        label: 'Motivos Devolução Cheque',
        icon: 'assignment_return',
        color: 'red-8',
        to: { name: 'cheque-motivo-devolucao' },
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
          v-if="$route.meta.leftDrawer"
          dense
          flat
          round
          icon="menu"
          @click="leftDrawerOpen = !leftDrawerOpen"
        />

        <MgPageTitle app-name="Contas" :home-route="{ name: 'pix' }" />

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
      <MgAppFooter app-name="Contas" />
    </q-footer>
  </q-layout>
</template>
