<template>
  <q-page class="flex flex-center">
    <div class="text-center">
      <q-spinner color="primary" size="3em" />
      <p class="q-mt-md">Processando login...</p>
    </div>
  </q-page>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from 'src/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

onMounted(() => {
  // Aguarda um pouco para o cookie ser setado
  setTimeout(() => {
    // Tenta pegar token do cookie
    const cookies = document.cookie.split(';')
    const tokenCookie = cookies.find(c => c.trim().startsWith('access_token='))

    if (tokenCookie) {
      const token = tokenCookie.split('=')[1]
      authStore.setToken(token)
      console.log('Token capturado:', token.substring(0, 20) + '...')
      router.push('/')
    } else {
      console.error('Cookie access_token não encontrado')
      alert('Erro ao fazer login. Cookie não encontrado.')
    }
  }, 500)
})
</script>
