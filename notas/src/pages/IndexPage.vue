<template>
  <q-page class="q-pa-md">
    <div class="row q-col-gutter-md">
      <!-- Card de Boas-vindas -->
      <div class="col-12">
        <q-card flat bordered>
          <q-card-section class="bg-primary text-white">
            <div class="text-h4">Bem-vindo, {{ user?.usuario }}! 👋</div>
            <div class="text-subtitle1">Sistema de Notas Fiscais e Documentos Eletrônicos</div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Ações Rápidas -->
      <!-- <div class="col-12">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h6 q-mb-md">Ações Rápidas</div>
            <div class="row q-gutter-md">
              <q-btn
                v-if="hasPermission('Financeiro') || isAdmin"
                color="primary"
                icon="description"
                label="Notas Fiscais"
                size="lg"
                unelevated
                :to="{ name: 'notas' }"
              />
              <q-btn
                color="secondary"
                icon="settings"
                label="Configurações"
                size="lg"
                unelevated
                @click="$q.notify('Em breve')"
              />
            </div>
          </q-card-section>
        </q-card>
      </div> -->
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
