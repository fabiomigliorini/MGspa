<template>
  <q-layout view="hHh lpR fFf">
    <!-- Header -->
    <q-header elevated class="bg-primary text-white">
      <q-toolbar>
        <q-btn dense flat round icon="menu" @click="toggleLeftDrawer" />

        <q-toolbar-title>
          <q-icon name="description" size="sm" class="q-mr-sm" />
          Sistema de Notas Fiscais
        </q-toolbar-title>

        <div v-if="isAuthenticated">
          <q-btn round flat icon="account_circle">
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
                      <q-icon name="star" size="xs" color="amber" /> Administrador
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
        </div>
      </q-toolbar>
    </q-header>

    <!-- Menu Lateral LIMPO -->
    <q-drawer v-model="leftDrawerOpen" show-if-above bordered class="bg-grey-2">
      <q-scroll-area class="fit">
        <q-list>
          <q-item-label header>Menu</q-item-label>

          <!-- Home -->
          <q-item clickable :to="{ name: 'home' }" exact active-class="bg-primary text-white">
            <q-item-section avatar>
              <q-icon name="home" />
            </q-item-section>
            <q-item-section>
              <q-item-label>Início</q-item-label>
            </q-item-section>
          </q-item>

          <!-- Notas Fiscais (se tiver permissão) -->
          <q-item
            v-if="hasPermission('Financeiro') || isAdmin"
            clickable
            :to="{ name: 'notas' }"
            active-class="bg-primary text-white"
          >
            <q-item-section avatar>
              <q-icon name="description" />
            </q-item-section>
            <q-item-section>
              <q-item-label>Notas Fiscais</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-scroll-area>
    </q-drawer>

    <!-- Conteúdo -->
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref } from 'vue'
import { useAuth } from 'src/composables/useAuth'
import { Dialog } from 'quasar'

const { user, isAuthenticated, isAdmin, permissions, hasPermission, logout } = useAuth()
const leftDrawerOpen = ref(false)

function toggleLeftDrawer() {
  leftDrawerOpen.value = !leftDrawerOpen.value
}

function handleLogout() {
  Dialog.create({
    title: 'Confirmar',
    message: 'Deseja realmente sair do sistema?',
    cancel: {
      label: 'Cancelar',
      flat: true,
    },
    ok: {
      label: 'Sair',
      color: 'negative',
    },
    persistent: true,
  }).onOk(() => {
    logout()
  })
}
</script>
