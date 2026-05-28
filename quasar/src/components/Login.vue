<template>
  <div id="row-login">
    <q-card id="card-login">
      <q-card-section class="text-center">
        <q-spinner color="primary" size="3em" />
        <p class="q-mt-md">{{ status }}</p>
      </q-card-section>
    </q-card>
  </div>
</template>

<script>
import auth from '../services/auth'

export default {
  name: 'login',
  data () {
    return {
      status: 'Processando login...'
    }
  },
  mounted () {
    // Padrão SSO (api-dev): após autenticar em https://api-dev.mgpapelaria.com.br/login,
    // o backend redireciona de volta para cá com o cookie `access_token` setado em
    // .mgpapelaria.com.br. Aqui só capturamos o cookie, gravamos em localStorage e
    // redirecionamos pra urlRetorno (ou /).
    setTimeout(() => {
      const cookies = document.cookie.split(';')
      const tokenCookie = cookies.find(c => c.trim().startsWith('access_token='))

      if (tokenCookie) {
        const token = tokenCookie.split('=')[1]
        auth.gravarToken(token)
        this.status = 'Redirecionando...'
        const urlRetorno = auth.consumirUrlRetorno()
        this.$router.replace(urlRetorno || '/')
      } else {
        this.status = 'Cookie de autenticação não encontrado — redirecionando para o login...'
        setTimeout(() => {
          auth.redirecionarParaLogin('/')
        }, 1500)
      }
    }, 300)
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
  background-color: rgba(255, 255, 255, 0.9);
  margin: 0 auto;
  width: 320px;
}
</style>
