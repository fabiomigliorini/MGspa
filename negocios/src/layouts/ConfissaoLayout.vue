<script setup>
import MainLayout from "layouts/MainLayout.vue";
import UsuarioConectado from "components/UsuarioConectado.vue";
</script>
<template>
  <main-layout title="Conferência das Confissões de Dívida" left-drawer>
    <template #usuario>
      <!-- USUARIO  -->
      <usuario-conectado />
    </template>
    <template #left-drawer>
      <q-item-label header> Confissões de Dívida </q-item-label>

      <!-- ComputerSettings -->
      <q-item clickable tag="a" :to="'/confissao/'" v-ripple exact-active-class="bg-blue-1">
        <q-item-section avatar>
          <q-avatar icon="photo_camera" round color="primary" text-color="white" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis"> Digitalizar </q-item-label>
        </q-item-section>
      </q-item>
      <q-separator />

      <!-- Listagem PDV -->
      <q-item clickable tag="a" :to="'/confissao/faltando'" v-ripple exact-active-class="bg-blue-1">
        <q-item-section avatar>
          <q-avatar icon="checklist" color="primary" text-color="white" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis"> Faltando Digitalizar </q-item-label>
        </q-item-section>
      </q-item>
      <q-separator />

    </template>
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
