<template>
    <v-app>

      <!-- menu esquerda - contexto -->
      <v-navigation-drawer persistent clipped v-model="drawer" light enable-resize-watcher>
        <slot name="menu"></slot>
      </v-navigation-drawer>

      <!-- menu direita - aplicativos -->
      <v-navigation-drawer temporary v-model="drawerRight" right>
        <v-list dense>
          <v-list-group v-for="item in menu" :value="item.ativo" v-bind:key="item.titulo">

            <!-- Titulo -->
            <v-list-tile router slot="item" :to="{path: item.path}">
              <v-list-tile-action>
                <v-icon>{{ item.icone }}</v-icon>
              </v-list-tile-action>
              <v-list-tile-content>
                <v-list-tile-title>{{ item.titulo }}</v-list-tile-title>
              </v-list-tile-content>
              <v-list-tile-action v-if="item.submenu">
                <v-icon>keyboard_arrow_down</v-icon>
              </v-list-tile-action>
            </v-list-tile>

            <!-- SubMenu -->
            <v-list-item v-for="item2 in item.submenu" v-bind:key="item2.titulo">
              <v-list-tile router :to="{path: item2.path}">
                <v-list-tile-content>
                  <v-list-tile-title>{{ item2.titulo }}</v-list-tile-title>
                </v-list-tile-content>
                <v-list-tile-action>
                  <v-icon>{{ item2.icone }}</v-icon>
                </v-list-tile-action>
              </v-list-tile>
            </v-list-item>

          </v-list-group>
        </v-list>
      </v-navigation-drawer>

      <!-- menu superior - titulo -->
      <v-toolbar fixed class="yellow">

        <!-- botão menu esquerda -->
        <v-toolbar-side-icon class="blue--text" @click.native.stop="drawer = !drawer" v-tooltip:bottom="{ html: 'Opções'}"></v-toolbar-side-icon>

        <!-- titulo -->
        <v-toolbar-title class="blue--text">
          <slot name="titulo"></slot>
        </v-toolbar-title>


        <v-spacer></v-spacer>

        <!-- botao voltar -->
        <v-btn icon v-tooltip:bottom="{ html: 'Voltar'}" @click.native.stop="voltar()" class="blue--text">
          <v-icon>arrow_back</v-icon>
        </v-btn>

        <!-- Sair -->
        <v-btn icon v-tooltip:bottom="{ html: 'Sair do sistema'}" @click.native.stop="logout()" class="blue--text">
          <v-icon>exit_to_app</v-icon>
        </v-btn>

        <!-- botao aplicativos -->
        <v-btn icon v-tooltip:bottom="{ html: 'Aplicações'}" @click.native.stop="drawerRight = !drawerRight" class="blue--text">
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
    data () {
      return {
        drawer: true,
        drawerRight: false,
        menu: [
          {
            icone: 'home',
            titulo: 'Início',
            path: '/'
          },
          {
            icone: 'toys',
            titulo: 'Estoque',
            submenu: [
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
