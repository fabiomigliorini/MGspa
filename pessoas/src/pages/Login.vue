<template>
  <q-layout>
    <q-page-container>
      <q-page class="flex bg-image flex-center">
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script>
import { defineComponent, onMounted } from 'vue'
import { guardaToken } from 'stores/index'
import { useRoute, useRouter } from 'vue-router'
import { pessoaStore } from 'src/stores/pessoa'
const auth = guardaToken()

export default defineComponent({
  setup() {

    const route = useRoute()
    const sPessoa = pessoaStore()

    // Se existir o token redireciona o usuario para home
    const verificaauth = async () => {

      const urlParams = new URLSearchParams(window.location.search);
      const Token = urlParams.get("accesstoken");

        if(route.redirectedFrom && route.redirectedFrom.href != "#/") {
          sPessoa.urlRetorno = route.redirectedFrom.href
        }
        
      if (Token) {
        auth.accessToken(Token);
        setTimeout(function () {
          window.location.replace("/" + sPessoa.urlRetorno)
          sPessoa.urlRetorno = "#/"
        }, 1000);
      }

      if ((localStorage.getItem('access_token'))) {
        window.location = "#/"
      } else {
        window.location = process.env.OAUTH_URL + "?state=quasar-v2"
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