<script setup>

import { ref, defineProps } from 'vue'
import { Dialog } from 'quasar';
import { version } from "../../package.json";
import MgMenu from 'layouts/MGMenu.vue';

const leftDrawerOpen = ref(false)
const user = ref(localStorage.getItem('usuario'))

defineProps({
  drawer: {
    type: Boolean,
    default: false
  },
  backButton: {
    type: Boolean,
    default: false
  }
})

const deslogar = async () => {
  Dialog.create({
    title: 'Sair da conta',
    message: 'Tem certeza que deseja sair?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    localStorage.removeItem('access_token')
    localStorage.removeItem('usuario')
    window.location = process.env.LOGOUT_URL
  })
}

const toggleLeftDrawer = () => {
  leftDrawerOpen.value = !leftDrawerOpen.value
}


</script>

<template>
  <q-layout view="Hhh lpR fff">
    <q-header reveal elevated class="bg-yellow text-blue-grey">
      <q-toolbar>

        <q-btn flat dense round @click="toggleLeftDrawer" icon="menu" aria-label="Menu" v-if="drawer" />

        <q-btn flat dense round v-if="backButton">
          <slot name="botaoVoltar"></slot>
        </q-btn>

        <q-toolbar-title>
          <slot name="tituloPagina"></slot>
        </q-toolbar-title>

        <!-- Renderiza o menu -->
        <mg-menu></mg-menu>

        <!-- Usuario logout -->
        <q-btn-dropdown flat dense color="blue-grey" icon="person" :label="user">
          <div class="row no-wrap q-pa-md justify-center">

            <div class="column items-center">
              <q-avatar size="72px">
                <img src="https://cdn.quasar.dev/img/boy-avatar.png">
              </q-avatar>
              <div class="text-subtitle1 q-mt-md q-mb-xs">{{ user }}</div>

              <q-btn color="primary" :to="'/perfil'" class="q-mb-md" label="Perfil" push size="sm" v-close-popup />

              <q-btn color="primary" label="Sair" push size="sm" v-close-popup @click="deslogar()" />
            </div>
          </div>
        </q-btn-dropdown>
      </q-toolbar>
    </q-header>

    <!-- Drawer padrão MG Layout -->
    <q-drawer show-if-above v-model="leftDrawerOpen" side="left" elevated v-if="drawer">
      <slot name="drawer"></slot>
    </q-drawer>

    <q-page-container class="bg-grey-2">
      <router-view />
      <slot name="content"></slot>
    </q-page-container>
    <q-footer elevated reveal class="bg-grey-8 text-white">
      <div class="q-ma-xs text-weight-light text-center">
        Pessoas | MG Papelaria &copy; | v{{ version }}
      </div>
    </q-footer>
  </q-layout>
</template>

<style>
/* FONT AWESOME GENERIC BEAT */
.fa-beat {
  animation: fa-beat 5s ease infinite;
}

@keyframes fa-beat {
  0% {
    transform: scale(1);
  }

  5% {
    transform: scale(1.25);
  }

  20% {
    transform: scale(1);
  }

  30% {
    transform: scale(1);
  }

  35% {
    transform: scale(1.25);
  }

  50% {
    transform: scale(1);
  }

  55% {
    transform: scale(1.25);
  }

  70% {
    transform: scale(1);
  }
}
</style>
