<template>
<q-layout>

  <div slot="header" class="toolbar">

    <button class="hide-on-drawer-visible" @click="$refs.drawer.toggle()">
      <i>menu</i>
    </button>

    <q-toolbar-title :padding="1">
      <slot name="title"></slot>
    </q-toolbar-title>

    <button @click="$refs.rightDrawer.open()">
      <i>apps</i>
    </button>

  </div>

  <q-tabs slot="navigation" v-if="navigation">
    <slot name="navigation"></slot>
  </q-tabs>

  <q-drawer ref="drawer" v-if="drawer">
    <slot name="drawer"></slot>
  </q-drawer>

  <!--
  <router-view class="layout-view"></router-view>
  -->

  <div class="layout-view">
    <slot name="content"></slot>
  </div>

  <q-drawer ref="rightDrawer" swipe-only right-side>


    <div class="list">

      <div class="item">
        <img class="item-primary" :src="'https://randomuser.me/api/portraits/men/3.jpg'">
        <div class="item-content has-secondary">
          Fabio Migliorini
        </div>
        <i class="item-secondary">
          exit_to_app
        </i>
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
</template>

<script>
export default {
  data () {
    return {
      aplicativos: [
        {
          icon: 'home',
          title: 'Início',
          path: '/'
        },
        {
          icon: 'label_outline',
          title: 'Marcas',
          path: '/marca'
        }
      ]
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
    }
  }
}
</script>

<style>
.icone-app {
  font-size: 50px
}
</style>
