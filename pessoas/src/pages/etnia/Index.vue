<script setup>
import { defineAsyncComponent, ref, onMounted } from "vue";
import { useQuasar } from "quasar";
import { etniaStore } from "src/stores/etnia";

const MGLayout = defineAsyncComponent(() => import("layouts/MGLayout.vue"));

const $q = useQuasar();
const store = etniaStore();

const etnias = ref([]);
const loading = ref(false);
const dialog = ref(false);
const editando = ref(false);
const model = ref({ etnia: "" });
const filtro = ref({
  etnia: null,
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
    name: "codetnia",
    label: "Código",
    field: "codetnia",
    align: "left",
    sortable: true,
  },
  {
    name: "etnia",
    label: "Descrição",
    field: "etnia",
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
    etnias.value = store.etnias;
  } catch (error) {
    $q.notify({
      type: "negative",
      message: "Erro ao carregar etnias",
    });
  } finally {
    loading.value = false;
    $q.loadingBar.stop();
  }
};

const abrirNovo = () => {
  model.value = { etnia: "" };
  editando.value = false;
  dialog.value = true;
};

const abrirEditar = (item) => {
  model.value = { ...item };
  editando.value = true;
  dialog.value = true;
};

const salvar = async () => {
  if (!model.value.etnia || model.value.etnia.trim() === "") {
    $q.notify({
      type: "warning",
      message: "Informe a descrição",
    });
    return;
  }

  loading.value = true;
  try {
    if (editando.value) {
      await store.update(model.value.codetnia, model.value);
      $q.notify({
        type: "positive",
        message: "Etnia atualizada com sucesso",
      });
    } else {
      await store.store(model.value);
      $q.notify({
        type: "positive",
        message: "Etnia criada com sucesso",
      });
    }
    etnias.value = store.etnias;
    dialog.value = false;
  } catch (error) {
    $q.notify({
      type: "negative",
      message: "Erro ao salvar etnia",
    });
  } finally {
    loading.value = false;
  }
};

const toggleInativo = async (item) => {
  loading.value = true;
  try {
    if (item.inativo) {
      await store.ativar(item.codetnia);
      $q.notify({
        type: "positive",
        message: "Etnia ativada com sucesso",
      });
    } else {
      await store.inativar(item.codetnia);
      $q.notify({
        type: "positive",
        message: "Etnia inativada com sucesso",
      });
    }
    etnias.value = store.etnias;
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
    message: `Deseja realmente excluir a etnia "${item.etnia}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    loading.value = true;
    try {
      await store.destroy(item.codetnia);
      etnias.value = store.etnias;
      $q.notify({
        type: "positive",
        message: "Etnia excluída com sucesso",
      });
    } catch (error) {
      $q.notify({
        type: "negative",
        message: "Erro ao excluir etnia. Verifique se não está em uso.",
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
      Etnias
    </template>

    <template #content>
      <div class="q-pa-md">
        <q-table
          :rows="etnias"
          :columns="columns"
          row-key="codetnia"
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
              <span>Nenhuma etnia encontrada</span>
            </div>
          </template>
        </q-table>
      </div>

      <q-dialog v-model="dialog">
        <q-card style="min-width: 350px">
          <q-card-section>
            <div class="text-h6">{{ editando ? "Editar" : "Nova" }} Etnia</div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <q-input
              outlined
              v-model="model.etnia"
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
            v-model="filtro.etnia"
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
