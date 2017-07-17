<template>

  <div id="row-login">

    <div class="card" id="card-login">

      <div class="card-content">
        <form @submit.prevent="login()">

          <div class="item-content">
            <div class="floating-label">
              <input required class="full-width" v-model="usuario">
              <label>Usuário</label>
            </div>
          </div>

          <div class="item-content">
            <div class="floating-label">
              <input required class="full-width" v-model="senha" type="password">
              <label>Senha</label>
            </div>
          </div>
          <br>
          <button class="primary" type="submit">
            entrar
            <i>send</i>
          </button>

        </form>
      </div>
    </div>

  </div>
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
