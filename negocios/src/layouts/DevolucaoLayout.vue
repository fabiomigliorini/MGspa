<script setup>
import { computed } from "vue";
import MainLayout from "layouts/MainLayout.vue";
import UsuarioConectado from "components/UsuarioConectado.vue";
import { negocioStore } from "src/stores/negocio";
const sNegocio = negocioStore();
const linkVoltar = computed({
  get() {
    if (!sNegocio.negocio) {
      return null;
    }
    return "/offline/" + sNegocio.negocio.uuid;
  },
});
</script>
<template>
  <main-layout title="Devolução" :back-to="linkVoltar">
    <template #usuario>
      <!-- USUARIO  -->
      <usuario-conectado />
    </template>
    <template #content>
      <router-view :key="$route.fullPath" />
    </template>
  </main-layout>
</template>
