<script setup>
import { ref, onMounted, watch } from "vue";
import { useQuasar, debounce } from "quasar";
import { useRoute } from "vue-router";
import { pessoaStore } from "stores/pessoa";
import { guardaToken } from "src/stores";
import { formataData, formataFromNow } from "src/utils/formatador";
import IconeInfoCriacao from "components/IconeInfoCriacao.vue";

const $q = useQuasar();
const sPessoa = pessoaStore();
const route = useRoute();
const user = guardaToken();
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

const scrollHistorico = async (index, done) => {
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
};

const novaCobranca = async () => {
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
};

const editarHistorico = (codcobrancahistorico, historico) => {
  cobrancaNova.value = false;
  dialogEditarHistorico.value = true;
  modelCobrancaHistorico.value = {
    historico: historico,
    codcobrancahistorico: codcobrancahistorico,
  };
};

const deletarHistorico = (codcobrancahistorico) => {
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
};

const salvarHistorico = async () => {
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
};

const submit = () => {
  cobrancaNova.value ? novaCobranca() : salvarHistorico();
};

onMounted(() => {
  buscarCobrancas();
});

watch(
  () => route.params.id,
  (novoId) => {
    if (novoId) {
      buscarCobrancas();
    }
  }
);
</script>

<template>
  <!-- Dialog Editar Histórico -->
  <q-dialog v-model="dialogEditarHistorico">
    <q-card bordered flat style="width: 600px; max-width: 90vw">
      <q-form
        @submit="submit()"
      >
        <q-card-section class="text-grey-9 text-overline row">
          <template v-if="cobrancaNova">NOVO HISTÓRICO DE COBRANÇA</template>
          <template v-else>EDITAR HISTÓRICO DE COBRANÇA</template>
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <q-input
            outlined
            v-model="modelCobrancaHistorico.historico"
            autofocus
            autogrow
            type="textarea"
            input-style="min-height: 5em"
            label="Histórico"
            :rules="[(val) => (val && val.length > 0) || 'Histórico obrigatório']"
          />
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <q-infinite-scroll @load="scrollHistorico" :disable="loading">
    <q-card bordered flat>
      <q-card-section class="text-grey-9 text-overline row">
        HISTÓRICO DE COBRANÇA
        <q-space />
        <q-btn
          flat
          round
          dense
          icon="add"
          size="sm"
          color="primary"
          v-if="user.verificaPermissaoUsuario('Publico')"
          @click="
            (dialogEditarHistorico = true),
              (modelCobrancaHistorico = {}),
              (cobrancaNova = true)
          "
        />
      </q-card-section>

      <q-list v-if="HistoricosCobranca?.length > 0">
        <template
          v-for="historico in HistoricosCobranca"
          v-bind:key="historico.codcobrancahistorico"
        >
          <q-separator inset />
          <q-item>
            <q-item-section avatar>
              <q-btn round flat icon="history" color="primary" />
            </q-item-section>

            <q-item-section>
              <q-item-label style="white-space: pre-wrap">
                {{ historico.historico }}

                <!-- INFO -->
                <icone-info-criacao
                  :usuariocriacao="historico.usuariocriacao"
                  :criacao="historico.criacao"
                  :usuarioalteracao="historico.usuarioalteracao"
                  :alteracao="historico.alteracao"
                />
              </q-item-label>

              <q-item-label caption>
                {{ historico.usuariocriacao }}
                {{ formataFromNow(historico.criacao) }}
              </q-item-label>
            </q-item-section>

            <q-item-section side>
              <q-item-label
                caption
                v-if="user.verificaPermissaoUsuario('Publico')"
              >
                <!-- EDITAR -->
                <q-btn
                  flat
                  dense
                  round
                  icon="edit"
                  size="sm"
                  color="grey-7"
                  @click="
                    editarHistorico(
                      historico.codcobrancahistorico,
                      historico.historico
                    )
                  "
                >
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>

                <!-- EXCLUIR -->
                <q-btn
                  flat
                  dense
                  round
                  icon="delete"
                  size="sm"
                  color="grey-7"
                  @click="deletarHistorico(historico.codcobrancahistorico)"
                >
                  <q-tooltip>Excluir</q-tooltip>
                </q-btn>
              </q-item-label>
            </q-item-section>
          </q-item>
        </template>
      </q-list>
      <div v-else class="q-pa-md text-center text-grey">
        Nenhum histórico de cobrança
      </div>
    </q-card>
  </q-infinite-scroll>
</template>

<style scoped></style>
