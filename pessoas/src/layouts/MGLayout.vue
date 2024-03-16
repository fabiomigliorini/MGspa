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

        <!-- <q-btn round dense flat color="white" icon="notifications">
            <q-badge color="red" text-color="white" floating>
              0
            </q-badge>
            <q-menu
            >
              <q-list style="min-width: 100px">
                <q-card class="text-center no-shadow no-border">
                  <q-btn label="Ver tudo" style="max-width: 120px !important;" flat dense
                         class="text-indigo-8"></q-btn>
                </q-card>
              </q-list>
            </q-menu>
          </q-btn> -->

        <!-- Renderiza o menu -->
        <mg-menu></mg-menu>

        <!-- Usuario logout -->
        <q-btn-dropdown flat color="blue-grey" icon="person" :label="user">
          <div class="row no-wrap q-pa-md justify-center">

            <div class="column items-center">
              <q-avatar size="72px">
                <img src="https://cdn.quasar.dev/img/boy-avatar.png">
              </q-avatar>
              <div class="text-subtitle1 q-mt-md q-mb-xs">{{ user }}</div>

              <q-btn color="primary" :to="'/perfil'" class="q-mb-md" label="Perfil" push size="sm" v-close-popup />

              <q-btn color="primary" label="Sair" push size="sm" v-close-popup @click="Deslogar" />
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
        MGpwa - &copy; MG Papelaria
      </div>
    </q-footer>
  </q-layout>
</template>

<script>

import { defineComponent, ref, defineAsyncComponent } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'

export default defineComponent({
  name: 'MGLayout',

  components: {
    MgMenu: defineAsyncComponent(() => import('layouts/MGMenu.vue'))
  },
  props: {
    drawer: {
      type: Boolean,
      default: false
    },
    backButton: {
      type: Boolean,
      default: false
    }
  },

  setup() {
    const leftDrawerOpen = ref(false)
    const $q = useQuasar()
    const router = useRouter()
    const user = ref(localStorage.getItem('usuario'))

    const Deslogar = async () => {
      $q.dialog({
        title: 'Sair da conta',
        message: 'Tem certeza que deseja sair?',
        cancel: true,
        persistent: true
      }).onOk(async () => {
        localStorage.removeItem('access_token')
        localStorage.removeItem('usuario')
        window.location = process.env.LOGOUT_URL
        // router.replace({name: 'login'})
      })
    }

    return {
      user,
      leftDrawerOpen,
      toggleLeftDrawer() {
        leftDrawerOpen.value = !leftDrawerOpen.value
      },
      Deslogar
    }
  }
})
</script>

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
