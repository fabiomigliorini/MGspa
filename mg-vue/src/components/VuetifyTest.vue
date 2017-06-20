<template>
    <v-app id="example-1">

      <!-- menu esquerda - contexto -->
      <v-navigation-drawer persistent clipped v-model="drawer" light enable-resize-watcher>
        <v-list dense>
          <v-list-item>
            <v-list-tile @click.native.stop="left = !left">
              <v-list-tile-action>
                <v-icon>exit_to_app</v-icon>
              </v-list-tile-action>
              <v-list-tile-content>
                <v-list-tile-title>Open Temporary Drawer</v-list-tile-title>
              </v-list-tile-content>
            </v-list-tile>
          </v-list-item>
        </v-list>
      </v-navigation-drawer>

      <!-- menu direita - aplicativos -->
      <v-navigation-drawer temporary v-model="drawerRight" right>
        <v-list dense>
          <v-list-item>
            <v-list-tile @click.native.stop="right = !right">
              <v-list-tile-action>
                <v-icon>exit_to_app</v-icon>
              </v-list-tile-action>
              <v-list-tile-content>
                <v-list-tile-title>Open Temporary Drawer</v-list-tile-title>
              </v-list-tile-content>
            </v-list-tile>
          </v-list-item>
        </v-list>
      </v-navigation-drawer>

      <!-- menu superior - titulo -->
      <v-toolbar class="yellow">

        <!-- botão menu esquerda -->
        <v-toolbar-side-icon @click.native.stop="drawer = !drawer" v-tooltip:bottom="{ html: 'Opções'}"></v-toolbar-side-icon>

        <!-- titulo -->
        <v-toolbar-title class="">Toolbar</v-toolbar-title>


        <v-spacer></v-spacer>

        <!-- botao voltar -->
        <v-btn icon v-tooltip:bottom="{ html: 'Voltar'}">
          <v-icon>arrow_back</v-icon>
        </v-btn>

        <!-- botao inicio -->
        <v-btn icon v-tooltip:bottom="{ html: 'Início'}">
          <v-icon>home</v-icon>
        </v-btn>

        <!-- Sair -->
        <v-btn icon v-tooltip:bottom="{ html: 'Sair do sistema'}" @click.native.stop="logout()">
          <v-icon>exit_to_app</v-icon>
        </v-btn>

        <!-- botao aplicativos -->
        <v-btn icon v-tooltip:bottom="{ html: 'Aplicações'}" @click.native.stop="drawerRight = !drawerRight">
          <v-icon>apps</v-icon>
        </v-btn>
      </v-toolbar>

      <main>
        <v-navigation-drawer
          temporary
          v-model="left"
        ></v-navigation-drawer>
        <v-container fluid>
          asdasd
          <!--v-router-->
        </v-container>
        <v-navigation-drawer
          right
          temporary
          v-model="right"
        ></v-navigation-drawer>
      </main>


      <v-footer class="yellow">
        <span class="black--text">© 2017</span>
      </v-footer>
    </v-app>
</template>

<script>
  export default {
    data () {
      return {
        drawer: true,
        drawerRight: false,
        right: null,
        left: null
      }
    },
    methods: {
      logout () {
        var vm = this
        window.axios.get('http://api.notmig01.teste/api/auth/logout').then(response => {
          localStorage.removeItem('auth.token')
          vm.$router.push('/Login')
        })
      }
    }
  }
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
