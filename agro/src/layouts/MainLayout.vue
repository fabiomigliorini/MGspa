<script setup>
import { ref, computed } from 'vue'
import { date } from 'quasar'
import MgUserMenu from '@components/MgUserMenu.vue'
import MgAppFooter from '@components/MgAppFooter.vue'
import MgAppsMenu from '@components/MgAppsMenu.vue'
import MgPageTitle from '@components/MgPageTitle.vue'
import { useAuth } from 'src/composables/useAuth'
import { useSincronizacaoAutomatica } from 'src/composables/useSincronizacaoAutomatica'
import { useCargaStore } from 'src/stores/carga'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'

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

// Sincronização do pátio: roda sozinha (a cada minuto e quando a rede volta) e o
// estado fica sempre à vista aqui no header. Clique = sincronizar agora,
// ignorando o cache dos cadastros.
useSincronizacaoAutomatica()
const carga = useCargaStore()
const sinc = useSincronizacaoStore()
const estadoSync = computed(() => {
  const hora = sinc.ultimoCiclo ? ` Última: ${date.formatDate(sinc.ultimoCiclo, 'HH:mm')}.` : ''
  if (sinc.sincronizando) return { icon: 'cloud_sync', color: 'white', dica: 'Sincronizando…' }
  if (!sinc.online) {
    return {
      icon: 'cloud_off',
      color: 'orange-4',
      dica: `Sem conexão: as cargas ficam no aparelho e sobem sozinhas quando a internet voltar.${hora}`,
    }
  }
  if (sinc.erro) {
    return {
      icon: 'sync_problem',
      color: 'amber-4',
      dica: `Falha ao sincronizar: ${sinc.erro}. A tela mostra os cadastros já baixados.${hora}`,
    }
  }
  return { icon: 'cloud_done', color: 'white', dica: `Sincronizado.${hora}` }
})

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

        <q-btn
          dense
          flat
          round
          :icon="estadoSync.icon"
          :color="estadoSync.color"
          @click="carga.sincronizar({ force: true })"
        >
          <q-tooltip>{{ estadoSync.dica }}</q-tooltip>
        </q-btn>

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
