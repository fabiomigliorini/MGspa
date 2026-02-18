<script setup>
import { ref, computed, onMounted } from "vue";
import { useQuasar } from "quasar";
import { unidadeNegocioStore } from "src/stores/unidadenegocio";
import { guardaToken } from "src/stores";
import { formataData } from "src/utils/formatador";
import MGLayout from "layouts/MGLayout.vue";
import SelectFilial from "components/select/SelectFilial.vue";

const $q = useQuasar();
const sUnidade = unidadeNegocioStore();
const user = guardaToken();

const filtro = ref("ativos");
const unidadesFiltradas = computed(() => {
  const lista = sUnidade.listagem || [];
  if (filtro.value === "ativos") return lista.filter((x) => !x.inativo);
  return lista;
});

const dialog = ref(false);
const isNovo = ref(false);
const model = ref({});

const carregar = async () => {
  try {
    await sUnidade.getListagem();
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response?.data?.mensagem || "Erro ao carregar unidades",
    });
  }
};

const abrirNovo = () => {
  model.value = { descricao: "", codfilial: null };
  isNovo.value = true;
  dialog.value = true;
};

const abrirEditar = (unidade) => {
  model.value = {
    codunidadenegocio: unidade.codunidadenegocio,
    descricao: unidade.descricao,
    codfilial: unidade.codfilial,
  };
  isNovo.value = false;
  dialog.value = true;
};

const novo = async () => {
  dialog.value = false;
  try {
    await sUnidade.criar(model.value);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Unidade criada",
    });
    await carregar();
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response?.data?.mensagem || "Erro ao criar unidade",
    });
  }
};

const salvar = async () => {
  dialog.value = false;
  try {
    await sUnidade.atualizar(model.value.codunidadenegocio, model.value);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Unidade alterada",
    });
    await carregar();
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response?.data?.mensagem || "Erro ao alterar unidade",
    });
  }
};

const excluir = (unidade) => {
  $q.dialog({
    title: "Excluir Unidade",
    message: 'Tem certeza que deseja excluir "' + unidade.descricao + '"?',
    cancel: true,
  }).onOk(async () => {
    try {
      await sUnidade.excluir(unidade.codunidadenegocio);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Unidade excluída",
      });
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response?.data?.mensagem || "Erro ao excluir unidade",
      });
    }
  });
};

const inativar = async (unidade) => {
  try {
    await sUnidade.inativar(unidade.codunidadenegocio);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Inativado!",
    });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response?.data?.mensagem || "Erro ao inativar",
    });
  }
};

const ativar = async (unidade) => {
  try {
    await sUnidade.ativar(unidade.codunidadenegocio);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Ativado!",
    });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response?.data?.mensagem || "Erro ao ativar",
    });
  }
};

const submit = () => {
  isNovo.value ? novo() : salvar();
};

onMounted(() => {
  carregar();
});
</script>

<template>
  <!-- DIALOG NOVO/EDITAR -->
  <q-dialog v-model="dialog">
    <q-card bordered flat style="width: 600px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline row items-center">
        <template v-if="isNovo">NOVA UNIDADE DE NEGÓCIO</template>
        <template v-else>EDITAR UNIDADE DE NEGÓCIO</template>
      </q-card-section>

      <q-form @submit="submit()">
        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-input
                outlined
                v-model="model.descricao"
                label="Descrição"
                autofocus
                :rules="[
                  (val) => (val && val.length > 0) || 'Descrição obrigatória',
                ]"
              />
            </div>
            <div class="col-12">
              <select-filial
                v-model="model.codfilial"
                outlined
                label="Filial"
                clearable
              />
            </div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn
            flat
            label="Cancelar"
            v-close-popup
            tabindex="-1"
            color="grey-8"
          />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <MGLayout>
    <template #tituloPagina>
      <span class="q-pl-sm">Unidades de Negócio</span>
    </template>

    <template #content>
      <q-page>
        <div style="max-width: 900px; margin: auto" class="q-pa-md">
          <q-card bordered flat>
            <q-card-section
              class="text-grey-9 text-overline row items-center"
            >
              UNIDADES DE NEGÓCIO
              <q-space />
              <q-btn-toggle
                v-model="filtro"
                color="grey-3"
                toggle-color="primary"
                text-color="grey-7"
                toggle-text-color="grey-3"
                unelevated
                dense
                no-caps
                size="sm"
                :options="[
                  { label: 'Ativos', value: 'ativos' },
                  { label: 'Todos', value: 'todos' },
                ]"
              />
              <q-btn
                flat
                round
                dense
                icon="add"
                size="sm"
                color="primary"
                @click="abrirNovo()"
              >
                <q-tooltip>Nova Unidade</q-tooltip>
              </q-btn>
            </q-card-section>

            <q-list v-if="unidadesFiltradas.length > 0">
              <template
                v-for="unidade in unidadesFiltradas"
                :key="unidade.codunidadenegocio"
              >
                <q-separator inset />
                <q-item>
                  <q-item-section avatar>
                    <q-avatar
                      color="primary"
                      text-color="white"
                      size="35px"
                    >
                      {{ unidade.descricao?.charAt(0) }}
                    </q-avatar>
                  </q-item-section>

                  <q-item-section>
                    <q-item-label
                      :class="unidade.inativo ? 'text-strike' : ''"
                    >
                      {{ unidade.descricao }}
                    </q-item-label>
                    <q-item-label
                      caption
                      class="text-red-14"
                      v-if="unidade.inativo"
                    >
                      Inativo desde: {{ formataData(unidade.inativo) }}
                    </q-item-label>
                    <q-item-label caption v-if="unidade.Filial">
                      {{ unidade.Filial.filial }}
                    </q-item-label>
                  </q-item-section>

                  <q-item-section side>
                    <q-item-label caption>
                      <!-- EDITAR -->
                      <q-btn
                        flat
                        dense
                        round
                        icon="edit"
                        size="sm"
                        color="grey-7"
                        @click="abrirEditar(unidade)"
                      >
                        <q-tooltip>Editar</q-tooltip>
                      </q-btn>

                      <!-- INATIVAR -->
                      <q-btn
                        v-if="!unidade.inativo"
                        flat
                        dense
                        round
                        icon="pause"
                        size="sm"
                        color="grey-7"
                        @click="inativar(unidade)"
                      >
                        <q-tooltip>Inativar</q-tooltip>
                      </q-btn>

                      <!-- ATIVAR -->
                      <q-btn
                        v-if="unidade.inativo"
                        flat
                        dense
                        round
                        icon="play_arrow"
                        size="sm"
                        color="grey-7"
                        @click="ativar(unidade)"
                      >
                        <q-tooltip>Ativar</q-tooltip>
                      </q-btn>

                      <!-- EXCLUIR -->
                      <q-btn
                        flat
                        dense
                        round
                        icon="delete"
                        size="sm"
                        color="grey-7"
                        @click="excluir(unidade)"
                      >
                        <q-tooltip>Excluir</q-tooltip>
                      </q-btn>
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-list>
            <div v-else class="q-pa-md text-center text-grey">
              Nenhuma unidade cadastrada
            </div>
          </q-card>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>
