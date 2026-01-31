<template>
  <MGLayout drawer>
    <template #tituloPagina>
      Graus de Instrução
    </template>

    <template #content>
      <div class="q-pa-md">
        <q-table
          :rows="grausInstrucao"
          :columns="columns"
          row-key="codgrauinstrucao"
          :loading="loading"
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
              <span>Nenhum grau de instrução encontrado</span>
            </div>
          </template>
        </q-table>
      </div>

      <q-dialog v-model="dialog">
        <q-card style="min-width: 350px">
          <q-card-section>
            <div class="text-h6">{{ editando ? "Editar" : "Novo" }} Grau de Instrução</div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <q-input
              outlined
              v-model="model.grauinstrucao"
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
            v-model="filtro.grauinstrucao"
            label="Buscar"
            @change="buscar"
            clearable
          >
            <template v-slot:prepend>
              <q-icon name="search" />
            </template>
          </q-input>

          <q-toggle
            v-model="filtro.inativo"
            label="Mostrar inativos"
            @update:model-value="buscar"
          />
        </div>
      </div>
    </template>
  </MGLayout>
</template>

<script>
import { defineComponent, defineAsyncComponent, ref, onMounted } from "vue";
import { useQuasar } from "quasar";
import { grauInstrucaoStore } from "src/stores/grau-instrucao";

export default defineComponent({
  name: "GrauInstrucaoIndex",
  components: {
    MGLayout: defineAsyncComponent(() => import("layouts/MGLayout.vue")),
  },

  setup() {
    const $q = useQuasar();
    const store = grauInstrucaoStore();

    const grausInstrucao = ref([]);
    const loading = ref(false);
    const dialog = ref(false);
    const editando = ref(false);
    const model = ref({ grauinstrucao: "" });
    const filtro = ref({
      grauinstrucao: null,
      inativo: false,
    });

    const columns = [
      {
        name: "codgrauinstrucao",
        label: "Código",
        field: "codgrauinstrucao",
        align: "left",
        sortable: true,
      },
      {
        name: "grauinstrucao",
        label: "Descrição",
        field: "grauinstrucao",
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

    async function buscar() {
      loading.value = true;
      $q.loadingBar.start();
      try {
        store.filtro = filtro.value;
        await store.index();
        grausInstrucao.value = store.grausInstrucao;
      } catch (error) {
        $q.notify({
          type: "negative",
          message: "Erro ao carregar graus de instrução",
        });
      } finally {
        loading.value = false;
        $q.loadingBar.stop();
      }
    }

    function abrirNovo() {
      model.value = { grauinstrucao: "" };
      editando.value = false;
      dialog.value = true;
    }

    function abrirEditar(item) {
      model.value = { ...item };
      editando.value = true;
      dialog.value = true;
    }

    async function salvar() {
      if (!model.value.grauinstrucao || model.value.grauinstrucao.trim() === "") {
        $q.notify({
          type: "warning",
          message: "Informe a descrição",
        });
        return;
      }

      loading.value = true;
      try {
        if (editando.value) {
          await store.update(model.value.codgrauinstrucao, model.value);
          $q.notify({
            type: "positive",
            message: "Grau de instrução atualizado com sucesso",
          });
        } else {
          await store.store(model.value);
          $q.notify({
            type: "positive",
            message: "Grau de instrução criado com sucesso",
          });
        }
        grausInstrucao.value = store.grausInstrucao;
        dialog.value = false;
      } catch (error) {
        $q.notify({
          type: "negative",
          message: "Erro ao salvar grau de instrução",
        });
      } finally {
        loading.value = false;
      }
    }

    async function toggleInativo(item) {
      loading.value = true;
      try {
        if (item.inativo) {
          await store.ativar(item.codgrauinstrucao);
          $q.notify({
            type: "positive",
            message: "Grau de instrução ativado com sucesso",
          });
        } else {
          await store.inativar(item.codgrauinstrucao);
          $q.notify({
            type: "positive",
            message: "Grau de instrução inativado com sucesso",
          });
        }
        grausInstrucao.value = store.grausInstrucao;
      } catch (error) {
        $q.notify({
          type: "negative",
          message: "Erro ao alterar status",
        });
      } finally {
        loading.value = false;
      }
    }

    function excluir(item) {
      $q.dialog({
        title: "Confirmar exclusão",
        message: `Deseja realmente excluir o grau de instrução "${item.grauinstrucao}"?`,
        cancel: true,
        persistent: true,
      }).onOk(async () => {
        loading.value = true;
        try {
          await store.destroy(item.codgrauinstrucao);
          grausInstrucao.value = store.grausInstrucao;
          $q.notify({
            type: "positive",
            message: "Grau de instrução excluído com sucesso",
          });
        } catch (error) {
          $q.notify({
            type: "negative",
            message: "Erro ao excluir grau de instrução. Verifique se não está em uso.",
          });
        } finally {
          loading.value = false;
        }
      });
    }

    onMounted(() => {
      buscar();
    });

    return {
      grausInstrucao,
      loading,
      dialog,
      editando,
      model,
      filtro,
      columns,
      buscar,
      abrirNovo,
      abrirEditar,
      salvar,
      toggleInativo,
      excluir,
    };
  },
});
</script>
