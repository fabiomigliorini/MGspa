<template>
  <q-layout>
    <q-page-container>
      <q-page class="flex bg-image flex-center"> </q-page>
    </q-page-container>
  </q-layout>
</template>

<script>
import { defineComponent, onMounted } from 'vue'
import { useAuthStore } from 'stores/index'
import { useRoute } from 'vue-router'

export default defineComponent({
  setup() {
    const route = useRoute()
    const auth = useAuthStore()

    // Se existir o token redireciona o usuario para home
    const verificaauth = async () => {
      const urlRetorno = route.query.redirect || '/'

      let tokenCookie = document.cookie.split(';').find((c) => c.trim().startsWith('access_token='))
      if (tokenCookie) {
        const Token = tokenCookie.split('=')[1]
        if (Token) {
          auth.gravarToken(Token)
          window.location.replace(urlRetorno)
          return
        }
      }

      if (localStorage.getItem('access_token')) {
        window.location.replace(urlRetorno)
      } else {
        let url = encodeURIComponent(window.location.origin + urlRetorno)
        window.location.href = process.env.API_AUTH_URL + '/login?redirect_uri=' + url
      }
    }

    onMounted(() => {
      verificaauth()
    })

    return {
      verificaauth,
    }
  },
})
</script>

<style>
.bg-image {
  background-repeat: no-repeat;
  background-size: cover;
}
</style>
