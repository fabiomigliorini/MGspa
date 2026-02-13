<script setup>
import { defineAsyncComponent, ref, onMounted } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { pessoaStore } from "stores/pessoa";
import { guardaToken } from "src/stores";
import { formataDocumetos } from "src/stores/formataDocumentos";
import IconeInfoCriacao from "components/IconeInfoCriacao.vue";

const SelectCertidaoEmissor = defineAsyncComponent(() =>
  import("components/pessoa/SelectCertidaoEmissor.vue")
);
const SelectCertidaoTipo = defineAsyncComponent(() =>
  import("components/pessoa/SelectCertidaoTipo.vue")
);

const $q = useQuasar();
const sPessoa = pessoaStore();
const route = useRoute();
const user = guardaToken();
const Documentos = formataDocumetos();

const editCertidao = ref(false);
const dialogCertidao = ref(false);
const modelCertidao = ref({});
const filtroCertidaomodel = ref("validas");
const certidoesS = ref([]);

const brasil = {
  days: "Domingo_Segunda_Terça_Quarta_Quinta_Sexta_Sábado".split("_"),
  daysShort: "Dom_Seg_Ter_Qua_Qui_Sex_Sáb".split("_"),
  months:
    "Janeiro_Fevereiro_Março_Abril_Maio_Junho_Julho_Agosto_Setembro_Outubro_Novembro_Dezembro".split(
      "_"
    ),
  monthsShort: "Jan_Fev_Mar_Abr_Mai_Jun_Jul_Ago_Set_Out_Nov_Dez".split("_"),
  firstDayOfWeek: 1,
  format24h: true,
  pluralDay: "dias",
};

function filtroCertidao() {
  if (filtroCertidaomodel.value == "validas") {
    let validas = sPessoa.item.PessoaCertidaoS.filter(
      (x) => x.validade >= Documentos.dataAtual()
    );
    sPessoa.item.PessoaCertidaoS = validas;
  }
  if (filtroCertidaomodel.value == "todas") {
    sPessoa.item.PessoaCertidaoS = certidoesS.value;
  }
}

async function novaCertidao() {
  modelCertidao.value.codpessoa = route.params.id;
  if (modelCertidao.value.validade) {
    modelCertidao.value.validade = Documentos.dataFormatoSql(
      modelCertidao.value.validade
    );
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
}

function editarCertidao(
  codpessoacertidao,
  codcertidaoemissor,
  numero,
  autenticacao,
  validade,
  codcertidaotipo
) {
  editCertidao.value = true;
  dialogCertidao.value = true;
  modelCertidao.value = {
    codpessoacertidao: codpessoacertidao,
    codcertidaoemissor: codcertidaoemissor,
    numero: numero,
    autenticacao: autenticacao,
    validade: Documentos.formataDataInput(validade),
    codcertidaotipo: codcertidaotipo,
  };
}

async function salvarCertidao() {
  if (modelCertidao.value.validade) {
    modelCertidao.value.validade = Documentos.dataFormatoSql(
      modelCertidao.value.validade
    );
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
}

async function inativaCertidao(codpessoacertidao) {
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
}

async function ativaCertidao(codpessoacertidao) {
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
}

async function deletarCertidao(codpessoacertidao) {
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
}

onMounted(() => {
  if (!sPessoa.item) return;
  certidoesS.value = sPessoa.item.PessoaCertidaoS;
  let validas = sPessoa.item.PessoaCertidaoS.filter(
    (x) => x.validade >= Documentos.dataAtual()
  );
  sPessoa.item.PessoaCertidaoS = validas;
});
</script>

<template>
  <div>
    <q-card bordered>
      <q-card-section class="bg-yellow text-grey-9 q-py-sm">
        <div class="row items-center no-wrap q-gutter-x-sm">
          <q-icon name="description" size="sm" />
          <span class="text-subtitle1 text-weight-medium">Certidões</span>
          <q-space />
          <q-radio
            v-model="filtroCertidaomodel"
            val="todas"
            label="Todas"
            dense
            @click="filtroCertidao()"
          />
          <q-radio
            v-model="filtroCertidaomodel"
            val="validas"
            label="Válidas"
            dense
            @click="filtroCertidao()"
          />
          <q-btn
            flat
            round
            dense
            icon="add"
            size="sm"
            color="grey-9"
            v-if="user.verificaPermissaoUsuario('Publico')"
            @click="
              (dialogCertidao = true),
                (modelCertidao = {}),
                (editCertidao = false)
            "
          />
        </div>
      </q-card-section>

      <q-list separator>
        <template
          v-for="certidao in sPessoa.item?.PessoaCertidaoS"
          v-bind:key="certidao.codpessoacertidao"
        >
          <q-item>
            <q-item-section avatar>
              <q-btn round flat icon="description" color="primary" />
            </q-item-section>

            <q-item-section>
              <q-item-label
                v-if="certidao.validade"
                class="text-weight-bold"
                :class="certidao.validade < Documentos.dataAtual() ? 'text-strike' : null"
              >
                Validade: {{ Documentos.formataDatasemHr(certidao.validade) }}
              </q-item-label>
              <q-item-label caption>
                {{ certidao.certidaotipo }} {{ certidao.certidaoemissor }}
              </q-item-label>
              <q-item-label caption>
                {{ certidao.numero }}

                <!-- INFO -->
                <icone-info-criacao
                  :usuariocriacao="certidao.usuariocriacao"
                  :criacao="certidao.criacao"
                  :usuarioalteracao="certidao.usuarioalteracao"
                  :alteracao="certidao.alteracao"
                />
              </q-item-label>
              <q-item-label
                caption
                v-if="certidao.autenticacao"
                :class="certidao.validade < Documentos.dataAtual() ? 'text-strike' : null"
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
    </q-card>

    <!-- Dialog Certidões -->
    <q-dialog v-model="dialogCertidao">
      <q-card style="min-width: 350px">
        <q-form
          @submit="editCertidao == false ? novaCertidao() : salvarCertidao()"
        >
          <q-card-section>
            <div v-if="editCertidao" class="text-h6">Editar Certidão</div>
            <div v-else class="text-h6">Nova Certidão</div>
          </q-card-section>
          <q-card-section>
            <q-input
              outlined
              v-model="modelCertidao.numero"
              mask="####################"
              autofocus
              label="Número"
              :rules="[
                (val) => (val && val.length > 0) || 'Numero obrigatório',
              ]"
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
                      :locale="brasil"
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

            <select-certidao-emissor
              v-model="modelCertidao.codcertidaoemissor"
            />

            <select-certidao-tipo
              v-model="modelCertidao.codcertidaotipo"
              class="q-mt-md"
            />
          </q-card-section>

          <q-card-actions align="right" class="text-primary">
            <q-btn flat label="Cancelar" v-close-popup />
            <q-btn flat label="Salvar" type="submit" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </div>
</template>

<style scoped></style>
