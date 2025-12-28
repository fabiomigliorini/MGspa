<template>
  <q-page class="flex flex-center">
    <div class="text-center q-pa-md" style="max-width: 500px;">
      <h4>Bem-vindo ao Sistema de Notas!</h4>

      <div v-if="authStore.loading" class="q-mt-md">
        <q-spinner color="primary" size="3em" />
        <p>Validando...</p>
      </div>

      <div v-else-if="authStore.user" class="q-mt-md">
        <q-card>
          <q-card-section>
            <div class="text-h6">✅ Você está autenticado!</div>
            <div class="text-subtitle2 q-mt-sm">
              Usuário: <strong>{{ authStore.user.usuario }}</strong>
            </div>
          </q-card-section>

          <q-card-section v-if="authStore.user.permissoes">
            <div class="text-caption text-grey-7">Permissões:</div>
            <q-chip
              v-for="perm in authStore.user.permissoes"
              :key="perm.grupousuario"
              color="primary"
              text-color="white"
              size="sm"
            >
              {{ perm.grupousuario }}
            </q-chip>
          </q-card-section>

          <q-card-actions>
            <q-btn
              color="negative"
              label="Sair"
              @click="authStore.logout()"
              icon="logout"
            />
          </q-card-actions>
        </q-card>
      </div>

      <div v-else class="q-mt-md">
        <p>❌ Você não está autenticado.</p>
        <q-btn
          color="primary"
          label="Fazer Login"
          icon="login"
          @click="goLogin"
        />
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from 'src/stores/auth'

const authStore = useAuthStore()

function goLogin() {
  const currentUrl = encodeURIComponent(window.location.origin + '/#/login')
  window.location.href = `${process.env.API_AUTH_URL}/login?redirect_uri=${currentUrl}`
}

onMounted(async () => {
  if (authStore.token) {
    await authStore.validateToken()
  }
})
</script>