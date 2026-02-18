<script setup>
import { ref, onMounted } from "vue";
import { useQuasar, debounce } from "quasar";
import { useRouter } from "vue-router";
import { GrupoEconomicoStore } from "stores/GrupoEconomico";
import { guardaToken } from "src/stores";
import MGLayout from "layouts/MGLayout.vue";

const $q = useQuasar();
const router = useRouter();
const sPessoa = GrupoEconomicoStore();
const loading = ref(true);
const user = guardaToken();

const buscarGrupos = debounce(async () => {
  $q.loadingBar.start();
  sPessoa.filtroGrupoPesquisa.page = 1;
  try {
    const ret = await sPessoa.buscaGrupos();
    loading.value = false;
    $q.loadingBar.stop();
    if (ret.data.data.length == 0) {
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

const scrollGrupo = async (index, done) => {
  loading.value = true;
  $q.loadingBar.start();
  sPessoa.filtroGrupoPesquisa.page++;
  const ret = await sPessoa.buscaGrupos();
  loading.value = false;
  $q.loadingBar.stop();
  if (ret.data.data.length == 0) {
    loading.value = true;
  }
  await done();
};

const novoGrupo = () => {
  $q.dialog({
    title: "Novo Grupo Econômico",
    prompt: { model: "", type: "text" },
    cancel: true,
  }).onOk(async (grupoeconomico) => {
    const ret = await sPessoa.novoGrupoEconomico(grupoeconomico);
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Grupo Criado!",
      });
      router.push("/grupoeconomico/" + ret.data.data.codgrupoeconomico);
    }
  });
};

const pessoasOrdenadas = (pessoas) => {
  if (!pessoas) return [];
  return [...pessoas].sort((a, b) =>
    (a.fantasia || "").localeCompare(b.fantasia || "", "pt-BR", { sensitivity: "base" })
  );
};

onMounted(() => {
  if (sPessoa.arrGrupos.length == 0) {
    buscarGrupos();
  }
});
</script>

<template>
  <MGLayout drawer>
    <template #tituloPagina> Grupo Econômico </template>

    <template #content>
      <q-infinite-scroll
        @load="scrollGrupo"
        :disable="loading"
        style="min-height: 100vh"
      >
        <div class="row q-pa-md q-col-gutter-md">
          <div
            class="col-md-4 col-sm-6 col-xs-12 col-lg-3 col-xl-2"
            v-for="grupoEconomico in sPessoa.arrGrupos"
            :key="grupoEconomico.codgrupoeconomico"
          >
            <router-link
              :to="'/grupoeconomico/' + grupoEconomico.codgrupoeconomico"
              class="link-card"
            >
              <q-card bordered flat class="cursor-pointer full-height">
                <q-badge
                  v-if="grupoEconomico.inativo"
                  color="red"
                  floating
                  label="Inativo"
                />
                <q-card-section class="text-grey-9 text-overline">
                  <div class="flex items-center" style="height: 3rem">
                    <div class="ellipsis-2-lines titulo-grupo">
                      {{ grupoEconomico.grupoeconomico }}
                    </div>
                  </div>
                </q-card-section>

                <!-- PESSOAS DO GRUPO -->
                <q-list class="lista-pessoas">
                  <template
                    v-for="pessoa in pessoasOrdenadas(grupoEconomico.PessoasdoGrupo)"
                    :key="pessoa.codpessoa"
                  >
                    <q-separator inset />
                    <q-item>
                      <q-item-section side>
                        <q-icon :name="pessoa.fisica ? 'person' : 'business'" color="grey" />
                      </q-item-section>
                      <q-item-section>
                        <q-item-label class="ellipsis text-caption">
                          {{ pessoa.fantasia }}
                        </q-item-label>
                      </q-item-section>
                    </q-item>
                  </template>
                  <q-item v-if="!grupoEconomico.PessoasdoGrupo?.length">
                    <q-item-section>
                      <q-item-label class="text-caption text-grey">
                        Nenhuma pessoa
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>

                <template v-if="grupoEconomico.observacoes">
                  <q-separator inset />
                  <q-card-section class="q-pt-sm q-pb-sm">
                    <div class="text-caption text-grey ellipsis">
                      {{ grupoEconomico.observacoes }}
                    </div>
                  </q-card-section>
                </template>
              </q-card>
            </router-link>
          </div>
        </div>
      </q-infinite-scroll>

      <q-page-sticky
        position="bottom-right"
        :offset="[18, 18]"
        v-if="user.verificaPermissaoUsuario('Publico')"
      >
        <q-fab icon="add" direction="up" color="accent" @click="novoGrupo()" />
      </q-page-sticky>
    </template>

    <template #drawer>
      <div class="q-pa-none q-pt-sm">
        <q-card flat>
          <q-list>
            <q-item-label header>
              Filtro Grupo Econômico
              <q-btn icon="replay" @click="buscarGrupos()" flat round no-caps />
            </q-item-label>
          </q-list>
        </q-card>
        <div class="q-pa-md q-gutter-md">
          <q-input
            outlined
            v-model="sPessoa.filtroGrupoPesquisa.nome"
            label="Nome"
            @change="buscarGrupos()"
          />
        </div>
      </div>
    </template>
  </MGLayout>
</template>

<style scoped>
.link-card {
  text-decoration: none;
  color: inherit;
}

.titulo-grupo {
  line-height: 1.3;
  font-size: 1rem;
}

.razao-social {
  text-transform: none;
}

.lista-pessoas {
  max-height: 200px;
  overflow-y: auto;
}
</style>
