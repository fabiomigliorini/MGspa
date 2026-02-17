<template>
  <MGLayout drawer>
    <template #tituloPagina>Grupo Usuários</template>

    <template #content v-if="user.verificaPermissaoUsuario('Administrador')">
      <q-infinite-scroll
        @load="scrollGrupoUsuario"
        :disable="loading"
        style="min-height: 100vh"
      >
        <div class="row q-pa-md q-col-gutter-md">
          <div
            class="col-xs-12 col-sm-6 col-md-4 col-lg-3"
            v-for="grupoUsuario in sGrupoUsuario.grupoUsuarios"
            :key="grupoUsuario.codgrupousuario"
          >
            <card-grupo-usuario :grupo-usuario="grupoUsuario" />
          </div>
        </div>
      </q-infinite-scroll>
    </template>

    <template #content v-else>
      <nao-autorizado />
    </template>

    <template #drawer v-if="user.verificaPermissaoUsuario('Administrador')">
      <div class="q-pa-none q-pt-sm">
        <q-card flat>
          <q-list>
            <q-item-label header>
              Filtro Grupo Usuário
              <q-btn
                icon="replay"
                @click="buscarGruposUsuarios()"
                flat
                round
                no-caps
              />
            </q-item-label>
          </q-list>
        </q-card>
        <q-form @change="buscarGruposUsuarios()">
          <div class="q-pa-md q-gutter-md">
            <q-input
              outlined
              v-model="sGrupoUsuario.filtroGrupoUsuarioPesquisa.grupo"
              label="Grupo Usuário"
            />

            <q-select
              outlined
              v-model="sGrupoUsuario.filtroGrupoUsuarioPesquisa.inativo"
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
import { grupoUsuarioStore } from "src/stores/grupo-usuario";
import { guardaToken } from "src/stores";

// 3. Imports de utilitários
import { useQuasar, debounce } from "quasar";

// 4. Imports de componentes
import MGLayout from "layouts/MGLayout.vue";
import NaoAutorizado from "components/NaoAutorizado.vue";
import CardGrupoUsuario from "components/usuario/CardGrupoUsuario.vue";

// 5. Instâncias
const sGrupoUsuario = grupoUsuarioStore();
const user = guardaToken();
const $q = useQuasar();

// 6. Refs
const loading = ref(true);

// 7. Funções
const buscarGruposUsuarios = debounce(async () => {
  $q.loadingBar.start();
  sGrupoUsuario.filtroGrupoUsuarioPesquisa.page = 1;
  try {
    const ret = await sGrupoUsuario.todosGruposUsuarios();
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

const scrollGrupoUsuario = async (_index, done) => {
  loading.value = true;
  sGrupoUsuario.filtroGrupoUsuarioPesquisa.page++;
  const ret = await sGrupoUsuario.todosGruposUsuarios();
  loading.value = false;
  if (ret.data.data.length === 0) {
    loading.value = true;
  }
  await done();
};

// 8. Watchers
watch(
  () => sGrupoUsuario.filtroGrupoUsuarioPesquisa.inativo,
  () => buscarGruposUsuarios()
);

// 9. Lifecycle
onMounted(() => {
  if (sGrupoUsuario.grupoUsuarios.length === 0) {
    buscarGruposUsuarios();
  }
});
</script>
