<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from 'src/stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const status = ref('Processando login...')

onMounted(() => {
  console.log('LoginPage montada')

  setTimeout(() => {
    const cookies = document.cookie.split(';')
    console.log('Cookies atuais:', cookies)

    const tokenCookie = cookies.find((c) => c.trim().startsWith('access_token='))

    if (tokenCookie) {
      const token = tokenCookie.split('=')[1]
      console.log('Token extraído:', token.substring(0, 20) + '...')

      authStore.setToken(token)
      status.value = 'Redirecionando...'

      router.replace('/')
    } else {
      console.error('Cookie não encontrado')
      status.value = 'Erro: Cookie não encontrado!'

      setTimeout(() => {
        window.location.href = `${process.env.API_AUTH_URL}/login?redirect_uri=${encodeURIComponent(window.location.origin + '/login')}`
      }, 2000)
    }
  }, 500)
})
</script>

<template>
  <q-page class="flex flex-center">
    <div class="text-center">
      <q-spinner color="primary" size="3em" />
      <p class="q-mt-md">{{ status }}</p>
    </div>
  </q-page>
</template>
