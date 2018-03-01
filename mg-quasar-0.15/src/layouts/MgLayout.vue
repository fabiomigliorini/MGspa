<template>
  <q-layout :view="view">
    <q-layout-header :reveal="reveal">
      <q-toolbar color="primary">
        <slot name="menu">
          <q-btn flat round dense icon="menu" @click="leftSide = !leftSide" v-if="drawer"/>
        </slot>

        <q-toolbar-title>
          <slot name="title"></slot>
        </q-toolbar-title>

        <q-btn flat round class="within-iframe-hide" v-if="backPath" @click="$router.replace(backPath)" style="margin-right: 15px">
          <q-icon name="arrow_back" />
        </q-btn>

        <slot name="menuRight">
          <q-btn flat round dense icon="apps" @click="rightSide = !rightSide" />
        </slot>

      </q-toolbar>
    </q-layout-header>

    <!-- Left Side Panel -->
    <q-layout-drawer v-model="leftSide" side="left" :breakpoint="leftBreakpoint">
      <slot name="drawer"></slot>
    </q-layout-drawer>

    <!-- Right Side Panel -->
    <q-layout-drawer v-model="rightSide" side="right" behavior="mobile">

      <q-list inset-separator>

        <q-item>
          <q-item-side link to="/inbox/1" v-if="perfil.avatar">
            <q-item-tile avatar>
              <img :src="perfil.avatar">
            </q-item-tile>
          </q-item-side>
          <q-item-main link @click="$router.push('/usuario/perfil')">
            {{ perfil.usuario }}
          </q-item-main>
          <q-item-side right>
            <q-item-tile icon="exit_to_app" @click="$router.push('/usuario/perfil')"/>
          </q-item-side>
        </q-item>

      </q-list>

      <!-- <q-list inset-separator>
        <q-item>
          <q-item-side :avatar="perfil.avatar" v-if="perfil.avatar.length > 0"/>
          <q-item-main>
            <router-link :to="{ path: '/usuario/perfil' }">
            {{ perfil.usuario }}
            </router-link>
          </q-item-main>
          <q-item-side right icon="exit_to_app" @click="logout" style="cursor:pointer"/>
        </q-item>
      </q-list> -->

      <div class="row wrap">
        <div class="text-center col-3" v-for="aplicativo in aplicativos">
          <span @click="$router.push(aplicativo.path)" style="cursor:pointer">
            <q-icon :name="aplicativo.icon" style="font-size:3em" color="primary" />
            <small class="text-primary">{{ aplicativo.title }}</small>
          </span>
        </div>

      </div>
    </q-layout-drawer>

    </q-layout-drawer>

    <q-page-container>
      <slot name="content"></slot>
    </q-page-container>

    <q-toolbar slot="footer">
      <q-toolbar-title>
        <slot name="footer">
        &copy; MG Papelaria
        </slot>
      </q-toolbar-title>
    </q-toolbar>

  </q-layout>
</template>

<script>
import {
  QLayout,
  QToolbar,
  QToolbarTitle,
  QSearch,
  QTabs,
  QRouteTab,
  QBtn,
  QIcon,
  QList,
  QItem,
  QItemSide,
  QItemMain,
  QListHeader,
  QScrollArea,
  Dialog,
} from 'quasar'

export default {
  name: 'mg-layout',
  data () {
    return {
      view: 'hHr LpR ffr',
      reveal: true,
      leftScroll: false,
      rightScroll: true,
      leftBreakpoint: 996,
      rightBreakpoint: 2000,
      hideTabs: true,
      leftSide: false,
      rightSide: false
    }
  },
  components: {
    QLayout,
    QToolbar,
    QToolbarTitle,
    QSearch,
    QTabs,
    QRouteTab,
    QBtn,
    QIcon,
    QList,
    QItem,
    QItemSide,
    QItemMain,
    QListHeader,
    QScrollArea
  },
  computed: {
    aplicativos: {
      get () {
        return this.$store.state.aplicativos.aplicativos
      }
    },
    perfil: {
      get () {
        return this.$store.state.perfil
      }
    }
  },
  props: {
    navigation: {
      type: Boolean,
      default: false
    },
    drawer: {
      type: Boolean,
      default: false
    },
    backPath: {
      type: String,
      default: null
    }
  },
  methods: {
    logout () {
      var vm = this
      Dialog.create({
        title: 'Sair do sistema',
        message: 'Tem certeza que deseja sair?',
        buttons: [
          {
            label: 'Não',
            handler () {
            }
          },
          {
            label: 'Sim',
            handler () {
              vm.$axios.get('auth/logout').then(response => {
                localStorage.removeItem('auth.token')
                localStorage.removeItem('auth.usuario.usuario')
                localStorage.removeItem('auth.usuario.codusuario')
                localStorage.removeItem('auth.usuario.avatar')
                vm.$router.push('/login')
                Notify.create('Até mais...')
              })
            }
          }
        ]
      })
    }
  }
}
</script>

<style>
.icone-app {
  font-size: 50px
}
</style>
