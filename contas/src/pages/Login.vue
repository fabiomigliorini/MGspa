<template>
  <q-layout>
    <q-page-container>
      <q-page class="flex bg-image flex-center"> </q-page>
    </q-page-container>
  </q-layout>
</template>

<script>
import { useRoute } from 'vue-router'
import { authStore } from 'stores/auth'
import { pessoaStore } from 'src/stores/pessoa'
const auth = authStore()


const verificaAuth = async () => {
  const route = useRoute()
  const sPessoa = pessoaStore()
  auth.urlRetorno = "teste";
  // const urlParams = new URLSearchParams(window.location.search);
  // const Token = urlParams.get("accesstoken");
  if(route.redirectedFrom && route.redirectedFrom.href != "#/") {
    sPessoa.urlRetorno = route.redirectedFrom.href
  }
  let tokenCookie = document.cookie.split(";").find((c) => c.trim().startsWith("access_token="));
  if (tokenCookie) {
    const Token = tokenCookie.split("=")[1];
    localStorage.setItem('access_token', Token);

    if (Token) {
      auth.accessToken(Token);
      setTimeout(function () {
        window.location.replace("/" + sPessoa.urlRetorno)
        sPessoa.urlRetorno = "#/"
      }, 1000);
    }
  }



  if ((localStorage.getItem('access_token'))) {
    // window.location = "#/"
  } else {
    let url = new URL(window.location.href)
    url = encodeURI(url.origin)
    window.location.href = process.env.API_AUTH_URL + '/login?redirect_uri=' + url
  }
}

export default {
  setup(){
  },

  mounted() {
    verificaAuth();
  }
}
</script>

<style>
  .bg-image {
    background-repeat: no-repeat;
    background-size: cover;
  }
</style>
