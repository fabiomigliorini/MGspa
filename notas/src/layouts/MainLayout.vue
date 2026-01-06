<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import AppLauncher from 'src/components/AppLauncher.vue'
import UserMenu from 'src/components/UserMenu.vue'
import { version } from '../../package.json'

const leftDrawerOpen = ref(false)
const rightDrawerOpen = ref(false)

// PWA Install
const deferredPrompt = ref(null)
const canInstall = ref(false)

const handleBeforeInstallPrompt = (e) => {
  e.preventDefault()
  deferredPrompt.value = e
  canInstall.value = true
}

const installPwa = async () => {
  const promptEvent = deferredPrompt.value || window.deferredPwaPrompt
  if (!promptEvent) return

  promptEvent.prompt()
  const { outcome } = await promptEvent.userChoice

  if (outcome === 'accepted') {
    canInstall.value = false
  }
  deferredPrompt.value = null
  window.deferredPwaPrompt = null
}

onMounted(() => {
  // Verifica se o evento já foi capturado antes do componente montar
  if (window.deferredPwaPrompt) {
    deferredPrompt.value = window.deferredPwaPrompt
    canInstall.value = true
  }

  window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt)

  // Esconde o botão se já estiver instalado
  window.addEventListener('appinstalled', () => {
    canInstall.value = false
    deferredPrompt.value = null
    window.deferredPwaPrompt = null
  })

  // Verifica se está rodando como PWA instalado (standalone)
  if (window.matchMedia('(display-mode: standalone)').matches) {
    canInstall.value = false
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
})
</script>

<template>
  <q-layout view="hHh lpR fFf">
    <!-- Header -->
    <q-header elevated class="bg-primary text-white">
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
          <q-avatar size="36px" class="q-mr-sm">
            <img src="/MGPapelariaQuadrado.svg" alt="MG Papelaria" />
          </q-avatar>
          Notas & Documentos Fiscais
        </q-toolbar-title>

        <div class="gt-xs q-mr-sm text-caption">v{{ version }}</div>

        <!-- Botão Instalar PWA -->
        <q-btn v-if="canInstall" flat dense round icon="install_desktop" @click="installPwa">
          <q-tooltip>Instalar aplicativo</q-tooltip>
        </q-btn>

        <!-- Menu do Usuário -->
        <user-menu />

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
  </q-layout>
</template>
