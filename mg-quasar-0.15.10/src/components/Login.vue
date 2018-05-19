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
  },
  created() {
    //do something after creating vue instance
  },
  methods: {

    login: function (e) {
      var vm = this
      let data = {
        usuario: vm.usuario,
        senha: vm.senha
      }
      // Busca Autenticacao
      vm.$axios.post('auth/login', data).then(response => {
        // salva token no Local Storage
        let token = response.data.token
        localStorage.setItem('auth.token', token)

        vm.$axios.get('auth/user').then(response => {
          // salva código da imagem avatar do usuário
          localStorage.setItem('auth.usuario.avatar', response.data.user.avatar)
          localStorage.setItem('auth.usuario.usuario', response.data.user.usuario)
          localStorage.setItem('auth.usuario.codusuario', response.data.user.codusuario)
          this.$store.commit('perfil/updatePerfil', {
            usuario: localStorage.getItem('auth.usuario.usuario'),
            avatar: localStorage.getItem('auth.usuario.avatar'),
            codusuario: localStorage.getItem('auth.usuario.codusuario')
          })
        }).catch(error => {
          console.log(error.response)
        })
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
#row-login {
  background-image: url("/assets/images/fundo-login.jpg");
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
