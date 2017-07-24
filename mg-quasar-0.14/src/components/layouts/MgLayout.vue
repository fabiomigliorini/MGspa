<template>
<!-- <q-layout>

  <div slot="header" class="toolbar">

  <slot name="menu">
    <button class="hide-on-drawer-visible" @click="$refs.drawer.toggle()">
      <i>menu</i>
    </button>
  </slot>

    <q-toolbar-title :padding="1">
      <slot name="title"></slot>
    </q-toolbar-title>

    <slot name="rightMenu">
      <button @click="$refs.rightDrawer.open()">
        <i>apps</i>
      </button>
    </slot>

  </div>

  <q-tabs slot="navigation" v-if="navigation">
    <slot name="navigation"></slot>
  </q-tabs>

  <div slot="left" v-if="drawer">
    <slot name="drawer"></slot>
  </div>


  <div class="layout-view">
    <slot name="content"></slot>
  </div>

  <q-drawer ref="rightDrawer" swipe-only right-side>


    <div class="list">

      <div class="item">
        <img class="item-primary" :src="'https://randomuser.me/api/portraits/men/3.jpg'">
        <div class="item-content has-secondary">
          Usuário
        </div>
        <div class="item-secondary">
          <a @click="logout()">
            <i>
              exit_to_app
            </i>
          </a>
        </div>
      </div>
    </div>

    <div class="row wrap">

      <div class="text-center width-1of5" v-for="aplicativo in aplicativos">
        <router-link :to="{ path: aplicativo.path, params: {} }">
          <i class="icone-app">{{aplicativo.icon}}</i>
          <br>
          <small>
            {{aplicativo.title}}
          </small>
        </router-link>
      </div>

    </div>
  </q-drawer>

  <div slot="footer" class="toolbar">
    <slot name="footer">
      &copy; MG Papelaria
    </slot>
  </div>

</q-layout>
-->
<q-layout
  ref="layout"
  :view="view"
  :left-breakpoint="leftBreakpoint"
  :right-breakpoint="rightBreakpoint"
  :reveal="reveal"
>
  <q-toolbar slot="header">
    <q-btn flat @click="$refs.layout.toggleLeft()">
      <q-icon name="menu" />
    </q-btn>

    <q-toolbar-title>
      <slot name="title"></slot>
      <!-- <span slot="subtitle">Empowering your app</span> -->
    </q-toolbar-title>


    <q-btn class="within-iframe-hide" flat @click="$router.replace('/showcase')" style="margin-right: 15px">
      <q-icon name="arrow_back" />
    </q-btn>
    <q-btn flat @click="$refs.layout.toggleRight()">
      <q-icon name="apps" />
    </q-btn>
  </q-toolbar>

  <q-tabs slot="navigation" v-if="!hideTabs">
    <q-route-tab slot="title" icon="play_circle_outline" to="/showcase/layout/play-with-layout" replace label="Play with Layout" />
    <q-route-tab slot="title" icon="view_array" to="/showcase/layout/drawer-panels" replace label="Drawer Panels" />
    <q-route-tab slot="title" icon="pin_drop" to="/showcase/layout/fixed-positioning" replace label="Fixed Positioning" />
    <q-route-tab slot="title" icon="play_for_work" to="/showcase/layout/floating-action-button" replace label="Floating Action Button" />
  </q-tabs>

  <q-scroll-area slot="left" style="width: 100%; height: 100%">
    <q-list-header>Menu</q-list-header>
    <q-side-link item to="/showcase/layout/play-with-layout">
      <q-item-side icon="account circle" />
      <q-item-main label="Play with Layout" sublabel="Learn more about it" />
      <q-item-side right icon="thumb_up" />
    </q-side-link>
    <q-side-link item to="/showcase/layout/drawer-panels">
      <q-item-side icon="view_array" />
      <q-item-main label="Drawer Panels" sublabel="Layout left/right sides" />
    </q-side-link>
    <q-side-link item to="/showcase/layout/fixed-positioning">
      <q-item-side icon="pin_drop" />
      <q-item-main label="Fixed Positioning" sublabel="...on a Layout" />
    </q-side-link>
    <q-side-link item to="/showcase/layout/floating-action-button">
      <q-item-side icon="play_for_work" />
      <q-item-main label="Floating Action Button" sublabel="For Page actions" />
    </q-side-link>

    <!--
    <div v-if="leftScroll" style="padding: 25px 16px 16px;">
      <p class="caption" v-for="n in 50">
        <em>Left Panel has intended scroll</em>
      </p>
    </div>
    -->

  </q-scroll-area>

  <q-scroll-area slot="right" style="width: 100%; height: 100%">
    <q-list-header>Apps</q-list-header>
    <q-side-link v-for="aplicativo in aplicativos" item :to="aplicativo.path" :key="aplicativo.title">
      <q-item-side :icon="aplicativo.icon" />
      <q-item-main :label="aplicativo.title" sublabel="Learn more about it" />
    </q-side-link>

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
  QItemSide,
  QItemMain,
  QSideLink,
  QListHeader,
  QScrollArea,
  // Dialog,
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
      rightBreakpoint: 1200,
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
    QItemSide,
    QItemMain,
    QSideLink,
    QListHeader,
    QScrollArea,
    // Dialog,
    Toast
  },
  computed: {
    aplicativos: function () {
      const aplicativos = this.$store.getters['aplicativos/listagem']
      return aplicativos
    }
  },
  props: {
    // navigation: {
    //   type: Boolean,
    //   default: false
    // },
    // drawer: {
    //   type: Boolean,
    //   default: false
    // }
  },
  methods: {
    logout () {
      // var vm = this
      // Dialog.create({
      //   title: 'Sair do sistema',
      //   message: 'Tem certeza que deseja sair?',
      //   buttons: [
      //     {
      //       label: 'Não',
      //       handler () {
      //       }
      //     },
      //     {
      //       label: 'Sim',
      //       handler () {
      //         window.axios.get('auth/logout').then(response => {
      //           localStorage.removeItem('auth.token')
      //           vm.$router.push('/login')
      //           Toast.create('Até mais...')
      //         })
      //       }
      //     }
      //   ]
      // })
    }
  }
}
</script>

<style>
.icone-app {
  font-size: 50px
}
</style>
