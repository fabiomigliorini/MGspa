<template>
  <MGLayout drawer>
    <template #tituloPagina>Usuários</template>

    <template #content v-if="user.verificaPermissaoUsuario('Administrador')">
      <q-infinite-scroll
        @load="scrollUsuario"
        :disable="loading"
        style="min-height: 100vh"
      >
        <div class="row q-pa-md q-col-gutter-md">
          <div
            class="col-xs-12 col-sm-6 col-md-4 col-lg-3"
            v-for="usuario in sUsuario.usuarios"
            :key="usuario.codusuario"
          >
            <card-usuario :usuario="usuario" />
          </div>
        </div>
      </q-infinite-scroll>

      <q-page-sticky
        position="bottom-right"
        :offset="[18, 18]"
        v-if="user.verificaPermissaoUsuario('Administrador')"
      >
        <q-btn fab icon="add" color="accent" :to="{ name: 'usuarionovo' }" />
      </q-page-sticky>
    </template>

    <template #content v-else>
      <nao-autorizado />
    </template>

    <template #drawer v-if="user.verificaPermissaoUsuario('Administrador')">
      <div class="q-pa-none q-pt-sm">
        <q-card flat>
          <q-list>
            <q-item-label header>
              Filtro Usuário
              <q-btn icon="replay" @click="buscarUsuarios()" flat round no-caps />
            </q-item-label>
          </q-list>
        </q-card>
        <q-form @change="buscarUsuarios()">
          <div class="q-pa-md q-gutter-md">
            <q-input
              outlined
              v-model="sUsuario.filtroUsuarioPesquisa.usuario"
              label="Usuário"
            />

            <q-select
              outlined
              v-model="sUsuario.filtroUsuarioPesquisa.inativo"
              label="Ativo / Inativo"
              :options="[
                { label: 'Ativos', value: 1 },
                { label: 'Inativos', value: 2 },
              ]"
              map-options
              emit-value
              clearable
            />
          </div>
        </q-form>
      </div>
    </template>
  </MGLayout>
</template>

<script setup>
// 1. Imports do Vue
import { ref, onMounted, watch } from "vue";

// 2. Imports de stores
import { usuarioStore } from "src/stores/usuario";
import { guardaToken } from "src/stores";

// 3. Imports de utilitários
import { useQuasar, debounce } from "quasar";

// 4. Imports de componentes
import MGLayout from "layouts/MGLayout.vue";
import NaoAutorizado from "components/NaoAutorizado.vue";
import CardUsuario from "components/usuario/CardUsuario.vue";

// 5. Instâncias
const sUsuario = usuarioStore();
const user = guardaToken();
const $q = useQuasar();

// 6. Refs
const loading = ref(true);

// 7. Funções
const buscarUsuarios = debounce(async () => {
  $q.loadingBar.start();
  sUsuario.filtroUsuarioPesquisa.page = 1;
  try {
    const ret = await sUsuario.todosUsuarios();
    loading.value = false;
    $q.loadingBar.stop();
    if (ret.data.data.length === 0) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "warning",
        message: "Nenhum Registro encontrado",
      });
    }
  } catch (error) {
    $q.loadingBar.stop();
  }
}, 500);

const scrollUsuario = async (_index, done) => {
  loading.value = true;
  sUsuario.filtroUsuarioPesquisa.page++;
  const ret = await sUsuario.todosUsuarios();
  loading.value = false;
  if (ret.data.data.length === 0) {
    loading.value = true;
  }
  await done();
};

// 8. Watchers
watch(
  () => sUsuario.filtroUsuarioPesquisa.inativo,
  () => buscarUsuarios()
);

// 9. Lifecycle
onMounted(() => {
  if (sUsuario.usuarios.length === 0) {
    buscarUsuarios();
  }
});
</script>
