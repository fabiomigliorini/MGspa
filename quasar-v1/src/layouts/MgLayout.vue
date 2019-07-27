<template>
  <q-layout :view="view">
    <q-header v-model="header" :reveal="headerReveal">
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

      <slot name="tabHeader"></slot>

    </q-header>

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
    <q-drawer
      bordered
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
    </q-drawer>

    <!-- Right Side Panel -->
    <q-drawer v-model="rightSide" side="right" behavior="mobile" bordered>

      <q-item>
        <q-item-section avatar link to="/inbox/1" v-if="perfil.avatar">
          <img :src="perfil.avatar">
        </q-item-section>

        <q-item-section @click.native="$router.push('/usuario/perfil')" class="cursor-pointer text-subtitle1">
          {{ perfil.usuario }}
          <q-tooltip anchor="center left" self="center middle">
            Ver meu perfil
          </q-tooltip>
        </q-item-section>

        <q-item-section avatar @click.native="logout" class="cursor-pointer">
          <q-btn icon="exit_to_app" flat/>
          <q-tooltip>
            Sair do sistema
          </q-tooltip>
        </q-item-section>
      </q-item>

      <q-separator/>



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

      <div class="row">
        <div class="text-center col-4" v-for="aplicativo in aplicativos">

          <q-item @click.native="$router.push(aplicativo.path)" style="cursor:pointer">

            <!--<q-item-section avatar>-->
              <!--<q-icon :name="aplicativo.icon" color="primary"/>-->
            <!--</q-item-section>-->
            <!--<q-item-section>-->
              <!--<small class="text-primary">{{ aplicativo.title }}</small>-->
            <!--</q-item-section>-->


            <q-item-section>
              <q-item-label>
                <q-icon size="25px" :name="aplicativo.icon" color="primary"/>
              </q-item-label>
              <q-item-label caption class="text-primary">
                {{ aplicativo.title }}
              </q-item-label>
            </q-item-section>

          </q-item>

        </div>

      </div>
    </q-drawer>

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
      let vm = this;
      this.$q.dialog({
        cancel: 'Cancelar',
        persistent: true,
        title: 'Sair do sistema',
        message: 'Tem certeza que deseja sair do sistema?'
      }).onOk(() => {
        vm.$axios.get('auth/logout').then(response => {
          localStorage.removeItem('auth.token');
          localStorage.removeItem('auth.usuario.usuario');
          localStorage.removeItem('auth.usuario.codusuario');
          localStorage.removeItem('auth.usuario.avatar');
          vm.$router.push('/login');
          vm.$q.notify({
            message: 'Até mais...',
            color: 'positive',
          })
        })
      }).onCancel(() => {});

    }
  }
}
</script>

<style>
.icone-app {
  font-size: 50px
}
.space-end{
  margin-bottom: 100px;
}
</style>
