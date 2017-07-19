<template>
<q-layout>

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
</template>

<script>
import { Dialog, Toast } from 'quasar'

export default {
  data () {
    return {
    }
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
                  /*
      */
    }
  }
}
</script>

<style>
.icone-app {
  font-size: 50px
}
</style>
