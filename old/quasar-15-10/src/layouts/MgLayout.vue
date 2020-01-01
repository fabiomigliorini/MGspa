<template>
  <q-layout :view="view">
    <q-layout-header v-model="header" :reveal="headerReveal">
      <q-toolbar color="primary">
        <slot name="menu">
          <q-btn flat round dense icon="menu" @click="left = !left" v-if="drawer"/>
        </slot>

        <q-toolbar-title>
          <slot name="title">Padrão</slot>
        </q-toolbar-title>

        <q-btn flat round class="within-iframe-hide" v-if="backPath" @click="$router.replace(backPath)" style="margin-right: 15px">
          <q-icon name="arrow_back" />
        </q-btn>

        <slot name="menuRight">
          <q-btn flat round dense icon="apps" @click="rightSide = !rightSide" />
        </slot>
      </q-toolbar>

      <slot name="tabHeader">
      </slot>

    </q-layout-header>

    <!-- <q-layout-footer v-model="footer" :reveal="footerReveal">
      <q-toolbar>
        <slot name="footer">
          <q-toolbar-title>
            © MG Papelaria
          </q-toolbar-title>
        </slot>
      </q-toolbar>
    </q-layout-footer> -->

    <!-- Left Side Panel -->
    <q-layout-drawer
      v-if="drawer"
      side="left"
      v-model="left"
      :overlay="leftOverlay"
      :behavior="leftBehavior"
      :breakpoint="leftBreakpoint"
    >
      <q-scroll-area class="fit">
        <slot name="drawer"></slot>
      </q-scroll-area>
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
          <q-item-main link @click.native="$router.push('/usuario/perfil')" style="cursor:pointer">
            {{ perfil.usuario }}
          </q-item-main>
          <q-item-side right>
            <q-item-tile link icon="exit_to_app"@click.native="logout" style="cursor:pointer"/>
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

    <q-page-container>
      <slot name="content">
       <router-view />
     </slot>
    </q-page-container>

    <!-- <q-toolbar>
      <q-toolbar-title>
        <slot name="footer">
          &copy; MG Papelaria
        </slot>
      </q-toolbar-title>
    </q-toolbar> -->

    <!-- Footer -->
    <!--
    <q-toolbar>
    <q-toolbar-title>
    &copy; MG Papelaria
  </q-toolbar-title>
</q-toolbar>
-->

  </q-layout>
</template>

<script>
export default {
  name: 'mg-layout',
  data () {
    return {

      view: 'lHr LpR lFr',
      header: true,
      headerReveal: true,

      footer: false,
      footerReveal: false,

      left: true,
      leftOverlay: false,
      leftBreakpoint: 996,
      leftBehavior: 'default',

      leftScroll: false,
      rightScroll: true,
      rightBreakpoint: 2000,
      hideTabs: true,
      leftSide: false,
      rightSide: false,

      // right: true,
      //
      // headerReveal: true,
      // rightOverlay: false,
      // rightBehavior: 'default',
      // leftBreakpoint: 992,
      // rightBreakpoint: 992,
      //
      // topleft: 'h',
      // topcenter: 'H',
      // topright: 'h',
      // middleleft: 'L',
      // middlecenter: 'p',
      // middleright: 'r',
      // bottomleft: 'l',
      bottomcenter: 'F',
      bottomright: 'f',
      scrolling: true
    }
  },
  components: {
  },
  computed: {
    aplicativos: {
      get () {
        return this.$store.state.aplicativos.aplicativos
      }
    },
    perfil: {
      get () {
        return this.$store.state.perfil.perfilState
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

      vm.$q.dialog({
        title: 'Sair do sistema',
        message: 'Tem certeza que deseja sair?',
        ok: 'Sair',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.get('auth/logout').then(response => {
          localStorage.removeItem('auth.token')
          localStorage.removeItem('auth.usuario.usuario')
          localStorage.removeItem('auth.usuario.codusuario')
          localStorage.removeItem('auth.usuario.avatar')
          vm.$router.push('/login')
          vm.$q.notify({
            message: 'Até mais...',
            type: 'positive',
          })
        })
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
