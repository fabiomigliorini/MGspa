<script setup>
import { defineAsyncComponent, ref, onMounted } from "vue";
import { useQuasar } from "quasar";
import { estadoCivilStore } from "src/stores/estado-civil";

const MGLayout = defineAsyncComponent(() => import("layouts/MGLayout.vue"));

const $q = useQuasar();
const store = estadoCivilStore();

const estadosCivis = ref([]);
const loading = ref(false);
const dialog = ref(false);
const editando = ref(false);
const model = ref({ estadocivil: "" });
const filtro = ref({
  estadocivil: null,
  status: "ativos",
});

const statusOptions = [
  { label: "Ativos", value: "ativos" },
  { label: "Inativos", value: "inativos" },
  { label: "Todos", value: "todos" },
];
const pagination = ref({
  rowsPerPage: 50,
});

const columns = [
  {
    name: "codestadocivil",
    label: "Código",
    field: "codestadocivil",
    align: "left",
    sortable: true,
  },
  {
    name: "estadocivil",
    label: "Descrição",
    field: "estadocivil",
    align: "left",
    sortable: true,
  },
  {
    name: "inativo",
    label: "Status",
    field: "inativo",
    align: "center",
  },
  {
    name: "acoes",
    label: "Ações",
    field: "acoes",
    align: "center",
  },
];

const buscar = async () => {
  loading.value = true;
  $q.loadingBar.start();
  try {
    store.filtro = filtro.value;
    await store.index();
    estadosCivis.value = store.estadosCivis;
  } catch (error) {
    $q.notify({
      type: "negative",
      message: "Erro ao carregar estados civis",
    });
  } finally {
    loading.value = false;
    $q.loadingBar.stop();
  }
};

const abrirNovo = () => {
  model.value = { estadocivil: "" };
  editando.value = false;
  dialog.value = true;
};

const abrirEditar = (item) => {
  model.value = { ...item };
  editando.value = true;
  dialog.value = true;
};

const salvar = async () => {
  if (!model.value.estadocivil || model.value.estadocivil.trim() === "") {
    $q.notify({
      type: "warning",
      message: "Informe a descrição",
    });
    return;
  }

  loading.value = true;
  try {
    if (editando.value) {
      await store.update(model.value.codestadocivil, model.value);
      $q.notify({
        type: "positive",
        message: "Estado civil atualizado com sucesso",
      });
    } else {
      await store.store(model.value);
      $q.notify({
        type: "positive",
        message: "Estado civil criado com sucesso",
      });
    }
    estadosCivis.value = store.estadosCivis;
    dialog.value = false;
  } catch (error) {
    $q.notify({
      type: "negative",
      message: "Erro ao salvar estado civil",
    });
  } finally {
    loading.value = false;
  }
};

const toggleInativo = async (item) => {
  loading.value = true;
  try {
    if (item.inativo) {
      await store.ativar(item.codestadocivil);
      $q.notify({
        type: "positive",
        message: "Estado civil ativado com sucesso",
      });
    } else {
      await store.inativar(item.codestadocivil);
      $q.notify({
        type: "positive",
        message: "Estado civil inativado com sucesso",
      });
    }
    estadosCivis.value = store.estadosCivis;
  } catch (error) {
    $q.notify({
      type: "negative",
      message: "Erro ao alterar status",
    });
  } finally {
    loading.value = false;
  }
};

const excluir = (item) => {
  $q.dialog({
    title: "Confirmar exclusão",
    message: `Deseja realmente excluir o estado civil "${item.estadocivil}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    loading.value = true;
    try {
      await store.destroy(item.codestadocivil);
      estadosCivis.value = store.estadosCivis;
      $q.notify({
        type: "positive",
        message: "Estado civil excluído com sucesso",
      });
    } catch (error) {
      $q.notify({
        type: "negative",
        message: "Erro ao excluir estado civil. Verifique se não está em uso.",
      });
    } finally {
      loading.value = false;
    }
  });
};

onMounted(() => {
  buscar();
});
</script>

<template>
  <MGLayout drawer>
    <template #tituloPagina>
      Estados Civis
    </template>

    <template #content>
      <div class="q-pa-md">
        <q-table
          :rows="estadosCivis"
          :columns="columns"
          row-key="codestadocivil"
          :loading="loading"
          :pagination="pagination"
          flat
          bordered
        >
          <template v-slot:body-cell-inativo="props">
            <q-td :props="props">
              <q-chip
                :color="props.row.inativo ? 'red' : 'green'"
                text-color="white"
                dense
              >
                {{ props.row.inativo ? "Inativo" : "Ativo" }}
              </q-chip>
            </q-td>
          </template>

          <template v-slot:body-cell-acoes="props">
            <q-td :props="props">
              <q-btn
                flat
                round
                dense
                icon="edit"
                color="primary"
                @click="abrirEditar(props.row)"
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                dense
                :icon="props.row.inativo ? 'check_circle' : 'block'"
                :color="props.row.inativo ? 'green' : 'orange'"
                @click="toggleInativo(props.row)"
              >
                <q-tooltip>{{
                  props.row.inativo ? "Ativar" : "Inativar"
                }}</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                dense
                icon="delete"
                color="negative"
                @click="excluir(props.row)"
              >
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-td>
          </template>

          <template v-slot:no-data>
            <div class="full-width row flex-center q-gutter-sm">
              <q-icon size="2em" name="sentiment_dissatisfied" />
              <span>Nenhum estado civil encontrado</span>
            </div>
          </template>
        </q-table>
      </div>

      <q-dialog v-model="dialog">
        <q-card style="min-width: 350px">
          <q-card-section>
            <div class="text-h6">{{ editando ? "Editar" : "Novo" }} Estado Civil</div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <q-input
              outlined
              v-model="model.estadocivil"
              label="Descrição"
              autofocus
              @keyup.enter="salvar"
            />
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey" v-close-popup />
            <q-btn
              flat
              label="Salvar"
              color="primary"
              @click="salvar"
              :loading="loading"
            />
          </q-card-actions>
        </q-card>
      </q-dialog>

      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-fab icon="add" color="accent" @click="abrirNovo" />
      </q-page-sticky>
    </template>

    <template #drawer>
      <div class="q-pa-none q-pt-sm">
        <q-card flat>
          <q-list>
            <q-item-label header>
              Filtros
              <q-btn icon="replay" @click="buscar()" flat round no-caps />
            </q-item-label>
          </q-list>
        </q-card>
        <div class="q-pa-md q-gutter-md">
          <q-input
            outlined
            v-model="filtro.estadocivil"
            label="Buscar"
            @change="buscar"
            clearable
          >
            <template v-slot:prepend>
              <q-icon name="search" />
            </template>
          </q-input>

          <q-select
            outlined
            v-model="filtro.status"
            :options="statusOptions"
            label="Status"
            emit-value
            map-options
            @update:model-value="buscar"
          />
        </div>
      </div>
    </template>
  </MGLayout>
</template>
