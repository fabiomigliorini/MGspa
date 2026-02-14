<script setup>
import { ref, watch } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { pessoaStore } from "stores/pessoa";
import { guardaToken } from "src/stores";
import IconeInfoCriacao from "components/IconeInfoCriacao.vue";
import SelectCertidaoEmissor from "components/pessoa/SelectCertidaoEmissor.vue";
import SelectCertidaoTipo from "components/pessoa/SelectCertidaoTipo.vue";
import {
  formataDataSemHora,
  dataAtual,
  dataFormatoSql,
  formataDataInput,
  localeBrasil,
} from "src/utils/formatador";

const $q = useQuasar();
const sPessoa = pessoaStore();
const route = useRoute();
const user = guardaToken();
const editCertidao = ref(false);
const dialogCertidao = ref(false);
const modelCertidao = ref({});
const filtroCertidaomodel = ref("validas");
const certidoesS = ref([]);

const filtroCertidao = () => {
  if (filtroCertidaomodel.value == "validas") {
    let validas = sPessoa.item.PessoaCertidaoS.filter(
      (x) => x.validade >= dataAtual()
    );
    sPessoa.item.PessoaCertidaoS = validas;
  }
  if (filtroCertidaomodel.value == "todas") {
    sPessoa.item.PessoaCertidaoS = certidoesS.value;
  }
};

const novaCertidao = async () => {
  modelCertidao.value.codpessoa = route.params.id;
  if (modelCertidao.value.validade) {
    modelCertidao.value.validade = dataFormatoSql(modelCertidao.value.validade);
  }
  try {
    const ret = await sPessoa.novaCertidao(modelCertidao.value);
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Certidão criada!",
      });
      dialogCertidao.value = false;
      sPessoa.get(route.params.id);
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response.data.errors.codcertidaoemissor
        ? "O campo Emissor é obrigatório"
        : "O campo Tipo é obrigatório",
    });
  }
};

const editarCertidao = (
  codpessoacertidao,
  codcertidaoemissor,
  numero,
  autenticacao,
  validade,
  codcertidaotipo
) => {
  editCertidao.value = true;
  dialogCertidao.value = true;
  modelCertidao.value = {
    codpessoacertidao: codpessoacertidao,
    codcertidaoemissor: codcertidaoemissor,
    numero: numero,
    autenticacao: autenticacao,
    validade: formataDataInput(validade),
    codcertidaotipo: codcertidaotipo,
  };
};

const salvarCertidao = async () => {
  if (modelCertidao.value.validade) {
    modelCertidao.value.validade = dataFormatoSql(modelCertidao.value.validade);
  }
  try {
    const ret = await sPessoa.salvarEdicaoCertidao(
      modelCertidao.value.codpessoacertidao,
      modelCertidao.value
    );
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Certidão alterada!",
      });
      editCertidao.value = false;
      dialogCertidao.value = false;
      const i = sPessoa.item.PessoaCertidaoS.findIndex(
        (item) =>
          item.codpessoacertidao === modelCertidao.value.codpessoacertidao
      );
      sPessoa.item.PessoaCertidaoS[i] = ret.data.data;
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

const inativaCertidao = async (codpessoacertidao) => {
  try {
    const ret = await sPessoa.inativarCertidao(codpessoacertidao);
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Inativado!",
      });
      const i = sPessoa.item.PessoaCertidaoS.findIndex(
        (item) => item.codpessoacertidao === codpessoacertidao
      );
      sPessoa.item.PessoaCertidaoS[i] = ret.data.data;
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.message,
    });
  }
};

const ativaCertidao = async (codpessoacertidao) => {
  try {
    const ret = await sPessoa.ativarCertidao(codpessoacertidao);
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Ativado!",
      });
      const i = sPessoa.item.PessoaCertidaoS.findIndex(
        (item) => item.codpessoacertidao === codpessoacertidao
      );
      sPessoa.item.PessoaCertidaoS[i] = ret.data.data;
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.message,
    });
  }
};

const deletarCertidao = async (codpessoacertidao) => {
  $q.dialog({
    title: "Excluir Histórico",
    message: "Tem certeza que deseja excluir essa certidão?",
    cancel: true,
  }).onOk(async () => {
    try {
      await sPessoa.deletarCertidao(codpessoacertidao);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Certidão excluida!",
      });
      sPessoa.get(route.params.id);
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

const submit = () => {
  editCertidao.value === false ? novaCertidao() : salvarCertidao();
};

watch(
  () => sPessoa.item,
  (newItem) => {
    if (!newItem) return;
    certidoesS.value = newItem.PessoaCertidaoS;
    filtroCertidaomodel.value = "validas";
    newItem.PessoaCertidaoS = newItem.PessoaCertidaoS.filter(
      (x) => x.validade >= dataAtual()
    );
  },
  { immediate: true }
);
</script>

<template>
  <!-- Dialog Certidões -->
  <q-dialog v-model="dialogCertidao">
    <q-card bordered flat style="width: 600px; max-width: 90vw">
      <q-form @submit="submit()">
        <q-card-section class="text-grey-9 text-overline row">
          <template v-if="editCertidao">EDITAR CERTIDÃO</template>
          <template v-else>NOVA CERTIDÃO</template>
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <q-input
            outlined
            v-model="modelCertidao.numero"
            mask="####################"
            autofocus
            label="Número"
            :rules="[(val) => (val && val.length > 0) || 'Numero obrigatório']"
          />

          <q-input
            outlined
            v-model="modelCertidao.autenticacao"
            class="q-mb-md"
            label="Autenticação"
          />

          <q-input
            outlined
            v-model="modelCertidao.validade"
            mask="##/##/####"
            label="Validade"
            :rules="[
              (val) => (val && val.length > 0) || 'Validade obrigatório',
            ]"
          >
            <template v-slot:append>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy
                  cover
                  transition-show="scale"
                  transition-hide="scale"
                >
                  <q-date
                    v-model="modelCertidao.validade"
                    :locale="localeBrasil"
                    mask="DD/MM/YYYY"
                  >
                    <div class="row items-center justify-end">
                      <q-btn
                        v-close-popup
                        label="Fechar"
                        color="primary"
                        flat
                      />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>

          <select-certidao-emissor v-model="modelCertidao.codcertidaoemissor" />

          <select-certidao-tipo
            v-model="modelCertidao.codcertidaotipo"
            class="q-mt-md"
          />
        </q-card-section>

        <q-separator inset />

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

  <q-card bordered flat>
    <q-card-section class="text-grey-9 text-overline row items-center">
      CERTIDÕES
      <q-space />
      <q-btn-toggle
        v-model="filtroCertidaomodel"
        color="grey-3"
        toggle-color="primary"
        text-color="grey-7"
        toggle-text-color="grey-3"
        unelevated
        dense
        no-caps
        size="sm"
        :options="[
          { label: 'Válidas', value: 'validas' },
          { label: 'Todas', value: 'todas' },
        ]"
        @update:model-value="filtroCertidao()"
      />
      <q-btn
        flat
        round
        dense
        icon="add"
        size="sm"
        color="primary"
        v-if="user.verificaPermissaoUsuario('Publico')"
        @click="
          (dialogCertidao = true), (modelCertidao = {}), (editCertidao = false)
        "
      />
    </q-card-section>

    <q-list v-if="sPessoa.item?.PessoaCertidaoS?.length > 0">
      <template
        v-for="certidao in sPessoa.item?.PessoaCertidaoS"
        v-bind:key="certidao.codpessoacertidao"
      >
        <q-separator inset />
        <q-item>
          <q-item-section avatar>
            <q-btn round flat icon="description" color="primary" />
          </q-item-section>

          <q-item-section>
            <q-item-label
              v-if="certidao.validade"
              class="text-weight-bold"
              :class="certidao.validade < dataAtual() ? 'text-strike' : null"
            >
              Validade: {{ formataDataSemHora(certidao.validade) }}
              <!-- INFO -->
              <icone-info-criacao
                :usuariocriacao="certidao.usuariocriacao"
                :criacao="certidao.criacao"
                :usuarioalteracao="certidao.usuarioalteracao"
                :alteracao="certidao.alteracao"
              />
            </q-item-label>
            <q-item-label caption>
              {{ certidao.certidaotipo }} {{ certidao.certidaoemissor }}
            </q-item-label>
            <q-item-label caption>
              {{ certidao.numero }}
            </q-item-label>
            <q-item-label
              caption
              v-if="certidao.autenticacao"
              :class="certidao.validade < dataAtual() ? 'text-strike' : null"
            >
              {{ certidao.autenticacao }}
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
                  editarCertidao(
                    certidao.codpessoacertidao,
                    certidao.codcertidaoemissor,
                    certidao.numero,
                    certidao.autenticacao,
                    certidao.validade,
                    certidao.codcertidaotipo
                  )
                "
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>

              <!-- INATIVAR -->
              <q-btn
                v-if="!certidao.inativo"
                flat
                dense
                round
                icon="pause"
                size="sm"
                color="grey-7"
                @click="inativaCertidao(certidao.codpessoacertidao)"
              >
                <q-tooltip>Inativar</q-tooltip>
              </q-btn>

              <!-- ATIVAR -->
              <q-btn
                v-if="certidao.inativo"
                flat
                dense
                round
                icon="play_arrow"
                size="sm"
                color="grey-7"
                @click="ativaCertidao(certidao.codpessoacertidao)"
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
                @click="deletarCertidao(certidao.codpessoacertidao)"
              >
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-list>
    <div v-else class="q-pa-md text-center text-grey">
      Nenhuma certidão cadastrada
    </div>
  </q-card>
</template>

<style scoped></style>
