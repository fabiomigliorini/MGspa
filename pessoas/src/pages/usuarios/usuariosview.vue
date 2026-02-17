<script setup>
import { onMounted, watch } from "vue";
import { useRoute } from "vue-router";
import { usuarioStore } from "src/stores/usuario";
import { guardaToken } from "src/stores";
import CardDetalhesUsuario from "components/usuario/CardDetalhesUsuario.vue";
import CardPermissoesUsuario from "components/usuario/CardPermissoesUsuario.vue";
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
      <span class="q-pl-sm">Usuário - Detalhes</span>
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
        <div v-if="sUsuario.detalheUsuarios">
          <q-item class="q-pt-lg q-pb-sm">
            <q-item-section avatar>
              <q-avatar color="grey-8" text-color="grey-4" size="80px">
                {{ sUsuario.detalheUsuarios.usuario?.slice(0, 1)?.toUpperCase() }}
              </q-avatar>
            </q-item-section>
            <q-item-section>
              <div class="text-h5 text-grey-9">
                {{ sUsuario.detalheUsuarios.usuario }}
                <q-badge v-if="sUsuario.detalheUsuarios.inativo" color="red" class="q-ml-sm">
                  Inativo
                </q-badge>
              </div>
            </q-item-section>
          </q-item>

          <div class="row q-col-gutter-md q-pa-md q-pt-none">
            <div class="col-xs-12 col-md-8">
              <card-detalhes-usuario />
            </div>

            <div class="col-xs-12 col-md-4">
              <card-permissoes-usuario />
            </div>
          </div>
        </div>
      </div>
    </template>

    <template #content v-else>
      <nao-autorizado />
    </template>
  </MGLayout>
</template>
