<template>
    <q-layout>
      <q-page-container>
        <q-page class="flex bg-image flex-center">
        </q-page>
      </q-page-container>
    </q-layout>
  </template>
    
  <script>
  import { defineComponent, nextTick, onMounted } from 'vue'
  import { api } from 'boot/axios'
  import { reactive } from 'vue'
  import { guardaToken } from 'stores/index'
  import { Notify } from 'quasar'
  import { ref } from 'vue'
  
  const auth = guardaToken()
  
  // Se existir o token redireciona o usuario para home
  const verificaauth = async () => {
  
    const urlParams = new URLSearchParams(window.location.search);
    const Token = urlParams.get("accesstoken");
    if (Token) {
      auth.accessToken(Token);
      setTimeout(function () {
        window.location.replace("/")
      }, 1000);
    }
  
    if ((localStorage.getItem('access_token'))) {
      window.location = "#/"
    } else {
      window.location = process.env.OAUTH_URL + "?state=quasar-notas"
    }
  }
  
  const user = reactive({
    username: '',
    password: ''
  })
  
  async function login() {
  
    try {
      const { data } = await api.post('v1/auth/login', user)
      if (data.access_token) {
        auth.accessToken(data.access_token)
        window.location = "#/"
      } else {
        Notify.create({
          type: 'negative',
          message: 'Usuário ou senha incorretos!'
        })
      }
    } catch (error) {
      console.log(error?.response?.data)
    }
  }
  
  export default defineComponent({
    setup() {
  
      onMounted(() => {
        verificaauth()
      })
  
      return {
        user,
        verificaauth,
        login
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
    