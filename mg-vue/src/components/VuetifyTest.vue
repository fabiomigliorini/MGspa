<template>
    <v-app id="example-1">

      <!-- menu esquerda - contexto -->
      <v-navigation-drawer persistent clipped v-model="drawer" light enable-resize-watcher>
        <v-list dense>
          <v-list-item>
            <v-list-tile>
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
            <v-list-tile>
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
      <v-toolbar fixed class="yellow">

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
        <v-container fluid>
          asdasd
          <v-fab error @click.native.stop="testeFab()">
            <v-icon light>add</v-icon>
          </v-fab>
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <hr>
          teste
          <!--
          <v-btn error floating id="btn-adicionar">
            <v-icon light>add</v-icon>
          </v-btn>
        -->
          <!--v-router-->
        </v-container>
      </main>


      <v-footer fixed class="yellow">
        <span class="black--text">© 2017</span>
      </v-footer>
    </v-app>
</template>

<script>
  export default {
    data () {
      return {
        drawer: true,
        drawerRight: false
      }
    },
    methods: {
      logout () {
        var vm = this
        window.axios.get('http://api.notmig01.teste/api/auth/logout').then(response => {
          localStorage.removeItem('auth.token')
          vm.$router.push('/Login')
        })
      },

      testeFab () {
        console.log('aqui no fab')
      }
    }
  }
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
  #btn-adicionar {
    position: fixed;
    bottom: 50px;
    right: 15px
  }
</style>
