<script setup>
import { ref, computed, onMounted } from 'vue'
import moment from 'moment'
import 'moment/min/locales'
import { useAuthStore } from 'src/stores'
import MgAppFooter from '@components/MgAppFooter.vue'
import MgUserMenu from '@components/MgUserMenu.vue'
import MgAppsMenu from '@components/MgAppsMenu.vue'
import MgScreensMenu from '@components/MgScreensMenu.vue'
import MgPageTitle from '@components/MgPageTitle.vue'
import { useAuth } from 'src/composables/useAuth'

moment.locale('pt-br')

const auth = useAuth()
const user = useAuthStore()
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

const menuGroups = computed(() => {
  const groups = [
    {
      items: [
        { label: 'Pessoas', icon: 'person', color: 'teal-7', to: { name: 'pessoa' } },
        {
          label: 'Grupo Econômico',
          icon: 'apartment',
          color: 'indigo-7',
          to: { name: 'grupoeconomicoindex' },
        },
        {
          label: 'Aniversários',
          icon: 'celebration',
          color: 'pink-6',
          to: { name: 'aniversariosindex' },
        },
        { label: 'Metas', icon: 'trending_up', color: 'green-7', to: { name: 'metaIndex' } },
        {
          label: 'Unidades & Setores',
          icon: 'store',
          color: 'brown-6',
          to: { name: 'unidadeNegocioIndex' },
        },
        {
          label: 'Meu Painel',
          icon: 'assignment_ind',
          color: 'deep-purple-6',
          to: { name: 'rhMeuPainelIndex' },
        },
      ],
    },
    {
      label: 'Administração',
      items: [
        {
          label: 'Usuários',
          icon: 'admin_panel_settings',
          color: 'indigo-7',
          to: { name: 'usuarios' },
          hide: !user.temPermissao('Administrador'),
        },
        {
          label: 'Grupo Usuários',
          icon: 'groups',
          color: 'indigo-7',
          to: { name: 'grupousuarios' },
          hide: !user.temPermissao('Administrador'),
        },
        {
          label: 'Empresas',
          icon: 'business',
          color: 'indigo-7',
          to: { name: 'empresa' },
          hide: !user.temPermissao('Administrador'),
        },
      ],
    },
    {
      label: 'Recursos Humanos',
      items: [
        {
          label: 'Férias',
          icon: 'hotel',
          color: 'amber-8',
          to: '/ferias/' + moment().year(),
          hide: !user.temPermissao('Recursos Humanos'),
        },
        {
          label: 'Cargos',
          icon: 'work',
          color: 'amber-8',
          to: { name: 'cargosindex' },
          hide: !user.temPermissao('Recursos Humanos'),
        },
        {
          label: 'Metas RH',
          icon: 'paid',
          color: 'amber-8',
          to: { name: 'rhIndex' },
          hide: !user.temPermissao('Recursos Humanos'),
        },
        {
          label: 'Feriados',
          icon: 'event_busy',
          color: 'amber-8',
          to: { name: 'feriadoIndex' },
          hide: !user.temPermissao('Recursos Humanos'),
        },
        {
          label: 'Emissores de Certidões',
          icon: 'description',
          color: 'amber-8',
          to: { name: 'certidaoemissor' },
          hide: !user.temPermissao('Recursos Humanos'),
        },
      ],
    },
    {
      label: 'Cadastros',
      items: [
        { label: 'Etnia', icon: 'diversity_1', color: 'grey-8', to: { name: 'etnia' } },
        {
          label: 'Grau Instrução',
          icon: 'school',
          color: 'grey-8',
          to: { name: 'grauinstrucao' },
        },
        {
          label: 'Estado Civil',
          icon: 'favorite',
          color: 'grey-8',
          to: { name: 'estadocivil' },
        },
        {
          label: 'Grupo Cliente',
          icon: 'groups',
          color: 'grey-8',
          to: { name: 'grupocliente' },
        },
      ],
    },
  ]
  return groups
})
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

        <MgPageTitle app-name="Pessoas" :home-route="{ name: 'inicio' }">
          <template v-if="$slots.tituloPagina" #default>
            <slot name="tituloPagina"></slot>
          </template>
        </MgPageTitle>

        <!-- Usuario logout -->
        <MgUserMenu :auth="auth" />

        <!-- Apps + Telas -->
        <MgAppsMenu />
        <MgScreensMenu :groups="menuGroups" />
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
