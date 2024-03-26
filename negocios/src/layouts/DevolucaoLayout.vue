<script setup>
import UsuarioConectado from "components/UsuarioConectado.vue";
import { version } from "../../package.json";
import { negocioStore } from "src/stores/negocio";
const sNegocio = negocioStore();
</script>

<template>
  <q-layout view="hHh lpr fFf">
    <q-header reveal elevated height-hint="98">
      <q-toolbar>
        <template v-if="sNegocio.negocio">
          <q-btn
            dense
            flat
            round
            icon="arrow_back"
            :to="'/offline/' + sNegocio.negocio.uuid"
          />
        </template>

        <q-toolbar-title>
          <q-avatar>
            <img src="MGPapelariaQuadrado.svg" />
          </q-avatar>
          Devolução
        </q-toolbar-title>
        <div class="gt-xs q-mr-sm">v{{ version }}</div>

        <!-- TODO: criar componente para usar nos dois layouts -->
        <usuario-conectado />
      </q-toolbar>
    </q-header>

    <q-page-container>
      <router-view :key="$route.fullPath" />
    </q-page-container>
  </q-layout>
</template>
