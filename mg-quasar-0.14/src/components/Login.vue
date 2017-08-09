<template>

  <div id="row-login">

    <q-card id="card-login">
      <q-card-main>
        <form @submit.prevent="login()">

          <div class="item-content">
            <q-input v-model="usuario" float-label="Usuário" autofocus />
          </div>

          <div class="item-content">
            <q-input v-model="senha" type="password" float-label="Senha" />
          </div>
          <br>
          <q-btn color="primary" icon-right="send" type="submit">
            entrar
          </q-btn>
        </form>
      </q-card-main>
    </q-card>

  </div>
</template>

<script>
import {
  QBtn,
  QIcon,
  QInput,
  QCard,
  QCardMain
} from 'quasar'

export default {
  name: 'login',
  data () {
    return {
      usuario: null,
      senha: null,
      erro: false,
      mensagem: 'mensagem'
    }
  },

  components: {
    QBtn,
    QIcon,
    QInput,
    QCard,
    QCardMain
  },

  methods: {

    login: function (e) {
      var vm = this
      // Busca Autenticacao
      window.axios.post('auth/login', {
        usuario: this.usuario,
        senha: this.senha
      }).then(response => {
        // salva token no Local Storage
        let token = response.data.token
        localStorage.setItem('auth.token', token)

        window.axios.get('auth/user').then(response => {
          console.log(response.data)
          // salva código da imagem avatar do usuário
          let codimagem = response.data.user.codimagem
          localStorage.setItem('auth.codimagem', codimagem)
        }).catch(error => {
          console.log(error.response)
        })

        vm.$router.go(-1)
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
#row-login {
  background-image: url("/statics/fundo-login.jpg");
  background-position: center center;
  background-repeat: no-repeat;
  background-attachment: fixed;
  background-size: cover;
  background-color:#464646;
  height: 100%;
  padding-top: 10%;
}

#q-app {
  width: 100%;
  height: 100%;
  position: absolute;
}

#card-login {
  background-color: rgba(255, 255, 255, 0.8);
  margin: 0 auto;
  width: 280px;
}
</style>
