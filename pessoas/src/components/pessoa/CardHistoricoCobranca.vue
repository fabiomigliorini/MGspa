<script setup>
import { ref, onMounted } from "vue";
import { useQuasar, debounce } from "quasar";
import { useRoute } from "vue-router";
import { pessoaStore } from "stores/pessoa";
import { guardaToken } from "src/stores";
import { formataDocumetos } from "src/stores/formataDocumentos";

const $q = useQuasar();
const sPessoa = pessoaStore();
const route = useRoute();
const user = guardaToken();
const Documentos = formataDocumetos();

const dialogEditarHistorico = ref(false);
const modelCobrancaHistorico = ref([]);
const loading = ref(true);
const cobrancaNova = ref(false);
const HistoricosCobranca = ref([]);
const Paginas = ref({ page: 1 });

const buscarCobrancas = debounce(async () => {
  try {
    Paginas.value.page = 1;
    const ret = await sPessoa.getCobrancaHistorico(
      route.params.id,
      Paginas.value
    );
    HistoricosCobranca.value = ret.data.data;
    loading.value = false;
    $q.loadingBar.stop();
    if (ret.data.data.length == 0) {
      loading.value = true;
    }
  } catch (error) {
    $q.loadingBar.stop();
  }
}, 500);

async function scrollHistorico(index, done) {
  loading.value = true;
  $q.loadingBar.start();
  Paginas.value.page++;
  const ret = await sPessoa.getCobrancaHistorico(
    route.params.id,
    Paginas.value
  );
  HistoricosCobranca.value.push(...ret.data.data);
  loading.value = false;
  if (ret.data.data.length == 0) {
    loading.value = true;
  }
  $q.loadingBar.stop();
  done();
}

async function novaCobranca() {
  const ret = await sPessoa.novoHistoricoCobranca(
    route.params.id,
    modelCobrancaHistorico.value.historico
  );
  if (ret.data.data) {
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Histórico criado!",
    });
    buscarCobrancas();
    dialogEditarHistorico.value = false;
  } else {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "warning",
      message: "Erro, tente novamente",
    });
  }
}

function editarHistorico(codcobrancahistorico, historico) {
  cobrancaNova.value = false;
  dialogEditarHistorico.value = true;
  modelCobrancaHistorico.value = {
    historico: historico,
    codcobrancahistorico: codcobrancahistorico,
  };
}

function deletarHistorico(codcobrancahistorico) {
  $q.dialog({
    title: "Excluir Histórico",
    message: "Tem certeza que deseja excluir esse histórico de cobrança?",
    cancel: true,
  }).onOk(async () => {
    try {
      await sPessoa.deletaCobrancaHistorico(
        route.params.id,
        codcobrancahistorico
      );
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Histórico excluido!",
      });
      buscarCobrancas();
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response.data.message,
      });
    }
  });
}

async function salvarHistorico() {
  try {
    const ret = await sPessoa.salvarHistoricoCobranca(
      route.params.id,
      modelCobrancaHistorico.value.codcobrancahistorico,
      modelCobrancaHistorico.value
    );
    if (ret.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Histórico alterado",
      });
      dialogEditarHistorico.value = false;
      const i = HistoricosCobranca.value.findIndex(
        (item) =>
          item.codcobrancahistorico ===
          modelCobrancaHistorico.value.codcobrancahistorico
      );
      HistoricosCobranca.value[i] = ret.data.data;
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response.data.message,
    });
  }
}

onMounted(() => {
  buscarCobrancas();
});
</script>

<template>
  <q-infinite-scroll @load="scrollHistorico" :disable="loading">
    <q-card bordered>
      <q-card-section class="bg-yellow text-grey-9 q-py-sm">
        <div class="row items-center no-wrap q-gutter-x-sm">
          <q-icon name="history" size="sm" />
          <span class="text-subtitle1 text-weight-medium"
            >Histórico de Cobrança</span
          >
          <q-space />
          <q-btn
            flat
            round
            dense
            icon="add"
            size="sm"
            color="grey-9"
            v-if="user.verificaPermissaoUsuario('Publico')"
            @click="
              (dialogEditarHistorico = true),
                (modelCobrancaHistorico = {}),
                (cobrancaNova = true)
            "
          />
        </div>
      </q-card-section>

      <div
        v-for="historico in HistoricosCobranca"
        v-bind:key="historico.codcobrancahistorico"
      >
        <q-separator />
        <div class="row q-pa-sm items-center">
          <div class="col">
            <div class="text-body2">{{ historico.historico }}</div>
            <div class="text-caption text-grey-7">
              {{ historico.usuariocriacao }}
              {{ Documentos.formataFromNow(historico.criacao) }}
              <q-tooltip>
                {{ Documentos.formataData(historico.criacao) }}
              </q-tooltip>
            </div>
          </div>
          <q-btn-dropdown
            flat
            auto-close
            dense
            v-if="user.verificaPermissaoUsuario('Publico')"
          >
            <q-btn
              flat
              round
              icon="edit"
              @click="
                editarHistorico(
                  historico.codcobrancahistorico,
                  historico.historico
                )
              "
            />
            <q-btn
              flat
              round
              icon="delete"
              @click="deletarHistorico(historico.codcobrancahistorico)"
            />
          </q-btn-dropdown>
        </div>
      </div>
    </q-card>
  </q-infinite-scroll>

  <!-- Dialog Editar Histórico -->
  <q-dialog v-model="dialogEditarHistorico">
    <q-card style="min-width: 350px">
      <q-form
        @submit="cobrancaNova == true ? novaCobranca() : salvarHistorico()"
      >
        <q-card-section>
          <div v-if="cobrancaNova == false" class="text-h6">
            Editar Histórico de cobrança
          </div>
          <div v-else class="text-h6">Novo Histórico de cobrança</div>
        </q-card-section>
        <q-card-section>
          <q-input
            outlined
            v-model="modelCobrancaHistorico.historico"
            autofocus
            label="Histórico"
            :rules="[(val) => (val && val.length > 0) || 'Histórico obrigatório']"
          />
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<style scoped></style>
