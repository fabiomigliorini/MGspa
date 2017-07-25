<template>
<q-layout
  ref="layout"
  :view="view"
  :left-breakpoint="leftBreakpoint"
  :right-breakpoint="rightBreakpoint"
  :reveal="reveal"
>
  <q-toolbar slot="header">
    <slot name="menu">
      <q-btn flat @click="$refs.layout.toggleLeft()" v-if="drawer">
        <q-icon name="menu" />
      </q-btn>
    </slot>

    <q-toolbar-title>
      <slot name="title"></slot>
    </q-toolbar-title>

    <q-btn class="within-iframe-hide" v-if="backPath" flat @click="$router.replace(backPath)" style="margin-right: 15px">
      <q-icon name="arrow_back" />
    </q-btn>

    <slot name="menuRight">
      <q-btn flat @click="$refs.layout.toggleRight()">
        <q-icon name="apps" />
      </q-btn>
    </slot>

  </q-toolbar>

  <q-tabs slot="navigation" v-if="navigation">
    <slot name="navigation"></slot>
  </q-tabs>

  <q-scroll-area slot="left" style="width: 100%; height: 100%"  v-if="drawer">
    <slot name="drawer"></slot>
  </q-scroll-area>

  <q-scroll-area slot="right" style="width: 100%; height: 100%">
    <q-list inset-separator>
      <q-item>
        <q-item-side :avatar="'https://randomuser.me/api/portraits/men/3.jpg'" />
        <q-item-main :label="'Usuário'" />
        <q-item-side right icon="exit_to_app" @click="logout" style="cursor:pointer"/>
      </q-item>
    </q-list>

    <div class="row wrap">

      <div class="text-center col-3" v-for="aplicativo in aplicativos">
        <router-link :to="{ path: aplicativo.path, params: {} }">
          <q-icon :name="aplicativo.icon" style="font-size:3em" />
          <br>
          <small>
            {{aplicativo.title}}
          </small>
        </router-link>
      </div>

    </div>
  </q-scroll-area>

  <slot name="content"></slot>

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
  QSideLink,
  QListHeader,
  QScrollArea,
  Dialog,
  Toast
} from 'quasar'

export default {
  data () {
    return {
      view: 'hHr LpR fFr',
      reveal: true,
      leftScroll: false,
      rightScroll: true,
      leftBreakpoint: 996,
      rightBreakpoint: 2000,
      hideTabs: true
      // right: false
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
    QSideLink,
    QListHeader,
    QScrollArea
  },
  computed: {
    aplicativos: function () {
      const aplicativos = this.$store.getters['aplicativos/listagem']
      return aplicativos
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
              window.axios.get('auth/logout').then(response => {
                localStorage.removeItem('auth.token')
                vm.$router.push('/login')
                Toast.create('Até mais...')
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
