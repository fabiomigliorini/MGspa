<template>

  <div id="row-login">

    <q-card id="card-login">
      <q-card-section>
        <form @submit.prevent="login()">

          <div class="item-content">
            <q-input v-model="username" label="Usuário" autofocus />
          </div>

          <div class="item-content">
            <q-input v-model="password" type="password" label="Senha" />
          </div>
          <br>
          <q-btn color="primary" icon-right="send" type="submit">
            entrar
          </q-btn>
          <q-btn color="red" style="margin-top: 10px;">
            <a href="http://api-mgspa-dev.mgpapelaria.com.br/api/quasar" style="color:white">
              Login usando MGSpa
            </a>
          </q-btn>
        </form>
      </q-card-section>
    </q-card>

  </div>
</template>

<script>
import axios from 'axios'
//import env
// require('dotenv').config()
export default {
  name: 'login',
  data() {
    return {
      username: null,
      password: null,
      erro: false,
      mensagem: 'mensagem'
    }
  },

  components: {
  },
  mounted() {
    var vm = this
    vm.$axios.get('auth/user').then(response => {
      localStorage.setItem('auth.usuario.avatar', response.data.avatar)
      localStorage.setItem('auth.usuario.usuario', response.data.usuario)
      localStorage.setItem('auth.usuario.codusuario', response.data.codusuario)
      this.$store.commit('perfil/updatePerfil', {
        usuario: localStorage.getItem('auth.usuario.usuario'),
        avatar: localStorage.getItem('auth.usuario.avatar'),
        codusuario: localStorage.getItem('auth.usuario.codusuario')
      })
      vm.$router.push('/')
    }).catch(error => {
      let url = new URL(window.location.href)
      url = encodeURI(url.origin)
      window.location.href = process.env.API_AUTH_URL + '/login?redirect_uri=' + url
    });
  },
  created() {
  },
  methods: {
    login: function (e) {
      var vm = this
      let data = {
        username: vm.username,
        password: vm.password,
        grant_type: 'password',
        client_id: process.env.API_AUTH_CLIENT_ID,
        client_secret: process.env.API_AUTH_CLIENT_SECRET
      }
      axios.post(process.env.API_AUTH_URL + '/api/oauth/token', data, { withCredentials: true }).then(response => {
        // salva token no Local Storage
        let token = response.data.access_token
        localStorage.setItem('auth.token', token)
        vm.$axios.get('auth/user').then(response => {
          // salva código da imagem avatar do usuário
          localStorage.setItem('auth.usuario.avatar', response.data.avatar)
          localStorage.setItem('auth.usuario.usuario', response.data.usuario)
          localStorage.setItem('auth.usuario.codusuario', response.data.codusuario)
          this.$store.commit('perfil/updatePerfil', {
            usuario: localStorage.getItem('auth.usuario.usuario'),
            avatar: localStorage.getItem('auth.usuario.avatar'),
            codusuario: localStorage.getItem('auth.usuario.codusuario')
          })
          vm.$router.push('/')
        }).catch(error => {
          console.log(error.response)
        })

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
  background-color: #464646;
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
