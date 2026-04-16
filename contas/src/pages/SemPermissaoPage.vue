<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from 'src/stores/auth'
import { goBack } from 'src/utils/goBack'

const router = useRouter()
const authStore = useAuthStore()
</script>

<template>
  <q-page class="flex flex-center column q-pa-lg">
    <q-icon name="lock" size="80px" color="grey-6" />
    <div class="text-h5 q-mt-md text-grey-8">Sem permissão</div>
    <div class="text-body2 q-mt-sm text-grey-7 text-center" style="max-width: 400px">
      Você não tem permissão para acessar essa página. Fale com o administrador do sistema se
      acredita que isso é um erro.
    </div>
    <div class="q-mt-lg q-gutter-sm">
      <q-btn flat color="grey-8" label="Voltar" @click="goBack(router)" />
      <q-btn color="negative" label="Sair" @click="authStore.logout()" />
    </div>
  </q-page>
</template>
