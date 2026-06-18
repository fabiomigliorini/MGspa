<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from 'src/stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const status = ref('Processando login...')

onMounted(() => {
  // O cookie access_token (.mgpapelaria.com.br) é setado fresco pelo SSO ao
  // redirecionar de volta pra cá. Adotamos esse token e seguimos pro destino.
  const cookies = document.cookie.split(';')
  const tokenCookie = cookies.find((c) => c.trim().startsWith('access_token='))

  if (tokenCookie) {
    const token = tokenCookie.split('=')[1]
    authStore.gravarToken(token)
    status.value = 'Redirecionando...'

    const urlRetorno = authStore.consumirUrlRetorno()
    router.replace(urlRetorno || '/')
  } else {
    status.value = 'Erro: Cookie não encontrado!'
    setTimeout(() => {
      window.location.href = `${process.env.API_AUTH_URL}/login?redirect_uri=${encodeURIComponent(window.location.origin + '/login')}`
    }, 2000)
  }
})
</script>

<template>
  <q-layout>
    <q-page-container>
      <q-page class="flex flex-center">
        <div class="text-center">
          <q-spinner color="primary" size="3em" />
          <p class="q-mt-md">{{ status }}</p>
        </div>
      </q-page>
    </q-page-container>
  </q-layout>
</template>
