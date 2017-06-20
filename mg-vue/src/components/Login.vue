<template>
  <v-layout row class="layout-login">
    <v-flex xs12 sm4 offset-sm4 lg3 offset-lg4>
    <v-card id="card-login">
        <v-card-text>
          <form @submit.prevent="login()">
          <v-container>

            <v-layout row>
              <v-flex xl4 xs12>
                <v-text-field
                  name="usuario"
                  label="Usuário"
                  v-model="usuario"
                  type="text"
                ></v-text-field>
              </v-flex>
            </v-layout>

            <v-layout row>
              <v-flex xl4 xs12>
                <v-text-field
                  name="senha"
                  label="Senha"
                  v-model="senha"
                  type="password"
                ></v-text-field>
              </v-flex>
            </v-layout>

            <v-layout row>
              <v-flex xl4 xs12>
                <v-btn block light primary type="submit">
                  entrar
                  <v-icon right light>send</v-icon>
                </v-btn>
              </v-flex>
            </v-layout>

          </v-container>
        </form>
        </v-card-text>
    </v-card>
  </v-flex>
  </v-layout>
</template>

<script>
export default {

  name: 'login',

  data () {
    return {
      usuario: 'fabio',
      senha: 'baseteste',
      erro: false,
      mensagem: 'mensagem'
    }
  },

  methods: {

    login: function (e) {
      var vm = this
      // Busca Autenticacao
      window.axios.post('http://api.notmig01.teste/api/auth/login', {
        usuario: this.usuario,
        senha: this.senha
      }).then(response => {
        // salva token no Local Storage
        var token = response.data.token
        localStorage.setItem('auth.token', token)
        // console.log(response)
        vm.$router.push('/')
      }).catch(error => {
        // Mensagem de erro
        console.log('erro no login')
        this.erro = true
        this.mensagem = error.response.data.mensagem
      })
    }

  }

}
</script>

<style>
.layout-login {
  background-image: url("/static/imagens/fundo-login.jpg");
  background-position: center center;
  background-repeat:  no-repeat;
  background-attachment: fixed;
  background-size:  cover;
  background-color: #999;
  height: 100%;
  /* border: 5px solid yellow; */
  display: table-cell;
  vertical-align: middle;
}

#app {
  display: table;
  width: 100%;
}

#card-login {
  background-color: rgba(255, 255, 255, 0.9);
}
</style>
