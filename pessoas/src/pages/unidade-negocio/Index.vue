<script setup>
import { ref, computed, onMounted } from "vue";
import { useQuasar } from "quasar";
import { unidadeNegocioStore } from "src/stores/unidadenegocio";
import { setorStore } from "src/stores/setor";
import { tipoSetorStore } from "src/stores/tiposetor";
import { guardaToken } from "src/stores";
import { formataData } from "src/utils/formatador";
import MGLayout from "layouts/MGLayout.vue";
import SelectFilial from "components/select/SelectFilial.vue";
import SelectUnidadeNegocio from "components/select/SelectUnidadeNegocio.vue";
import SelectTipoSetor from "components/select/SelectTipoSetor.vue";

const $q = useQuasar();
const sUnidade = unidadeNegocioStore();
const sSetor = setorStore();
const sTipoSetor = tipoSetorStore();
const user = guardaToken();

// --- FILTROS ---

const filtroUnidade = ref("ativos");
const filtroSetor = ref("ativos");
const filtroTipoSetor = ref("ativos");

const unidadesFiltradas = computed(() => {
  const lista = sUnidade.listagem || [];
  if (filtroUnidade.value === "ativos") return lista.filter((x) => !x.inativo);
  return lista;
});

const setoresFiltrados = computed(() => {
  const lista = sSetor.listagem || [];
  if (filtroSetor.value === "ativos") return lista.filter((x) => !x.inativo);
  return lista;
});

const tiposSetorFiltrados = computed(() => {
  const lista = sTipoSetor.listagem || [];
  if (filtroTipoSetor.value === "ativos") return lista.filter((x) => !x.inativo);
  return lista;
});

// --- HELPERS ---

const extrairErro = (error, fallback) => {
  const data = error.response?.data;
  if (!data) return fallback;
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0];
    if (primeiro) return primeiro;
  }
  return data.mensagem || data.message || fallback;
};

// --- DIALOG UNIDADE ---

const dialogUnidade = ref(false);
const isNovoUnidade = ref(false);
const modelUnidade = ref({});

const abrirNovaUnidade = () => {
  modelUnidade.value = { descricao: "", codfilial: null };
  isNovoUnidade.value = true;
  dialogUnidade.value = true;
};

const editarUnidade = (unidade) => {
  modelUnidade.value = {
    codunidadenegocio: unidade.codunidadenegocio,
    descricao: unidade.descricao,
    codfilial: unidade.codfilial,
  };
  isNovoUnidade.value = false;
  dialogUnidade.value = true;
};

const submitUnidade = async () => {
  dialogUnidade.value = false;
  try {
    if (isNovoUnidade.value) {
      await sUnidade.criar(modelUnidade.value);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Unidade criada",
      });
    } else {
      await sUnidade.atualizar(
        modelUnidade.value.codunidadenegocio,
        modelUnidade.value
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Unidade alterada",
      });
    }
    await sUnidade.getListagem();
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao salvar unidade"),
    });
  }
};

const excluirUnidade = (unidade) => {
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
        message: extrairErro(error, "Erro ao excluir unidade"),
      });
    }
  });
};

const inativarUnidade = async (unidade) => {
  try {
    await sUnidade.inativar(unidade.codunidadenegocio);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Inativado",
    });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao inativar"),
    });
  }
};

const ativarUnidade = async (unidade) => {
  try {
    await sUnidade.ativar(unidade.codunidadenegocio);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Ativado",
    });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao ativar"),
    });
  }
};

// --- DIALOG SETOR ---

const dialogSetor = ref(false);
const isNovoSetor = ref(false);
const modelSetor = ref({});

const abrirNovoSetor = () => {
  modelSetor.value = {
    setor: "",
    codunidadenegocio: null,
    codtiposetor: null,
    indicadorvendedor: false,
    indicadorcaixa: false,
    indicadorcoletivo: false,
  };
  isNovoSetor.value = true;
  dialogSetor.value = true;
};

const editarSetor = (setor) => {
  modelSetor.value = {
    codsetor: setor.codsetor,
    setor: setor.setor,
    codunidadenegocio: setor.codunidadenegocio,
    codtiposetor: setor.codtiposetor,
    indicadorvendedor: setor.indicadorvendedor,
    indicadorcaixa: setor.indicadorcaixa,
    indicadorcoletivo: setor.indicadorcoletivo,
  };
  isNovoSetor.value = false;
  dialogSetor.value = true;
};

const submitSetor = async () => {
  dialogSetor.value = false;
  try {
    if (isNovoSetor.value) {
      await sSetor.criar(modelSetor.value);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Setor criado",
      });
    } else {
      await sSetor.atualizar(modelSetor.value.codsetor, modelSetor.value);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Setor alterado",
      });
    }
    await sSetor.getListagem();
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao salvar setor"),
    });
  }
};

const excluirSetor = (setor) => {
  $q.dialog({
    title: "Excluir Setor",
    message: 'Tem certeza que deseja excluir "' + setor.setor + '"?',
    cancel: true,
  }).onOk(async () => {
    try {
      await sSetor.excluir(setor.codsetor);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Setor excluído",
      });
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao excluir setor"),
      });
    }
  });
};

const inativarSetor = async (setor) => {
  try {
    await sSetor.inativar(setor.codsetor);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Inativado",
    });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao inativar"),
    });
  }
};

const ativarSetor = async (setor) => {
  try {
    await sSetor.ativar(setor.codsetor);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Ativado",
    });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao ativar"),
    });
  }
};

// --- DIALOG TIPO SETOR ---

const dialogTipoSetor = ref(false);
const isNovoTipoSetor = ref(false);
const modelTipoSetor = ref({});

const abrirNovoTipoSetor = () => {
  modelTipoSetor.value = { tiposetor: "" };
  isNovoTipoSetor.value = true;
  dialogTipoSetor.value = true;
};

const editarTipoSetor = (tipo) => {
  modelTipoSetor.value = {
    codtiposetor: tipo.codtiposetor,
    tiposetor: tipo.tiposetor,
  };
  isNovoTipoSetor.value = false;
  dialogTipoSetor.value = true;
};

const submitTipoSetor = async () => {
  dialogTipoSetor.value = false;
  try {
    if (isNovoTipoSetor.value) {
      await sTipoSetor.criar(modelTipoSetor.value);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Tipo criado",
      });
    } else {
      await sTipoSetor.atualizar(
        modelTipoSetor.value.codtiposetor,
        modelTipoSetor.value
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Tipo alterado",
      });
    }
    await sTipoSetor.getListagem();
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao salvar tipo de setor"),
    });
  }
};

const excluirTipoSetor = (tipo) => {
  $q.dialog({
    title: "Excluir Tipo de Setor",
    message: 'Tem certeza que deseja excluir "' + tipo.tiposetor + '"?',
    cancel: true,
  }).onOk(async () => {
    try {
      await sTipoSetor.excluir(tipo.codtiposetor);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Tipo excluído",
      });
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao excluir tipo de setor"),
      });
    }
  });
};

const inativarTipoSetor = async (tipo) => {
  try {
    await sTipoSetor.inativar(tipo.codtiposetor);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Inativado",
    });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao inativar"),
    });
  }
};

const ativarTipoSetor = async (tipo) => {
  try {
    await sTipoSetor.ativar(tipo.codtiposetor);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Ativado",
    });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao ativar"),
    });
  }
};

// --- LIFECYCLE ---

onMounted(async () => {
  try {
    await Promise.all([
      sUnidade.getListagem(),
      sSetor.getListagem(),
      sTipoSetor.getListagem(),
    ]);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao carregar dados"),
    });
  }
});
</script>

<template>
  <!-- DIALOG UNIDADE -->
  <q-dialog v-model="dialogUnidade">
    <q-card bordered flat style="width: 400px; max-width: 90vw">
      <q-form @submit="submitUnidade()">
        <q-card-section class="text-grey-9 text-overline">
          <template v-if="isNovoUnidade">NOVA UNIDADE DE NEGÓCIO</template>
          <template v-else>EDITAR UNIDADE DE NEGÓCIO</template>
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-input
                outlined
                v-model="modelUnidade.descricao"
                label="Descrição"
                autofocus
                :rules="[
                  (val) => (val && val.length > 0) || 'Descrição obrigatória',
                ]"
              />
            </div>
            <div class="col-12">
              <SelectFilial
                v-model="modelUnidade.codfilial"
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

  <!-- DIALOG SETOR -->
  <q-dialog v-model="dialogSetor">
    <q-card bordered flat style="width: 400px; max-width: 90vw">
      <q-form @submit="submitSetor()">
        <q-card-section class="text-grey-9 text-overline">
          <template v-if="isNovoSetor">NOVO SETOR</template>
          <template v-else>EDITAR SETOR</template>
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-input
                outlined
                v-model="modelSetor.setor"
                label="Nome do Setor"
                autofocus
                :rules="[
                  (val) => (val && val.length > 0) || 'Nome obrigatório',
                ]"
              />
            </div>
            <div class="col-12">
              <SelectUnidadeNegocio
                v-model="modelSetor.codunidadenegocio"
                outlined
                label="Unidade de Negócio"
                :rules="[(val) => !!val || 'Obrigatório']"
              />
            </div>
            <div class="col-12">
              <SelectTipoSetor
                v-model="modelSetor.codtiposetor"
                outlined
                label="Tipo de Setor"
                :rules="[(val) => !!val || 'Obrigatório']"
              />
            </div>
            <div class="col-12">
              <q-toggle
                v-model="modelSetor.indicadorvendedor"
                label="Indicador Vendedor"
              />
              <q-toggle
                v-model="modelSetor.indicadorcaixa"
                label="Indicador Caixa"
              />
              <q-toggle
                v-model="modelSetor.indicadorcoletivo"
                label="Indicador Coletivo"
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

  <!-- DIALOG TIPO SETOR -->
  <q-dialog v-model="dialogTipoSetor">
    <q-card bordered flat style="width: 400px; max-width: 90vw">
      <q-form @submit="submitTipoSetor()">
        <q-card-section class="text-grey-9 text-overline">
          <template v-if="isNovoTipoSetor">NOVO TIPO DE SETOR</template>
          <template v-else>EDITAR TIPO DE SETOR</template>
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <q-input
            outlined
            v-model="modelTipoSetor.tiposetor"
            label="Tipo de Setor"
            autofocus
            :rules="[
              (val) => (val && val.length > 0) || 'Nome obrigatório',
            ]"
          />
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
      <span class="q-pl-sm">Unidades &amp; Setores</span>
    </template>

    <template #content>
      <q-page>
        <div style="max-width: 1200px; margin: auto" class="q-pa-md">
          <div class="row q-col-gutter-md">
            <!-- COLUNA ESQUERDA: UNIDADES DE NEGÓCIO -->
            <div class="col-xs-12 col-md-5">
              <q-card bordered flat>
                <q-card-section
                  class="text-grey-9 text-overline row items-center"
                >
                  UNIDADES DE NEGÓCIO
                  <q-space />
                  <q-btn-toggle
                    v-model="filtroUnidade"
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
                    @click="abrirNovaUnidade()"
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
                          <q-btn
                            flat
                            dense
                            round
                            icon="edit"
                            size="sm"
                            color="grey-7"
                            @click="editarUnidade(unidade)"
                          >
                            <q-tooltip>Editar</q-tooltip>
                          </q-btn>
                          <q-btn
                            v-if="!unidade.inativo"
                            flat
                            dense
                            round
                            icon="pause"
                            size="sm"
                            color="grey-7"
                            @click="inativarUnidade(unidade)"
                          >
                            <q-tooltip>Inativar</q-tooltip>
                          </q-btn>
                          <q-btn
                            v-if="unidade.inativo"
                            flat
                            dense
                            round
                            icon="play_arrow"
                            size="sm"
                            color="grey-7"
                            @click="ativarUnidade(unidade)"
                          >
                            <q-tooltip>Ativar</q-tooltip>
                          </q-btn>
                          <q-btn
                            flat
                            dense
                            round
                            icon="delete"
                            size="sm"
                            color="grey-7"
                            @click="excluirUnidade(unidade)"
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

              <!-- TIPOS DE SETOR -->
              <q-card bordered flat class="q-mt-md">
                <q-card-section
                  class="text-grey-9 text-overline row items-center"
                >
                  TIPOS DE SETOR
                  <q-space />
                  <q-btn-toggle
                    v-model="filtroTipoSetor"
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
                    @click="abrirNovoTipoSetor()"
                  >
                    <q-tooltip>Novo Tipo</q-tooltip>
                  </q-btn>
                </q-card-section>

                <q-list v-if="tiposSetorFiltrados.length > 0">
                  <template
                    v-for="tipo in tiposSetorFiltrados"
                    :key="tipo.codtiposetor"
                  >
                    <q-separator inset />
                    <q-item>
                      <q-item-section>
                        <q-item-label
                          :class="tipo.inativo ? 'text-strike' : ''"
                        >
                          {{ tipo.tiposetor }}
                        </q-item-label>
                        <q-item-label
                          caption
                          class="text-red-14"
                          v-if="tipo.inativo"
                        >
                          Inativo desde: {{ formataData(tipo.inativo) }}
                        </q-item-label>
                      </q-item-section>

                      <q-item-section side>
                        <q-item-label caption>
                          <q-btn
                            flat
                            dense
                            round
                            icon="edit"
                            size="sm"
                            color="grey-7"
                            @click="editarTipoSetor(tipo)"
                          >
                            <q-tooltip>Editar</q-tooltip>
                          </q-btn>
                          <q-btn
                            v-if="!tipo.inativo"
                            flat
                            dense
                            round
                            icon="pause"
                            size="sm"
                            color="grey-7"
                            @click="inativarTipoSetor(tipo)"
                          >
                            <q-tooltip>Inativar</q-tooltip>
                          </q-btn>
                          <q-btn
                            v-if="tipo.inativo"
                            flat
                            dense
                            round
                            icon="play_arrow"
                            size="sm"
                            color="grey-7"
                            @click="ativarTipoSetor(tipo)"
                          >
                            <q-tooltip>Ativar</q-tooltip>
                          </q-btn>
                          <q-btn
                            flat
                            dense
                            round
                            icon="delete"
                            size="sm"
                            color="grey-7"
                            @click="excluirTipoSetor(tipo)"
                          >
                            <q-tooltip>Excluir</q-tooltip>
                          </q-btn>
                        </q-item-label>
                      </q-item-section>
                    </q-item>
                  </template>
                </q-list>
                <div v-else class="q-pa-md text-center text-grey">
                  Nenhum tipo cadastrado
                </div>
              </q-card>
            </div>

            <!-- COLUNA DIREITA: SETORES -->
            <div class="col-xs-12 col-md-7">
              <q-card bordered flat>
                <q-card-section
                  class="text-grey-9 text-overline row items-center"
                >
                  SETORES
                  <q-space />
                  <q-btn-toggle
                    v-model="filtroSetor"
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
                    @click="abrirNovoSetor()"
                  >
                    <q-tooltip>Novo Setor</q-tooltip>
                  </q-btn>
                </q-card-section>

                <q-list v-if="setoresFiltrados.length > 0">
                  <template
                    v-for="setor in setoresFiltrados"
                    :key="setor.codsetor"
                  >
                    <q-separator inset />
                    <q-item>
                      <q-item-section>
                        <q-item-label
                          :class="setor.inativo ? 'text-strike' : ''"
                        >
                          {{ setor.setor }}
                        </q-item-label>
                        <q-item-label caption v-if="setor.UnidadeNegocio">
                          {{ setor.UnidadeNegocio.descricao }}
                          <span
                            v-if="setor.TipoSetor"
                            class="text-grey-5"
                          >
                            — {{ setor.TipoSetor.tiposetor }}
                          </span>
                        </q-item-label>
                        <q-item-label
                          caption
                          class="text-red-14"
                          v-if="setor.inativo"
                        >
                          Inativo desde: {{ formataData(setor.inativo) }}
                        </q-item-label>
                        <q-item-label caption v-if="!setor.inativo">
                          <q-badge
                            v-if="setor.indicadorvendedor"
                            color="blue"
                            label="Vendedor"
                            class="q-mr-xs"
                          />
                          <q-badge
                            v-if="setor.indicadorcaixa"
                            color="purple"
                            label="Caixa"
                            class="q-mr-xs"
                          />
                          <q-badge
                            v-if="setor.indicadorcoletivo"
                            color="teal"
                            label="Coletivo"
                          />
                        </q-item-label>
                      </q-item-section>

                      <q-item-section side>
                        <q-item-label caption>
                          <q-btn
                            flat
                            dense
                            round
                            icon="edit"
                            size="sm"
                            color="grey-7"
                            @click="editarSetor(setor)"
                          >
                            <q-tooltip>Editar</q-tooltip>
                          </q-btn>
                          <q-btn
                            v-if="!setor.inativo"
                            flat
                            dense
                            round
                            icon="pause"
                            size="sm"
                            color="grey-7"
                            @click="inativarSetor(setor)"
                          >
                            <q-tooltip>Inativar</q-tooltip>
                          </q-btn>
                          <q-btn
                            v-if="setor.inativo"
                            flat
                            dense
                            round
                            icon="play_arrow"
                            size="sm"
                            color="grey-7"
                            @click="ativarSetor(setor)"
                          >
                            <q-tooltip>Ativar</q-tooltip>
                          </q-btn>
                          <q-btn
                            flat
                            dense
                            round
                            icon="delete"
                            size="sm"
                            color="grey-7"
                            @click="excluirSetor(setor)"
                          >
                            <q-tooltip>Excluir</q-tooltip>
                          </q-btn>
                        </q-item-label>
                      </q-item-section>
                    </q-item>
                  </template>
                </q-list>
                <div v-else class="q-pa-md text-center text-grey">
                  Nenhum setor cadastrado
                </div>
              </q-card>
            </div>
          </div>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>
