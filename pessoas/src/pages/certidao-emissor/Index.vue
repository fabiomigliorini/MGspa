<script setup>
import { defineAsyncComponent, ref, onMounted } from "vue";
import { useQuasar } from "quasar";
import { certidaoEmissorStore } from "src/stores/certidao-emissor";

const MGLayout = defineAsyncComponent(() => import("layouts/MGLayout.vue"));

const $q = useQuasar();
const store = certidaoEmissorStore();

const certidaoEmissores = ref([]);
const loading = ref(false);
const dialog = ref(false);
const editando = ref(false);
const model = ref({ certidaoemissor: "" });
const filtro = ref({
  certidaoemissor: null,
  inativo: 1,
});

const statusOptions = [
  { label: "Ativos", value: 1 },
  { label: "Inativos", value: 2 },
  { label: "Todos", value: 9 },
];
const pagination = ref({
  rowsPerPage: 50,
});

const columns = [
  {
    name: "codcertidaoemissor",
    label: "Código",
    field: "codcertidaoemissor",
    align: "left",
    sortable: true,
  },
  {
    name: "certidaoemissor",
    label: "Descrição",
    field: "certidaoemissor",
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
    certidaoEmissores.value = store.certidaoEmissores;
  } catch (error) {
    $q.notify({
      type: "negative",
      message: "Erro ao carregar certidão emissores",
    });
  } finally {
    loading.value = false;
    $q.loadingBar.stop();
  }
};

const abrirNovo = () => {
  model.value = { certidaoemissor: "" };
  editando.value = false;
  dialog.value = true;
};

const abrirEditar = (item) => {
  model.value = { ...item };
  editando.value = true;
  dialog.value = true;
};

const salvar = async () => {
  if (
    !model.value.certidaoemissor ||
    model.value.certidaoemissor.trim() === ""
  ) {
    $q.notify({
      type: "warning",
      message: "Informe a descrição",
    });
    return;
  }

  loading.value = true;
  try {
    if (editando.value) {
      await store.update(model.value.codcertidaoemissor, model.value);
      $q.notify({
        type: "positive",
        message: "Certidão emissor atualizada com sucesso",
      });
    } else {
      await store.store(model.value);
      $q.notify({
        type: "positive",
        message: "Certidão emissor criada com sucesso",
      });
    }
    certidaoEmissores.value = store.certidaoEmissores;
    dialog.value = false;
  } catch (error) {
    $q.notify({
      type: "negative",
      message: "Erro ao salvar certidão emissor",
    });
  } finally {
    loading.value = false;
  }
};

const toggleInativo = async (item) => {
  loading.value = true;
  try {
    if (item.inativo) {
      await store.ativar(item.codcertidaoemissor);
      $q.notify({
        type: "positive",
        message: "Certidão emissor ativada com sucesso",
      });
    } else {
      await store.inativar(item.codcertidaoemissor);
      $q.notify({
        type: "positive",
        message: "Certidão emissor inativada com sucesso",
      });
    }
    certidaoEmissores.value = store.certidaoEmissores;
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
    message: `Deseja realmente excluir a certidão emissor "${item.certidaoemissor}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    loading.value = true;
    try {
      await store.destroy(item.codcertidaoemissor);
      certidaoEmissores.value = store.certidaoEmissores;
      $q.notify({
        type: "positive",
        message: "Certidão emissor excluída com sucesso",
      });
    } catch (error) {
      $q.notify({
        type: "negative",
        message:
          "Erro ao excluir certidão emissor. Verifique se não está em uso.",
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
    <template #tituloPagina>Emissores de Certidões</template>

    <template #content>
      <div class="q-pa-md">
        <q-table
          :rows="certidaoEmissores"
          :columns="columns"
          row-key="codcertidaoemissor"
          :loading="loading"
          :pagination="pagination"
          hide-pagination
          flat
          bordered
        >
          <template v-slot:body-cell-inativo="props">
            <q-td :props="props">
              <q-chip
                :color="props.row.inativo ? 'red' : 'green'"
                text-color="white"
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
                size="sm"
                icon="edit"
                color="grey-7"
                @click="abrirEditar(props.row)"
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                size="sm"
                :icon="props.row.inativo ? 'check_circle' : 'block'"
                :color="props.row.inativo ? 'green' : 'grey-7'"
                @click="toggleInativo(props.row)"
              >
                <q-tooltip>{{
                  props.row.inativo ? "Ativar" : "Inativar"
                }}</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                size="sm"
                icon="delete"
                color="grey-7"
                @click="excluir(props.row)"
              >
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-td>
          </template>

          <template v-slot:no-data>
            <div class="full-width row flex-center q-gutter-sm">
              <q-icon size="2em" name="sentiment_dissatisfied" />
              <span>Nenhuma certidão emissor encontrada</span>
            </div>
          </template>
        </q-table>
      </div>

      <q-dialog v-model="dialog">
        <q-card style="min-width: 400px" class="q-pa-md">
          <q-card-section>
            <div class="caption">
              {{ editando ? "Editar" : "Nova" }} Certidão Emissor
            </div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <q-input
              outlined
              v-model="model.certidaoemissor"
              label="Certidão Emissor"
              maxlength="30"
              autofocus
              @keyup.enter="salvar"
            />
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="red" v-close-popup />
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
            v-model="filtro.certidaoemissor"
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
            v-model="filtro.inativo"
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
