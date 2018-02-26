<template>
  <q-layout :view="view">
    <q-layout-header>
      <q-toolbar color="primary">
        <slot name="menu">
          <q-btn flat round dense icon="menu" @click="leftSide = !leftSide" v-if="drawer"/>
        </slot>

        <q-toolbar-title>
          <slot name="title"></slot>
        </q-toolbar-title>

        <q-btn class="within-iframe-hide" v-if="backPath" flat @click="$router.replace(backPath)" style="margin-right: 15px">
          <q-icon name="arrow_back" />
        </q-btn>

        <slot name="menuRight">
          <q-btn flat round dense icon="apps" @click="rightSide = !rightSide" />
        </slot>

      </q-toolbar>
    </q-layout-header>

    <!-- Left Side Panel -->
    <q-layout-drawer v-model="leftSide" side="left">
      <slot name="drawer"></slot>
    </q-layout-drawer>

    <!-- Right Side Panel -->
    <q-layout-drawer v-model="rightSide" side="right" overlay>
      Right Side of Layout
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
  // computed: {
  //   aplicativos: function () {
  //     const aplicativos = this.$store.getters['aplicativos/listagem']
  //     return aplicativos
  //   },
  //   perfil: function () {
  //     const perfil = this.$store.getters['perfil/usuario']
  //     return perfil
  //   }
  // },
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
              window.axios.get('auth/logout').then(response => {
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
