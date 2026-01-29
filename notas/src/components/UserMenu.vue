<script setup>
import { useAuth } from 'src/composables/useAuth'
import { Dialog } from 'quasar'

const { user, isAuthenticated, isAdmin, permissions, logout } = useAuth()

function handleLogout() {
  Dialog.create({
    title: 'Confirmar',
    message: 'Deseja realmente sair do sistema?',
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Sair', color: 'negative' },
  }).onOk(() => {
    logout()
  })
}
</script>

<template>
  <q-btn v-if="isAuthenticated" round flat icon="account_circle">
    <q-menu>
      <q-list style="width: 250px">
        <!-- Avatar e Nome -->
        <q-item>
          <q-item-section avatar>
            <q-avatar color="primary" text-color="white" size="50px">
              {{ user?.usuario?.charAt(0).toUpperCase() }}
            </q-avatar>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-weight-bold">{{ user?.usuario }}</q-item-label>
            <q-item-label caption v-if="isAdmin">
              <q-icon name="star" size="xs" color="amber" />
              Administrador
            </q-item-label>
          </q-item-section>
        </q-item>

        <q-separator />

        <!-- Permissões -->
        <q-item>
          <q-item-section>
            <q-item-label overline class="text-grey-7">Permissões</q-item-label>
            <div class="row q-gutter-xs q-mt-xs">
              <q-chip
                v-for="perm in permissions"
                :key="perm"
                size="sm"
                color="primary"
                text-color="white"
                dense
              >
                {{ perm }}
              </q-chip>
            </div>
          </q-item-section>
        </q-item>

        <q-separator />

        <!-- Logout -->
        <q-item clickable v-close-popup @click="handleLogout">
          <q-item-section avatar>
            <q-icon name="logout" color="negative" />
          </q-item-section>
          <q-item-section>
            <q-item-label>Sair</q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </q-menu>
  </q-btn>
</template>
