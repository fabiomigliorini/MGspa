<script setup>
import { ref, onMounted, watch } from "vue";
import { useQuasar } from "quasar";
import { useRoute, useRouter } from "vue-router";
import { grupoUsuarioStore } from "src/stores/grupo-usuario";
import { guardaToken } from "src/stores";
import MGLayout from "layouts/MGLayout.vue";
import NaoAutorizado from "components/NaoAutorizado.vue";

const $q = useQuasar();
const route = useRoute();
const router = useRouter();
const sGrupoUsuario = grupoUsuarioStore();
const user = guardaToken();

// Dialog
const dialogEditar = ref(false);
const modelGrupoUsuario = ref({});

// Functions
const editar = () => {
  modelGrupoUsuario.value = {
    codgrupousuario: sGrupoUsuario.detalheGrupoUsuarios.codgrupousuario,
    grupousuario: sGrupoUsuario.detalheGrupoUsuarios.grupousuario,
    observacoes: sGrupoUsuario.detalheGrupoUsuarios.observacoes,
  };
  dialogEditar.value = true;
};

const salvar = async () => {
  try {
    const ret = await sGrupoUsuario.alterarGrupo(modelGrupoUsuario.value);
    if (ret.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Grupo alterado!",
      });
      dialogEditar.value = false;
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response?.data?.message || "Erro ao alterar",
    });
  }
};

const excluir = (codgrupousuario) => {
  $q.dialog({
    title: "Excluir Grupo",
    message: "Tem certeza que deseja excluir esse grupo de usuário?",
    cancel: true,
  }).onOk(async () => {
    try {
      await sGrupoUsuario.excluir(codgrupousuario);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Grupo excluído",
      });
      router.push("/grupo-usuarios");
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response?.data?.message || "Erro ao excluir",
      });
    }
  });
};

const inativar = async (codgrupousuario) => {
  try {
    const ret = await sGrupoUsuario.inativar(codgrupousuario);
    if (ret.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Inativado!",
      });
    }
  } catch (error) {
    $q.notify({
      color: "negative",
      textColor: "white",
      icon: "error",
      message: error.message || "Erro ao inativar",
    });
  }
};

const ativar = async (codgrupousuario) => {
  try {
    const ret = await sGrupoUsuario.ativar(codgrupousuario);
    if (ret.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Ativado!",
      });
    }
  } catch (error) {
    $q.notify({
      color: "negative",
      textColor: "white",
      icon: "error",
      message: error.message || "Erro ao ativar",
    });
  }
};

// Lifecycle
onMounted(async () => {
  await sGrupoUsuario.getGrupoUsuarioDetalhes(route.params.codgrupousuario);
});

watch(
  () => route.params.codgrupousuario,
  async (novoId) => {
    if (novoId) {
      await sGrupoUsuario.getGrupoUsuarioDetalhes(novoId);
    }
  }
);
</script>

<template>
  <MGLayout back-button>
    <template #botaoVoltar>
      <q-btn
        flat
        dense
        round
        :to="{ name: 'grupousuarios' }"
        icon="arrow_back"
        aria-label="Voltar"
      />
    </template>

    <template #tituloPagina>Grupo Usuário</template>

    <template #content v-if="user.verificaPermissaoUsuario('Administrador')">
      <div
        style="max-width: 1280px; margin: auto; min-height: 100vh"
        v-if="sGrupoUsuario.detalheGrupoUsuarios"
      >
        <q-item class="q-pt-lg q-pb-sm">
          <q-item-section avatar>
            <q-avatar color="grey-8" text-color="grey-4" size="80px">
              {{
                sGrupoUsuario.detalheGrupoUsuarios.grupousuario
                  ?.slice(0, 1)
                  ?.toUpperCase()
              }}
            </q-avatar>
          </q-item-section>
          <q-item-section>
            <div class="text-h5 text-grey-9">
              {{ sGrupoUsuario.detalheGrupoUsuarios.grupousuario }}
              <q-badge
                v-if="sGrupoUsuario.detalheGrupoUsuarios.inativo"
                color="red"
                class="q-ml-sm"
              >
                Inativo
              </q-badge>
            </div>
          </q-item-section>
        </q-item>

        <div class="row q-col-gutter-md q-pa-md q-pt-none">
          <!-- CARD DETALHES -->
          <div class="col-12">
            <q-card bordered flat>
              <q-card-section
                class="text-grey-9 text-overline row items-center"
              >
                DETALHES DO GRUPO
                <q-space />
                <q-btn
                  flat
                  round
                  dense
                  size="sm"
                  color="grey-7"
                  icon="edit"
                  @click="editar()"
                >
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>

                <q-btn
                  flat
                  round
                  dense
                  size="sm"
                  color="grey-7"
                  icon="delete"
                  @click="
                    excluir(sGrupoUsuario.detalheGrupoUsuarios.codgrupousuario)
                  "
                >
                  <q-tooltip>Excluir</q-tooltip>
                </q-btn>

                <q-btn
                  v-if="!sGrupoUsuario.detalheGrupoUsuarios.inativo"
                  flat
                  round
                  dense
                  size="sm"
                  color="grey-7"
                  icon="pause"
                  @click="
                    inativar(sGrupoUsuario.detalheGrupoUsuarios.codgrupousuario)
                  "
                >
                  <q-tooltip>Inativar</q-tooltip>
                </q-btn>

                <q-btn
                  v-if="sGrupoUsuario.detalheGrupoUsuarios.inativo"
                  flat
                  round
                  dense
                  size="sm"
                  color="grey-7"
                  icon="play_arrow"
                  @click="
                    ativar(sGrupoUsuario.detalheGrupoUsuarios.codgrupousuario)
                  "
                >
                  <q-tooltip>Ativar</q-tooltip>
                </q-btn>
              </q-card-section>

              <q-separator inset />

              <q-list>
                <q-item>
                  <q-item-section avatar>
                    <q-icon color="primary" name="notes" size="xs" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label
                      class="text-caption"
                      v-if="sGrupoUsuario.detalheGrupoUsuarios.observacoes"
                    >
                      {{ sGrupoUsuario.detalheGrupoUsuarios.observacoes }}
                    </q-item-label>
                    <q-item-label class="text-caption" v-else>
                      Sem Observações
                    </q-item-label>
                    <q-item-label caption>Observações</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <!-- USUÁRIOS DO GRUPO -->
          <div class="col-12">
            <q-card bordered flat>
              <q-card-section class="text-grey-9 text-overline">
                USUÁRIOS DO GRUPO
              </q-card-section>

              <q-separator inset />

              <q-list
                v-if="sGrupoUsuario.detalheGrupoUsuarios.Usuarios?.length"
              >
                <template
                  v-for="(usuariosDoGrupo, index) in sGrupoUsuario
                    .detalheGrupoUsuarios.Usuarios"
                  :key="usuariosDoGrupo.codusuario"
                >
                  <q-separator inset v-if="index > 0" />
                  <q-item :to="'/usuarios/' + usuariosDoGrupo.codusuario">
                    <q-item-section avatar>
                      <q-icon color="primary" name="person" size="xs" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label class="ellipsis text-caption">
                        {{ usuariosDoGrupo.usuario }}
                      </q-item-label>
                      <q-item-label
                        caption
                        v-if="usuariosDoGrupo.filiais?.length"
                      >
                        <template
                          v-for="(filial, i) in usuariosDoGrupo.filiais"
                          :key="filial.codfilial"
                        >
                          <span v-if="i !== 0"> | </span>
                          {{ filial.filial }}
                        </template>
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </template>
              </q-list>

              <div v-else class="q-pa-md text-center text-grey">
                Nenhum usuário neste grupo
              </div>
            </q-card>
          </div>
        </div>
      </div>

      <!-- Dialog Editar Grupo -->
      <q-dialog v-model="dialogEditar">
        <q-card style="min-width: 400px">
          <q-form @submit.prevent="salvar()">
            <q-card-section class="text-grey-9 text-overline">
              EDITAR GRUPO USUÁRIO
            </q-card-section>

            <q-separator inset />

            <q-card-section class="q-gutter-md">
              <q-input
                outlined
                v-model="modelGrupoUsuario.grupousuario"
                label="Grupo Usuário"
                :rules="[
                  (val) => (val && val.length > 0) || 'Campo obrigatório',
                ]"
              />

              <q-input
                outlined
                v-model="modelGrupoUsuario.observacoes"
                label="Observações"
                type="textarea"
              />
            </q-card-section>

            <q-card-actions align="right" class="text-primary">
              <q-btn
                flat
                label="Cancelar"
                color="grey-8"
                v-close-popup
                tabindex="-1"
              />
              <q-btn flat label="Salvar" type="submit" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </template>

    <template #content v-else>
      <nao-autorizado />
    </template>
  </MGLayout>
</template>
