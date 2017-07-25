<template>

  <div id="row-login">

    <!-- <q-card>
      <q-card-title>
        Card Title
      </q-card-title>
      <q-card-separator />
      <q-card-main>
        Card Content
      </q-card-main>
    </q-card> -->

    <div class="card" id="card-login">

      <div class="card-content">
        <form @submit.prevent="login()">

          <div class="item-content">
            <q-input v-model="usuario" float-label="Usuário" />
          </div>

          <div class="item-content">
            <q-input v-model="senha" type="password" float-label="Senha" />
          </div>
          <br>
          <q-btn color="primary" icon="send" type="submit">
            entrar
          </q-btn>
        </form>
      </div>
    </div>

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

#q-app {
  width: 100%;
  height: 100%;
}

#row-login {
  background-image: url("/statics/fundo-login.jpg");
  background-position: center center;
  background-repeat:  no-repeat;
  background-attachment: fixed;
  background-size:  cover;
  background-color: #999;

  width: 100%;
  height: 100%;

  display: flex;
  align-items: center;

  justify-content: center;
}

#card-login {
  background-color: rgba(255, 255, 255, 0.8);
  text-align: center;
  width: 280px;
}
</style>
