<script setup>
import { ref, computed, onMounted } from "vue";
import { useQuasar } from "quasar";
import { cargoStore } from "src/stores/cargo";
import { guardaToken } from "src/stores";
import { formataData } from "src/utils/formatador";
import MGLayout from "layouts/MGLayout.vue";
import DialogCargo from "components/cargo/DialogCargo.vue";

const $q = useQuasar();
const sCargo = cargoStore();
const user = guardaToken();

// --- FILTROS ---

const filtro = ref("ativos");

const cargosFiltrados = computed(() => {
  const lista = sCargo.listagem || [];
  if (filtro.value === "ativos") return lista.filter((x) => !x.inativo);
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

const formataMoeda = (valor) => {
  return new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
  }).format(valor || 0);
};

// --- DIALOG CARGO ---

const dialogCargoRef = ref(null);

const onSubmit = async (model, isNovo) => {
  try {
    if (isNovo) {
      await sCargo.criar(model);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Cargo criado",
      });
    } else {
      await sCargo.atualizar(model.codcargo, model);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Cargo alterado",
      });
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao salvar cargo"),
    });
  }
};

const excluir = (cargo) => {
  $q.dialog({
    title: "Excluir Cargo",
    message: 'Tem certeza que deseja excluir "' + cargo.cargo + '"?',
    cancel: true,
  }).onOk(async () => {
    try {
      await sCargo.excluir(cargo.codcargo);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Cargo excluído",
      });
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: extrairErro(error, "Erro ao excluir cargo"),
      });
    }
  });
};

const inativar = async (cargo) => {
  try {
    await sCargo.inativar(cargo.codcargo);
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

const ativar = async (cargo) => {
  try {
    await sCargo.ativar(cargo.codcargo);
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
    await sCargo.getListagem();
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao carregar cargos"),
    });
  }
});
</script>

<template>
  <DialogCargo ref="dialogCargoRef" @submit="onSubmit" />

  <MGLayout>
    <template #tituloPagina>
      <span class="q-pl-sm">Cargos</span>
    </template>

    <template #content>
      <q-page>
        <div style="max-width: 800px; margin: auto" class="q-pa-md">
          <q-card bordered flat>
            <q-card-section class="text-grey-9 text-overline row items-center">
              CARGOS
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
                @click="dialogCargoRef.abrirNovo()"
              >
                <q-tooltip>Novo Cargo</q-tooltip>
              </q-btn>
            </q-card-section>

            <q-list v-if="cargosFiltrados.length > 0">
              <template v-for="cargo in cargosFiltrados" :key="cargo.codcargo">
                <q-separator inset />
                <q-item>
                  <q-item-section avatar>
                    <q-avatar color="primary" text-color="white" size="35px">
                      {{ cargo.cargo?.charAt(0) }}
                    </q-avatar>
                  </q-item-section>

                  <q-item-section>
                    <q-item-label :class="cargo.inativo ? 'text-strike' : ''">
                      {{ cargo.cargo }}
                    </q-item-label>
                    <q-item-label caption>
                      {{ formataMoeda(cargo.salario) }}
                      <span v-if="cargo.adicional" class="text-grey-5">
                        — {{ cargo.adicional }}% adicional
                      </span>
                      <q-badge
                        v-if="cargo.colaboradores_ativos > 0"
                        color="blue"
                        :label="cargo.colaboradores_ativos + ' colab.'"
                        class="q-ml-xs"
                      />
                    </q-item-label>
                    <q-item-label
                      caption
                      class="text-red-14"
                      v-if="cargo.inativo"
                    >
                      Inativo desde: {{ formataData(cargo.inativo) }}
                    </q-item-label>
                  </q-item-section>

                  <q-item-section side>
                    <q-item-label caption>
                      <q-btn
                        flat
                        dense
                        round
                        icon="group"
                        size="sm"
                        color="grey-7"
                        :to="'/cargo/' + cargo.codcargo"
                      >
                        <q-tooltip>Colaboradores</q-tooltip>
                      </q-btn>
                      <q-btn
                        flat
                        dense
                        round
                        icon="edit"
                        size="sm"
                        color="grey-7"
                        @click="dialogCargoRef.editar(cargo)"
                      >
                        <q-tooltip>Editar</q-tooltip>
                      </q-btn>
                      <q-btn
                        v-if="!cargo.inativo"
                        flat
                        dense
                        round
                        icon="pause"
                        size="sm"
                        color="grey-7"
                        @click="inativar(cargo)"
                      >
                        <q-tooltip>Inativar</q-tooltip>
                      </q-btn>
                      <q-btn
                        v-if="cargo.inativo"
                        flat
                        dense
                        round
                        icon="play_arrow"
                        size="sm"
                        color="grey-7"
                        @click="ativar(cargo)"
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
                        @click="excluir(cargo)"
                      >
                        <q-tooltip>Excluir</q-tooltip>
                      </q-btn>
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-list>
            <div v-else class="q-pa-md text-center text-grey">
              Nenhum cargo cadastrado
            </div>
          </q-card>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>
