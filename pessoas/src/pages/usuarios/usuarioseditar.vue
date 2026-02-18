<script setup>
import { onMounted, watch } from "vue";
import { useRoute } from "vue-router";
import { usuarioStore } from "src/stores/usuario";
import { guardaToken } from "src/stores";
import FormUsuario from "components/usuario/FormUsuario.vue";
import MGLayout from "layouts/MGLayout.vue";
import NaoAutorizado from "components/NaoAutorizado.vue";

const route = useRoute();
const sUsuario = usuarioStore();
const user = guardaToken();

function carregarUsuario(id) {
  sUsuario.detalheUsuarios = [];
  sUsuario.getUsuario(id);
}

onMounted(() => {
  carregarUsuario(route.params.codusuario);
});

watch(
  () => route.params.codusuario,
  (novoId) => {
    if (novoId) {
      carregarUsuario(novoId);
    }
  }
);
</script>

<template>
  <MGLayout back-button>
    <template #tituloPagina>
      <span class="q-pl-sm">Usuário - Editar</span>
    </template>

    <template #botaoVoltar>
      <q-btn
        flat
        dense
        round
        :to="'/usuarios/' + route.params.codusuario"
        icon="arrow_back"
        aria-label="Voltar"
      />
    </template>

    <template #content v-if="user.verificaPermissaoUsuario('Administrador')">
      <div style="max-width: 1280px; margin: auto; min-height: 100vh">
        <div class="row q-pa-md">
          <div class="col-12" v-if="sUsuario.detalheUsuarios">
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
