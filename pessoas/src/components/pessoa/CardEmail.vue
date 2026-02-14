<script setup>
import { ref, computed } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { pessoaStore } from "stores/pessoa";
import { guardaToken } from "src/stores";
import { formataData } from "src/utils/formatador";
import IconeInfoCriacao from "components/IconeInfoCriacao.vue";
import InputFiltered from "components/InputFiltered.vue";

const $q = useQuasar();
const route = useRoute();
const sPessoa = pessoaStore();
const user = guardaToken();

const filtroEmail = ref("ativos");
const emailsFiltrados = computed(() => {
  const lista = sPessoa.item?.PessoaEmailS || [];
  if (filtroEmail.value === "ativos") return lista.filter((x) => !x.inativo);
  return lista;
});

const dialogEmail = ref(false);
const emailNovo = ref(false);
const modelEmail = ref({
  codpessoa: "",
  email: "",
  apelido: "",
  verificacao: "",
  nfe: "",
  cobranca: "",
});

const linkEmail = (email) => {
  return "mailto:" + email;
};

const modalNovoEmail = async () => {
  dialogEmail.value = true;
  const cobranca =
    sPessoa.item.PessoaEmailS.filter((email) => email.cobranca == true)
      .length == 0;
  const nfe =
    sPessoa.item.PessoaEmailS.filter((email) => email.nfe == true).length == 0;
  modelEmail.value = { cobranca: cobranca, nfe: nfe };
  emailNovo.value = true;
};

const novoEmail = async () => {
  if (modelEmail.value.email !== "") {
    modelEmail.value.codpessoa = route.params.id;
    try {
      const ret = await sPessoa.emailNovo(route.params.id, modelEmail.value);
      if (ret.data.data) {
        $q.notify({
          color: "green-5",
          textColor: "white",
          icon: "done",
          message: "Email criado.",
        });
        emailNovo.value = false;
        dialogEmail.value = false;
      }
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response.data.message,
      });
    }
  } else {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: "Campo Email é obrigatório!",
    });
  }
};

const excluirEmail = async (codpessoaemail) => {
  $q.dialog({
    title: "Excluir Email",
    message: "Tem certeza que deseja excluir esse email?",
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      const ret = await sPessoa.emailExcluir(route.params.id, codpessoaemail);
      if (ret) {
        $q.notify({
          color: "green-5",
          textColor: "white",
          icon: "done",
          message: "Email excluido",
        });
        sPessoa.get(route.params.id);
      }
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

const editarEmail = (codpessoaemail, email, apelido, verificacao, nfe, cobranca) => {
  dialogEmail.value = true;
  modelEmail.value = {
    codpessoaemail: codpessoaemail,
    email: email,
    apelido: apelido,
    verificacao: verificacao,
    nfe: nfe,
    cobranca: cobranca,
  };
};

const salvarEmail = async (codpessoa) => {
  try {
    const ret = await sPessoa.emailSalvar(
      codpessoa,
      modelEmail.value.codpessoaemail,
      modelEmail.value
    );
    if (ret.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Email alterado",
      });
      dialogEmail.value = false;
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
  emailNovo.value ? novoEmail(route.params.id) : salvarEmail(route.params.id);
};

const inativar = async (codpessoa, codpessoaemail) => {
  try {
    const ret = await sPessoa.emailInativar(codpessoa, codpessoaemail);
    if (ret.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Inativado!",
      });
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

const ativar = async (codpessoa, codpessoaemail) => {
  try {
    const ret = await sPessoa.emailAtivar(codpessoa, codpessoaemail);
    if (ret.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Ativado!",
      });
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

const cima = async (codpessoa, codpessoaemail) => {
  try {
    await sPessoa.emailParaCima(codpessoa, codpessoaemail);
    $q.notify({
      color: "green-4",
      textColor: "white",
      icon: "done",
      message: "Movido para cima",
    });
    sPessoa.get(codpessoa);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.message,
    });
    sPessoa.get(codpessoa);
  }
};

const baixo = async (codpessoa, codpessoaemail) => {
  try {
    await sPessoa.emailParaBaixo(codpessoa, codpessoaemail);
    $q.notify({
      color: "green-4",
      textColor: "white",
      icon: "done",
      message: "Movido para baixo",
    });
    sPessoa.get(codpessoa);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.message,
    });
    sPessoa.get(codpessoa);
  }
};

const enviarEmail = (email, codpessoaemail) => {
  $q.dialog({
    title: "Verificação de E-mail",
    message:
      "Deseja enviar o código de verificação para o e-mail  " + email + " ?",
    cancel: true,
  }).onOk(() => {
    sPessoa
      .emailVerificar(route.params.id, codpessoaemail)
      .then((resp) => {
        if (resp.data) {
          $q.notify({
            color: "green-5",
            textColor: "white",
            icon: "done",
            message: "Código enviado para o e-mail",
          });
        }
      });
    confirmaEmail(email, codpessoaemail);
  });
};

const confirmaEmail = (email, codpessoaemail) => {
  $q.dialog({
    title: "Verificação de E-mail",
    message: "Digite o código enviado para o e-mail " + email,
    prompt: { model: "", type: "number", step: "1" },
    cancel: true,
    persistent: true,
  }).onOk((codverificacao) => {
    postEmail(email, codpessoaemail, codverificacao);
  });
};

const postEmail = async (email, codpessoaemail, codverificacao) => {
  try {
    const ret = await sPessoa.emailConfirmaVerificacao(
      route.params.id,
      codpessoaemail,
      codverificacao
    );
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "E-mail Verificado!",
      });
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response.data.message,
    });
    confirmaEmail(email, codpessoaemail);
  }
};
</script>

<template>
  <!-- DIALOG NOVO EMAIL/EDITAR EMAIL -->
  <q-dialog v-model="dialogEmail">
      <q-card bordered flat style="width: 600px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline row items-center">
          <template v-if="emailNovo">NOVO EMAIL</template>
          <template v-else>EDITAR EMAIL</template>
        </q-card-section>

        <q-form @submit="submit()">
          <q-separator inset />

          <q-card-section>
            <q-input
              outlined
              v-model="modelEmail.email"
              autofocus
              label="Email"
              :rules="[(val) => (val && val.length > 0) || 'Email obrigatório']"
            />
            <input-filtered
              outlined
              v-model="modelEmail.apelido"
              label="Apelido"
            />
            <q-toggle v-model="modelEmail.cobranca" label="Cobrança" />
            <q-toggle v-model="modelEmail.nfe" label="Envio de NFe" />
          </q-card-section>

          <q-separator inset />

          <q-card-actions align="right" class="text-primary">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn flat label="Salvar" type="submit" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <q-card bordered flat>
      <q-card-section class="text-grey-9 text-overline row items-center">
        EMAILS
        <q-space />
        <q-btn-toggle
          v-model="filtroEmail"
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
          v-if="user.verificaPermissaoUsuario('Publico')"
          @click="modalNovoEmail()"
        />
      </q-card-section>

      <q-list v-if="emailsFiltrados.length > 0">
        <template
          v-for="(element, i) in emailsFiltrados"
          v-bind:key="element.codpessoaemail"
        >
          <q-separator inset />
          <q-item>
            <!-- BOTAO EMAIL -->
            <q-item-section avatar>
              <q-btn
                round
                icon="email"
                color="primary"
                flat
                :href="linkEmail(element.email)"
              />
            </q-item-section>

            <q-item-section>
              <!-- EMAIL -->
              <q-item-label :class="element.inativo ? 'text-strike' : null">
                {{ element.email }}
                <q-icon
                  v-if="element.verificacao"
                  color="primary"
                  name="verified"
                  class="q-ml-xs"
                >
                  <q-tooltip>Verificado</q-tooltip>
                </q-icon>
                <q-icon
                  v-if="element.cobranca"
                  color="green"
                  name="paid"
                  class="q-ml-xs"
                >
                  <q-tooltip>Cobrança</q-tooltip>
                </q-icon>
                <q-icon
                  v-if="element.nfe"
                  color="green"
                  name="description"
                  class="q-ml-xs"
                >
                  <q-tooltip>Envio de NFe</q-tooltip>
                </q-icon>

                <!-- INFO -->
                <icone-info-criacao
                  :usuariocriacao="element.usuariocriacao"
                  :criacao="element.criacao"
                  :usuarioalteracao="element.usuarioalteracao"
                  :alteracao="element.alteracao"
                />
              </q-item-label>

              <!-- INATIVO -->
              <q-item-label caption class="text-red-14" v-if="element.inativo">
                Inativo desde: {{ formataData(element.inativo) }}
              </q-item-label>

              <!-- APELIDO -->
              <q-item-label caption v-if="element.apelido">
                {{ element.apelido }}
              </q-item-label>

              <!-- VERIFICAR -->
              <q-item-label
                caption
                v-if="user.verificaPermissaoUsuario('Publico')"
              >
                <q-btn
                  v-if="!element.verificacao"
                  flat
                  dense
                  size="sm"
                  label="Verificar"
                  color="primary"
                  @click="enviarEmail(element.email, element.codpessoaemail)"
                />
              </q-item-label>
            </q-item-section>

            <q-item-section side>
              <!-- BOTOES -->
              <q-item-label
                caption
                v-if="user.verificaPermissaoUsuario('Publico')"
              >
                <template v-if="sPessoa.item?.PessoaEmailS.length > 1">
                  <!-- CIMA -->
                  <q-btn
                    flat
                    dense
                    round
                    icon="north"
                    size="sm"
                    color="grey-7"
                    @click="cima(element.codpessoa, element.codpessoaemail)"
                    v-if="i != 0"
                  >
                    <q-tooltip>Mover para cima</q-tooltip>
                  </q-btn>

                  <!-- BAIXO -->
                  <q-btn
                    flat
                    dense
                    round
                    icon="south"
                    size="sm"
                    color="grey-7"
                    @click="baixo(element.codpessoa, element.codpessoaemail)"
                    v-else
                  >
                    <q-tooltip>Mover para baixo</q-tooltip>
                  </q-btn>
                </template>

                <!-- EDITAR -->
                <q-btn
                  flat
                  dense
                  round
                  icon="edit"
                  size="sm"
                  color="grey-7"
                  @click="
                    editarEmail(
                      element.codpessoaemail,
                      element.email,
                      element.apelido,
                      element.verificacao,
                      element.nfe,
                      element.cobranca
                    ),
                      (emailNovo = false)
                  "
                >
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>

                <!-- INATIVAR -->
                <q-btn
                  v-if="!element.inativo"
                  flat
                  dense
                  round
                  icon="pause"
                  size="sm"
                  color="grey-7"
                  @click="inativar(element.codpessoa, element.codpessoaemail)"
                >
                  <q-tooltip>Inativar</q-tooltip>
                </q-btn>

                <!-- ATIVAR -->
                <q-btn
                  v-if="element.inativo"
                  flat
                  dense
                  round
                  icon="play_arrow"
                  size="sm"
                  color="grey-7"
                  @click="ativar(element.codpessoa, element.codpessoaemail)"
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
                  @click="excluirEmail(element.codpessoaemail)"
                >
                  <q-tooltip>Excluir</q-tooltip>
                </q-btn>
              </q-item-label>
            </q-item-section>
          </q-item>
        </template>
      </q-list>
      <div v-else class="q-pa-md text-center text-grey">
        Nenhum email cadastrado
      </div>
  </q-card>
</template>

<style scoped></style>
