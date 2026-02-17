<script setup>
import { guardaToken } from "src/stores";
import FormUsuario from "components/usuario/FormUsuario.vue";
import MGLayout from "layouts/MGLayout.vue";
import NaoAutorizado from "components/NaoAutorizado.vue";

const user = guardaToken();
</script>

<template>
  <MGLayout back-button>
    <template #tituloPagina>
      <span class="q-pl-sm">Usuário - Novo</span>
    </template>

    <template #botaoVoltar>
      <q-btn
        flat
        dense
        round
        :to="{ name: 'usuarios' }"
        icon="arrow_back"
        aria-label="Voltar"
      />
    </template>

    <template #content v-if="user.verificaPermissaoUsuario('Administrador')">
      <div style="max-width: 1280px; margin: auto; min-height: 100vh">
        <div class="row q-pa-md">
          <div class="col-12">
            <form-usuario />
          </div>
        </div>
      </div>
    </template>

    <template #content v-else>
      <nao-autorizado />
    </template>
  </MGLayout>
</template>
