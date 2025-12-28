<template>
  <q-page class="q-pa-md">
    <div class="row q-col-gutter-md">
      <div class="col-12">
        <q-card flat bordered>
          <q-card-section class="bg-primary text-white">
            <div class="text-h4">Bem-vindo, {{ user?.usuario }}! 👋</div>
            <div class="text-subtitle1">Sistema de Notas Fiscais e Documentos Eletrônicos</div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuth } from 'src/composables/useAuth'

const { user, validateToken } = useAuth()

onMounted(async () => {
  await validateToken()
})
</script>
