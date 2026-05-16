<script setup>
import { ref, onMounted } from 'vue'
import MgMenu from 'layouts/MGMenu.vue'
import MgAppFooter from '@components/MgAppFooter.vue'
import MgUserMenu from '@components/MgUserMenu.vue'
import { useAuth } from 'src/composables/useAuth'

const auth = useAuth()
const leftDrawerOpen = ref(false)

defineProps({
  drawer: {
    type: Boolean,
    default: false,
  },
  backButton: {
    type: Boolean,
    default: false,
  },
})

onMounted(() => {
  if (!auth.usuario.value) auth.validarToken()
})

const toggleLeftDrawer = () => {
  leftDrawerOpen.value = !leftDrawerOpen.value
}
</script>

<template>
  <q-layout view="Hhh lpR fff">
    <!-- CABECALHO -->
    <q-header reveal bordered class="bg-primary text-white">
      <q-toolbar>
        <q-btn
          flat
          dense
          round
          @click="toggleLeftDrawer"
          icon="menu"
          aria-label="Menu"
          v-if="drawer"
        />

        <q-btn flat dense round v-if="backButton">
          <slot name="botaoVoltar"></slot>
        </q-btn>

        <q-toolbar-title>
          <slot name="tituloPagina"></slot>
        </q-toolbar-title>

        <!-- Renderiza o menu -->
        <mg-menu></mg-menu>

        <!-- Usuario logout -->
        <MgUserMenu :auth="auth" />
      </q-toolbar>
    </q-header>

    <!-- DRAWER -->
    <q-drawer show-if-above v-model="leftDrawerOpen" side="left" bordered v-if="drawer">
      <slot name="drawer"></slot>
    </q-drawer>

    <!-- CONTEUDO -->
    <q-page-container class="bg-grey-2">
      <router-view v-if="!$slots.content" />
      <slot name="content"></slot>
    </q-page-container>

    <!-- RODAPE -->
    <q-footer bordered reveal class="bg-primary text-blue-3 text-caption">
      <MgAppFooter app-name="Pessoas" />
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
