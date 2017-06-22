<template>
    <v-app>

      <!-- menu esquerda - contexto -->
      <v-navigation-drawer persistent clipped v-model="menuContexto" light>
        <slot name="menu"></slot>
      </v-navigation-drawer>

      <!-- menu direita - aplicativos -->
      <v-navigation-drawer temporary v-model="menuApps" right light>
        <v-list dense>
          <v-list-group v-for="item in apps" :value="item.ativo" v-bind:key="item.titulo" disable-route-watcher>

            <!-- Titulo -->
            <v-list-tile router slot="item" :to="{path: item.path}">
              <v-list-tile-action>
                <v-icon>{{ item.icone }}</v-icon>
              </v-list-tile-action>
              <v-list-tile-content>
                <v-list-tile-title>{{ item.titulo }}</v-list-tile-title>
              </v-list-tile-content>
              <v-list-tile-action v-if="item.subapps">
                <v-icon>keyboard_arrow_down</v-icon>
              </v-list-tile-action>
            </v-list-tile>

            <!-- SubMenu -->
            <v-list-item v-for="subapp in item.subapps" v-bind:key="subapp.titulo">
              <v-list-tile router :to="{path: subapp.path}">
                <v-list-tile-content>
                  <v-list-tile-title>{{ subapp.titulo }}</v-list-tile-title>
                </v-list-tile-content>
                <v-list-tile-action>
                  <v-icon>{{ subapp.icone }}</v-icon>
                </v-list-tile-action>
              </v-list-tile>
            </v-list-item>

          </v-list-group>
        </v-list>
      </v-navigation-drawer>

      <!-- menu superior - titulo -->
      <v-toolbar fixed class="yellow">

        <!-- botão menu esquerda -->
        <v-toolbar-side-icon class="blue--text" @click.native.stop="menuContexto = !menuContexto" v-tooltip:bottom="{ html: 'Opções'}"></v-toolbar-side-icon>

        <!-- titulo -->
        <v-toolbar-title class="blue--text">
          <slot name="titulo"></slot>
        </v-toolbar-title>


        <!--
        <v-spacer></v-spacer>
        -->

        <!-- botao voltar -->
        <!--
        <v-btn icon v-tooltip:bottom="{ html: 'Voltar'}" @click.native.stop="voltar()" class="blue--text">
          <v-icon>arrow_back</v-icon>
        </v-btn>
        -->

        <!-- Sair -->
        <!--
        <v-btn icon v-tooltip:bottom="{ html: 'Sair do sistema'}" @click.native.stop="logout()" class="blue--text">
          <v-icon>exit_to_app</v-icon>
        </v-btn>
        -->

        <!-- botao aplicativos -->
        <v-btn icon v-tooltip:bottom="{ html: 'Aplicações'}" @click.native.stop="menuApps = !menuApps" class="blue--text">
          <v-icon>apps</v-icon>
        </v-btn>

      </v-toolbar>

      <main>
        <slot name="conteudo"></slot>
      </main>


      <v-footer fixed class="blue">
        <span class="yellow--text"><slot name="rodape">© 2017</slot></span>
      </v-footer>
    </v-app>
</template>

<script>
  export default {
    name: 'mg-layout',
    props: {
      menu: {
        type: Boolean,
        default: false
      }
    },
    mounted () {
      this.menuContexto = this.menu
    },
    data () {
      return {
        menuContexto: false,
        menuApps: false,
        apps: [
          {
            icone: 'home',
            titulo: 'Início',
            path: '/'
          },
          {
            icone: 'toys',
            titulo: 'Estoque',
            subapps: [
              { titulo: 'Marcas', path: '/marca' }
            ]
          }
        ]
      }
    },
    methods: {
      logout () {
        var vm = this
        window.axios.get('auth/logout').then(response => {
          localStorage.removeItem('auth.token')
          vm.$router.push('/Login')
        })
      },
      voltar () {
        console.log('aqio')
        this.$router.go(-1)
      }
    }
  }
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

  main {
    margin-bottom: 45px
  }

  .component-fade-enter-active, .component-fade-leave-active {
    transition: opacity .3s ease;
  }
  .component-fade-enter, .component-fade-leave-to {
    opacity: 0;
  }

</style>
